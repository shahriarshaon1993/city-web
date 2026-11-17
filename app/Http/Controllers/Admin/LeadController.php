<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Leads\AddLeadNote;
use App\Actions\Leads\ChangeLeadStatus;
use App\Actions\Leads\CreateManualLead;
use App\Actions\Leads\ReassignLead;
use App\Actions\Leads\SetLeadFollowUp;
use App\Actions\Leads\UndoLeadStatus;
use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignLeadRequest;
use App\Http\Requests\Admin\BulkLeadsRequest;
use App\Http\Requests\Admin\ChangeLeadStatusRequest;
use App\Http\Requests\Admin\SetLeadFollowUpRequest;
use App\Http\Requests\Admin\StoreLeadNoteRequest;
use App\Http\Requests\Admin\StoreManualLeadRequest;
use App\Http\Resources\Admin\LeadDetailResource;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Queries\Admin\LeadAssignees;
use App\Queries\Admin\LeadsTable;
use App\Queries\Admin\RecycleBinTable;
use App\Support\Forms\Countries;
use App\Support\Leads\LeadReasons;
use App\Support\Toasts\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-11 Leads: the table, the board and the detail drawer (FR-LEAD-006/009). A Sales Executive
 * only ever reaches their own leads (the Policy and `Lead::visibleTo`, AC-LEAD-05). Controllers only
 * authorise, validate, call an Action and respond (DEC-32); each mutation flashes one toast (TOAST-01).
 */
class LeadController extends Controller
{
    public function index(Request $request, LeadsTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Lead::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/leads/Index', [
            ...$table->props($request, $actor),
            ...$this->shared($request, $actor),
        ]);
    }

    public function board(Request $request, LeadsTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Lead::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/leads/Board', [
            'columns' => fn (): array => $table->board($request, $actor),
            'views' => fn (): array => $table->views($actor),
            'filterOptions' => fn (): array => $table->filterOptions($actor),
            ...$this->shared($request, $actor),
        ]);
    }

    /**
     * The drawer's data (JSON). Opened in a browser, the address lands on the list with the drawer
     * open, so a pasted link works.
     */
    public function show(Request $request, Lead $lead): JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $lead);

        if ($request->wantsJson()) {
            return response()->json(['data' => $this->detail($request, $lead)]);
        }

        return redirect()->route('admin.leads.index', ['lead' => $lead->id]);
    }

    /** DEC-33 (owner: a page, not a dialog): the "New lead" form for phone calls and walk-ins. */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Lead::class);

        return Inertia::render('admin/leads/Create', [
            'options' => $this->newLeadOptions($request->user()),
            'breadcrumbs' => [
                ['title' => 'Sales', 'href' => null],
                ['title' => 'Leads', 'href' => route('admin.leads.index')],
                ['title' => 'New lead', 'href' => null],
            ],
        ]);
    }

    /** A repeat enquiry becomes a touch (FR-LEAD-004). Either way the browser lands on the lead. */
    public function store(StoreManualLeadRequest $request, CreateManualLead $createManualLead): RedirectResponse
    {
        $result = $createManualLead->handle(
            $request->user(),
            $request->safe()->only(['name', 'channel', 'email', 'whatsapp', 'project_id', 'unit_type_id', 'message']),
            (string) $request->phone(),
            $request->filled('assigned_to') ? User::query()->find($request->integer('assigned_to')) : null,
            $request->followUpAt(),
        );

        $lead = $result['lead'];

        if ($result['touched']) {
            Toast::info('Added to a lead we already have')
                ->description("{$lead->name} asked about the same project in the last 24 hours, so this is a touch on that lead.")
                ->flash();
        } else {
            Toast::success('Lead added')->description($lead->name)->flash();
        }

        return redirect()->route('admin.leads.index', ['lead' => $lead->id]);
    }

    /** FR-LEAD-006 (board drag, drawer, row menu). TOAST-02: Undo carries the previous status and its text. */
    public function status(ChangeLeadStatusRequest $request, Lead $lead, ChangeLeadStatus $changeLeadStatus, UndoLeadStatus $undoLeadStatus): RedirectResponse
    {
        $actor = $request->user();
        $to = $request->status();

        if ($request->boolean('undo')) {
            $undoLeadStatus->handle($actor, $lead, $to, $request->input('lost_reason'), $request->input('won_note'));
            Toast::success('Undone')->description("{$lead->name} is back to {$to->label()}")->flash();

            return back();
        }

        $from = $lead->status;
        $lostReason = $lead->lost_reason;
        $wonNote = $lead->won_note;

        if (! $changeLeadStatus->handle($actor, $lead, $to, $request->input('reason'), $request->input('note'))) {
            Toast::info('No change')->description("{$lead->name} is already {$to->label()}")->flash();

            return back();
        }

        Toast::success("Lead moved to {$to->label()}")
            ->description($lead->name)
            ->action('Undo', route('admin.leads.status', $lead), 'patch', [
                'status' => $from->value,
                'undo' => true,
                'lost_reason' => $lostReason,
                'won_note' => $wonNote,
            ])
            ->flash();

        return back();
    }

    /** FR-LEAD-007: a Sales Manager reassigns. */
    public function assign(AssignLeadRequest $request, Lead $lead, ReassignLead $reassignLead): RedirectResponse
    {
        $assignee = $request->assignee();

        if (! $reassignLead->handle($request->user(), $lead, $assignee)) {
            Toast::info('No change')->description($assignee === null ? 'The lead has no owner already' : "{$assignee->name} already has this lead")->flash();

            return back();
        }

        Toast::success($assignee === null ? 'Lead left unassigned' : "Lead assigned to {$assignee->name}")->description($lead->name)->flash();

        return back();
    }

    public function note(StoreLeadNoteRequest $request, Lead $lead, AddLeadNote $addLeadNote): RedirectResponse
    {
        $addLeadNote->handle($request->user(), $lead, $request->string('note')->toString());

        Toast::success('Note added')->description($lead->name)->flash();

        return back();
    }

    public function followUp(SetLeadFollowUpRequest $request, Lead $lead, SetLeadFollowUp $setLeadFollowUp): RedirectResponse
    {
        $at = $request->at();

        if (! $setLeadFollowUp->handle($request->user(), $lead, $at)) {
            Toast::info('No change')->description('The follow-up is the same as before')->flash();

            return back();
        }

        Toast::success($at === null ? 'Follow-up cleared' : 'Follow-up set')
            ->description($at === null ? $lead->name : $lead->name.' · '.$at->timezone('Asia/Dhaka')->format('d M Y, H:i'))
            ->flash();

        return back();
    }

    /** DEL-02: Delete = Recycle bin for 365 days, with Undo (TOAST-02). Admin only. */
    public function destroy(Request $request, Lead $lead, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        Gate::authorize('delete', $lead);

        $entry = $moveToRecycleBin->handle($request->user(), $lead);

        Toast::success('Lead moved to Recycle bin')
            ->description($lead->name)
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return back();
    }

    /** ADM-TBL-009: assign or delete the ticked leads; what is not allowed is counted and explained (TOAST-06). */
    public function bulk(BulkLeadsRequest $request, LeadsTable $table, ReassignLead $reassignLead, MoveManyToRecycleBin $moveMany): RedirectResponse
    {
        $actor = $request->user();
        $leads = $table->selection($request, $actor)->limit(BulkLeadsRequest::MAX_IDS * 20)->get();

        if ($request->string('action')->toString() === 'delete') {
            $allowed = $leads->filter(fn (Lead $lead): bool => $actor->can('delete', $lead));
            $skipped = $leads->count() - $allowed->count();
            $result = $moveMany->handle($actor, $allowed);
            $count = count($result['entries']);

            $title = $count === 0 ? 'No leads were deleted' : $count.' '.($count === 1 ? 'lead' : 'leads').' moved to Recycle bin';
            $toast = $skipped > 0 || $result['refused'] !== []
                ? Toast::warning($title.' · '.($skipped + count($result['refused'])).' skipped')->description('Only an Admin can delete leads.')
                : Toast::success($title);

            if ($count > 0) {
                $toast->undo(route('admin.recycle-bin.restore-many'), [
                    'ids' => array_map(fn ($entry): int => $entry->id, $result['entries']),
                    'undo' => true,
                ]);
            }

            $toast->flash();

            return back();
        }

        $assignee = $request->filled('assignee_id') ? User::query()->find($request->integer('assignee_id')) : null;

        if ($assignee !== null && ! app(LeadAssignees::class)->get()->contains('id', $assignee->id)) {
            Toast::error('Leads not assigned')->description('Choose someone from the list who can work on leads.')->flash();

            return back();
        }

        $changed = 0;
        $skipped = 0;

        foreach ($leads as $lead) {
            if (! $actor->can('assign', $lead)) {
                $skipped++;

                continue;
            }

            $changed += $reassignLead->handle($actor, $lead, $assignee) ? 1 : 0;
        }

        $noun = $changed === 1 ? 'lead' : 'leads';
        $title = $changed === 0 ? 'No leads were changed' : "{$changed} {$noun} ".($assignee === null ? 'left unassigned' : "assigned to {$assignee->name}");

        ($skipped > 0 ? Toast::warning($title.' · '.$skipped.' skipped')->description('You can only assign leads you may see and manage.') : Toast::success($title))->flash();

        return back();
    }

    /**
     * Props both screens share: what the user may do, the lists the dialogs need, and the drawer's
     * lead when the address has `?lead={id}` (a full visit only, a refetch never recomputes it).
     *
     * @return array<string, mixed>
     */
    private function shared(Request $request, User $actor): array
    {
        return [
            'canCreate' => $actor->can('create', Lead::class),
            'canAssign' => $actor->can('leads.assign'),
            'canExport' => $actor->can('export', Lead::class),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, Lead::binType()),
            'assignees' => fn (): array => $actor->can('leads.assign') ? app(LeadAssignees::class)->options() : [],
            'reasons' => ['won' => LeadReasons::options(LeadReasons::WON), 'lost' => LeadReasons::options(LeadReasons::LOST)],
            'statuses' => array_map(fn (LeadStatus $status): array => ['value' => $status->value, 'label' => $status->label()], LeadStatus::cases()),
            'openLead' => function () use ($request): ?array {
                $id = $request->query('lead');

                if (! is_string($id) || ! ctype_digit($id)) {
                    return null;
                }

                $lead = Lead::query()->findOrFail((int) $id);
                Gate::authorize('view', $lead);

                return $this->detail($request, $lead);
            },
            'breadcrumbs' => [
                ['title' => 'Sales', 'href' => null],
                ['title' => 'Leads', 'href' => null],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function newLeadOptions(User $actor): array
    {
        return [
            'channels' => LeadReasons::options(LeadReasons::CHANNELS),
            'countries' => Countries::all(),
            'defaultCountry' => Countries::DEFAULT,
            'projects' => Project::query()
                ->with('unitTypes:id,project_id,name')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'unit_types' => $project->unitTypes->map(fn ($unit): array => ['id' => $unit->id, 'name' => $unit->name])->values()->all(),
                ])
                ->all(),
            'assignees' => $actor->can('leads.assign') ? app(LeadAssignees::class)->options() : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Request $request, Lead $lead): array
    {
        $lead->load(['project:id,name', 'unitType:id,name', 'assignee:id,name', 'activities.user:id,name']);

        return (new LeadDetailResource($lead))->resolve($request);
    }
}

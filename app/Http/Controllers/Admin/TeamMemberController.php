<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\Social\ReorderSocialItems;
use App\Actions\Social\SaveTeamMember;
use App\Actions\Social\SetSocialPublished;
use App\Http\Controllers\Admin\Concerns\ManagesSocialItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkSocialRequest;
use App\Http\Requests\Admin\ReorderRequest;
use App\Http\Requests\Admin\SaveTeamMemberRequest;
use App\Models\TeamMember;
use App\Queries\Admin\RecycleBinTable;
use App\Queries\Admin\SocialEditor;
use App\Queries\Admin\TeamTable;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** ADM-SCR-09 team: a table in team-grid order with a Published switch; a full-page editor. */
class TeamMemberController extends Controller
{
    use ManagesSocialItems;

    protected function itemClass(): string
    {
        return TeamMember::class;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function nouns(): array
    {
        return ['team member', 'team members'];
    }

    protected function logName(): string
    {
        return 'team';
    }

    public function index(Request $request, TeamTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', TeamMember::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/team/Index', [
            ...$table->props($request, $actor),
            'canManage' => $actor->can('create', TeamMember::class),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, TeamMember::binType()),
            // The reorder dialog orders one group at a time, like the grids on the site.
            'order' => fn (): array => SocialEditor::orderList(TeamMember::class, fn (TeamMember $item): array => [
                'id' => $item->id, 'label' => $item->name, 'hint' => $item->role_title, 'group' => $item->group->value, 'published' => $item->is_published,
            ]),
            'groups' => SocialEditor::teamOptions()['groups'],
            'breadcrumbs' => [['title' => 'Content', 'href' => null], ['title' => 'Team', 'href' => null]],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', TeamMember::class);

        return $this->editor($request, null);
    }

    public function store(SaveTeamMemberRequest $request, SaveTeamMember $saveMember): RedirectResponse
    {
        $result = $saveMember->handle($request->user(), $request->validated());

        Toast::success('Team member created')->description($result['member']->name)->flash();

        return to_route('admin.team.edit', $result['member']);
    }

    public function edit(Request $request, TeamMember $member): Response
    {
        Gate::authorize('update', $member);

        return $this->editor($request, $member);
    }

    public function update(SaveTeamMemberRequest $request, TeamMember $member, SaveTeamMember $saveMember): RedirectResponse
    {
        $result = $saveMember->handle($request->user(), $request->validated(), $member);

        $result['changed']
            ? Toast::success('Team member saved')->description($member->name)->flash()
            : Toast::info('No changes to save')->flash();

        return back();
    }

    public function published(Request $request, TeamMember $member, SetSocialPublished $setPublished): RedirectResponse
    {
        return $this->respondPublished($request, $member, $setPublished);
    }

    public function order(ReorderRequest $request, ReorderSocialItems $reorder): RedirectResponse
    {
        return $this->respondOrder($request, $reorder, 'admin.team.order');
    }

    public function destroy(Request $request, TeamMember $member, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        return $this->respondDestroy($request, $member, $moveToRecycleBin);
    }

    public function bulk(BulkSocialRequest $request, TeamTable $table, MoveManyToRecycleBin $moveMany, SetSocialPublished $setPublished): RedirectResponse
    {
        return $this->respondBulk($request, $table, $moveMany, $setPublished);
    }

    private function editor(Request $request, ?TeamMember $member): Response
    {
        $form = SocialEditor::team($member);

        return Inertia::render('admin/team/Edit', [
            'member' => $member === null ? null : ['id' => $member->id, 'name' => $member->name],
            'form' => $form,
            'assets' => SocialEditor::assets($request, [$form['photo_asset_id']]),
            'options' => SocialEditor::teamOptions(),
            'lastSaved' => $member?->updated_at?->toIso8601String(),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Team', 'href' => route('admin.team.index')],
                ['title' => $member->name ?? 'New team member', 'href' => null],
            ],
        ]);
    }
}

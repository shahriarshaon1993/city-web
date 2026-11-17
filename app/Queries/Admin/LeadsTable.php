<?php

namespace App\Queries\Admin;

use App\Enums\LeadStatus;
use App\Http\Resources\Admin\LeadCardResource;
use App\Http\Resources\Admin\LeadRowResource;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use App\Support\Leads\ContactMask;
use App\Support\Leads\LeadTypes;
use App\Support\Tables\TableState;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-11 leads list and board (design/admin.md §12.3, FR-LEAD-009/010). A Sales Executive's query
 * is cut down to their own leads in `baseQuery` (AC-LEAD-05). Without `leads.view-contact` the Resources
 * and the export mask phone numbers and emails, and the search does not look at them (a search by
 * phone prefix would reveal the number). The board reads the same state as the table.
 *
 * @extends AdminTable<Lead>
 */
final class LeadsTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'leads';

    /** Cards per board column; "Show all" opens the table on that status. */
    public const int BOARD_COLUMN_LIMIT = 25;

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['leads.view-all', 'leads.view-assigned'];
    }

    public function url(): string
    {
        return route('admin.leads.index');
    }

    public function canExport(User $actor): bool
    {
        return $actor->can('export', Lead::class);
    }

    protected function baseQuery(User $actor): Builder
    {
        return Lead::query()->visibleTo($actor)->with(['project:id,name', 'assignee:id,name']);
    }

    protected function filters(): array
    {
        return [
            $this->enumFilter('status', 'status', array_map(fn (LeadStatus $status): string => $status->value, LeadStatus::cases())),
            $this->enumFilter('type', 'type', array_keys(LeadTypes::LABELS)),
            AllowedFilter::callback('stage', function (Builder $query, mixed $value): void {
                $values = array_intersect(array_map(strval(...), Arr::wrap($value)), ['open', 'closed']);

                if (count($values) !== 1) {
                    return;
                }

                if (in_array('open', $values, true)) {
                    $query->whereNotIn('status', LeadStatus::closedValues());
                } else {
                    $query->whereIn('status', LeadStatus::closedValues());
                }
            }),
            AllowedFilter::callback('project', fn (Builder $query, mixed $value) => $query->whereIn('project_id', array_map(intval(...), Arr::wrap($value)))),
            AllowedFilter::callback('assignee', function (Builder $query, mixed $value): void {
                $values = array_map(strval(...), Arr::wrap($value));
                $actor = request()->user();

                $query->where(function (Builder $query) use ($values, $actor): void {
                    foreach ($values as $item) {
                        match (true) {
                            $item === 'me' => $query->orWhere('assigned_to', $actor?->id),
                            $item === 'none' => $query->orWhereNull('assigned_to'),
                            ctype_digit($item) => $query->orWhere('assigned_to', (int) $item),
                            default => null,
                        };
                    }
                });
            }),
            AllowedFilter::callback('follow_up', function (Builder $query, mixed $value): void {
                $now = now();
                $startOfToday = $now->copy()->timezone('Asia/Dhaka')->startOfDay()->utc();
                $endOfToday = $now->copy()->timezone('Asia/Dhaka')->endOfDay()->utc();

                $query->where(function (Builder $query) use ($value, $now, $startOfToday, $endOfToday): void {
                    foreach (array_map(strval(...), Arr::wrap($value)) as $item) {
                        match ($item) {
                            'overdue' => $query->orWhere(fn (Builder $query) => $query->where('follow_up_at', '<', $now)->whereNotIn('status', LeadStatus::closedValues())),
                            'today' => $query->orWhereBetween('follow_up_at', [$startOfToday, $endOfToday]),
                            'upcoming' => $query->orWhere('follow_up_at', '>', $endOfToday),
                            'none' => $query->orWhereNull('follow_up_at'),
                            default => null,
                        };
                    }
                });
            }),
            ...$this->dateRange('created', 'created_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['created_at', 'name', 'type', 'status', 'follow_up_at', 'last_touched_at', 'touches_count'];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';
        $actor = request()->user();
        $contact = $actor?->can('viewContact', Lead::class) ?? false;

        $query->where(function (Builder $query) use ($like, $term, $contact): void {
            $query->where('name', 'like', $like)->orWhere('reference', 'like', $like)->orWhere('message', 'like', $like);

            if ($contact) {
                $query->orWhere('email', 'like', $like);

                $digits = preg_replace('/\D/', '', $term) ?? '';

                if (strlen($digits) >= 4 && preg_match('/^[\d\s+\-()]+$/', $term) === 1) {
                    // 01712… is how people type the number the table stores as +8801712….
                    $stored = str_starts_with($digits, '0') ? '88'.$digits : $digits;
                    $query->orWhere('phone', 'like', '%'.$stored.'%');
                }
            }
        });
    }

    protected function resource(): string
    {
        return LeadRowResource::class;
    }

    protected function builtInViews(User $actor): array
    {
        $month = CarbonImmutable::now('Asia/Dhaka');

        return [
            ['id' => -1, 'name' => 'My open leads', 'state' => ['filter' => ['assignee' => 'me', 'stage' => 'open']]],
            ['id' => -2, 'name' => 'Overdue follow-ups', 'state' => ['filter' => ['follow_up' => 'overdue'], 'sort' => 'follow_up_at']],
            ['id' => -3, 'name' => 'NRB this month', 'state' => ['filter' => [
                'type' => 'nrb',
                'created_from' => $month->startOfMonth()->format('Y-m-d'),
                'created_to' => $month->endOfMonth()->format('Y-m-d'),
            ]]],
        ];
    }

    /**
     * FR-LEAD-010: phone numbers are marked `text` so the `+` survives. A user without
     * `leads.view-contact` gets the masked values here too.
     */
    public function exportColumns(): array
    {
        $contact = fn (): bool => request()->user()?->can('viewContact', Lead::class) ?? false;

        return [
            'created_at' => ['label' => 'Received', 'value' => fn (Lead $lead): ?string => $lead->created_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
            'name' => ['label' => 'Name', 'value' => fn (Lead $lead): string => $lead->name],
            'phone' => ['label' => 'Phone', 'text' => true, 'value' => fn (Lead $lead): string => $contact() ? $lead->phone : ContactMask::phone($lead->phone)],
            'whatsapp' => ['label' => 'WhatsApp', 'text' => true, 'value' => fn (Lead $lead): string => $lead->whatsapp === null ? '' : ($contact() ? $lead->whatsapp : ContactMask::phone($lead->whatsapp))],
            'email' => ['label' => 'Email', 'value' => fn (Lead $lead): string => (string) ($contact() ? $lead->email : ContactMask::email($lead->email))],
            'type' => ['label' => 'Type', 'value' => fn (Lead $lead): string => LeadTypes::label($lead->type)],
            'project' => ['label' => 'Project', 'value' => fn (Lead $lead): string => $lead->project->name ?? ''],
            'status' => ['label' => 'Status', 'value' => fn (Lead $lead): string => $lead->status->label()],
            'assignee' => ['label' => 'Assignee', 'value' => fn (Lead $lead): string => $lead->assignee->name ?? ''],
            'follow_up_at' => ['label' => 'Follow-up', 'value' => fn (Lead $lead): ?string => $lead->follow_up_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
            'source' => ['label' => 'Source', 'value' => fn (Lead $lead): string => LeadTypes::source($lead)],
            'utm_campaign' => ['label' => 'Campaign', 'value' => fn (Lead $lead): string => (string) $lead->utm_campaign],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $scope = fn (): Builder => Lead::query()->visibleTo($actor);
        $counts = fn (string $column): array => $scope()->select($column)->selectRaw('count(*) as total')->groupBy($column)->pluck('total', $column)->all();

        $statusCounts = $counts('status');
        $typeCounts = $counts('type');
        $projectCounts = $scope()->whereNotNull('project_id')->select('project_id')->selectRaw('count(*) as total')->groupBy('project_id')->pluck('total', 'project_id')->all();
        $closed = array_sum(array_map(fn (string $status): int => (int) ($statusCounts[$status] ?? 0), LeadStatus::closedValues()));
        $total = array_sum($statusCounts);

        $options = [
            'status' => array_map(fn (LeadStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($statusCounts[$status->value] ?? 0),
            ], LeadStatus::cases()),
            'type' => array_map(fn (string $type, string $label): array => [
                'value' => $type,
                'label' => $label,
                'count' => (int) ($typeCounts[$type] ?? 0),
            ], array_keys(LeadTypes::LABELS), array_values(LeadTypes::LABELS)),
            'stage' => [
                ['value' => 'open', 'label' => 'Open', 'count' => (int) ($total - $closed)],
                ['value' => 'closed', 'label' => 'Won, lost or spam', 'count' => $closed],
            ],
            'project' => Project::query()->whereIn('id', array_keys($projectCounts))->orderBy('name')->get(['id', 'name'])
                ->map(fn (Project $project): array => ['value' => (string) $project->id, 'label' => $project->name, 'count' => (int) $projectCounts[$project->id]])
                ->all(),
            'follow_up' => [
                ['value' => 'overdue', 'label' => 'Overdue'],
                ['value' => 'today', 'label' => 'Due today'],
                ['value' => 'upcoming', 'label' => 'Later'],
                ['value' => 'none', 'label' => 'None set'],
            ],
        ];

        // Someone who only sees their own leads has no use for an owner filter.
        if ($actor->can('leads.view-all')) {
            $options['assignee'] = [
                ['value' => 'me', 'label' => 'Me'],
                ['value' => 'none', 'label' => 'Unassigned', 'count' => $scope()->whereNull('assigned_to')->count()],
                ...array_map(
                    fn (array $user): array => ['value' => (string) $user['id'], 'label' => $user['name']],
                    app(LeadAssignees::class)->options(),
                ),
            ];
        }

        return $options;
    }

    /**
     * The board: one column per status with its count and the newest cards, for the same search and
     * filters as the table (a `status` filter does not apply, the columns are the statuses).
     *
     * @return list<array{status: string, label: string, count: int, cards: array<int, mixed>}>
     */
    public function board(Request $request, User $actor): array
    {
        $state = $this->state($request);
        $boardState = new TableState(
            page: 1,
            perPage: self::BOARD_COLUMN_LIMIT,
            sorts: ['-created_at'],
            filters: Arr::except($state->filters, ['status']),
            search: $state->search,
        );

        $counts = $this->query($boardState, $request, $actor)->getEloquentBuilder()->reorder()
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        $columns = [];

        foreach (LeadStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            $cards = $count === 0 ? [] : $this->query($boardState, $request, $actor)->getEloquentBuilder()
                ->where('status', $status->value)
                ->limit(self::BOARD_COLUMN_LIMIT)
                ->get();

            $columns[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => $count,
                'cards' => $count === 0 ? [] : LeadCardResource::collection($cards)->resolve($request),
            ];
        }

        return $columns;
    }

    /**
     * @param  list<string>  $allowed
     */
    private function enumFilter(string $name, string $column, array $allowed): AllowedFilter
    {
        return AllowedFilter::callback($name, function (Builder $query, mixed $value) use ($column, $allowed): void {
            $values = array_values(array_intersect(array_map(strval(...), Arr::wrap($value)), $allowed));

            if ($values !== []) {
                $query->whereIn($column, $values);
            }
        });
    }
}

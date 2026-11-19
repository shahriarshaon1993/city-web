<?php

namespace App\Queries\Admin;

use App\Enums\LeadStatus;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\Lead;
use App\Models\MediaAsset;
use App\Models\NotFoundLog;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use App\Support\Activity\ActivityAction;
use App\Support\Leads\LeadTypes;
use App\Support\Media\MediaKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity;

/**
 * ADM-DASH (spec/admin.md §10.3, design/admin.md §12.2): every number and list on the dashboard
 * home. Each widget is built only when the user holds its permission, and every lead figure is
 * cut down to what the user may see (`Lead::visibleTo`, AC-LEAD-05), so a Sales Executive's
 * cards count their own leads only. Spam never counts as a lead. Days are Asia/Dhaka days (ARC-016).
 */
final class DashboardData
{
    public const string TIMEZONE = 'Asia/Dhaka';

    /** @var list<int> ADM-DASH filter choices, in days. */
    public const array RANGES = [7, 30, 90];

    /** Colour slots in `--chart-1…5` (CMP-ADM-CHART): four lead types and "Other". */
    private const int TREND_TYPES = 4;

    private const int TOP_ROWS = 8;

    /** Dhaka is UTC+6 all year (no daylight saving), so a fixed offset groups rows into Dhaka days. */
    private const string DHAKA_DAY = "DATE(CONVERT_TZ(created_at, '+00:00', '+06:00'))";

    private CarbonImmutable $now;

    public function __construct(private readonly User $actor)
    {
        $this->now = CarbonImmutable::now(self::TIMEZONE);
    }

    public static function range(mixed $value, int $default): int
    {
        return in_array((int) $value, self::RANGES, true) ? (int) $value : $default;
    }

    public function sees(string ...$permissions): bool
    {
        return $this->actor->canAny($permissions);
    }

    public function seesLeads(): bool
    {
        return $this->sees('leads.view-all', 'leads.view-assigned');
    }

    /**
     * CMP-ADM-KPI cards the user may see, in the order of ADM-DASH. A zero is `value: null` with a
     * helper line, so a new site reads "—" and an explanation instead of a bare 0.
     *
     * @return list<array<string, mixed>>
     */
    public function kpis(): array
    {
        $cards = [];

        if ($this->seesLeads()) {
            $cards[] = $this->unreadLeads();
            $cards[] = $this->leadsThisMonth();
            $cards[] = $this->siteVisits();
            $cards[] = $this->conversion();
        }

        if ($this->sees('projects.view')) {
            $cards[] = $this->publishedProjects();
        }

        if ($this->sees('news.view')) {
            $cards[] = $this->articlesPublished();
        }

        if ($this->sees('jobs.view', 'applications.manage')) {
            $cards[] = $this->openRoles();
        }

        if ($this->sees('seo.view', 'seo.manage')) {
            $cards[] = $this->kpi('snapshots', 'Snapshot coverage', 'network', null, 'Pre-rendered pages are not switched on yet.', null, null);
        }

        return $cards;
    }

    /**
     * Leads per Dhaka day for the last `$days` days: all leads, and the busiest types.
     *
     * @return array{days: int, total: int, dates: list<string>, series: array{all: list<int>, types: list<array{key: string, label: string, values: list<int>}>}}
     */
    public function trend(int $days): array
    {
        $start = $this->now->startOfDay()->subDays($days - 1);

        $rows = $this->leads()
            ->where('created_at', '>=', $start->utc())
            ->selectRaw(self::DHAKA_DAY.' as day, type, count(*) as total')
            ->groupBy('day', 'type')
            ->get();

        $dates = [];

        for ($index = 0; $index < $days; $index++) {
            $dates[] = $start->addDays($index)->format('Y-m-d');
        }

        $perType = [];

        foreach ($rows as $row) {
            $perType[$row->type][(string) $row->getAttribute('day')] = (int) $row->getAttribute('total');
        }

        uasort($perType, fn (array $a, array $b): int => array_sum($b) <=> array_sum($a));

        $series = fn (array $byDay): array => array_map(fn (string $date): int => $byDay[$date] ?? 0, $dates);

        $types = [];
        $other = [];

        foreach (array_keys($perType) as $position => $type) {
            if ($position < self::TREND_TYPES) {
                $types[] = ['key' => $type, 'label' => LeadTypes::label($type), 'values' => $series($perType[$type])];

                continue;
            }

            foreach ($perType[$type] as $date => $count) {
                $other[$date] = ($other[$date] ?? 0) + $count;
            }
        }

        if ($other !== []) {
            $types[] = ['key' => 'other', 'label' => 'Other', 'values' => $series($other)];
        }

        $all = [];

        foreach ($perType as $byDay) {
            foreach ($byDay as $date => $count) {
                $all[$date] = ($all[$date] ?? 0) + $count;
            }
        }

        $allValues = $series($all);

        return [
            'days' => $days,
            'total' => array_sum($allValues),
            'dates' => $dates,
            'series' => ['all' => $allValues, 'types' => $types],
        ];
    }

    /**
     * ADM-DASH "Pipeline": current lead counts per status; each row opens the list on that status.
     *
     * @return list<array{key: string, label: string, count: int, href: string}>
     */
    public function pipeline(): array
    {
        $counts = $this->leads()->select('status')->selectRaw('count(*) as total')->groupBy('status')->pluck('total', 'status');

        $rows = [];

        foreach (LeadStatus::cases() as $status) {
            if ($status === LeadStatus::Spam) {
                continue;
            }

            $rows[] = [
                'key' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($counts[$status->value] ?? 0),
                'href' => $this->leadsUrl(['status' => $status->value]),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{key: string, label: string, count: int, href: string|null}>
     */
    public function leadsByProject(int $days): array
    {
        $since = $this->now->startOfDay()->subDays($days - 1);

        $rows = $this->leads()
            ->whereNotNull('project_id')
            ->where('created_at', '>=', $since->utc())
            ->select('project_id')->selectRaw('count(*) as total')
            ->groupBy('project_id')->orderByDesc('total')->limit(self::TOP_ROWS)
            ->get();

        $names = Project::query()->withTrashed()->whereIn('id', $rows->pluck('project_id'))->pluck('name', 'id');

        return array_values($rows->map(fn (Lead $row): array => [
            'key' => (string) $row->project_id,
            'label' => (string) ($names[$row->project_id] ?? 'Deleted project'),
            'count' => (int) $row->getAttribute('total'),
            'href' => $this->leadsUrl(['project' => (string) $row->project_id, 'created_from' => $since->format('Y-m-d')]),
        ])->all());
    }

    /**
     * Where leads came from: the campaign source/medium, else the referring site, else "Direct".
     *
     * @return list<array{key: string, label: string, count: int, href: string|null}>
     */
    public function leadSources(int $days): array
    {
        $since = $this->now->startOfDay()->subDays($days - 1);

        $totals = [];

        $this->leads()
            ->where('created_at', '>=', $since->utc())
            ->select('type', 'utm_source', 'utm_medium', 'referrer_host')->selectRaw('count(*) as total')
            ->groupBy('type', 'utm_source', 'utm_medium', 'referrer_host')
            ->get()
            ->each(function (Lead $row) use (&$totals): void {
                $label = LeadTypes::source($row);
                $totals[$label] = ($totals[$label] ?? 0) + (int) $row->getAttribute('total');
            });

        arsort($totals);

        return array_map(
            fn (string $label, int $count): array => ['key' => $label, 'label' => $label, 'count' => $count, 'href' => null],
            array_slice(array_keys($totals), 0, self::TOP_ROWS),
            array_slice(array_values($totals), 0, self::TOP_ROWS),
        );
    }

    /**
     * ADM-DASH "My follow-ups": open leads assigned to the user whose follow-up date is today or past.
     *
     * @return list<array{id: int, name: string, project: string|null, due_at: string, overdue: bool, href: string}>
     */
    public function followUps(): array
    {
        $endOfToday = $this->now->endOfDay();

        return array_values(Lead::query()
            ->with('project:id,name')
            ->where('assigned_to', $this->actor->id)
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', $endOfToday->utc())
            ->whereNotIn('status', LeadStatus::closedValues())
            ->orderBy('follow_up_at')
            ->limit(self::TOP_ROWS)
            ->get()
            ->map(fn (Lead $lead): array => [
                'id' => $lead->id,
                'name' => $lead->name,
                'project' => $lead->project?->name,
                'due_at' => $lead->follow_up_at?->toIso8601String() ?? '',
                'overdue' => $lead->follow_up_at !== null && $lead->follow_up_at->isPast(),
                'href' => route('admin.leads.show', $lead),
            ])->all());
    }

    /**
     * ADM-DASH "Unread enquiries": the newest leads nobody has opened.
     *
     * @return list<array{id: int, name: string, type: string, project: string|null, received_at: string, href: string}>
     */
    public function unreadEnquiries(): array
    {
        return array_values($this->leads()
            ->with('project:id,name')
            ->where('status', LeadStatus::New->value)
            ->latest()->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Lead $lead): array => [
                'id' => $lead->id,
                'name' => $lead->name,
                'type' => LeadTypes::label($lead->type),
                'project' => $lead->project?->name,
                'received_at' => $lead->created_at?->toIso8601String() ?? '',
                'href' => route('admin.leads.show', $lead),
            ])->all());
    }

    /**
     * ADM-DASH "Recent activity": the last ten audit entries.
     *
     * @return list<array{id: int, who: string|null, action: array{label: string, tone: string}, subject: string|null, created_at: string}>
     */
    public function recentActivity(): array
    {
        return array_values(Activity::query()
            ->with([
                'causer' => fn (MorphTo $causer) => $causer->withTrashed(),
                'subject',
            ])
            ->latest()->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'who' => $activity->causer instanceof User ? $activity->causer->name : null,
                'action' => ActivityAction::for($activity),
                'subject' => $activity->subject_type === null ? null : (string) (data_get($activity->subject, 'name') ?? data_get($activity->subject, 'title') ?? '#'.$activity->subject_id),
                'created_at' => $activity->created_at?->toIso8601String() ?? '',
            ])->all());
    }

    /**
     * ADM-DASH "SEO health": what to fix first.
     *
     * @return array{not_found: list<array{path: string, hits: int}>, not_found_href: string, missing_meta: array{pages: int, projects: int, articles: int}, missing_alt: int, missing_alt_href: string|null, snapshot_misses: int|null}
     */
    public function seoHealth(): array
    {
        $hasDescription = fn (Builder $meta): Builder => $meta->whereNotNull('meta_description')->where('meta_description', '!=', '');

        return [
            'not_found' => array_values(NotFoundLog::query()
                ->where('is_ignored', false)
                ->where('last_seen_at', '>=', $this->now->subDays(7)->utc())
                ->orderByDesc('hits')->limit(5)
                ->get()
                ->map(fn (NotFoundLog $log): array => ['path' => $log->path, 'hits' => $log->hits])
                ->all()),
            'not_found_href' => route('admin.not-found.index'),
            'missing_meta' => [
                'pages' => Page::query()->published()->whereDoesntHave('seoMeta', $hasDescription)->count(),
                'projects' => Project::query()->published()->whereDoesntHave('seoMeta', $hasDescription)->count(),
                'articles' => Post::query()->published()->whereDoesntHave('seoMeta', $hasDescription)->count(),
            ],
            'missing_alt' => MediaAsset::query()
                ->whereIn('kind', [MediaKind::Image->value, MediaKind::Svg->value])
                ->where('is_decorative', false)
                ->where(fn (Builder $alt) => $alt->whereNull('alt')->orWhere('alt', ''))
                ->count(),
            'missing_alt_href' => $this->sees('media.view', 'media.upload', 'media.update') ? url('admin/media').'?filter[missing_alt]=1' : null,
            // 19.3 builds the snapshot pipeline; until then there is nothing to miss.
            'snapshot_misses' => null,
        ];
    }

    /**
     * Leads the user may see, spam excluded.
     *
     * @return Builder<Lead>
     */
    private function leads(): Builder
    {
        return Lead::query()->visibleTo($this->actor)->where('status', '!=', LeadStatus::Spam->value);
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function leadsUrl(array $filters): string
    {
        return route('admin.leads.index', ['filter' => $filters]);
    }

    /** @return array<string, mixed> */
    private function unreadLeads(): array
    {
        $unread = $this->leads()->where('status', LeadStatus::New->value)->count();
        $thisWeek = $this->leads()->where('created_at', '>=', $this->now->subDays(7)->utc())->count();
        $lastWeek = $this->leads()->whereBetween('created_at', [$this->now->subDays(14)->utc(), $this->now->subDays(7)->utc()])->count();
        $helper = $this->leads()->exists() ? 'Every lead has been opened.' : 'No leads have come in yet.';

        return $this->kpi('unread_leads', 'Unread leads', 'inbox', $unread, $helper, $this->delta($thisWeek, $lastWeek, 'received vs the previous 7 days'), $this->leadsUrl(['status' => LeadStatus::New->value]));
    }

    /** @return array<string, mixed> */
    private function leadsThisMonth(): array
    {
        $monthStart = $this->now->startOfMonth();
        $elapsed = $monthStart->diffInSeconds($this->now);
        $lastStart = $monthStart->subMonthNoOverflow();
        $thisMonth = $this->leads()->where('created_at', '>=', $monthStart->utc())->count();
        $lastMonth = $this->leads()->whereBetween('created_at', [$lastStart->utc(), $lastStart->addSeconds($elapsed)->utc()])->count();

        return $this->kpi(
            'leads_month',
            'Leads this month',
            'phone-call',
            $thisMonth,
            'No leads yet this month.',
            $this->delta($thisMonth, $lastMonth, 'vs the same days last month'),
            $this->leadsUrl(['created_from' => $monthStart->format('Y-m-d'), 'created_to' => $monthStart->endOfMonth()->format('Y-m-d')]),
        );
    }

    /** @return array<string, mixed> */
    private function siteVisits(): array
    {
        $today = $this->now->startOfDay();
        $until = $today->addDays(7);

        // The follow-up date is the visit day; a visit nobody dated falls back to the day the visitor asked for.
        $count = $this->leads()
            ->where('status', LeadStatus::SiteVisitScheduled->value)
            ->where(fn (Builder $visit) => $visit
                ->whereBetween('follow_up_at', [$today->utc(), $until->utc()])
                ->orWhere(fn (Builder $undated) => $undated
                    ->whereNull('follow_up_at')
                    ->whereBetween('answers->visit_date', [$today->format('Y-m-d'), $until->format('Y-m-d')])))
            ->count();

        return $this->kpi('site_visits', 'Site visits', 'calendar-check', $count, 'No visits scheduled in the next 7 days.', null, $this->leadsUrl(['status' => LeadStatus::SiteVisitScheduled->value]), 'next 7 days');
    }

    /** @return array<string, mixed> */
    private function conversion(): array
    {
        $since = $this->now->subDays(90)->utc();
        $closed = $this->leads()->whereIn('status', [LeadStatus::Won->value, LeadStatus::Lost->value])->where('updated_at', '>=', $since);
        $total = (clone $closed)->count();
        $won = (clone $closed)->where('status', LeadStatus::Won->value)->count();

        return $this->kpi(
            'conversion',
            'Conversion',
            'trending-up',
            $total === 0 ? null : (int) round($won / $total * 100),
            'No leads were won or lost in the last 90 days.',
            null,
            $this->leadsUrl(['stage' => 'closed']),
            $total === 0 ? null : "{$won} won of {$total} closed, last 90 days",
            '%',
        );
    }

    /** @return array<string, mixed> */
    private function publishedProjects(): array
    {
        $drafts = Project::query()->where('publish_status', 'draft')->count();

        return $this->kpi(
            'projects',
            'Published projects',
            'building-2',
            Project::query()->published()->count(),
            'No project is published yet.',
            null,
            route('admin.projects.index', ['filter' => ['status' => 'published']]),
            $drafts > 0 ? "{$drafts} in draft" : null,
        );
    }

    /** @return array<string, mixed> */
    private function articlesPublished(): array
    {
        $now = $this->now->utc();
        $current = Post::query()->published()->whereBetween('published_at', [$now->subDays(30), $now])->count();
        $before = Post::query()->published()->whereBetween('published_at', [$now->subDays(60), $now->subDays(30)])->count();

        return $this->kpi('articles', 'Articles published', 'newspaper', $current, 'No article published in the last 30 days.', $this->delta($current, $before, 'vs the previous 30 days'), route('admin.news.index', ['filter' => ['status' => 'published']]), 'last 30 days');
    }

    /** @return array<string, mixed> */
    private function openRoles(): array
    {
        $roles = JobPost::query()->acceptingApplications()->count();
        $new = $this->sees('applications.manage') ? JobApplication::query()->where('status', 'new')->count() : null;
        $helper = $roles === 0 ? 'No role is open for applications.' : null;

        return $this->kpi(
            'open_roles',
            'Open roles',
            'briefcase',
            $roles,
            $helper ?? '',
            null,
            $this->sees('jobs.view') ? route('admin.careers.index') : route('admin.applications.index'),
            $new === null ? null : ($new === 0 ? 'no new applications' : "{$new} new ".($new === 1 ? 'application' : 'applications')),
        );
    }

    /**
     * @param  array{direction: string, text: string}|null  $delta
     * @return array<string, mixed>
     */
    private function kpi(string $key, string $label, string $icon, ?int $value, string $emptyHelper, ?array $delta, ?string $href, ?string $note = null, string $unit = ''): array
    {
        // A count of 0 reads "—" with its helper; a percentage of 0 is a real answer.
        $isEmpty = $value === null || ($value === 0 && $unit === '');

        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'value' => $isEmpty ? null : number_format($value).$unit,
            'helper' => $isEmpty ? $emptyHelper : $note,
            'delta' => $isEmpty ? null : $delta,
            'href' => $href,
        ];
    }

    /**
     * CMP-ADM-KPI delta chip: ▲ more, ▼ fewer, "—" no change. Nothing to compare with → no chip.
     *
     * @return array{direction: string, text: string}|null
     */
    private function delta(int $current, int $previous, string $comparison): ?array
    {
        if ($current === 0 && $previous === 0) {
            return null;
        }

        $difference = $current - $previous;

        return [
            'direction' => $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'flat'),
            'text' => ($difference === 0 ? 'No change' : ($difference > 0 ? '+' : '−').abs($difference)).' '.$comparison,
        ];
    }

    /**
     * Quick actions (CMP-ADM-QUICK) per permission.
     *
     * @return list<array{key: string, label: string, icon: string, href: string}>
     */
    public function quickActions(): array
    {
        $home = Page::query()->where('slug', Page::HOME)->value('id');

        $actions = [
            ['key' => 'project', 'label' => 'New project', 'icon' => 'building-2', 'href' => route('admin.projects.create'), 'can' => 'projects.write'],
            ['key' => 'article', 'label' => 'Write an article', 'icon' => 'pen-line', 'href' => route('admin.news.create'), 'can' => 'news.manage'],
            ['key' => 'media', 'label' => 'Upload media', 'icon' => 'upload', 'href' => route('admin.media.index'), 'can' => 'media.upload'],
            ['key' => 'home', 'label' => 'Edit the home page', 'icon' => 'layout-template', 'href' => $home === null ? route('admin.pages.index') : route('admin.pages.edit', $home), 'can' => 'pages.manage'],
            ['key' => 'role', 'label' => 'Post a role', 'icon' => 'briefcase', 'href' => route('admin.careers.create'), 'can' => 'jobs.manage'],
            ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'href' => route('admin.settings.index'), 'can' => 'settings.manage'],
        ];

        $allowed = [];

        foreach ($actions as $action) {
            if ($this->actor->can($action['can'])) {
                $allowed[] = ['key' => $action['key'], 'label' => $action['label'], 'icon' => $action['icon'], 'href' => $action['href']];
            }
        }

        return $allowed;
    }
}

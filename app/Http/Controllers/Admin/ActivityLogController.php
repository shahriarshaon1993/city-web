<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\Admin\ActivityLogTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-17: read-only (ADM-LOG-004). `activity.view-all` sees everyone, `activity.view-own` only
 * their own rows; the scoping is in ActivityLogTable::baseQuery so it also covers the export.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request, ActivityLogTable $table): Response|RedirectResponse
    {
        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/ActivityLog', [
            ...$table->props($request, $actor),
            'canViewAll' => ActivityLogTable::canViewAll($actor),
            'canShareViews' => $actor->can('views.share'),
            'breadcrumbs' => [
                ['title' => 'System', 'href' => null],
                ['title' => 'Activity log', 'href' => null],
            ],
        ]);
    }

    /**
     * Options for the "Who" filter (ADM-TBL-003 relation combobox): users matching the typed text.
     */
    public function causers(Request $request): JsonResponse
    {
        abort_unless(ActivityLogTable::canViewAll($request->user()), 403);

        $term = '%'.addcslashes(trim((string) $request->query('q')), '%_\\').'%';
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));

        return response()->json(
            User::query()
                ->withTrashed()
                ->when(
                    $ids !== [],
                    fn (Builder $query) => $query->whereKey($ids),
                    fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('name', 'like', $term)->orWhere('email', 'like', $term)),
                )
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => $user->name, 'hint' => $user->email])
                ->all(),
        );
    }
}

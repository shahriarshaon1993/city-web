<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Monitoring\DeleteNotFoundLogs;
use App\Actions\Monitoring\SetNotFoundIgnored;
use App\Actions\Monitoring\UpdateNotFoundIgnoreList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkNotFoundRequest;
use App\Http\Requests\Admin\UpdateNotFoundIgnoreListRequest;
use App\Models\NotFoundLog;
use App\Queries\Admin\NotFoundTable;
use App\Support\Redirects\NotFoundIgnore;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-14 404 monitor tab (SEO-TEC-009). Ignore is inline; delete is for good (DEL-09).
 */
class NotFoundController extends Controller
{
    public function index(Request $request, NotFoundTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', NotFoundLog::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        // Triage starts with what nobody has dealt with yet.
        if ($request->query() === []) {
            return to_route('admin.not-found.index', ['filter' => ['status' => 'open']]);
        }

        return Inertia::render('admin/NotFound', [
            ...$table->props($request, $actor),
            'canManage' => $actor->can('manage', NotFoundLog::class),
            'canShareViews' => $actor->can('views.share'),
            'ignoreList' => [
                'patterns' => NotFoundIgnore::text(),
                'defaults' => implode("\n", NotFoundIgnore::DEFAULTS),
                'is_custom' => NotFoundIgnore::isCustom(),
            ],
            'retentionDays' => NotFoundLog::RETENTION_DAYS,
            'breadcrumbs' => [
                ['title' => 'SEO', 'href' => null],
                ['title' => '404 monitor', 'href' => null],
            ],
        ]);
    }

    /** The inline Ignore / Stop ignoring of one row. */
    public function ignore(Request $request, NotFoundLog $log, SetNotFoundIgnored $setIgnored): RedirectResponse
    {
        Gate::authorize('update', $log);

        $ignored = $request->boolean('ignored');
        $setIgnored->handle($request->user(), collect([$log]), $ignored);

        $toast = Toast::success($ignored ? 'Address ignored' : 'Address is open again')->description($log->path);

        if ($ignored) {
            $toast->action('Undo', route('admin.not-found.ignore', $log), 'post', ['ignored' => false]);
        }

        $toast->flash();

        return back();
    }

    /** DEL-09: a 404 row is hard-deleted after a confirm; it returns if visitors ask for the address again. */
    public function destroy(Request $request, NotFoundLog $log, DeleteNotFoundLogs $deleteLogs): RedirectResponse
    {
        Gate::authorize('delete', $log);

        $deleteLogs->handle($request->user(), collect([$log]));

        Toast::success('Address deleted')->description($log->path)->flash();

        return back();
    }

    public function bulk(BulkNotFoundRequest $request, NotFoundTable $table, SetNotFoundIgnored $setIgnored, DeleteNotFoundLogs $deleteLogs): RedirectResponse
    {
        $logs = $table->selection($request, $request->user())->limit(BulkNotFoundRequest::MAX_IDS * 20)->get();
        $action = $request->string('action')->toString();

        if ($action === 'delete') {
            $count = $deleteLogs->handle($request->user(), $logs);
            Toast::success($count.' '.($count === 1 ? 'address' : 'addresses').' deleted')->flash();

            return back();
        }

        $ignored = $action === 'ignore';
        $count = $setIgnored->handle($request->user(), $logs, $ignored);
        $noun = $count === 1 ? 'address' : 'addresses';

        $toast = $count === 0
            ? Toast::info('No addresses were changed')
            : Toast::success($count.' '.$noun.($ignored ? ' ignored' : ' open again'));

        if ($ignored && $count > 0) {
            $toast->action('Undo', route('admin.not-found.bulk'), 'post', ['action' => 'unignore', 'ids' => $logs->modelKeys()]);
        }

        $toast->flash();

        return back();
    }

    public function updateIgnoreList(UpdateNotFoundIgnoreListRequest $request, UpdateNotFoundIgnoreList $updateIgnoreList): RedirectResponse
    {
        $reset = $request->boolean('reset');

        $updateIgnoreList->handle($request->user(), $reset ? null : (string) $request->validated('patterns'));

        Toast::success($reset ? 'Ignore list reset to the defaults' : 'Ignore list saved')->flash();

        return back();
    }
}

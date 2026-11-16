<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\Redirects\ImportRedirects;
use App\Actions\Redirects\SaveRedirect;
use App\Actions\Redirects\SetRedirectActive;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkRedirectsRequest;
use App\Http\Requests\Admin\ImportRedirectsRequest;
use App\Http\Requests\Admin\SaveRedirectRequest;
use App\Models\Redirect;
use App\Queries\Admin\RecycleBinTable;
use App\Queries\Admin\RedirectsTable;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-14 Redirects tab (SEO-TEC-008). Controllers only authorise, validate, call an Action and
 * respond (DEC-32); each mutation flashes exactly one toast (TOAST-01).
 */
class RedirectController extends Controller
{
    public function index(Request $request, RedirectsTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Redirect::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/Redirects', [
            ...$table->props($request, $actor),
            'canManage' => $actor->can('create', Redirect::class),
            'canShareViews' => $actor->can('views.share'),
            'limit' => ['used' => Redirect::query()->count(), 'max' => Redirect::MAX_RULES],
            'binCount' => RecycleBinTable::countFor($actor, Redirect::binType()),
            'breadcrumbs' => [
                ['title' => 'SEO', 'href' => null],
                ['title' => 'Redirects', 'href' => null],
            ],
        ]);
    }

    public function store(SaveRedirectRequest $request, SaveRedirect $saveRedirect): RedirectResponse
    {
        $result = $saveRedirect->handle($request->user(), $request->validated());

        $this->savedToast('Redirect created', $result)->flash();

        return back();
    }

    public function update(SaveRedirectRequest $request, Redirect $redirect, SaveRedirect $saveRedirect): RedirectResponse
    {
        $result = $saveRedirect->handle($request->user(), $request->validated(), $redirect);

        if (! $result['changed']) {
            Toast::info('No changes to save')->flash();

            return back();
        }

        $this->savedToast('Redirect saved', $result)->flash();

        return back();
    }

    /** The inline Active switch (CRUD-01). */
    public function active(Request $request, Redirect $redirect, SetRedirectActive $setRedirectActive): RedirectResponse
    {
        Gate::authorize('update', $redirect);

        $active = $request->boolean('active');

        try {
            $setRedirectActive->handle($request->user(), $redirect, $active);
        } catch (ValidationException $exception) {
            Toast::error('Redirect not switched on')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success($active ? 'Redirect switched on' : 'Redirect switched off')->description($redirect->sourceLabel())->flash();

        return back();
    }

    /** DEL-02: Delete = Recycle bin, with Undo (TOAST-02). */
    public function destroy(Request $request, Redirect $redirect, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        Gate::authorize('delete', $redirect);

        $entry = $moveToRecycleBin->handle($request->user(), $redirect);

        Toast::success('Redirect moved to Recycle bin')
            ->description($redirect->sourceLabel())
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return back();
    }

    public function bulk(BulkRedirectsRequest $request, RedirectsTable $table, SetRedirectActive $setRedirectActive, MoveManyToRecycleBin $moveMany): RedirectResponse
    {
        $redirects = $table->selection($request, $request->user())->limit(BulkRedirectsRequest::MAX_IDS * 20)->get();
        $action = $request->string('action')->toString();

        if ($action === 'delete') {
            $result = $moveMany->handle($request->user(), $redirects);
            $count = count($result['entries']);
            $toast = $this->bulkToast($count === 0 ? 'No redirects were deleted' : $count.' '.($count === 1 ? 'redirect' : 'redirects').' moved to Recycle bin', $result['refused']);

            if ($count > 0) {
                $toast->undo(route('admin.recycle-bin.restore-many'), [
                    'ids' => array_map(fn ($entry): int => $entry->id, $result['entries']),
                    'undo' => true,
                ]);
            }

            $toast->flash();

            return back();
        }

        $active = $action === 'activate';
        $changed = 0;
        $refused = [];

        foreach ($redirects as $redirect) {
            try {
                $before = $redirect->is_active;
                $setRedirectActive->handle($request->user(), $redirect, $active);
                $changed += $before === $active ? 0 : 1;
            } catch (ValidationException $exception) {
                $refused[] = $redirect->sourceLabel().': '.$exception->validator->errors()->first();
            }
        }

        $noun = $changed === 1 ? 'redirect' : 'redirects';
        $this->bulkToast($changed === 0 ? 'No redirects were changed' : "{$changed} {$noun} ".($active ? 'switched on' : 'switched off'), $refused)->flash();

        return back();
    }

    /** SEO-TEC-008 CSV import; the report of rows that were skipped travels in the flash to the dialog. */
    public function import(ImportRedirectsRequest $request, ImportRedirects $importRedirects): RedirectResponse
    {
        $report = $importRedirects->handle($request->user(), $request->file('file')->getRealPath());

        $saved = $report['created'] + $report['updated'];
        $title = $saved === 0
            ? 'No redirects were imported'
            : $saved.' '.($saved === 1 ? 'redirect' : 'redirects').' imported';

        $toast = $report['skipped'] === 0 ? Toast::success($title) : Toast::warning($title.' · '.$report['skipped'].' skipped');
        $toast->description($report['created'].' new, '.$report['updated'].' updated.')->flash();

        Inertia::flash('import_report', $report);

        return back();
    }

    /**
     * @param  array{redirect: Redirect, shortened: int, changed: bool}  $result
     */
    private function savedToast(string $title, array $result): Toast
    {
        $toast = Toast::success($title)->description($result['redirect']->sourceLabel().($result['redirect']->target === null ? ' → Gone' : ' → '.$result['redirect']->target));

        if ($result['shortened'] > 0) {
            $toast = Toast::success($title)->description(
                $result['redirect']->sourceLabel().' · '.$result['shortened'].' '.($result['shortened'] === 1 ? 'chain' : 'chains').' shortened so visitors take one hop.',
            );
        }

        return $toast;
    }

    /**
     * TOAST-06: partial success is reported honestly, with the reasons.
     *
     * @param  list<string>  $refused
     */
    private function bulkToast(string $title, array $refused): Toast
    {
        if ($refused === []) {
            return Toast::success($title);
        }

        return Toast::warning($title.' · '.count($refused).' skipped')->description(implode(' ', array_slice($refused, 0, 3)));
    }
}

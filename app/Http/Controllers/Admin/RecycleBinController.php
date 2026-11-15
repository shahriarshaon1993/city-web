<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecycleBin\DeleteForever;
use App\Actions\RecycleBin\DeleteManyForever;
use App\Actions\RecycleBin\RestoreFromRecycleBin;
use App\Actions\RecycleBin\RestoreManyFromRecycleBin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecycleBinBulkRequest;
use App\Http\Requests\Admin\RestoreFromBinRequest;
use App\Models\RecycleBinEntry;
use App\Queries\Admin\RecycleBinTable;
use App\Support\RecycleBin\RestoreConflictException;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DEL-04 Recycle bin screen (System group). Controllers authorise, validate, call an Action and
 * respond (DEC-32); each mutation flashes exactly one toast (TOAST-01).
 */
class RecycleBinController extends Controller
{
    public function index(Request $request, RecycleBinTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', RecycleBinEntry::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/RecycleBin', [
            ...$table->props($request, $actor),
            'canShareViews' => $actor->can('views.share'),
            'breadcrumbs' => [
                ['title' => 'System', 'href' => null],
                ['title' => 'Recycle bin', 'href' => null],
            ],
        ]);
    }

    public function restore(RestoreFromBinRequest $request, RecycleBinEntry $entry, RestoreFromRecycleBin $restore): RedirectResponse
    {
        Gate::authorize('restore', $entry);

        $label = $entry->typeLabel();

        try {
            $item = $restore->handle($request->user(), $entry, $request->resolution());
        } catch (RestoreConflictException $exception) {
            Toast::error($label.' not restored')->description($exception->conflicts[0]->message)->flash();

            return back();
        } catch (ValidationException $exception) {
            Toast::error('Nothing was restored')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        if ($request->boolean('undo')) {
            Toast::success('Undone')->flash();
        } else {
            $toast = Toast::success($label.' restored');
            $url = $item->binUrl();

            if ($url !== null) {
                $toast->action('Open', $url);
            }

            $toast->flash();
        }

        return back();
    }

    public function restoreMany(RecycleBinBulkRequest $request, RecycleBinTable $table, RestoreManyFromRecycleBin $restore): RedirectResponse
    {
        $entries = $this->entriesFor($request, $table);

        $result = $restore->handle($request->user(), $entries);

        if ($request->boolean('undo')) {
            $toast = $result['refused'] === [] ? Toast::success('Undone') : Toast::warning('Undone · '.count($result['refused']).' skipped');
        } else {
            $count = $result['restored'];
            $title = $count.' '.($count === 1 ? 'item' : 'items').' restored';
            $toast = $result['refused'] === [] ? Toast::success($title) : Toast::warning($title.' · '.count($result['refused']).' skipped');
        }

        if ($result['refused'] !== []) {
            $toast->description(implode(' ', array_slice($result['refused'], 0, 3)));
        }

        $toast->flash();

        return back();
    }

    public function destroy(Request $request, RecycleBinEntry $entry, DeleteForever $deleteForever): RedirectResponse
    {
        Gate::authorize('forceDelete', $entry);

        $label = $entry->typeLabel();

        $deleteForever->handle($request->user(), $entry);

        Toast::success($label.' deleted forever')->flash();

        return back();
    }

    public function destroyMany(RecycleBinBulkRequest $request, RecycleBinTable $table, DeleteManyForever $deleteMany): RedirectResponse
    {
        $entries = $this->entriesFor($request, $table)
            ->filter(fn (RecycleBinEntry $entry): bool => $request->user()->can('forceDelete', $entry))
            ->values();

        $count = $deleteMany->handle($request->user(), $entries);

        Toast::success($count.' '.($count === 1 ? 'item' : 'items').' deleted forever')->flash();

        return back();
    }

    /**
     * @return Collection<int, RecycleBinEntry>
     */
    private function entriesFor(RecycleBinBulkRequest $request, RecycleBinTable $table): Collection
    {
        return $table->selection($request, $request->user())
            ->limit(RecycleBinBulkRequest::MAX_IDS * 20)
            ->get();
    }
}

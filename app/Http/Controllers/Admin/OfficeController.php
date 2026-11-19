<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\Social\ReorderSocialItems;
use App\Actions\Social\SaveOffice;
use App\Actions\Social\SetSocialPublished;
use App\Http\Controllers\Admin\Concerns\ManagesSocialItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkSocialRequest;
use App\Http\Requests\Admin\ReorderRequest;
use App\Http\Requests\Admin\SaveOfficeRequest;
use App\Models\Office;
use App\Queries\Admin\OfficesTable;
use App\Queries\Admin\RecycleBinTable;
use App\Queries\Admin\SocialEditor;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** ADM-SCR-09 offices: a table with drag order; a full-page editor; Delete is guarded by job posts (DEL-03). */
class OfficeController extends Controller
{
    use ManagesSocialItems;

    protected function itemClass(): string
    {
        return Office::class;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function nouns(): array
    {
        return ['office', 'offices'];
    }

    protected function logName(): string
    {
        return 'offices';
    }

    public function index(Request $request, OfficesTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Office::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/offices/Index', [
            ...$table->props($request, $actor),
            'canManage' => $actor->can('create', Office::class),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, Office::binType()),
            'order' => fn (): array => SocialEditor::orderList(Office::class, fn (Office $item): array => [
                'id' => $item->id, 'label' => $item->name, 'hint' => $item->city, 'published' => true,
            ]),
            'breadcrumbs' => [['title' => 'Content', 'href' => null], ['title' => 'Offices', 'href' => null]],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Office::class);

        return $this->editor(null);
    }

    public function store(SaveOfficeRequest $request, SaveOffice $saveOffice): RedirectResponse
    {
        $result = $saveOffice->handle($request->user(), $request->validated());

        Toast::success('Office created')->description($result['office']->name)->flash();

        return to_route('admin.offices.edit', $result['office']);
    }

    public function edit(Office $office): Response
    {
        Gate::authorize('update', $office);

        return $this->editor($office);
    }

    public function update(SaveOfficeRequest $request, Office $office, SaveOffice $saveOffice): RedirectResponse
    {
        $result = $saveOffice->handle($request->user(), $request->validated(), $office);

        $result['changed']
            ? Toast::success('Office saved')->description($office->name)->flash()
            : Toast::info('No changes to save')->flash();

        return back();
    }

    public function order(ReorderRequest $request, ReorderSocialItems $reorder): RedirectResponse
    {
        return $this->respondOrder($request, $reorder, 'admin.offices.order');
    }

    public function destroy(Request $request, Office $office, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        return $this->respondDestroy($request, $office, $moveToRecycleBin);
    }

    public function bulk(BulkSocialRequest $request, OfficesTable $table, MoveManyToRecycleBin $moveMany, SetSocialPublished $setPublished): RedirectResponse
    {
        return $this->respondBulk($request, $table, $moveMany, $setPublished);
    }

    private function editor(?Office $office): Response
    {
        return Inertia::render('admin/offices/Edit', [
            'office' => $office === null ? null : ['id' => $office->id, 'name' => $office->name],
            'form' => SocialEditor::office($office),
            'options' => SocialEditor::officeOptions(),
            'lastSaved' => $office?->updated_at?->toIso8601String(),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Offices', 'href' => route('admin.offices.index')],
                ['title' => $office->name ?? 'New office', 'href' => null],
            ],
        ]);
    }
}

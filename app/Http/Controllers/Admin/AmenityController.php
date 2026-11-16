<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Amenities\DeleteAmenity;
use App\Actions\Amenities\ReorderAmenities;
use App\Actions\Amenities\SaveAmenity;
use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Enums\AmenityCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderRequest;
use App\Http\Requests\Admin\SaveAmenityRequest;
use App\Models\Amenity;
use App\Queries\Admin\AmenitiesTable;
use App\Queries\Admin\RecycleBinTable;
use App\Support\Amenities\AmenityIcons;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** ADM-SCR-03 amenities: a table with a dialog editor, an icon picker and a "used by" guard (DEL-03). */
class AmenityController extends Controller
{
    public function index(Request $request, AmenitiesTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Amenity::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/Amenities', [
            ...$table->props($request, $actor),
            'canManage' => $actor->can('create', Amenity::class),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, Amenity::binType()),
            'icons' => AmenityIcons::names(),
            'categories' => array_map(fn (AmenityCategory $category): array => ['value' => $category->value, 'label' => $category->label()], AmenityCategory::cases()),
            // The reorder dialog lists every amenity in library order.
            'order' => fn (): array => Amenity::query()->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (Amenity $amenity): array => ['id' => $amenity->id, 'name' => $amenity->name, 'icon' => $amenity->icon, 'category' => $amenity->category->label()])->all(),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Locations', 'href' => route('admin.locations.index')],
                ['title' => 'Amenities', 'href' => null],
            ],
        ]);
    }

    public function store(SaveAmenityRequest $request, SaveAmenity $saveAmenity): RedirectResponse
    {
        $result = $saveAmenity->handle($request->user(), $request->validated());

        Toast::success('Amenity created')->description($result['amenity']->name)->flash();

        return back();
    }

    public function update(SaveAmenityRequest $request, Amenity $amenity, SaveAmenity $saveAmenity): RedirectResponse
    {
        $result = $saveAmenity->handle($request->user(), $request->validated(), $amenity);

        if (! $result['changed']) {
            Toast::info('No changes to save')->flash();

            return back();
        }

        Toast::success('Amenity saved')->description($amenity->name)->flash();

        return back();
    }

    /** DEL-02 / DEL-03: Recycle bin with Undo; `detach` is the second confirm when projects use it. */
    public function destroy(Request $request, Amenity $amenity, DeleteAmenity $deleteAmenity): RedirectResponse
    {
        Gate::authorize('delete', $amenity);

        try {
            $entry = $deleteAmenity->handle($request->user(), $amenity, $request->boolean('detach'));
        } catch (ValidationException $exception) {
            Toast::error('Amenity not deleted')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success('Amenity moved to Recycle bin')
            ->description($amenity->name)
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return back();
    }

    public function bulk(Request $request, AmenitiesTable $table, MoveManyToRecycleBin $moveMany): RedirectResponse
    {
        abort_unless($request->user()->can('catalog.manage'), 403);

        $amenities = $table->selection($request, $request->user())->limit(500)->get();
        $result = $moveMany->handle($request->user(), $amenities);
        $count = count($result['entries']);

        $title = $count === 0 ? 'No amenities were deleted' : $count.' '.($count === 1 ? 'amenity' : 'amenities').' moved to Recycle bin';
        $toast = $result['refused'] === []
            ? Toast::success($title)
            : Toast::warning($title.' · '.count($result['refused']).' skipped')->description(implode(' ', array_slice($result['refused'], 0, 3)));

        if ($count > 0) {
            $toast->undo(route('admin.recycle-bin.restore-many'), ['ids' => array_map(fn ($entry): int => $entry->id, $result['entries']), 'undo' => true]);
        }

        $toast->flash();

        return back();
    }

    public function order(ReorderRequest $request, ReorderAmenities $reorder): RedirectResponse
    {
        abort_unless($request->user()->can('catalog.manage'), 403);

        $previous = $reorder->handle($request->user(), $request->ids());

        Toast::success('Order saved')
            ->action('Undo', route('admin.amenities.order'), 'patch', ['ids' => $previous])
            ->flash();

        return back();
    }
}

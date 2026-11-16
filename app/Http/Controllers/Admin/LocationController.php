<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Locations\ReorderLocations;
use App\Actions\Locations\SaveArea;
use App\Actions\Locations\SaveCity;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Enums\Segment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderRequest;
use App\Http\Requests\Admin\SaveAreaRequest;
use App\Http\Requests\Admin\SaveCityRequest;
use App\Http\Resources\Admin\MediaAssetResource;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Queries\Admin\LocationTree;
use App\Queries\Admin\ProjectEditor;
use App\Queries\Admin\RecycleBinTable;
use App\Support\Locations\Indexability;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-04 locations: the city → area tree with drag order, the city dialog and the area editor
 * (with the indexability verdict of FR-LOC-003). Delete is the Recycle bin, guarded by projects
 * and child areas (DEL-03).
 */
class LocationController extends Controller
{
    public function index(Request $request, LocationTree $tree): Response
    {
        Gate::authorize('viewAny', Location::class);

        $actor = $request->user();

        return Inertia::render('admin/locations/Index', [
            'cities' => $tree->nodes(),
            'canManage' => $actor->can('create', Location::class),
            'binCount' => RecycleBinTable::countFor($actor, Location::binType()),
            'minIntroWords' => Indexability::MIN_INTRO_WORDS,
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Locations', 'href' => null],
            ],
        ]);
    }

    public function storeCity(SaveCityRequest $request, SaveCity $saveCity): RedirectResponse
    {
        $result = $saveCity->handle($request->user(), $request->validated());

        Toast::success('City created')->description($result['location']->name)->flash();

        return back();
    }

    public function updateCity(SaveCityRequest $request, Location $location, SaveCity $saveCity): RedirectResponse
    {
        abort_unless($location->isCity(), 404);

        $result = $saveCity->handle($request->user(), $request->validated(), $location);

        if (! $result['changed']) {
            Toast::info('No changes to save')->flash();

            return back();
        }

        Toast::success('City saved')->description($location->name)->flash();

        return back();
    }

    public function createArea(Request $request): Response
    {
        Gate::authorize('create', Location::class);

        $city = $request->integer('city');

        return $this->areaEditor($request, null, $city > 0 ? $city : null);
    }

    public function storeArea(SaveAreaRequest $request, SaveArea $saveArea): RedirectResponse
    {
        $area = $saveArea->handle($request->user(), $request->validated());

        Toast::success('Area created')->description($area->name)->flash();

        return to_route('admin.locations.areas.edit', $area);
    }

    public function editArea(Request $request, Location $location): Response
    {
        abort_if($location->isCity(), 404);
        Gate::authorize('viewAny', Location::class);

        return $this->areaEditor($request, $location, null);
    }

    public function updateArea(SaveAreaRequest $request, Location $location, SaveArea $saveArea): RedirectResponse
    {
        abort_if($location->isCity(), 404);

        $saveArea->handle($request->user(), $request->validated(), $location);

        Toast::success('Area saved')->description($location->name)->flash();

        return back();
    }

    /** DEL-03: a city with areas or an area with projects is refused; the dialog on the page listed them already. */
    public function destroy(Request $request, Location $location, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        Gate::authorize('delete', $location);

        try {
            $entry = $moveToRecycleBin->handle($request->user(), $location);
        } catch (ValidationException $exception) {
            Toast::error(($location->isCity() ? 'City' : 'Area').' not deleted')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success(($location->isCity() ? 'City' : 'Area').' moved to Recycle bin')
            ->description($location->name)
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return str_contains((string) url()->previous(), '/edit') ? to_route('admin.locations.index') : back();
    }

    /** CRUD-06: `parent_id` empty = the cities, else the areas of that city. */
    public function order(ReorderRequest $request, ReorderLocations $reorder): RedirectResponse
    {
        abort_unless($request->user()->can('catalog.manage'), 403);

        $parentId = $request->filled('parent_id') ? $request->integer('parent_id') : null;
        $previous = $reorder->handle($request->user(), $parentId, $request->ids());

        Toast::success('Order saved')
            ->action('Undo', route('admin.locations.order'), 'patch', ['ids' => $previous, 'parent_id' => $parentId])
            ->flash();

        return back();
    }

    private function areaEditor(Request $request, ?Location $area, ?int $cityId): Response
    {
        $actor = $request->user();
        $area?->load(['nearby', 'seoMeta']);

        $form = [
            'name' => $area->name ?? '',
            'slug' => $area->slug ?? '',
            'parent_id' => $area->parent_id ?? $cityId,
            'publish_status' => $area?->publish_status->value ?? 'draft',
            'intro' => $area->intro ?? '',
            'segment_intros' => collect(Segment::cases())->mapWithKeys(fn (Segment $segment): array => [$segment->value => $area?->segment_intros[$segment->value] ?? ''])->all(),
            'facts' => $area->facts ?? [],
            'hero_asset_id' => $area?->hero_asset_id,
            'latitude' => $area?->latitude,
            'longitude' => $area?->longitude,
            'zoom' => $area->zoom ?? 14,
            'nearby_ids' => $area?->nearby->pluck('id')->all() ?? [],
            'seo' => ProjectEditor::seo($area?->seoMeta),
        ];

        $assetIds = array_filter([$form['hero_asset_id'], $form['seo']['og_image_asset_id']]);

        return Inertia::render('admin/locations/Area', [
            'area' => $area === null ? null : [
                'id' => $area->id,
                'name' => $area->name,
                'status' => $area->publish_status->value,
                'public_url' => $area->isPublished() ? url("/locations/{$area->slug}") : null,
                'projects_count' => $area->projects()->count(),
                'indexability' => app(Indexability::class)->for($area),
            ],
            'form' => $form,
            'assets' => MediaAsset::query()->whereKey($assetIds)->with('media')->get()
                ->mapWithKeys(fn (MediaAsset $asset): array => [$asset->id => (new MediaAssetResource($asset))->resolve($request)])->all(),
            'options' => [
                'cities' => Location::query()->cities()->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
                    ->map(fn (Location $city): array => ['value' => $city->id, 'label' => $city->name])->all(),
                'areas' => Location::query()->areas()->with('parent')->when($area, fn ($query) => $query->whereKeyNot($area->id))->orderBy('name')->get()
                    ->map(fn (Location $other): array => ['value' => $other->id, 'label' => $other->name, 'group' => $other->parent->name ?? ''])->all(),
                'segments' => array_map(fn (Segment $segment): array => ['value' => $segment->value, 'label' => $segment->label()], Segment::cases()),
                'min_intro_words' => Indexability::MIN_INTRO_WORDS,
                'limits' => ['facts' => Location::FACTS_MAX, 'nearby' => Location::NEARBY_MAX, 'zoom_min' => Location::ZOOM_MIN, 'zoom_max' => Location::ZOOM_MAX],
            ],
            'lastSaved' => $area?->updated_at?->toIso8601String(),
            'can' => [
                'save' => $area === null ? $actor->can('create', Location::class) : $actor->can('update', $area),
                'delete' => $area !== null && $actor->can('delete', $area),
            ],
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Locations', 'href' => route('admin.locations.index')],
                ['title' => $area->name ?? 'New area', 'href' => null],
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Facets\UpdateFacetPage;
use App\Enums\FacetKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFacetPageRequest;
use App\Http\Resources\Admin\MediaAssetResource;
use App\Models\FacetPage;
use App\Models\MediaAsset;
use App\Queries\Admin\ProjectEditor;
use App\Support\Html\BasicHtml;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-05 facet pages: one fixed row per segment and per project status, each with an H1, an
 * intro and SEO. (The facet FAQ stays demo content until the faqs table, 16.4 / DEC-82.)
 */
class FacetPageController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FacetPage::class);

        $facets = FacetPage::query()->with('seoMeta')->orderBy('kind')->orderBy('sort_order')->get();

        return Inertia::render('admin/locations/Facets', [
            'facets' => $facets->map(fn (FacetPage $facet): array => $this->row($facet))->all(),
            'canManage' => $request->user()->can('catalog.manage'),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Locations', 'href' => route('admin.locations.index')],
                ['title' => 'Facet pages', 'href' => null],
            ],
        ]);
    }

    public function edit(Request $request, FacetPage $facet): Response
    {
        Gate::authorize('viewAny', FacetPage::class);

        $facet->load('seoMeta');
        $seo = ProjectEditor::seo($facet->seoMeta);

        return Inertia::render('admin/locations/Facet', [
            'facet' => $this->row($facet),
            'form' => ['title' => $facet->title, 'intro' => $facet->intro ?? '', 'seo' => $seo],
            'assets' => MediaAsset::query()->whereKey(array_filter([$seo['og_image_asset_id']]))->with('media')->get()
                ->mapWithKeys(fn (MediaAsset $asset): array => [$asset->id => (new MediaAssetResource($asset))->resolve($request)])->all(),
            'canManage' => $request->user()->can('update', $facet),
            'lastSaved' => $facet->updated_at?->toIso8601String(),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Locations', 'href' => route('admin.locations.index')],
                ['title' => 'Facet pages', 'href' => route('admin.facets.index')],
                ['title' => $facet->title, 'href' => null],
            ],
        ]);
    }

    public function update(UpdateFacetPageRequest $request, FacetPage $facet, UpdateFacetPage $updateFacetPage): RedirectResponse
    {
        $updateFacetPage->handle($request->user(), $facet, $request->validated());

        Toast::success('Facet page saved')->description($facet->title)->flash();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(FacetPage $facet): array
    {
        $segment = $facet->kind === FacetKind::Segment;
        $path = ($segment ? '/projects/type/' : '/projects/status/').str_replace('_', '-', $facet->value);

        return [
            'id' => $facet->id,
            'kind' => $facet->kind->value,
            'kind_label' => $segment ? 'Segment' : 'Status',
            'value' => $facet->value,
            'title' => $facet->title,
            'intro_words' => BasicHtml::wordCount($facet->intro),
            'has_seo' => $facet->relationLoaded('seoMeta') && $facet->seoMeta !== null,
            'path' => $path,
            'public_url' => url($path),
            'edit_url' => route('admin.facets.edit', $facet),
        ];
    }
}

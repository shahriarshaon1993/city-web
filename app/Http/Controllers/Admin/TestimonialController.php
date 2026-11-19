<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\Social\ReorderSocialItems;
use App\Actions\Social\SaveTestimonial;
use App\Actions\Social\SetSocialPublished;
use App\Http\Controllers\Admin\Concerns\ManagesSocialItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkSocialRequest;
use App\Http\Requests\Admin\ReorderRequest;
use App\Http\Requests\Admin\SaveTestimonialRequest;
use App\Models\Testimonial;
use App\Queries\Admin\RecycleBinTable;
use App\Queries\Admin\SocialEditor;
use App\Queries\Admin\TestimonialsTable;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** ADM-SCR-09 testimonials: a table with drag order and a Published switch; a full-page editor (7 fields). */
class TestimonialController extends Controller
{
    use ManagesSocialItems;

    protected function itemClass(): string
    {
        return Testimonial::class;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function nouns(): array
    {
        return ['testimonial', 'testimonials'];
    }

    protected function logName(): string
    {
        return 'testimonials';
    }

    public function index(Request $request, TestimonialsTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Testimonial::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/testimonials/Index', [
            ...$table->props($request, $actor),
            'canManage' => $actor->can('create', Testimonial::class),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, Testimonial::binType()),
            'order' => fn (): array => SocialEditor::orderList(Testimonial::class, fn (Testimonial $item): array => [
                'id' => $item->id, 'label' => $item->person_name, 'hint' => $item->designation, 'published' => $item->is_published,
            ]),
            'breadcrumbs' => [['title' => 'Content', 'href' => null], ['title' => 'Testimonials', 'href' => null]],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Testimonial::class);

        return $this->editor($request, null);
    }

    public function store(SaveTestimonialRequest $request, SaveTestimonial $saveTestimonial): RedirectResponse
    {
        $result = $saveTestimonial->handle($request->user(), $request->validated());

        Toast::success('Testimonial created')->description($result['testimonial']->person_name)->flash();

        return to_route('admin.testimonials.edit', $result['testimonial']);
    }

    public function edit(Request $request, Testimonial $testimonial): Response
    {
        Gate::authorize('update', $testimonial);

        return $this->editor($request, $testimonial);
    }

    public function update(SaveTestimonialRequest $request, Testimonial $testimonial, SaveTestimonial $saveTestimonial): RedirectResponse
    {
        $result = $saveTestimonial->handle($request->user(), $request->validated(), $testimonial);

        $result['changed']
            ? Toast::success('Testimonial saved')->description($testimonial->person_name)->flash()
            : Toast::info('No changes to save')->flash();

        return back();
    }

    public function published(Request $request, Testimonial $testimonial, SetSocialPublished $setPublished): RedirectResponse
    {
        return $this->respondPublished($request, $testimonial, $setPublished);
    }

    public function order(ReorderRequest $request, ReorderSocialItems $reorder): RedirectResponse
    {
        return $this->respondOrder($request, $reorder, 'admin.testimonials.order');
    }

    public function destroy(Request $request, Testimonial $testimonial, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        return $this->respondDestroy($request, $testimonial, $moveToRecycleBin);
    }

    public function bulk(BulkSocialRequest $request, TestimonialsTable $table, MoveManyToRecycleBin $moveMany, SetSocialPublished $setPublished): RedirectResponse
    {
        return $this->respondBulk($request, $table, $moveMany, $setPublished);
    }

    private function editor(Request $request, ?Testimonial $testimonial): Response
    {
        $form = SocialEditor::testimonial($testimonial);

        return Inertia::render('admin/testimonials/Edit', [
            'testimonial' => $testimonial === null ? null : ['id' => $testimonial->id, 'person_name' => $testimonial->person_name, 'updated_at' => $testimonial->updated_at?->toIso8601String()],
            'form' => $form,
            'assets' => SocialEditor::assets($request, [$form['photo_asset_id']]),
            'options' => SocialEditor::testimonialOptions(),
            'lastSaved' => $testimonial?->updated_at?->toIso8601String(),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Testimonials', 'href' => route('admin.testimonials.index')],
                ['title' => $testimonial->person_name ?? 'New testimonial', 'href' => null],
            ],
        ]);
    }
}

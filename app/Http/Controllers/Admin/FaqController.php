<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\Social\ReorderSocialItems;
use App\Actions\Social\SaveFaq;
use App\Actions\Social\SetSocialPublished;
use App\Http\Controllers\Admin\Concerns\ManagesSocialItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkSocialRequest;
use App\Http\Requests\Admin\ReorderRequest;
use App\Http\Requests\Admin\SaveFaqRequest;
use App\Models\Faq;
use App\Queries\Admin\FaqsTable;
use App\Queries\Admin\RecycleBinTable;
use App\Queries\Admin\SocialEditor;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** ADM-SCR-09 FAQs: a table with drag order and a Published switch; a full-page editor with a rich-text answer. */
class FaqController extends Controller
{
    use ManagesSocialItems;

    protected function itemClass(): string
    {
        return Faq::class;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function nouns(): array
    {
        return ['FAQ', 'FAQs'];
    }

    protected function logName(): string
    {
        return 'faqs';
    }

    public function index(Request $request, FaqsTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Faq::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/faqs/Index', [
            ...$table->props($request, $actor),
            'canManage' => $actor->can('create', Faq::class),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, Faq::binType()),
            // The reorder dialog orders one topic at a time, like the FAQ page.
            'order' => fn (): array => SocialEditor::orderList(Faq::class, fn (Faq $item): array => [
                'id' => $item->id, 'label' => $item->question, 'group' => $item->category->value, 'published' => $item->is_published,
            ]),
            'categories' => SocialEditor::faqOptions()['categories'],
            'breadcrumbs' => [['title' => 'Content', 'href' => null], ['title' => 'FAQs', 'href' => null]],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Faq::class);

        return $this->editor(null);
    }

    public function store(SaveFaqRequest $request, SaveFaq $saveFaq): RedirectResponse
    {
        $result = $saveFaq->handle($request->user(), $request->validated());

        Toast::success('FAQ created')->description($result['faq']->question)->flash();

        return to_route('admin.faqs.edit', $result['faq']);
    }

    public function edit(Faq $faq): Response
    {
        Gate::authorize('update', $faq);

        return $this->editor($faq);
    }

    public function update(SaveFaqRequest $request, Faq $faq, SaveFaq $saveFaq): RedirectResponse
    {
        $result = $saveFaq->handle($request->user(), $request->validated(), $faq);

        $result['changed']
            ? Toast::success('FAQ saved')->description($faq->question)->flash()
            : Toast::info('No changes to save')->flash();

        return back();
    }

    public function published(Request $request, Faq $faq, SetSocialPublished $setPublished): RedirectResponse
    {
        return $this->respondPublished($request, $faq, $setPublished);
    }

    public function order(ReorderRequest $request, ReorderSocialItems $reorder): RedirectResponse
    {
        return $this->respondOrder($request, $reorder, 'admin.faqs.order');
    }

    public function destroy(Request $request, Faq $faq, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        return $this->respondDestroy($request, $faq, $moveToRecycleBin);
    }

    public function bulk(BulkSocialRequest $request, FaqsTable $table, MoveManyToRecycleBin $moveMany, SetSocialPublished $setPublished): RedirectResponse
    {
        return $this->respondBulk($request, $table, $moveMany, $setPublished);
    }

    private function editor(?Faq $faq): Response
    {
        return Inertia::render('admin/faqs/Edit', [
            'faq' => $faq === null ? null : ['id' => $faq->id, 'question' => $faq->question],
            'form' => SocialEditor::faq($faq),
            'options' => SocialEditor::faqOptions(),
            'lastSaved' => $faq?->updated_at?->toIso8601String(),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'FAQs', 'href' => route('admin.faqs.index')],
                ['title' => $faq->question ?? 'New question', 'href' => null],
            ],
        ]);
    }
}

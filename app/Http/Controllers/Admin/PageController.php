<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pages\DeletePage;
use App\Actions\Pages\DuplicatePage;
use App\Actions\Pages\SavePage;
use App\Actions\Publishing\SaveEntityChanges;
use App\Http\Controllers\Admin\Concerns\SavesPublishable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewPageRequest;
use App\Http\Requests\Admin\SavePageRequest;
use App\Models\Page;
use App\Queries\Admin\PageEditor;
use App\Queries\Admin\PagesTable;
use App\Queries\Admin\PublishingState;
use App\Queries\Admin\RecycleBinTable;
use App\Queries\Site\PageContent;
use App\Support\Blocks\BlockSanitizer;
use App\Support\Blocks\BlockType;
use App\Support\Blocks\BlockValidator;
use App\Support\Pages\PageChecklist;
use App\Support\Publishing\PageHydrator;
use App\Support\Toasts\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-06 pages: the list, the page builder and the verbs on a page. Controllers only authorise,
 * validate, call an Action and respond (DEC-32); each mutation flashes one toast (TOAST-01).
 */
class PageController extends Controller
{
    use SavesPublishable;

    public function index(Request $request, PagesTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Page::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/pages/Index', [
            ...$table->props($request, $actor),
            'canCreate' => $actor->can('create', Page::class),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, Page::binType()),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Pages', 'href' => null],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Page::class);

        return $this->editor($request, null);
    }

    public function store(SavePageRequest $request, SavePage $savePage): RedirectResponse
    {
        $page = $savePage->handle($request->user(), $request->validated());

        Toast::success('Page created')->description($page->title.' is a draft until you publish it.')->flash();

        return to_route('admin.pages.edit', $page);
    }

    public function edit(Request $request, Page $page): Response
    {
        Gate::authorize('view', $page);

        return $this->editor($request, $page);
    }

    /** CMS-PUB-002: a published page saves to its pending revision; CMS-PUB-006: a stale save opens the conflict dialog. */
    public function update(SavePageRequest $request, Page $page, SaveEntityChanges $saveEntityChanges): RedirectResponse
    {
        return $this->saveChanges($request, $saveEntityChanges, $page, $request->validated(), 'page');
    }

    /** UX-10: the editor saves by itself every 60 seconds while it has unsaved changes. */
    public function autosave(SavePageRequest $request, Page $page, SaveEntityChanges $saveEntityChanges): JsonResponse
    {
        return $this->autosaveChanges($request, $saveEntityChanges, $page, $request->validated());
    }

    /**
     * CMS-PB-002: the blocks of an unsaved editor state as the public site would draw them, the error
     * list of each block (CMS-PB-003) and the publish checklist of that state. Nothing is stored.
     */
    public function preview(PreviewPageRequest $request, PageHydrator $hydrator, PageChecklist $checklist): JsonResponse
    {
        $live = $request->page() ?? new Page(['title' => $request->string('title')->toString(), 'slug' => 'preview', 'template' => Page::BUILDER]);

        $payload = $request->validated();
        $payload['blocks'] = array_map(function (array $block): array {
            $type = BlockType::from($block['type']);
            $block['data'] = BlockSanitizer::clean($type, (array) $block['data']);

            return $block;
        }, array_values($payload['blocks']));

        $copy = $hydrator->handle($live, $payload);
        $blocks = PageContent::blocksOf($copy);

        $errors = [];

        foreach ($payload['blocks'] as $block) {
            $found = BlockValidator::errors(BlockType::from($block['type']), $block['data']);

            if ($found !== []) {
                $errors[(string) $block['id']] = $found;
            }
        }

        return response()->json([
            'blocks' => $blocks,
            'starts_with_hero' => PageContent::startsWithHero($blocks),
            'errors' => (object) $errors,
            'checklist' => $checklist->items($copy),
        ]);
    }

    /** CRUD-05: a Draft "Copy of …" opens in its own editor. */
    public function duplicate(Request $request, Page $page, DuplicatePage $duplicatePage): RedirectResponse
    {
        Gate::authorize('view', $page);
        Gate::authorize('create', Page::class);
        abort_if($page->is_system || $page->isLegal(), 403);

        $copy = $duplicatePage->handle($request->user(), $page);

        Toast::success('Copy created')->description($copy->title)->flash();

        return to_route('admin.pages.edit', $copy);
    }

    /** DEL-02: Delete = Recycle bin with Undo; a system page is refused (DEL-03). */
    public function destroy(Request $request, Page $page, DeletePage $deletePage): RedirectResponse
    {
        Gate::authorize('delete', $page);

        try {
            $entry = $deletePage->handle($request->user(), $page);
        } catch (ValidationException $exception) {
            Toast::error('Page not deleted')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success('Page moved to Recycle bin')
            ->description($page->title)
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        // From the editor the page itself is gone: go back to the list.
        return str_contains((string) url()->previous(), '/edit') ? to_route('admin.pages.index') : back();
    }

    private function editor(Request $request, ?Page $page): Response
    {
        $actor = $request->user();
        // CMS-PUB-002: the editor starts from the pending revision when there is one.
        $form = PageEditor::decorate($page === null ? PageEditor::blank() : PublishingState::form($page));

        return Inertia::render('admin/pages/Edit', [
            'page' => $page === null ? null : [
                'id' => $page->id,
                'title' => $page->title,
                'path' => $page->path(),
                'template' => $page->template,
                'is_system' => $page->is_system,
                ...PublishingState::workflow($page, $actor),
            ],
            'form' => $form,
            'assets' => PageEditor::assets($request, $form),
            'options' => PageEditor::options(),
            'checklist' => $page === null ? [] : PublishingState::checklist($page),
            'lastSaved' => $page?->updated_at?->toIso8601String(),
            'can' => [
                'publish' => $page === null ? false : $actor->can('publish', $page),
                'delete' => $page !== null && $actor->can('delete', $page),
                'duplicate' => $page !== null && ! $page->is_system && ! $page->isLegal() && $actor->can('create', Page::class),
                'update' => $page === null ? $actor->can('create', Page::class) : $actor->can('update', $page),
            ],
            'previewFrame' => route('preview.builder'),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Pages', 'href' => route('admin.pages.index')],
                ['title' => $page->title ?? 'New page', 'href' => null],
            ],
        ]);
    }
}

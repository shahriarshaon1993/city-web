<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Posts\DeletePost;
use App\Actions\Posts\DuplicatePost;
use App\Actions\Posts\SavePost;
use App\Actions\Publishing\SaveEntityChanges;
use App\Http\Controllers\Admin\Concerns\SavesPublishable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\Queries\Admin\PostEditor;
use App\Queries\Admin\PostsTable;
use App\Queries\Admin\PublishingState;
use App\Queries\Admin\RecycleBinTable;
use App\Support\Toasts\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-08 posts: the list, the article editor and the verbs on a post. Controllers only authorise,
 * validate, call an Action and respond (DEC-32); each mutation flashes one toast (TOAST-01).
 */
class PostController extends Controller
{
    use SavesPublishable;

    public function index(Request $request, PostsTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Post::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/news/Index', [
            ...$table->props($request, $actor),
            'canCreate' => $actor->can('create', Post::class),
            'canShareViews' => $actor->can('views.share'),
            'hasCategories' => PostCategory::query()->exists(),
            'binCount' => RecycleBinTable::countFor($actor, Post::binType()),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'News', 'href' => null],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Post::class);

        return $this->editor($request, null);
    }

    public function store(SavePostRequest $request, SavePost $savePost): RedirectResponse
    {
        $post = $savePost->handle($request->user(), $request->validated());

        Toast::success('Post created')->description($post->title.' is a draft until you publish it.')->flash();

        return to_route('admin.news.edit', $post);
    }

    public function edit(Request $request, Post $post): Response
    {
        Gate::authorize('view', $post);

        return $this->editor($request, $post);
    }

    /** CMS-PUB-002: a published post saves to its pending revision; CMS-PUB-006: a stale save opens the conflict dialog. */
    public function update(SavePostRequest $request, Post $post, SaveEntityChanges $saveEntityChanges): RedirectResponse
    {
        return $this->saveChanges($request, $saveEntityChanges, $post, $request->validated(), 'post');
    }

    /** UX-10: the editor saves by itself every 60 seconds while it has unsaved changes. */
    public function autosave(SavePostRequest $request, Post $post, SaveEntityChanges $saveEntityChanges): JsonResponse
    {
        return $this->autosaveChanges($request, $saveEntityChanges, $post, $request->validated());
    }

    /** CRUD-05: a Draft "Copy of …" opens in its own editor. */
    public function duplicate(Request $request, Post $post, DuplicatePost $duplicatePost): RedirectResponse
    {
        Gate::authorize('view', $post);
        Gate::authorize('create', Post::class);

        $copy = $duplicatePost->handle($request->user(), $post);

        Toast::success('Copy created')->description($copy->title)->flash();

        return to_route('admin.news.edit', $copy);
    }

    /** DEL-02: Delete = Recycle bin with Undo. */
    public function destroy(Request $request, Post $post, DeletePost $deletePost): RedirectResponse
    {
        Gate::authorize('delete', $post);

        try {
            $entry = $deletePost->handle($request->user(), $post);
        } catch (ValidationException $exception) {
            Toast::error('Post not deleted')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success('Post moved to Recycle bin')
            ->description($post->title)
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        // From the editor the post itself is gone: go back to the list.
        return str_contains((string) url()->previous(), '/edit') ? to_route('admin.news.index') : back();
    }

    private function editor(Request $request, ?Post $post): Response
    {
        $actor = $request->user();
        // CMS-PUB-002: the editor starts from the pending revision when there is one.
        $form = $post === null ? PostEditor::blank($actor) : PublishingState::form($post);

        return Inertia::render('admin/news/Edit', [
            'post' => $post === null ? null : [
                'id' => $post->id,
                'title' => $post->title,
                'path' => $post->path(),
                'read_minutes' => $post->read_minutes,
                ...PublishingState::workflow($post, $actor),
            ],
            'form' => $form,
            'assets' => PostEditor::assets($request, $form),
            'options' => PostEditor::options(),
            'checklist' => $post === null ? [] : PublishingState::checklist($post),
            'lastSaved' => $post?->updated_at?->toIso8601String(),
            'can' => [
                'publish' => $post !== null && $actor->can('publish', $post),
                'delete' => $post !== null && $actor->can('delete', $post),
                'duplicate' => $post !== null && $actor->can('create', Post::class),
                'update' => $post === null ? $actor->can('create', Post::class) : $actor->can('update', $post),
            ],
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'News', 'href' => route('admin.news.index')],
                ['title' => $post->title ?? 'New post', 'href' => null],
            ],
        ]);
    }
}

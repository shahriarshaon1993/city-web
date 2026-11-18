<?php

namespace App\Http\Controllers\Admin;

use App\Actions\News\DeleteTag;
use App\Actions\News\MergeTags;
use App\Actions\News\RenameTag;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MergeTagsRequest;
use App\Http\Requests\Admin\RenameTagRequest;
use App\Models\Tag;
use App\Queries\Admin\TagsTable;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** ADM-SCR-08 tags: rename, merge (FR-NEWS-003) and delete for good, which detaches the tag from its posts. */
class TagController extends Controller
{
    public function index(Request $request, TagsTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Tag::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/news/Tags', [
            ...$table->props($request, $actor),
            'canManage' => $actor->can('news.manage'),
            'canShareViews' => $actor->can('views.share'),
            // The merge dialog's picker.
            'all' => fn (): array => Tag::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Tag $tag): array => ['id' => $tag->id, 'name' => $tag->name])->all(),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'News', 'href' => route('admin.news.index')],
                ['title' => 'Tags', 'href' => null],
            ],
        ]);
    }

    public function update(RenameTagRequest $request, Tag $tag, RenameTag $renameTag): RedirectResponse
    {
        $changed = $renameTag->handle($request->user(), $tag, $request->validated('name'));

        if (! $changed) {
            Toast::info('No changes to save')->flash();

            return back();
        }

        Toast::success('Tag renamed')->description($tag->name)->flash();

        return back();
    }

    /** The route tag is the one that stays; `ids` are folded into it. */
    public function merge(MergeTagsRequest $request, Tag $tag, MergeTags $mergeTags): RedirectResponse
    {
        $count = count($request->validated('ids'));
        $mergeTags->handle($request->user(), $tag, array_values(array_map(intval(...), $request->validated('ids'))));

        Toast::success($count === 1 ? 'Tag merged' : "{$count} tags merged")->description('Posts now use “'.$tag->name.'”.')->flash();

        return back();
    }

    public function destroy(Request $request, Tag $tag, DeleteTag $deleteTag): RedirectResponse
    {
        Gate::authorize('delete', $tag);

        $name = $tag->name;
        $posts = $deleteTag->handle($request->user(), $tag);

        Toast::success('Tag deleted')->description($posts === 0 ? $name : "{$name} was removed from {$posts} ".($posts === 1 ? 'post' : 'posts').'.')->flash();

        return back();
    }
}

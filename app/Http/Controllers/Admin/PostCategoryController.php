<?php

namespace App\Http\Controllers\Admin;

use App\Actions\News\DeletePostCategory;
use App\Actions\News\ReorderPostCategories;
use App\Actions\News\SavePostCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderRequest;
use App\Http\Requests\Admin\SavePostCategoryRequest;
use App\Models\PostCategory;
use App\Queries\Admin\PostCategoriesTable;
use App\Queries\Admin\RecycleBinTable;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** ADM-SCR-08 categories: a table with a dialog editor, drag order and the "Move posts to…" delete guard (DEL-03). */
class PostCategoryController extends Controller
{
    public function index(Request $request, PostCategoriesTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', PostCategory::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/news/Categories', [
            ...$table->props($request, $actor),
            'canManage' => $actor->can('create', PostCategory::class),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, PostCategory::binType()),
            // The delete dialog's "Move posts to…" and the reorder dialog list every category.
            'all' => fn (): array => PostCategory::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name'])
                ->map(fn (PostCategory $category): array => ['id' => $category->id, 'name' => $category->name])->all(),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'News', 'href' => route('admin.news.index')],
                ['title' => 'Categories', 'href' => null],
            ],
        ]);
    }

    public function store(SavePostCategoryRequest $request, SavePostCategory $saveCategory): RedirectResponse
    {
        $result = $saveCategory->handle($request->user(), $request->validated());

        Toast::success('Category created')->description($result['category']->name)->flash();

        return back();
    }

    public function update(SavePostCategoryRequest $request, PostCategory $category, SavePostCategory $saveCategory): RedirectResponse
    {
        $result = $saveCategory->handle($request->user(), $request->validated(), $category);

        if (! $result['changed']) {
            Toast::info('No changes to save')->flash();

            return back();
        }

        Toast::success('Category saved')->description($category->name)->flash();

        return back();
    }

    /** DEL-02 / DEL-03: Recycle bin with Undo; `move_to` is where the posts go when the category has any. */
    public function destroy(Request $request, PostCategory $category, DeletePostCategory $deleteCategory): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $moveTo = $request->filled('move_to') ? PostCategory::query()->find($request->integer('move_to')) : null;

        try {
            abort_if($request->filled('move_to') && $moveTo === null, 422, 'Choose a category that exists.');

            $entry = $deleteCategory->handle($request->user(), $category, $moveTo);
        } catch (ValidationException $exception) {
            Toast::error('Category not deleted')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success('Category moved to Recycle bin')
            ->description($category->name)
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return back();
    }

    public function order(ReorderRequest $request, ReorderPostCategories $reorder): RedirectResponse
    {
        abort_unless($request->user()->can('news.manage'), 403);

        $previous = $reorder->handle($request->user(), $request->ids());

        Toast::success('Order saved')
            ->action('Undo', route('admin.news.categories.order'), 'patch', ['ids' => $previous])
            ->flash();

        return back();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\Social\ReorderSocialItems;
use App\Actions\Social\SavePartner;
use App\Actions\Social\SetSocialPublished;
use App\Http\Controllers\Admin\Concerns\ManagesSocialItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkSocialRequest;
use App\Http\Requests\Admin\ReorderRequest;
use App\Http\Requests\Admin\SavePartnerRequest;
use App\Models\Partner;
use App\Queries\Admin\PartnersTable;
use App\Queries\Admin\RecycleBinTable;
use App\Queries\Admin\SocialEditor;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** ADM-SCR-09 partners: a table with drag order and a Published switch; the editor is a small dialog (4 fields). */
class PartnerController extends Controller
{
    use ManagesSocialItems;

    protected function itemClass(): string
    {
        return Partner::class;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function nouns(): array
    {
        return ['partner', 'partners'];
    }

    protected function logName(): string
    {
        return 'partners';
    }

    public function index(Request $request, PartnersTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Partner::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        $props = $table->props($request, $actor);

        return Inertia::render('admin/partners/Index', [
            ...$props,
            'canManage' => $actor->can('create', Partner::class),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, Partner::binType()),
            'categories' => SocialEditor::partnerCategories(),
            'order' => fn (): array => SocialEditor::orderList(Partner::class, fn (Partner $item): array => [
                'id' => $item->id, 'label' => $item->name, 'hint' => $item->category->label(), 'published' => $item->is_published,
            ]),
            'breadcrumbs' => [['title' => 'Content', 'href' => null], ['title' => 'Partners', 'href' => null]],
        ]);
    }

    public function store(SavePartnerRequest $request, SavePartner $savePartner): RedirectResponse
    {
        $result = $savePartner->handle($request->user(), $request->validated());

        Toast::success('Partner created')->description($result['partner']->name)->flash();

        return back();
    }

    public function update(SavePartnerRequest $request, Partner $partner, SavePartner $savePartner): RedirectResponse
    {
        $result = $savePartner->handle($request->user(), $request->validated(), $partner);

        $result['changed']
            ? Toast::success('Partner saved')->description($partner->name)->flash()
            : Toast::info('No changes to save')->flash();

        return back();
    }

    public function published(Request $request, Partner $partner, SetSocialPublished $setPublished): RedirectResponse
    {
        return $this->respondPublished($request, $partner, $setPublished);
    }

    public function order(ReorderRequest $request, ReorderSocialItems $reorder): RedirectResponse
    {
        return $this->respondOrder($request, $reorder, 'admin.partners.order');
    }

    public function destroy(Request $request, Partner $partner, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        return $this->respondDestroy($request, $partner, $moveToRecycleBin);
    }

    public function bulk(BulkSocialRequest $request, PartnersTable $table, MoveManyToRecycleBin $moveMany, SetSocialPublished $setPublished): RedirectResponse
    {
        return $this->respondBulk($request, $table, $moveMany, $setPublished);
    }
}

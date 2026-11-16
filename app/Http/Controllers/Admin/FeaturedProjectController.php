<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Projects\ReorderFeaturedProjects;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderRequest;
use App\Models\Project;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-02 featured order: the featured projects as a drag list (the home page follows this order,
 * FR-PROJ-010). Reordering saves at once with "Order saved" + Undo (CRUD-06).
 */
class FeaturedProjectController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Project::class);

        $row = fn (Project $project): array => [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'thumb' => $project->cover?->thumbUrl(),
            'area' => $project->location?->name,
            'segment' => $project->segment->label(),
            'status' => ['value' => $project->publish_status->value, 'label' => $project->publish_status->label()],
            'edit_url' => route('admin.projects.edit', $project),
        ];

        return Inertia::render('admin/projects/Featured', [
            'featured' => Project::query()->where('is_featured', true)->with(['cover.media', 'location'])->orderBy('featured_order')->orderBy('id')->get()->map($row)->all(),
            'candidates' => Project::query()->where('is_featured', false)->with(['cover.media', 'location'])->orderBy('name')->get()->map($row)->all(),
            'canManage' => $request->user()->can('projects.write'),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Projects', 'href' => route('admin.projects.index')],
                ['title' => 'Featured order', 'href' => null],
            ],
        ]);
    }

    public function update(ReorderRequest $request, ReorderFeaturedProjects $reorder): RedirectResponse
    {
        abort_unless($request->user()->can('projects.write'), 403);

        $previous = $reorder->handle($request->user(), $request->ids());

        Toast::success('Order saved')
            ->description('The home page shows featured projects in this order.')
            ->action('Undo', route('admin.projects.featured.order'), 'patch', ['ids' => $previous])
            ->flash();

        return back();
    }
}

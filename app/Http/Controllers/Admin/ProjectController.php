<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\DuplicateProject;
use App\Actions\Projects\PublishProject;
use App\Actions\Projects\SaveProject;
use App\Actions\Projects\SetProjectFeatured;
use App\Actions\Projects\UnpublishProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkProjectsRequest;
use App\Http\Requests\Admin\DeleteProjectRequest;
use App\Http\Requests\Admin\SaveProjectRequest;
use App\Models\Project;
use App\Queries\Admin\ProjectEditor;
use App\Queries\Admin\ProjectsTable;
use App\Queries\Admin\RecycleBinTable;
use App\Support\Projects\PublishChecklist;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-01 projects: the list, the tabbed editor and the verbs on a project. Controllers only
 * authorise, validate, call an Action and respond (DEC-32); each mutation flashes one toast (TOAST-01).
 */
class ProjectController extends Controller
{
    public function index(Request $request, ProjectsTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Project::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/projects/Index', [
            ...$table->props($request, $actor),
            'canCreate' => $actor->can('create', Project::class),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, Project::binType()),
            'redirectOptions' => fn (): array => $this->redirectOptions(),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Projects', 'href' => null],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Project::class);

        return $this->editor($request, null);
    }

    public function store(SaveProjectRequest $request, SaveProject $saveProject): RedirectResponse
    {
        $project = $saveProject->handle($request->user(), $request->validated());

        Toast::success('Project created')->description($project->name.' is a draft until you publish it.')->flash();

        return to_route('admin.projects.edit', $project);
    }

    public function edit(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        return $this->editor($request, $project);
    }

    public function update(SaveProjectRequest $request, Project $project, SaveProject $saveProject): RedirectResponse
    {
        $saveProject->handle($request->user(), $request->validated(), $project);

        Toast::success('Project saved')->flash();

        return back();
    }

    /** AC-PROJ-03: refused until the checklist passes. */
    public function publish(Request $request, Project $project, PublishProject $publishProject): RedirectResponse
    {
        Gate::authorize('publish', $project);

        try {
            $publishProject->handle($request->user(), $project);
        } catch (ValidationException $exception) {
            Toast::error('Project not published')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success('Project published')
            ->action('View on site', url("/projects/{$project->slug}"))
            ->flash();

        return back();
    }

    public function unpublish(Request $request, Project $project, UnpublishProject $unpublishProject): RedirectResponse
    {
        Gate::authorize('publish', $project);

        $unpublishProject->handle($request->user(), $project);

        Toast::success('Project unpublished')
            ->description($project->name.' is a draft again and no longer on the website.')
            ->undo(route('admin.projects.publish', $project))
            ->flash();

        return back();
    }

    /** The star in the list (CRUD-01): featured projects appear on the home page in manual order. */
    public function featured(Request $request, Project $project, SetProjectFeatured $setProjectFeatured): RedirectResponse
    {
        Gate::authorize('update', $project);

        $featured = $request->boolean('featured');
        $setProjectFeatured->handle($request->user(), $project, $featured);

        Toast::success($featured ? 'Project featured' : 'Project no longer featured')->description($project->name)->flash();

        return back();
    }

    /** CRUD-05: a Draft "Copy of …" opens in its own editor. */
    public function duplicate(Request $request, Project $project, DuplicateProject $duplicateProject): RedirectResponse
    {
        Gate::authorize('view', $project);
        Gate::authorize('create', Project::class);

        $copy = $duplicateProject->handle($request->user(), $project);

        Toast::success('Copy created')->description($copy->name)->flash();

        return to_route('admin.projects.edit', $copy);
    }

    /** DEL-02: Delete = Recycle bin with Undo; a published project may redirect its address. */
    public function destroy(DeleteProjectRequest $request, Project $project, DeleteProject $deleteProject): RedirectResponse
    {
        $wasPublished = $project->publish_status->value === 'published';
        $redirectTo = $request->validated('redirect_to');

        try {
            $entry = $deleteProject->handle($request->user(), $project, $redirectTo);
        } catch (ValidationException $exception) {
            Toast::error('Project not deleted')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        $toast = Toast::success('Project moved to Recycle bin')
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true]);

        if ($wasPublished) {
            $toast->description($redirectTo !== null && $redirectTo !== ''
                ? "{$project->name} · visitors go to {$redirectTo}."
                : "{$project->name} · visitors get “Gone” for 90 days.");
        } else {
            $toast->description($project->name);
        }

        $toast->flash();

        // From the editor the page itself is gone: go back to the list.
        return str_contains((string) url()->previous(), '/edit') ? to_route('admin.projects.index') : back();
    }

    /** ADM-TBL-009: publish, unpublish or delete the ticked rows; partial results are reported (TOAST-06). */
    public function bulk(
        BulkProjectsRequest $request,
        ProjectsTable $table,
        PublishProject $publishProject,
        UnpublishProject $unpublishProject,
        DeleteProject $deleteProject,
    ): RedirectResponse {
        $actor = $request->user();
        $action = $request->string('action')->toString();
        $projects = $table->selection($request, $actor)->limit(BulkProjectsRequest::MAX_IDS * 20)->get();
        $permission = $action === 'delete' ? 'delete' : 'publish';
        $done = 0;
        $skipped = [];
        $entries = [];

        foreach ($projects as $project) {
            if (! $actor->can($permission, $project)) {
                $skipped[] = "{$project->name}: you cannot do this.";

                continue;
            }

            try {
                match ($action) {
                    'publish' => $publishProject->handle($actor, $project),
                    'unpublish' => $unpublishProject->handle($actor, $project),
                    'delete' => $entries[] = $deleteProject->handle($actor, $project)->id,
                    default => null,
                };
                $done++;
            } catch (ValidationException $exception) {
                $skipped[] = "{$project->name}: ".$exception->validator->errors()->first();
            }
        }

        $noun = $done === 1 ? 'project' : 'projects';
        $title = match (true) {
            $done === 0 => 'No projects were changed',
            $action === 'publish' => "{$done} {$noun} published",
            $action === 'unpublish' => "{$done} {$noun} unpublished",
            default => "{$done} {$noun} moved to Recycle bin",
        };

        $toast = $skipped === []
            ? Toast::success($title)
            : Toast::warning($title.' · '.count($skipped).' skipped')->description(implode(' ', array_slice($skipped, 0, 3)));

        if ($action === 'delete' && $entries !== []) {
            $toast->undo(route('admin.recycle-bin.restore-many'), ['ids' => $entries, 'undo' => true]);
        }

        $toast->flash();

        return back();
    }

    private function editor(Request $request, ?Project $project): Response
    {
        $actor = $request->user();
        $form = ProjectEditor::form($project);
        $checklist = $project === null ? [] : app(PublishChecklist::class)->items($project);

        return Inertia::render('admin/projects/Edit', [
            'project' => $project === null ? null : [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->publish_status->value,
                'status_label' => $project->publish_status->label(),
                'published_at' => $project->published_at?->toIso8601String(),
                'public_url' => $project->isPublished() ? url("/projects/{$project->slug}") : null,
                'has_redirect' => $project->archived_redirect_to !== null,
            ],
            'form' => $form,
            'assets' => ProjectEditor::assets($request, $form),
            'options' => ProjectEditor::options($project),
            'checklist' => $checklist,
            'lastSaved' => $project?->updated_at?->toIso8601String(),
            'can' => [
                'publish' => $project === null ? $actor->can('projects.publish') : $actor->can('publish', $project),
                'delete' => $project !== null && $actor->can('delete', $project),
                'create' => $actor->can('create', Project::class),
                'update' => $project === null ? $actor->can('create', Project::class) : $actor->can('update', $project),
            ],
            'redirectOptions' => $project === null ? [] : $this->redirectOptions($project),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Projects', 'href' => route('admin.projects.index')],
                ['title' => $project->name ?? 'New project', 'href' => null],
            ],
        ]);
    }

    /**
     * DEL-02: where the address of a deleted, published project may point.
     *
     * @return list<array{value: string, label: string}>
     */
    private function redirectOptions(?Project $except = null): array
    {
        return [
            ['value' => '/projects', 'label' => 'All projects'],
            ['value' => '/', 'label' => 'Home page'],
            ['value' => '/locations', 'label' => 'Locations'],
            ...Project::query()->published()->when($except, fn ($query) => $query->whereKeyNot($except->id))->orderBy('name')->get(['name', 'slug'])
                ->map(fn (Project $project): array => ['value' => "/projects/{$project->slug}", 'label' => $project->name])->all(),
        ];
    }
}

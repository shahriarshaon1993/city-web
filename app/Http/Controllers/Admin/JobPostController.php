<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Careers\DuplicateJobPost;
use App\Actions\Careers\SaveJobPost;
use App\Actions\Careers\SetJobPostStatus;
use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkJobPostsRequest;
use App\Http\Requests\Admin\SaveJobPostRequest;
use App\Http\Requests\Admin\SetJobPostStatusRequest;
use App\Models\JobPost;
use App\Models\Office;
use App\Queries\Admin\JobPostEditor;
use App\Queries\Admin\JobPostsTable;
use App\Queries\Admin\RecycleBinTable;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-10 Careers › Jobs: the list, the editor and the verbs on a job post (open, close, duplicate,
 * delete). Controllers only authorise, validate, call an Action and respond (DEC-32); each mutation
 * flashes one toast (TOAST-01).
 */
class JobPostController extends Controller
{
    public function index(Request $request, JobPostsTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', JobPost::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/careers/Index', [
            ...$table->props($request, $actor),
            'canCreate' => $actor->can('create', JobPost::class),
            'canShareViews' => $actor->can('views.share'),
            'canApplications' => $actor->can('applications.manage'),
            'binCount' => RecycleBinTable::countFor($actor, JobPost::binType()),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Careers', 'href' => null],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', JobPost::class);

        return $this->editor($request, null);
    }

    public function store(SaveJobPostRequest $request, SaveJobPost $saveJobPost): RedirectResponse
    {
        $job = $saveJobPost->handle($request->user(), $request->validated());

        Toast::success('Job post created')->description($this->statusLine($job))->flash();

        return to_route('admin.careers.edit', $job);
    }

    public function edit(Request $request, JobPost $job): Response
    {
        Gate::authorize('view', $job);

        return $this->editor($request, $job);
    }

    public function update(SaveJobPostRequest $request, JobPost $job, SaveJobPost $saveJobPost): RedirectResponse
    {
        $saveJobPost->handle($request->user(), $request->validated(), $job);

        Toast::success('Job post saved')->description($this->statusLine($job))->flash();

        return back();
    }

    /** CRUD-05: a Draft "Copy of …" opens in its own editor. */
    public function duplicate(Request $request, JobPost $job, DuplicateJobPost $duplicateJobPost): RedirectResponse
    {
        Gate::authorize('view', $job);
        Gate::authorize('create', JobPost::class);

        $copy = $duplicateJobPost->handle($request->user(), $job);

        Toast::success('Copy created')->description($copy->title)->flash();

        return to_route('admin.careers.edit', $copy);
    }

    /** Open, Close applications or Back to draft from the row menu (TOAST-02: Undo goes back to the old status). */
    public function status(SetJobPostStatusRequest $request, JobPost $job, SetJobPostStatus $setStatus): RedirectResponse
    {
        $to = $request->status();
        $from = $job->status;

        try {
            $changed = $setStatus->handle($request->user(), $job, $to);
        } catch (ValidationException $exception) {
            Toast::error('Job post not opened')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        if (! $changed) {
            Toast::info('No change')->description("{$job->title} is already {$to->label()}")->flash();

            return back();
        }

        $toast = Toast::success(match ($to) {
            JobStatus::Open => 'Job post opened',
            JobStatus::Closed => 'Applications closed',
            JobStatus::Draft => 'Job post moved back to draft',
        })->description($job->title);

        // Undo of "open" would need the checklist again; closing and drafting can always go back.
        if ($from !== JobStatus::Open || $to !== JobStatus::Draft) {
            $toast->action('Undo', route('admin.careers.status', $job), 'patch', ['status' => $from->value]);
        }

        $toast->flash();

        return back();
    }

    /** HELP-04: a signed, 60-minute link to the public page, so a draft can be read before it is opened. */
    public function preview(JobPost $job): RedirectResponse
    {
        Gate::authorize('view', $job);

        return redirect()->to(URL::temporarySignedRoute('site.careers.show', now()->addMinutes(60), ['slug' => $job->slug]));
    }

    /** DEL-02: Delete = Recycle bin with Undo. */
    public function destroy(Request $request, JobPost $job, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        Gate::authorize('delete', $job);

        try {
            $entry = $moveToRecycleBin->handle($request->user(), $job);
        } catch (ValidationException $exception) {
            Toast::error('Job post not deleted')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success('Job post moved to Recycle bin')
            ->description($job->title)
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        // From the editor the post itself is gone: go back to the list.
        return str_contains((string) url()->previous(), '/edit') ? to_route('admin.careers.index') : back();
    }

    public function bulk(BulkJobPostsRequest $request, JobPostsTable $table, MoveManyToRecycleBin $moveMany, SetJobPostStatus $setStatus): RedirectResponse
    {
        $actor = $request->user();
        $items = $table->selection($request, $actor)->limit(BulkJobPostsRequest::MAX_IDS * 20)->get();

        if ($request->string('action')->toString() === 'delete') {
            $result = $moveMany->handle($actor, $items);
            $count = count($result['entries']);
            $title = $count === 0 ? 'No job posts were deleted' : $count.' '.($count === 1 ? 'job post' : 'job posts').' moved to Recycle bin';

            $toast = $result['refused'] === []
                ? Toast::success($title)
                : Toast::warning($title.' · '.count($result['refused']).' skipped')->description(implode(' ', array_slice($result['refused'], 0, 3)));

            if ($count > 0) {
                $toast->undo(route('admin.recycle-bin.restore-many'), ['ids' => array_map(fn ($entry): int => $entry->id, $result['entries']), 'undo' => true]);
            }

            $toast->flash();

            return back();
        }

        $closed = 0;

        foreach ($items as $job) {
            $closed += $setStatus->handle($actor, $job, JobStatus::Closed) ? 1 : 0;
        }

        Toast::success($closed === 0 ? 'No job posts were changed' : 'Applications closed on '.$closed.' '.($closed === 1 ? 'job post' : 'job posts'))->flash();

        return back();
    }

    private function statusLine(JobPost $job): string
    {
        return match ($job->status) {
            JobStatus::Draft => "{$job->title} is a draft until you open it.",
            JobStatus::Open => "{$job->title} is open for applications.",
            JobStatus::Closed => "{$job->title} is closed.",
        };
    }

    private function editor(Request $request, ?JobPost $job): Response
    {
        $actor = $request->user();
        $form = $job === null ? JobPostEditor::blank() : JobPostEditor::form($job);

        return Inertia::render('admin/careers/Edit', [
            'job' => $job === null ? null : [
                'id' => $job->id,
                'title' => $job->title,
                'path' => $job->path(),
                'status' => $job->status->value,
                'deadline_passed' => $job->status === JobStatus::Open && $job->isClosed(),
                'applications_count' => $job->applications()->count(),
                'closed_at' => $job->closed_at?->toIso8601String(),
            ],
            'form' => $form,
            'assets' => JobPostEditor::assets($request, $form),
            'options' => JobPostEditor::options(),
            'hasOffices' => Office::query()->exists(),
            'lastSaved' => $job?->updated_at?->toIso8601String(),
            'can' => [
                'delete' => $job !== null && $actor->can('delete', $job),
                'duplicate' => $job !== null && $actor->can('create', JobPost::class),
                'update' => $job === null ? $actor->can('create', JobPost::class) : $actor->can('update', $job),
                'applications' => $actor->can('applications.manage'),
            ],
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Careers', 'href' => route('admin.careers.index')],
                ['title' => $job->title ?? 'New job post', 'href' => null],
            ],
        ]);
    }
}

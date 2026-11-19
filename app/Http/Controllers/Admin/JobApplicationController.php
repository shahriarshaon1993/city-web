<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Careers\ChangeApplicationStatus;
use App\Actions\Careers\SaveApplicationNotes;
use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkApplicationsRequest;
use App\Http\Requests\Admin\ChangeApplicationStatusRequest;
use App\Http\Requests\Admin\SaveApplicationNotesRequest;
use App\Http\Resources\Admin\JobApplicationDetailResource;
use App\Models\JobApplication;
use App\Queries\Admin\JobApplicationsTable;
use App\Queries\Admin\RecycleBinTable;
use App\Support\Toasts\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ADM-SCR-10 Applications: the table, the detail drawer and the authorised CV download (FR-CAR-005). Only
 * `applications.manage` reaches any of it: applications are personal data. Controllers only authorise,
 * validate, call an Action and respond (DEC-32); each mutation flashes one toast (TOAST-01).
 */
class JobApplicationController extends Controller
{
    public function index(Request $request, JobApplicationsTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', JobApplication::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        // `?application={id}` opens the drawer at once, so a pasted link or a notification works.
        $open = $request->integer('application') ?: null;
        $initial = $open === null ? null : JobApplication::query()->with('job')->find($open);

        return Inertia::render('admin/applications/Index', [
            ...$table->props($request, $actor),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, JobApplication::binType()),
            'statuses' => array_map(fn (ApplicationStatus $case): array => ['value' => $case->value, 'label' => $case->label()], ApplicationStatus::cases()),
            'openId' => $initial?->id,
            'initial' => $initial === null ? null : (new JobApplicationDetailResource($initial))->resolve($request),
            'breadcrumbs' => [
                ['title' => 'Sales', 'href' => null],
                ['title' => 'Applications', 'href' => null],
            ],
        ]);
    }

    /** The drawer's data (JSON). Opened in a browser, the address lands on the list with the drawer open. */
    public function show(Request $request, JobApplication $application): JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $application);

        if ($request->wantsJson()) {
            $application->loadMissing('job');

            return response()->json(['data' => (new JobApplicationDetailResource($application))->resolve($request)]);
        }

        return redirect()->route('admin.applications.index', ['application' => $application->id]);
    }

    /** FR-CAR-005 (drawer, row menu). TOAST-02: Undo carries the previous status. */
    public function status(ChangeApplicationStatusRequest $request, JobApplication $application, ChangeApplicationStatus $changeStatus): RedirectResponse
    {
        $to = $request->status();
        $from = $application->status;

        if (! $changeStatus->handle($request->user(), $application, $to)) {
            Toast::info('No change')->description("This application is already {$to->label()}")->flash();

            return back();
        }

        if ($request->boolean('undo')) {
            Toast::success('Undone')->description("Back to {$to->label()}")->flash();

            return back();
        }

        Toast::success("Application moved to {$to->label()}")
            ->description($application->name)
            ->action('Undo', route('admin.applications.status', $application), 'patch', ['status' => $from->value, 'undo' => true])
            ->flash();

        return back();
    }

    public function notes(SaveApplicationNotesRequest $request, JobApplication $application, SaveApplicationNotes $saveNotes): RedirectResponse
    {
        $saveNotes->handle($request->user(), $application, $request->input('notes'))
            ? Toast::success('Notes saved')->description($application->name)->flash()
            : Toast::info('No changes to save')->flash();

        return back();
    }

    /** FR-CAR-005: the CV leaves the private disk only here, for a user who may read applications. */
    public function cv(JobApplication $application): StreamedResponse
    {
        Gate::authorize('view', $application);

        $disk = Storage::disk(JobApplication::DISK);

        abort_unless(str_starts_with($application->cv_path, 'careers/') && $disk->exists($application->cv_path), 404);

        return $disk->download($application->cv_path, $application->cv_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /** DEL-02: Delete = Recycle bin for 365 days, with Undo. */
    public function destroy(Request $request, JobApplication $application, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        Gate::authorize('delete', $application);

        try {
            $entry = $moveToRecycleBin->handle($request->user(), $application);
        } catch (ValidationException $exception) {
            Toast::error('Application not deleted')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success('Application moved to Recycle bin')
            ->description($application->name)
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return back();
    }

    public function bulk(BulkApplicationsRequest $request, JobApplicationsTable $table, MoveManyToRecycleBin $moveMany, ChangeApplicationStatus $changeStatus): RedirectResponse
    {
        $actor = $request->user();
        $items = $table->selection($request, $actor)->limit(BulkApplicationsRequest::MAX_IDS * 20)->get();

        if ($request->string('action')->toString() === 'delete') {
            $result = $moveMany->handle($actor, $items);
            $count = count($result['entries']);

            $toast = Toast::success($count === 0 ? 'No applications were deleted' : $count.' '.($count === 1 ? 'application' : 'applications').' moved to Recycle bin');

            if ($count > 0) {
                $toast->undo(route('admin.recycle-bin.restore-many'), ['ids' => array_map(fn ($entry): int => $entry->id, $result['entries']), 'undo' => true]);
            }

            $toast->flash();

            return back();
        }

        $to = ApplicationStatus::from($request->string('status')->toString());
        $changed = 0;

        foreach ($items as $application) {
            $changed += $changeStatus->handle($actor, $application, $to) ? 1 : 0;
        }

        Toast::success($changed === 0 ? 'No applications were changed' : $changed.' '.($changed === 1 ? 'application' : 'applications').' moved to '.$to->label())->flash();

        return back();
    }
}

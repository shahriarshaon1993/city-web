<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Imports\SaveImportMapping;
use App\Actions\Imports\StartImport;
use App\Actions\Imports\UploadImportFile;
use App\Enums\ImportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveImportMappingRequest;
use App\Http\Requests\Admin\StartImportRequest;
use App\Http\Requests\Admin\UploadImportRequest;
use App\Models\ExportRun;
use App\Models\ImportRun;
use App\Queries\Admin\ImportPreview;
use App\Support\Import\ImportFile;
use App\Support\Import\ImportRunPresenter;
use App\Support\Import\ImportType;
use App\Support\Tables\ExportCell;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ADM-SCR import wizard (ADM-IO-001): upload → map columns → preview 20 rows → confirm → queued
 * import → report with an error CSV. Controllers only authorise, validate, call an Action and respond
 * (DEC-32); the wizard is one page per run (`runs/{run}`) whose content follows the run's status.
 */
class ImportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('admin/import/Index', [
            'types' => array_map(fn (ImportType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'help' => $type->definition()->help(),
                'fields' => array_map(fn ($field): array => $field->toArray(), $type->definition()->fields()),
                'template_url' => route('admin.import.template', $type, false),
            ], ImportType::usableBy($user)),
            'imports' => ImportRun::query()->where('user_id', $user->id)->latest('id')->limit(10)->get()
                ->map(fn (ImportRun $run): array => ImportRunPresenter::run($run))->all(),
            'exports' => ExportRun::query()->where('user_id', $user->id)->latest('id')->limit(10)->get()
                ->map(fn (ExportRun $run): array => ImportRunPresenter::export($run))->all(),
            'limits' => ['max_mb' => ImportFile::MAX_BYTES / 1024 / 1024, 'max_rows' => ImportFile::MAX_ROWS, 'preview_rows' => ImportFile::PREVIEW_ROWS],
            'breadcrumbs' => [
                ['title' => 'System', 'href' => null],
                ['title' => 'Import & export', 'href' => null],
            ],
        ]);
    }

    public function store(UploadImportRequest $request, UploadImportFile $upload): RedirectResponse
    {
        $run = $upload->handle($request->user(), $request->importType(), $request->file('file'));

        return redirect()->route('admin.import.runs.show', $run);
    }

    public function template(Request $request, ImportType $type): StreamedResponse
    {
        abort_unless($type->canBeUsedBy($request->user()), 403);

        $definition = $type->definition();

        return response()->streamDownload(function () use ($definition): void {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(fn ($field): string => $field->label, $definition->fields()), escape: '\\');
            fputcsv($out, array_map(fn (string $cell): mixed => ExportCell::safe($cell, true), array_values($definition->example())), escape: '\\');
            fclose($out);
        }, "{$type->value}-import-template.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(Request $request, ImportRun $run, ImportPreview $preview): Response
    {
        Gate::authorize('view', $run);

        $definition = $run->type->definition();
        $waiting = $run->status === ImportStatus::Uploaded;

        return Inertia::render('admin/import/Run', [
            'run' => ImportRunPresenter::run($run),
            'canEdit' => $request->user()->can('update', $run),
            'fields' => array_map(fn ($field): array => $field->toArray(), $definition->fields()),
            'options' => $definition->options(),
            'columns' => $waiting ? ImportFile::headers($run) : [],
            'mapping' => $waiting ? ($run->mapping ?? []) : [],
            'preview' => $waiting ? $preview->build($run) : null,
            'limits' => ['preview_rows' => ImportFile::PREVIEW_ROWS],
            'breadcrumbs' => [
                ['title' => 'System', 'href' => null],
                ['title' => 'Import & export', 'href' => route('admin.import.index', absolute: false)],
                ['title' => "{$run->type->label()} import", 'href' => null],
            ],
        ]);
    }

    public function mapping(SaveImportMappingRequest $request, ImportRun $run, SaveImportMapping $saveMapping): RedirectResponse
    {
        $saveMapping->handle($run, $request->mapping());

        return back();
    }

    public function start(StartImportRequest $request, ImportRun $run, StartImport $startImport): RedirectResponse
    {
        $startImport->handle($request->user(), $run, $request->confirmed());

        Toast::info('Import started')
            ->description('It runs in the background. You will get a notification when it is finished.')
            ->flash();

        return back();
    }

    public function errors(Request $request, ImportRun $run): StreamedResponse
    {
        Gate::authorize('view', $run);

        abort_if($run->error_path === null || ! Storage::disk(ImportRun::DISK)->exists($run->error_path), 404);

        return Storage::disk(ImportRun::DISK)->download($run->error_path, "{$run->type->value}-import-problems.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(Request $request, ImportRun $run): RedirectResponse
    {
        Gate::authorize('update', $run);

        abort_unless($run->status === ImportStatus::Uploaded, 422, 'This import has already started.');

        Storage::disk(ImportRun::DISK)->deleteDirectory($run->directory());
        $run->delete();

        Toast::success('Import cancelled')->description('The uploaded file was deleted.')->flash();

        return redirect()->route('admin.import.index');
    }
}

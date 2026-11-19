<?php

namespace App\Support\Import;

use App\Models\ExportRun;
use App\Models\ImportRun;
use Illuminate\Support\Facades\URL;

/** The shapes the import wizard and the Import & export page read (`types/import.ts`). */
final class ImportRunPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function run(ImportRun $run): array
    {
        return [
            'id' => $run->id,
            'type' => $run->type->value,
            'type_label' => $run->type->label(),
            'file' => $run->original_name,
            'status' => $run->status->value,
            'status_label' => $run->status->label(),
            'working' => $run->status->isWorking(),
            'total_rows' => $run->total_rows,
            'processed_rows' => min($run->processed_rows, $run->total_rows),
            'created' => $run->created_count,
            'updated' => $run->updated_count,
            'skipped' => $run->skipped_count,
            'failed' => $run->failed_count,
            'message' => $run->message,
            'has_errors' => $run->error_path !== null,
            'created_at' => $run->created_at?->toIso8601String(),
            'finished_at' => $run->finished_at?->toIso8601String(),
            'url' => route('admin.import.runs.show', $run, false),
            'list_url' => $run->type->listUrl(),
            'errors_url' => $run->error_path === null ? null : route('admin.import.runs.errors', $run, false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function export(ExportRun $run): array
    {
        $downloadable = $run->isDownloadable();

        return [
            'id' => $run->id,
            'filename' => $run->filename,
            'format' => strtoupper($run->format),
            'status' => match (true) {
                $downloadable => 'ready',
                $run->status === 'ready' || $run->status === 'expired' => 'expired',
                default => $run->status,
            },
            'rows' => $run->row_count,
            'message' => $run->message,
            'created_at' => $run->created_at?->toIso8601String(),
            'expires_at' => $run->expires_at?->toIso8601String(),
            'download_url' => $downloadable && $run->expires_at !== null
                ? URL::temporarySignedRoute('admin.exports.download', $run->expires_at, ['export' => $run->id], absolute: false)
                : null,
        ];
    }
}

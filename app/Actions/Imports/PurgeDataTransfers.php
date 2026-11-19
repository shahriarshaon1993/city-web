<?php

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Models\ExportRun;
use App\Models\ImportRun;
use Illuminate\Support\Facades\Storage;

/**
 * Nightly clean-up of the files the import wizard and the queued exports leave on the private disk
 * (they hold people's data, NFR-PRIV-01): an export file goes when its 24-hour link expires, an import
 * waiting for confirmation after a day, an error CSV after 7 days. The rows stay 90 days as history.
 */
final class PurgeDataTransfers
{
    /**
     * @return array{files: int, rows: int}
     */
    public function handle(): array
    {
        $disk = Storage::disk(ExportRun::DISK);
        $files = 0;
        $rows = 0;

        ExportRun::query()
            ->where('status', 'ready')
            ->where('expires_at', '<', now())
            ->each(function (ExportRun $run) use ($disk, &$files): void {
                $disk->deleteDirectory($run->directory());
                $run->forceFill(['status' => 'expired', 'path' => null])->save();
                $files++;
            });

        ExportRun::query()
            ->whereIn('status', ['failed', 'queued', 'running'])
            ->where('created_at', '<', now()->subDays(2))
            ->each(function (ExportRun $run) use ($disk, &$files): void {
                $disk->deleteDirectory($run->directory());
                $run->forceFill(['status' => 'failed', 'path' => null, 'message' => $run->message ?? 'The export never finished.'])->save();
                $files++;
            });

        ImportRun::query()
            ->where('status', ImportStatus::Uploaded->value)
            ->where('created_at', '<', now()->subDay())
            ->each(function (ImportRun $run) use ($disk, &$files, &$rows): void {
                $disk->deleteDirectory($run->directory());
                $run->delete();
                $files++;
                $rows++;
            });

        ImportRun::query()
            ->whereIn('status', [ImportStatus::Finished->value, ImportStatus::Failed->value])
            ->whereNotNull('error_path')
            ->where('finished_at', '<', now()->subDays(7))
            ->each(function (ImportRun $run) use ($disk, &$files): void {
                $disk->deleteDirectory($run->directory());
                $run->forceFill(['error_path' => null])->save();
                $files++;
            });

        $rows += ImportRun::query()->where('created_at', '<', now()->subDays(90))->delete();
        $rows += ExportRun::query()->where('created_at', '<', now()->subDays(90))->delete();

        return ['files' => $files, 'rows' => $rows];
    }
}

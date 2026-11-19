<?php

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Models\ImportRun;
use App\Support\Import\ImportFile;
use App\Support\Import\ImportMapper;
use App\Support\Import\RowOutcome;
use App\Support\Import\RowResult;
use App\Support\Tables\ExportCell;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Works through the rows of a queued import for about 40 seconds, then hands back. Shared hosting has
 * no long-running worker (the scheduler's `queue:work --max-time=50`), so a run is a chain of slices;
 * `processed_rows` is where the next one starts. Rows that fail go to the error CSV with their own
 * columns first and the reason last, so the file can be fixed and uploaded again as it is.
 */
final class ProcessImportSlice
{
    private const int SECONDS = 40;

    private const int SAVE_EVERY = 25;

    public function __construct(private readonly FinishImport $finish) {}

    /**
     * @return bool true when the file is done (finished or failed), false when another slice is needed
     */
    public function handle(ImportRun $run): bool
    {
        $actor = $run->user;

        if ($actor === null || ! $actor->is_active || ! $run->type->canBeUsedBy($actor)) {
            $this->finish->handle($run, ImportStatus::Failed, 'The person who started this import may no longer import.');

            return true;
        }

        $definition = $run->type->definition();
        $mapping = $run->mapping ?? [];
        $options = $run->options ?? [];
        $headers = null;
        $deadline = microtime(true) + self::SECONDS;
        $sinceSave = 0;

        foreach (ImportFile::rows($run, $run->processed_rows) as $number => $cells) {
            try {
                $result = $definition->commit($actor, ImportMapper::row($cells, $mapping), $options);
            } catch (Throwable $exception) {
                report($exception);
                $result = RowResult::fail('This row could not be saved.');
            }

            match ($result->outcome) {
                RowOutcome::Create => $run->created_count++,
                RowOutcome::Update => $run->updated_count++,
                RowOutcome::Skip => $run->skipped_count++,
                RowOutcome::Fail => $run->failed_count++,
            };

            if ($result->isFailure()) {
                $this->appendError($run, $headers ??= ImportFile::headers($run), $cells, (string) $result->message, $number);
            }

            $run->processed_rows = $number - 1;

            if (++$sinceSave >= self::SAVE_EVERY) {
                $run->save();
                $sinceSave = 0;
            }

            if (microtime(true) > $deadline) {
                $run->save();

                return false;
            }
        }

        $run->processed_rows = $run->total_rows;
        $run->save();

        $this->finish->handle($run, ImportStatus::Finished);

        return true;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string>  $cells
     */
    private function appendError(ImportRun $run, array $headers, array $cells, string $problem, int $number): void
    {
        $disk = Storage::disk(ImportRun::DISK);
        $path = "{$run->directory()}/errors.csv";
        $isNew = ! $disk->exists($path);

        if ($isNew) {
            $disk->makeDirectory($run->directory());
        }

        $handle = fopen($disk->path($path), 'ab');

        if ($handle === false) {
            return;
        }

        $width = max(count($headers), count($cells));

        if ($isNew) {
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [...array_pad($headers, $width, ''), 'Problem', 'File row'], escape: '\\');
        }

        fputcsv($handle, [
            ...array_map(fn (string $cell): mixed => ExportCell::safe($cell, true), array_pad($cells, $width, '')),
            $problem,
            $number,
        ], escape: '\\');

        fclose($handle);

        if ($run->error_path === null) {
            $run->error_path = $path;
        }
    }
}

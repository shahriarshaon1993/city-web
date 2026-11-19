<?php

namespace App\Actions\Tables;

use App\Actions\Notifications\NotifyExportReady;
use App\Models\ExportRun;
use App\Queries\Admin\AdminTables;
use App\Support\Tables\ExportCell;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Throwable;

/**
 * ADM-TBL-012: writes a queued export to the private disk row by row, then tells the person who asked.
 * The table is read as that person (their own permission scope, Sales Executives see only theirs) and
 * the file is deleted when the 24-hour link expires (`transfers:purge`).
 */
final class GenerateExport
{
    public function __construct(private readonly NotifyExportReady $notify) {}

    public function handle(ExportRun $run): void
    {
        $actor = $run->user;
        $table = AdminTables::find($run->table_key);

        if ($actor === null || ! $actor->is_active || $table === null || ! $table->isVisibleTo($actor) || ! $table->canExport($actor)) {
            $this->fail($run, 'You can no longer export this list.');

            return;
        }

        $run->forceFill(['status' => 'running'])->save();

        $disk = Storage::disk(ExportRun::DISK);
        $disk->makeDirectory($run->directory());
        $path = "{$run->directory()}/{$run->filename}";

        try {
            $request = Request::create('/', 'GET', $run->query);
            $writer = SimpleExcelWriter::create($disk->path($path), $run->format);
            $writer->addHeader($table->exportHeader($run->columns));

            $available = $table->exportColumns();
            $text = array_map(fn (string $id): bool => $available[$id]['text'] ?? false, $run->columns);
            $count = 0;

            foreach ($table->exportRows($request, $actor, $run->columns) as $cells) {
                $writer->addRow(array_map(fn (mixed $cell, bool $isText): mixed => ExportCell::safe($cell, $isText), $cells, $text));
                $count++;
            }

            $writer->close();
        } catch (Throwable $exception) {
            report($exception);
            $disk->deleteDirectory($run->directory());
            $this->fail($run, 'The file could not be written. Try the export again.');

            return;
        }

        $run->forceFill([
            'status' => 'ready',
            'path' => $path,
            'row_count' => $count,
            'expires_at' => now()->addHours(ExportRun::LINK_HOURS),
        ])->save();

        $this->notify->handle($run);
    }

    private function fail(ExportRun $run, string $message): void
    {
        $run->forceFill(['status' => 'failed', 'message' => $message])->save();

        $this->notify->handle($run);
    }
}

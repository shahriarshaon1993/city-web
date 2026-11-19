<?php

namespace App\Actions\Tables;

use App\Actions\Activity\LogActivity;
use App\Models\ExportRun;
use App\Models\User;
use App\Queries\Admin\AdminTable;
use App\Support\Tables\ExportCell;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ADM-TBL-012: CSV or XLSX of the current filtered, sorted state, with the visible columns in
 * their order. Up to 5,000 rows it streams row by row, so a large table never sits in memory; above
 * that the file is prepared in the background (QueueExport) and the person gets a notification with a
 * signed 24-hour link. Either way the export is logged with its row count and filters (ADM-IO-002).
 */
final class ExportTable
{
    public const array FORMATS = ['csv', 'xlsx'];

    public function __construct(
        private readonly LogActivity $logActivity,
        private readonly QueueExport $queueExport,
    ) {}

    /**
     * @param  AdminTable<*>  $table
     * @param  list<string>  $columns  column ids in the order shown; unknown ids are dropped, empty = all
     */
    public function handle(AdminTable $table, Request $request, User $actor, string $format, array $columns): StreamedResponse|RedirectResponse
    {
        $columns = $table->exportColumnIds($columns);
        $rows = $table->count($request, $actor);

        if ($rows > ExportRun::QUEUE_THRESHOLD) {
            $this->queueExport->handle($table, $request, $actor, $format, $columns, $rows);

            Toast::info('Export on its way')
                ->description('That is a lot of rows, so it is prepared in the background. You will get a notification with the download link.')
                ->flash();

            return back();
        }

        $state = $table->state($request);
        $filename = sprintf('%s-%s.%s', $table->key(), now('Asia/Dhaka')->format('Y-m-d-His'), $format);

        $this->logActivity->handle(
            'export',
            'exported',
            sprintf('Exported %s (%s)', $table->key(), strtoupper($format)),
            $actor,
            null,
            [
                'table' => $table->key(),
                'format' => $format,
                'rows' => $rows,
                'filters' => $state->filters === [] ? null : json_encode($state->filters),
                'search' => $state->search === '' ? null : $state->search,
            ],
        );

        $contentType = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($table, $request, $actor, $columns, $filename): void {
            $writer = SimpleExcelWriter::streamDownload($filename, writerCallback: fn ($writer) => $writer->openToFile('php://output'));
            $writer->addHeader($table->exportHeader($columns));

            $available = $table->exportColumns();
            $text = array_map(fn (string $id): bool => $available[$id]['text'] ?? false, $columns);

            foreach ($table->exportRows($request, $actor, $columns) as $cells) {
                $writer->addRow(array_map(fn (mixed $cell, bool $isText): mixed => ExportCell::safe($cell, $isText), $cells, $text));
            }

            $writer->close();
        }, $filename, ['Content-Type' => $contentType]);
    }
}

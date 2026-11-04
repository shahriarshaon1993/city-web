<?php

namespace App\Actions\Tables;

use App\Actions\Activity\LogActivity;
use App\Models\User;
use App\Queries\Admin\AdminTable;
use Illuminate\Http\Request;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ADM-TBL-012: CSV or XLSX of the current filtered, sorted state, with the visible columns in
 * their order, streamed row by row so a large table never sits in memory. The export is logged
 * with its row count and filters (ADM-IO-002).
 *
 * The queued path for more than 5,000 rows needs the notification inbox (ADM-SCR-21) and
 * arrives with it; until then every size streams.
 */
final class ExportTable
{
    public const array FORMATS = ['csv', 'xlsx'];

    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  AdminTable<*>  $table
     * @param  list<string>  $columns  column ids in the order shown; unknown ids are dropped, empty = all
     */
    public function handle(AdminTable $table, Request $request, User $actor, string $format, array $columns): StreamedResponse
    {
        $columns = $table->exportColumnIds($columns);

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
                'rows' => $table->count($request, $actor),
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

            foreach ($table->exportRows($request, $actor, $columns) as $cells) {
                $writer->addRow(array_map($this->safe(...), $cells));
            }

            $writer->close();
        }, $filename, ['Content-Type' => $contentType]);
    }

    /**
     * A cell that starts with = + - @ is read as a formula by Excel; prefix it so a name like
     * "=HYPERLINK(…)" stays text.
     */
    private function safe(mixed $value): mixed
    {
        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)
            ? "'".$value
            : $value;
    }
}

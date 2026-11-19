<?php

namespace App\Actions\Tables;

use App\Actions\Activity\LogActivity;
use App\Jobs\RunExport;
use App\Models\ExportRun;
use App\Models\User;
use App\Queries\Admin\AdminTable;
use Illuminate\Http\Request;

/**
 * ADM-TBL-012: remembers a table export of more than 5,000 rows (the table state as the URL carried
 * it, the columns, the format) and queues it on `exports`. Logged like a streamed export, with the
 * row count and filters (ADM-IO-002).
 */
final class QueueExport
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  AdminTable<*>  $table
     * @param  list<string>  $columns  from `exportColumnIds()`
     */
    public function handle(AdminTable $table, Request $request, User $actor, string $format, array $columns, int $rows): ExportRun
    {
        $state = $table->state($request);

        $run = ExportRun::create([
            'user_id' => $actor->id,
            'table_key' => $table->key(),
            'format' => $format,
            'columns' => $columns,
            'query' => $request->query(),
            'filename' => sprintf('%s-%s.%s', $table->key(), now('Asia/Dhaka')->format('Y-m-d-His'), $format),
        ]);

        $this->logActivity->handle(
            'export',
            'exported',
            sprintf('Exported %s (%s), prepared in the background', $table->key(), strtoupper($format)),
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

        RunExport::dispatch($run->id);

        return $run;
    }
}

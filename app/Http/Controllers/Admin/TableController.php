<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tables\ExportTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportTableRequest;
use App\Queries\Admin\AdminTables;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ADM-TBL-012: one export endpoint for every registered admin table (AdminTables).
 */
class TableController extends Controller
{
    public function export(ExportTableRequest $request, string $table, ExportTable $exportTable): StreamedResponse
    {
        $adminTable = AdminTables::find($table) ?? abort(404);

        return $exportTable->handle(
            $adminTable,
            $request,
            $request->user(),
            $request->string('format')->toString(),
            $request->columnIds(),
        );
    }
}

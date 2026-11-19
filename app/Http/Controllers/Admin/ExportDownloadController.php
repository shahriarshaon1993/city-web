<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExportRun;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ADM-TBL-012: the signed 24-hour link of a large export. The signature proves the link is the one we
 * sent; the signed-in user must also be the person who asked (the file holds what only they may see).
 */
class ExportDownloadController extends Controller
{
    public function __invoke(Request $request, ExportRun $export): StreamedResponse|RedirectResponse
    {
        abort_unless($export->user_id === $request->user()->id, 403);

        if (! $export->isDownloadable() || ! Storage::disk(ExportRun::DISK)->exists((string) $export->path)) {
            Toast::warning('This export is no longer available')
                ->description('The link works for '.ExportRun::LINK_HOURS.' hours. Export the list again to get a new one.')
                ->flash();

            return redirect()->route('admin.import.index');
        }

        return Storage::disk(ExportRun::DISK)->download((string) $export->path, $export->filename);
    }
}

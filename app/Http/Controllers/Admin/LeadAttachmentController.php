<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * FR-LEAD-011: landowner documents live on the private disk and leave it only here, for a user who
 * may see the lead. Always an attachment with `nosniff`, never rendered inline.
 */
class LeadAttachmentController extends Controller
{
    public function show(Lead $lead, int $index): StreamedResponse
    {
        Gate::authorize('view', $lead);

        $file = ($lead->attachments ?? [])[$index] ?? abort(404);
        $disk = Storage::disk('local');

        abort_unless(str_starts_with($file['path'], 'leads/') && $disk->exists($file['path']), 404);

        return $disk->download($file['path'], $file['name'], [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}

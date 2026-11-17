<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AC-PROJ-05: the gated brochure download. Only reachable through the signed, 10-minute URL that
 * `CreateBrochureLink` hands out after a lead; served as an attachment.
 */
class ProjectDocumentController extends Controller
{
    public function download(string $slug, int $document): StreamedResponse
    {
        $project = Project::query()->published()->where('slug', $slug)->firstOrFail();
        $record = $project->documents()->findOrFail($document);
        $file = $record->asset?->file() ?? abort(404);

        return Storage::disk($file->disk)->download(
            $file->getPathRelativeToRoot(),
            Str::slug($project->name.' '.$record->title).'.'.($file->extension ?: 'pdf'),
            ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'],
        );
    }
}

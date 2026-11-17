<?php

namespace App\Actions\Leads;

use App\Enums\DocumentKind;
use App\Models\Project;
use Illuminate\Support\Facades\URL;

/**
 * AC-PROJ-05: after a gated brochure request, the download is a signed URL that works for 10
 * minutes. The file is the project's brochure document (else its first document); null when the
 * project has none.
 */
final class CreateBrochureLink
{
    public const int VALID_MINUTES = 10;

    public function handle(Project $project): ?string
    {
        $documents = $project->documents()->orderBy('sort_order')->orderBy('id')->get();
        $document = $documents->firstWhere('kind', DocumentKind::Brochure) ?? $documents->first();

        if ($document === null) {
            return null;
        }

        return URL::temporarySignedRoute(
            'site.projects.documents.download',
            now()->addMinutes(self::VALID_MINUTES),
            ['slug' => $project->slug, 'document' => $document->id],
        );
    }
}

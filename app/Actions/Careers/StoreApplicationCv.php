<?php

namespace App\Actions\Careers;

use App\Models\JobApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * FR-CAR-002 / FR-LEAD-011: the CV goes to the private disk (`storage/app/private/careers/{reference}`)
 * under a random name whose extension comes from the file's content, never from the visitor's file
 * name. It is only ever served by the authorised download of the applications screen.
 */
final class StoreApplicationCv
{
    /**
     * @return array{path: string, name: string, size: int}
     */
    public function handle(UploadedFile $cv, string $reference): array
    {
        $extension = $cv->guessExtension() ?: 'pdf';
        $path = Storage::disk(JobApplication::DISK)->putFileAs("careers/{$reference}", $cv, Str::random(40).'.'.$extension);

        abort_if($path === false, 500, 'The CV could not be stored.');

        return ['path' => $path, 'name' => $this->displayName($cv), 'size' => (int) $cv->getSize()];
    }

    /** The visitor's file name for the download, stripped of path parts and control characters. */
    private function displayName(UploadedFile $file): string
    {
        $name = preg_replace('#[\x00-\x1F\x7F\\\\/]+#u', '', $file->getClientOriginalName()) ?? '';

        return Str::limit(trim($name) === '' ? 'cv' : $name, 120, '');
    }
}

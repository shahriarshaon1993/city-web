<?php

namespace App\Actions\Leads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * FR-LEAD-011: landowner documents go to the private disk (`storage/app/private/leads/{reference}`)
 * under a random name whose extension comes from the file's content, never from the visitor's
 * file name. They are only ever served by an authorised controller as downloads.
 */
final class StoreLeadDocuments
{
    public const string DISK = 'local';

    /**
     * @param  array<int, mixed>  $files
     * @return list<array{path: string, name: string, size: int, mime: string}>
     */
    public function handle(array $files, string $reference): array
    {
        $stored = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = Storage::disk(self::DISK)->putFile("leads/{$reference}", $file);

            if ($path === false) {
                continue;
            }

            $stored[] = [
                'path' => $path,
                'name' => $this->displayName($file),
                'size' => (int) $file->getSize(),
                'mime' => (string) ($file->getMimeType() ?? 'application/octet-stream'),
            ];
        }

        return $stored;
    }

    /** The visitor's file name for the download, stripped of path parts and control characters. */
    private function displayName(UploadedFile $file): string
    {
        $name = preg_replace('#[\x00-\x1F\x7F\\\\/]+#u', '', $file->getClientOriginalName()) ?? '';

        return Str::limit(trim($name) === '' ? 'document' : $name, 120, '');
    }
}

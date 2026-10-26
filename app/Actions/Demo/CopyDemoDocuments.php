<?php

namespace App\Actions\Demo;

use App\Support\Demo\DemoMedia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Copies the PDFs in storage/app/demo-src/documents/ (DM-PDOC demo files,
 * 4.3) into public/demo/documents/ under a content-hash folder (CMS-MED-011)
 * and checks the FR-PROJ limit of 20 MB.
 *
 * @phpstan-import-type DocumentEntry from DemoMedia
 */
final class CopyDemoDocuments
{
    public const string GROUP = 'documents';

    private const int MAX_BYTES = 20 * 1024 * 1024;

    /**
     * @param  list<string>  $warnings
     * @return array<string, DocumentEntry>
     */
    public function handle(array &$warnings): array
    {
        $sourceDir = DemoMedia::sourcePath(self::GROUP);

        if (! File::isDirectory($sourceDir)) {
            return [];
        }

        $entries = [];

        foreach (File::files($sourceDir) as $file) {
            if (strtolower($file->getExtension()) !== 'pdf') {
                continue;
            }

            $slug = Str::slug($file->getFilenameWithoutExtension());
            $key = self::GROUP.'/'.$slug;
            $bytes = (int) $file->getSize();

            if ($bytes > self::MAX_BYTES) {
                $warnings[] = "{$key}.pdf: larger than 20 MB (FR-PROJ media).";
            }

            $dir = $key.'-'.substr((string) hash_file('xxh128', $file->getPathname()), 0, 8);
            $target = DemoMedia::outputPath("{$dir}/{$slug}.pdf");
            File::ensureDirectoryExists(dirname($target));

            if (! File::exists($target)) {
                File::copy($file->getPathname(), $target);
            }

            $entries[$key] = ['dir' => $dir, 'path' => "{$dir}/{$slug}.pdf", 'bytes' => $bytes];
        }

        return $entries;
    }
}

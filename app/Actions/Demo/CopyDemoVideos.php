<?php

namespace App\Actions\Demo;

use App\Support\Demo\DemoMedia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Copies the videos in storage/app/demo-src/video/ (HERO-020: MP4 required,
 * WebM optional, paired by file name; a `.vtt` of the same name is the
 * captions track of a project video, FR-PROJ media) into public/demo/video/.
 * The folder name carries a content hash so URLs can be cached forever
 * (CMS-MED-011). There is no ffmpeg/getID3 here: only the size limits are
 * checked (HERO-022 for `hero*`, 50 MB for project videos); duration,
 * resolution and frame rate are the editor's job.
 *
 * @phpstan-import-type VideoEntry from DemoMedia
 */
final class CopyDemoVideos
{
    public const string GROUP = 'video';

    /** HERO-022 upload limits in bytes. */
    private const array MAX_BYTES = ['mp4' => 6 * 1024 * 1024, 'webm' => 5 * 1024 * 1024];

    /** FR-PROJ media: an uploaded project video is at most 50 MB. */
    private const int PROJECT_MAX_BYTES = 50 * 1024 * 1024;

    /**
     * @param  list<string>  $warnings
     * @return array<string, VideoEntry>
     */
    public function handle(array &$warnings): array
    {
        $sourceDir = DemoMedia::sourcePath(self::GROUP);

        if (! File::isDirectory($sourceDir)) {
            return [];
        }

        /** @var array<string, array<string, string>> $pairs */
        $pairs = [];

        foreach (File::files($sourceDir) as $file) {
            $extension = strtolower($file->getExtension());

            if (isset(self::MAX_BYTES[$extension]) || $extension === 'vtt') {
                $pairs[Str::slug($file->getFilenameWithoutExtension())][$extension] = $file->getPathname();
            }
        }

        $entries = [];

        foreach ($pairs as $slug => $files) {
            $key = self::GROUP.'/'.$slug;

            if (! isset($files['mp4'])) {
                // Placeholder captions wait for their video without a warning.
                if (array_keys($files) !== ['vtt']) {
                    $warnings[] = "{$key}: an MP4 is required (HERO-020); the WebM alone is skipped.";
                }

                continue;
            }

            $isHero = str_starts_with($slug, 'hero');

            foreach ($files as $extension => $path) {
                $max = $extension === 'vtt' ? null : ($isHero ? self::MAX_BYTES[$extension] : self::PROJECT_MAX_BYTES);

                if ($max !== null && File::size($path) > $max) {
                    $limit = $max / 1024 / 1024;
                    $warnings[] = "{$key}.{$extension}: larger than {$limit} MB (".($isHero ? 'HERO-022' : 'FR-PROJ media').').';
                }
            }

            $context = hash_init('xxh128');

            foreach ($files as $path) {
                hash_update_file($context, $path);
            }

            $hash = substr(hash_final($context), 0, 8);
            $dir = $key.'-'.$hash;
            File::ensureDirectoryExists(DemoMedia::outputPath($dir));

            foreach ($files as $extension => $path) {
                $target = DemoMedia::outputPath($dir.'/'.$slug.'.'.$extension);

                if (! File::exists($target)) {
                    File::copy($path, $target);
                }
            }

            $entries[$key] = [
                'dir' => $dir,
                'mp4' => $dir.'/'.$slug.'.mp4',
                'webm' => isset($files['webm']) ? $dir.'/'.$slug.'.webm' : null,
                'captions' => isset($files['vtt']) ? $dir.'/'.$slug.'.vtt' : null,
            ];
        }

        return $entries;
    }
}

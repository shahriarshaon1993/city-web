<?php

namespace App\Actions\Demo;

use App\Actions\Media\UploadMedia;
use App\Jobs\Media\GenerateMediaConversions;
use App\Models\MediaAsset;
use App\Support\Demo\DemoMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * 13.1: puts one demo source file (`storage/app/demo-src/{group}/{name}.{ext}`) into the media library
 * as a real item — original, metadata from the group's `meta.json`, conversions — so seeded projects
 * and locations use library assets exactly like admin-created ones. Idempotent: an item is found
 * again by its tag `demo:{key}`, which also marks it for removal with the demo content (16.4).
 */
final class ImportDemoMedia
{
    public const string TAG_PREFIX = 'demo:';

    /** @var array<string, MediaAsset> */
    private array $cache = [];

    public function __construct(private readonly UploadMedia $uploadMedia) {}

    /**
     * @param  string  $key  "projects/projects-01" or "documents/brochure"
     * @param  string|null  $title  library title; defaults to the readable file name
     * @return MediaAsset|null null when the source file is missing (run `demo:images` / add the photos first)
     */
    public function handle(string $key, ?string $title = null): ?MediaAsset
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $existing = MediaAsset::query()->whereJsonContains('tags', self::TAG_PREFIX.$key)->first();

        if ($existing !== null) {
            return $this->cache[$key] = $existing;
        }

        $path = $this->sourcePath($key);

        if ($path === null) {
            return null;
        }

        [$group, $name] = explode('/', $key, 2);
        $meta = $this->meta($group)[$name] ?? [];

        // The upload would queue the conversions before the metadata (focal point) is set; run them once, after.
        $queue = config('queue.default');
        config(['queue.connections.null' => ['driver' => 'null'], 'queue.default' => 'null']);

        try {
            $asset = $this->uploadMedia->handle(null, new UploadedFile($path, basename($path), null, null, true));
        } finally {
            config(['queue.default' => $queue]);
        }

        $asset->forceFill([
            'title' => Str::limit($title ?? Str::headline($name), 150, ''),
            'alt' => ($meta['decorative'] ?? false) ? null : ($meta['alt'] ?? null),
            'is_decorative' => (bool) ($meta['decorative'] ?? false),
            'caption' => $meta['caption'] ?? null,
            'credit' => isset($meta['credit']) ? Str::limit($meta['credit'], 120, '') : null,
            'focal_x' => (int) ($meta['focal'][0] ?? 50),
            'focal_y' => (int) ($meta['focal'][1] ?? 50),
            'tags' => [self::TAG_PREFIX.$key],
        ])->save();

        $media = $asset->file();

        if ($media !== null && $asset->kind->value === 'image') {
            GenerateMediaConversions::dispatchSync($media);
        }

        return $this->cache[$key] = $asset->refresh();
    }

    private function sourcePath(string $key): ?string
    {
        $base = DemoMedia::sourcePath($key);

        foreach (['jpg', 'jpeg', 'png', 'webp', 'svg', 'pdf'] as $extension) {
            if (File::exists("{$base}.{$extension}")) {
                return "{$base}.{$extension}";
            }
        }

        return null;
    }

    /**
     * @return array<string, array{alt?: string, caption?: string|null, credit?: string|null, focal?: list<int>, decorative?: bool}>
     */
    private function meta(string $group): array
    {
        $path = DemoMedia::sourcePath("{$group}/meta.json");

        return File::exists($path) ? (json_decode(File::get($path), true) ?: []) : [];
    }
}

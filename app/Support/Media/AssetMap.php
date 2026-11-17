<?php

namespace App\Support\Media;

use App\Http\Resources\Media\MediaImageResource;
use App\Models\MediaAsset;
use Illuminate\Support\Collection;

/**
 * A page's library assets loaded in one query (with their media rows), so shaping a gallery, floor
 * plans and progress photos costs two queries instead of two per image.
 */
final class AssetMap
{
    /**
     * @param  Collection<int, MediaAsset>  $assets  keyed by ID, `media` loaded
     */
    private function __construct(private readonly Collection $assets) {}

    /**
     * @param  iterable<int|null>  $ids
     */
    public static function load(iterable $ids): self
    {
        $ids = collect($ids)->filter()->unique()->values();

        return new self($ids->isEmpty() ? collect() : MediaAsset::query()->whereIn('id', $ids)->with('media')->get()->keyBy('id'));
    }

    public function asset(?int $id): ?MediaAsset
    {
        return $id === null ? null : $this->assets->get($id);
    }

    /**
     * The `MediaImage` prop; `$caption` replaces the library caption when given.
     *
     * @return array<string, mixed>|null
     */
    public function image(?int $id, ?string $caption = null): ?array
    {
        $asset = $this->asset($id);

        if ($asset === null) {
            return null;
        }

        $image = MediaImageResource::make($asset)->resolve();

        return $caption === null || $caption === '' ? $image : [...$image, 'caption' => $caption];
    }

    public function url(?int $id): ?string
    {
        return $this->asset($id)?->url();
    }

    /**
     * @return array{url: string, bytes: int}|null
     */
    public function document(?int $id): ?array
    {
        $asset = $this->asset($id);

        return $asset === null ? null : ['url' => (string) $asset->url(), 'bytes' => (int) $asset->file()?->size];
    }
}

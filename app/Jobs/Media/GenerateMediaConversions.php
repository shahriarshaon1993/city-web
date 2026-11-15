<?php

namespace App\Jobs\Media;

use App\Actions\Media\GenerateConversions;
use App\Models\MediaAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * ARC-011: conversions run on the `media` queue — idempotent, 3 tries, back-off 60 / 300 / 900 s.
 * A final failure marks the item `failed`; the original stays usable.
 */
class GenerateMediaConversions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Media $media)
    {
        $this->onQueue('media');
        $this->afterCommit();
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(GenerateConversions $generateConversions): void
    {
        $generateConversions->handle($this->media);
    }

    public function failed(Throwable $exception): void
    {
        $asset = $this->media->model;

        if ($asset instanceof MediaAsset) {
            $asset->forceFill(['conversions_status' => MediaAsset::STATUS_FAILED])->save();
        }
    }
}

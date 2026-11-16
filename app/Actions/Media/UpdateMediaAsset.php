<?php

namespace App\Actions\Media;

use App\Actions\Activity\LogActivity;
use App\Jobs\Media\GenerateMediaConversions;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\Media\MediaKind;
use Illuminate\Support\Facades\DB;

/**
 * CMS-MED-004 metadata from the detail drawer: title, alt (or "decorative"), caption, credit,
 * tags, folder and focal point. A moved focal point re-runs the conversions, because the `og`
 * crop is cut around it (CMS-MED-005).
 */
final class UpdateMediaAsset
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  array{title: string, alt: ?string, caption: ?string, credit: ?string, is_decorative: bool, tags: list<string>, folder_id: ?int, focal_x: int, focal_y: int}  $data
     * @return list<string> the fields that changed
     */
    public function handle(User $actor, MediaAsset $asset, array $data): array
    {
        if ($data['is_decorative']) {
            $data['alt'] = null;
        }

        return DB::transaction(function () use ($actor, $asset, $data): array {
            $asset->fill($data);
            $changed = array_keys($asset->getDirty());

            if ($changed === []) {
                return [];
            }

            $focalMoved = $asset->isDirty(['focal_x', 'focal_y']);
            $asset->save();

            $media = $asset->file();

            if ($focalMoved && $asset->kind === MediaKind::Image && $media !== null) {
                GenerateMediaConversions::dispatch($media);
            }

            $this->logActivity->handle('media', 'updated', 'Media details changed', $actor, $asset, [
                'title' => $asset->title,
                'fields' => $changed,
            ]);

            return $changed;
        });
    }
}

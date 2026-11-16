<?php

namespace App\Actions\Media;

use App\Actions\Activity\LogActivity;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\User;
use App\Support\Media\MediaKind;
use App\Support\Media\UsesMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CMS-MED-008 "Replace everywhere": every usage of `$from` points at `$to` afterwards, in one
 * transaction. Where `$to` already sits in the same field and position, the duplicate row of
 * `$from` is dropped. Models that also hold the id themselves are told (UsesMedia).
 */
final class ReplaceMediaEverywhere
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @return int usages moved
     *
     * @throws ValidationException on `replacement_id`
     */
    public function handle(User $actor, MediaAsset $from, MediaAsset $to): int
    {
        if ($from->is($to)) {
            throw ValidationException::withMessages(['replacement_id' => 'Pick a different file to replace it with.']);
        }

        if (! self::compatible($from->kind, $to->kind)) {
            throw ValidationException::withMessages([
                'replacement_id' => 'Pick a file of the same type: '.self::groupName($from->kind).' can only be replaced by '.self::groupName($from->kind).'.',
            ]);
        }

        return DB::transaction(function () use ($actor, $from, $to): int {
            $moved = 0;

            /** @var MediaUsage $usage */
            foreach ($from->usages()->lockForUpdate()->get() as $usage) {
                $duplicate = MediaUsage::query()
                    ->where('media_asset_id', $to->id)
                    ->where('usable_type', $usage->usable_type)
                    ->where('usable_id', $usage->usable_id)
                    ->where('field', $usage->field)
                    ->where('position', $usage->position)
                    ->exists();

                $duplicate ? $usage->delete() : $usage->update(['media_asset_id' => $to->id]);

                $usable = $usage->usable;

                if ($usable instanceof UsesMedia) {
                    $usable->replaceMediaAsset($usage->field, $from->id, $to->id);
                }

                $moved++;
            }

            $this->logActivity->handle('media', 'replaced', 'Media replaced everywhere', $actor, $from, [
                'title' => $from->title,
                'replacement_id' => $to->id,
                'replacement' => $to->title,
                'usages' => $moved,
            ]);

            return $moved;
        });
    }

    /** Images and SVGs stand in for each other; videos and PDFs only for their own kind. */
    public static function compatible(MediaKind $from, MediaKind $to): bool
    {
        return self::group($from) === self::group($to);
    }

    /**
     * Kinds that may replace an item of `$kind` (the picker's type preset).
     *
     * @return list<string>
     */
    public static function replacementKinds(MediaKind $kind): array
    {
        return array_values(array_map(
            fn (MediaKind $candidate): string => $candidate->value,
            array_filter(MediaKind::cases(), fn (MediaKind $candidate): bool => self::compatible($kind, $candidate)),
        ));
    }

    private static function group(MediaKind $kind): string
    {
        return $kind === MediaKind::Svg ? MediaKind::Image->value : $kind->value;
    }

    private static function groupName(MediaKind $kind): string
    {
        return match (self::group($kind)) {
            'image' => 'an image',
            'video' => 'a video',
            default => 'a PDF',
        };
    }
}

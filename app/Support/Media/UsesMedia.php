<?php

namespace App\Support\Media;

/**
 * A model that keeps library items in fields (DM-MUSE rows with `usable_type` = its morph alias).
 * The asset detail lists its usages with these labels and links (CMS-MED-007), and "Replace
 * everywhere" (CMS-MED-008) calls `replaceMediaAsset` for fields that also hold the asset id in a
 * column or JSON of their own, after the usage row itself was moved.
 */
interface UsesMedia
{
    /** "Profile picture · Aminul Haque", "Gemcon Orchid › Gallery". */
    public function mediaUsageLabel(string $field): string;

    /** The admin screen where the field is edited, or null when there is none to link to. */
    public function mediaUsageUrl(string $field): ?string;

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void;
}

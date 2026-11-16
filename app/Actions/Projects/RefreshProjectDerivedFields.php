<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\ProjectUnitType;

/**
 * DM-PROJ denormalised columns: recomputes `size_min/max_sft`, `beds_*`, `baths_*` and `price_from_bdt`
 * from the project's unit types. Run by `ProjectUnitType` after every save or delete, so a unit edited
 * anywhere (admin, tinker, import) keeps the project honest.
 *
 * `price_from_bdt` is the lowest price among unit types that show their price (FR-PROJ-009); a unit with
 * a hidden price is left out (AC-PROJ-02). While `price_from_manual` is on the editor's figure stays,
 * which is how land projects (no unit types) carry a price. Whether visitors may see the figure at all
 * is `Project::publicPriceFrom()`.
 */
final class RefreshProjectDerivedFields
{
    public function handle(Project $project): Project
    {
        $units = $project->unitTypes()->get();

        $project->forceFill([
            'size_min_sft' => $units->isEmpty() ? null : $units->min('size_sft'),
            'size_max_sft' => $units->isEmpty() ? null : $units->max(fn (ProjectUnitType $unit): int => $unit->size_sft_max ?? $unit->size_sft),
            'beds_min' => $units->whereNotNull('bedrooms')->min('bedrooms'),
            'beds_max' => $units->whereNotNull('bedrooms')->max('bedrooms'),
            'baths_min' => $units->whereNotNull('bathrooms')->min('bathrooms'),
            'baths_max' => $units->whereNotNull('bathrooms')->max('bathrooms'),
        ]);

        if (! $project->price_from_manual) {
            $project->price_from_bdt = $units
                ->filter(fn (ProjectUnitType $unit): bool => $unit->show_price && $unit->price_bdt !== null)
                ->min('price_bdt');
        }

        if ($project->isDirty()) {
            $project->saveQuietly();
        }

        return $project;
    }
}

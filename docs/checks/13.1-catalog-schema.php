<?php

/**
 * Phase 13.1 check: the seeded catalogue, the denormalised project figures, slug history, hidden unit
 * prices, media usages and the Recycle bin guards.
 * Run: php artisan tinker docs/checks/13.1-catalog-schema.php   (needs the seeders first; changes are rolled back)
 */

use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\RecycleBin\RestoreFromRecycleBin;
use App\Enums\Availability;
use App\Models\Amenity;
use App\Models\FacetPage;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\Project;
use App\Models\ProjectUnitType;
use App\Models\SlugHistory;
use App\Models\User;
use App\Support\Slugs\SlugAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};
$actor = User::query()->where('email', 'super-admin@gemcon.test')->firstOrFail();

echo "— seeded rows\n";
$say('cities', Location::cities()->count(), 3);
$say('areas', Location::areas()->count(), 11);
$say('projects', Project::count(), 27);
$say('published projects', Project::published()->count(), 27);
$say('facet pages', FacetPage::count(), 8);
$say('every project has a cover, gallery ≥ 4 and a location', Project::query()
    ->whereNull('cover_asset_id')->orWhere('location_id', 0)->count(), 0);
$say('projects with a gallery under 4', Project::all()->filter(fn (Project $project): bool => count($project->gallery ?? []) < 4)->count(), 0);
$say('amenities', Amenity::count() > 15, true);
$say('Uttara has 5 projects', Location::where('slug', 'uttara')->first()->projects()->count(), 5);
$say('Purbachal has none (AC-LOC-01)', Location::where('slug', 'purbachal')->first()->projects()->count(), 0);

echo "— derived figures\n";
$lakeview = Project::where('slug', 'gemcon-lakeview')->firstOrFail();
$say('Lakeview size range', [$lakeview->size_min_sft, $lakeview->size_max_sft], [1650, 2100]);
$say('Lakeview beds range', [$lakeview->beds_min, $lakeview->beds_max], [3, 4]);
$say('Lakeview price from', $lakeview->price_from_bdt, 14_500_000);
$say('Lakeview public price', $lakeview->publicPriceFrom(), 14_500_000);
$hidden = Project::where('slug', 'gemcon-orchid-heights')->firstOrFail();
$say('hidden project public price', $hidden->publicPriceFrom(), null);
$land = Project::where('slug', 'gemcon-riverside-plots')->firstOrFail();
$say('land keeps its manual price', [$land->price_from_bdt, $land->price_from_manual, $land->unitTypes()->count()], [12_500_000, true, 0]);

DB::beginTransaction();

echo "— unit type changes update the project\n";
$unit = $lakeview->unitTypes()->orderByDesc('size_sft')->firstOrFail();
$unit->update(['size_sft' => 2400]);
$say('size_max after enlarging the biggest unit', $lakeview->fresh()->size_max_sft, 2400);
$unit->update(['bedrooms' => 5]);
$say('beds_max after 5 bedrooms', $lakeview->fresh()->beds_max, 5);
$cheapest = $lakeview->unitTypes()->whereNotNull('price_bdt')->orderBy('price_bdt')->firstOrFail();
$cheapest->update(['show_price' => false]);
$say('hidden unit price is excluded from price_from', $lakeview->fresh()->price_from_bdt > 14_500_000, true);
$say('unit public price is null when its show_price is off', $cheapest->fresh()->publicPrice($lakeview->fresh()), null);
$cheapest->update(['show_price' => true]);
$say('price_from is back', $lakeview->fresh()->price_from_bdt, 14_500_000);
$lakeview->forceFill(['price_from_manual' => true, 'price_from_bdt' => 9_999_999])->save();
$unit->update(['size_sft' => 2200]);
$say('manual price survives a unit edit', $lakeview->fresh()->price_from_bdt, 9_999_999);
$lakeview->update(['price_from_manual' => false]);
$say('manual off recomputes', $lakeview->fresh()->price_from_bdt, 14_500_000);
$unit->delete();
$say('size_max after deleting a unit', $lakeview->fresh()->size_max_sft < 2200, true);
$new = $lakeview->unitTypes()->create(['name' => 'Penthouse', 'size_sft' => 4000, 'availability' => Availability::Limited]);
$say('a new unit widens the range', $lakeview->fresh()->size_max_sft, 4000);
$say('unit enum cast', $new->fresh()->availability, Availability::Limited);

echo "— slug history\n";
$lakeview->update(['slug' => 'gemcon-lakeview-new']);
$say('history row for the old slug', SlugHistory::where('slug', 'gemcon-lakeview')->value('sluggable_id'), $lakeview->id);
$say('old slug finds the project', Project::findBySlugHistory('gemcon-lakeview')?->id, $lakeview->id);
$lakeview->update(['slug' => 'gemcon-lakeview-v3']);
$say('chain stays flat (both old slugs point at the project)', SlugHistory::where('sluggable_id', $lakeview->id)->where('sluggable_type', 'project')->count(), 2);
$lakeview->update(['slug' => 'gemcon-lakeview']);
$say('a returning slug stops being history', SlugHistory::where('slug', 'gemcon-lakeview')->exists(), false);
$say('slug availability sees projects', (new SlugAvailability)->check('project', 'gemcon-greenfield')['available'], false);

echo "— media usages\n";
$say('cover is recorded as a usage', MediaUsage::where('usable_type', 'project')->where('usable_id', $lakeview->id)->where('field', 'cover')->count(), 1);
$say('the cover cannot be deleted while used', $lakeview->cover->binUsageBlock($actor) !== null, true);
$say('a floor plan is used by a unit type', MediaUsage::where('usable_type', 'unit_type')->where('field', 'floor_plan')->exists(), true);
$say('progress photos are used', MediaUsage::where('usable_type', 'progress_update')->where('field', 'photos')->exists(), true);
$say('location hero is used', MediaUsage::where('usable_type', 'location')->where('field', 'hero')->count(), 11);
$say('demo assets are tagged', MediaAsset::whereJsonContains('tags', 'demo:projects/projects-01')->count(), 1);

echo "— Recycle bin guards\n";
$dhaka = Location::where('slug', 'dhaka')->firstOrFail();
$say('a city with areas cannot be deleted', $dhaka->binUsageBlock($actor) !== null, true);
$uttara = Location::where('slug', 'uttara')->firstOrFail();
$say('an area with projects cannot be deleted', $uttara->binUsageBlock($actor) !== null, true);
$say('an empty area can', Location::where('slug', 'purbachal')->first()->binUsageBlock($actor), null);
$say('a used amenity cannot be deleted', Amenity::has('projects')->first()->binUsageBlock($actor) !== null, true);
$purbachal = Location::where('slug', 'purbachal')->firstOrFail();
$parent = $purbachal->parent;
$parent->forceFill(['deleted_at' => now()])->saveQuietly();
$purbachal->delete();
$say('restoring an area under a trashed city asks about the city', array_map(fn ($c) => $c->kind, $purbachal->fresh()->binRestoreConflicts()), ['parent']);
$purbachal = Location::withTrashed()->where('slug', 'purbachal')->first();
$purbachal->binResolveConflicts(['parent' => true]);
$purbachal->restore();
$say('city restored with it', Location::where('slug', 'narayanganj')->exists(), true);
$say('a project soft delete keeps its units', Project::where('slug', 'gemcon-greenfield')->first()->delete() && ProjectUnitType::count() > 0, true);
$say('soft-deleted project still holds its slug', (new SlugAvailability)->check('project', 'gemcon-greenfield')['available'], false);

echo "— the real bin actions\n";
$entry = app(MoveToRecycleBin::class)->handle($actor, Project::where('slug', 'gemcon-banani-square')->firstOrFail());
$say('project in the bin', [$entry->type, Project::where('slug', 'gemcon-banani-square')->exists()], ['project', false]);
app(RestoreFromRecycleBin::class)->handle($actor, $entry);
$say('project restored', Project::where('slug', 'gemcon-banani-square')->exists(), true);
try {
    app(MoveToRecycleBin::class)->handle($actor, Location::where('slug', 'uttara')->firstOrFail());
    $say('deleting an area with projects is refused', 'deleted', 'refused');
} catch (ValidationException $e) {
    $say('deleting an area with projects is refused', 'refused', 'refused');
}
DB::rollBack();
echo "rolled back\n";

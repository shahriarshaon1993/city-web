<?php

namespace Database\Seeders;

use App\Enums\PartnerCategory;
use App\Enums\TeamGroup;
use App\Models\Office;
use App\Models\Partner;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\Social\OfficePhones;
use Database\Seeders\Data\SocialSeedData;
use Database\Seeders\Support\ImportDemoMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The placeholder offices, testimonials, partners and team of the client's review (outside production
 * only; the client enters the real ones in the admin). Partner logos are the fictional wordmarks in
 * `storage/app/demo-src/partners`; a missing file leaves the logo empty and the partner hidden. Safe to
 * run again: a table that already has rows is left alone.
 */
class SocialSeeder extends Seeder
{
    public function run(ImportDemoMedia $importMedia): void
    {
        $this->offices();
        $this->testimonials();
        $this->partners($importMedia);
        $this->team($importMedia);
    }

    private function offices(): void
    {
        if (Office::withTrashed()->exists()) {
            return;
        }

        foreach (SocialSeedData::OFFICES as $position => $row) {
            Office::query()->create([
                ...$row,
                'phones' => array_map(fn (string $phone): string => OfficePhones::normalize($phone) ?? $phone, $row['phones']),
                'sort_order' => $position + 1,
            ]);
        }
    }

    private function testimonials(): void
    {
        if (Testimonial::withTrashed()->exists()) {
            return;
        }

        $projects = Project::query()->pluck('id', 'slug');

        foreach (SocialSeedData::TESTIMONIALS as $position => $row) {
            Testimonial::query()->create([
                'quote' => $row['quote'],
                'person_name' => $row['person_name'],
                'designation' => $row['designation'],
                'project_id' => $row['project'] === null ? null : ($projects[$row['project']] ?? null),
                'is_nrb' => $row['is_nrb'],
                'sort_order' => $position + 1,
                'is_published' => true,
            ]);
        }
    }

    private function partners(ImportDemoMedia $importMedia): void
    {
        if (Partner::withTrashed()->exists()) {
            return;
        }

        foreach (SocialSeedData::PARTNERS as $position => [$name, $category]) {
            $logo = $importMedia->handle(sprintf('partners/partners-%02d', $position + 1), $name);

            Partner::query()->create([
                'name' => $name,
                'logo_asset_id' => $logo?->id,
                'category' => PartnerCategory::from($category),
                'sort_order' => $position + 1,
                'is_published' => $logo !== null,
            ]);
        }
    }

    private function team(ImportDemoMedia $importMedia): void
    {
        if (TeamMember::withTrashed()->exists()) {
            return;
        }

        $order = 0;

        foreach (SocialSeedData::MEMBERS as $group => $members) {
            foreach ($members as [$name, $role, $bio, $portrait, $linkedin]) {
                $photo = $importMedia->handle(sprintf('team/team-%02d', $portrait), $name);

                TeamMember::query()->create([
                    'name' => $name,
                    'slug' => $this->freeSlug($name),
                    'role_title' => $role,
                    'group' => TeamGroup::from($group),
                    'photo_asset_id' => $photo?->id,
                    'bio' => $bio,
                    'linkedin_url' => $linkedin ? 'https://www.linkedin.com/' : null,
                    'sort_order' => ++$order,
                    'is_published' => true,
                ]);
            }
        }
    }

    private function freeSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'member';
        $candidate = $base;

        for ($number = 2; TeamMember::withTrashed()->where('slug', $candidate)->exists(); $number++) {
            $candidate = "{$base}-{$number}";
        }

        return $candidate;
    }
}

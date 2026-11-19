<?php

namespace App\Queries\Site;

use App\Http\Resources\Media\MediaImageResource;
use App\Models\MediaAsset;
use App\Models\Office;
use App\Models\Partner;
use App\Models\TeamMember;
use App\Models\Testimonial;

/**
 * The public reads of testimonials, partners, team members and offices (FR-SOC-001/002/004/005),
 * shaped like the Resources of ARC-002 (`Testimonial`, `Partner`, `TeamMember`, `Office` in
 * resources/js/types). Only published rows leave the server; everything comes back in the order
 * the editors dragged it into.
 */
final class SocialContent
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function testimonials(): array
    {
        return array_values(Testimonial::query()->published()
            ->with(['project' => fn ($query) => $query->published(), 'photo.media'])
            ->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Testimonial $testimonial): array => [
                'id' => $testimonial->id,
                'quote' => $testimonial->quote,
                'person_name' => $testimonial->person_name,
                'designation' => $testimonial->designation,
                'project' => $testimonial->project === null ? null : ['name' => $testimonial->project->name, 'url' => '/projects/'.$testimonial->project->slug],
                'photo' => self::image($testimonial->photo),
                'is_nrb' => $testimonial->is_nrb,
            ])->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function partners(): array
    {
        return array_values(Partner::query()->published()->with('logo.media')->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Partner $partner): array => [
                'id' => $partner->id,
                'name' => $partner->name,
                'logo' => self::image($partner->logo),
                'url' => $partner->url,
                'category' => $partner->category->value,
            ])->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function team(string $group): array
    {
        return array_values(TeamMember::query()->published()->where('group', $group)->with('photo.media')
            ->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (TeamMember $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->role_title,
                'photo' => self::image($member->photo),
                'bio' => $member->bio,
                'linkedin_url' => $member->linkedin_url,
                // FR-SOC-004: the address leaves the server only when the member asked for it to show.
                'email' => $member->show_email ? $member->email : null,
            ])->all());
    }

    /**
     * Head office first, then the editors' order.
     *
     * @return list<array<string, mixed>>
     */
    public static function offices(): array
    {
        return array_values(Office::query()->orderByDesc('is_head_office')->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Office $office): array => [
                'id' => $office->id,
                'name' => $office->name,
                'address' => $office->address,
                'city' => $office->city,
                'postal_code' => $office->postal_code,
                'phones' => $office->phones,
                'email' => $office->email,
                'hours' => $office->hours,
                'latitude' => $office->latitude,
                'longitude' => $office->longitude,
                'is_head_office' => $office->is_head_office,
            ])->all());
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function image(?MediaAsset $asset): ?array
    {
        return $asset === null ? null : MediaImageResource::make($asset)->resolve();
    }
}

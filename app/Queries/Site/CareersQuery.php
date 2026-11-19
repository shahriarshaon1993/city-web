<?php

namespace App\Queries\Site;

use App\Enums\JobStatus;
use App\Models\JobPost;
use App\Models\Office;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Reads of the public careers pages (PG-15 / PG-16): the roles that take applications, one job by slug
 * and the values of the three filter rows. Drafts and the Recycle bin never appear. The tables are small
 * (a company hires a few roles at a time), so nothing is cached.
 */
final class CareersQuery
{
    /** Jobs without an office are based on the project sites (FR-CAR-001 `office_id` null). */
    public const string SITES_SLUG = 'project-sites';

    public const string SITES_LABEL = 'Project sites';

    /**
     * Every role that takes applications, newest first.
     *
     * @return Collection<int, JobPost>
     */
    public static function open(): Collection
    {
        return JobPost::query()->acceptingApplications()->with('office')->orderByDesc('published_at')->orderByDesc('id')->get();
    }

    /**
     * A job that is public (Open or Closed); a draft is null. The 30-day window of FR-CAR-003 is the
     * caller's decision (`JobPost::isGone`).
     */
    public static function find(string $slug): ?JobPost
    {
        return JobPost::query()->with(['office', 'seoMeta.ogImage'])->where('slug', $slug)->where('status', '!=', JobStatus::Draft->value)->first();
    }

    /**
     * Any job by slug, drafts included (the signed preview).
     */
    public static function findAny(string $slug): ?JobPost
    {
        return JobPost::query()->with(['office', 'seoMeta.ogImage'])->where('slug', $slug)->first();
    }

    /**
     * Up to four other open roles, for the page of a closed one.
     *
     * @return Collection<int, JobPost>
     */
    public static function others(JobPost $job): Collection
    {
        return self::open()->where('id', '!=', $job->id)->take(4)->values();
    }

    /**
     * Office id → the URL value of the location facet: the slug of its name, `-{id}` added when two
     * offices share a name. Stable for every office, whether or not it has an open job today.
     *
     * @return array<int, string>
     */
    public static function officeSlugs(): array
    {
        $slugs = [];
        $taken = [self::SITES_SLUG => true];

        foreach (Office::withTrashed()->orderBy('id')->get(['id', 'name']) as $office) {
            $slug = Str::slug($office->name) ?: 'office';

            if (isset($taken[$slug])) {
                $slug .= '-'.$office->id;
            }

            $taken[$slug] = true;
            $slugs[$office->id] = $slug;
        }

        return $slugs;
    }

    /** The URL value of the department facet (free text → slug). */
    public static function departmentSlug(string $department): string
    {
        return Str::slug($department) ?: 'other';
    }
}

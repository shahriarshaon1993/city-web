<?php

namespace App\Support\Slugs;

use App\Models\Location;
use App\Models\Project;
use App\Support\Pages\ReservedSlugs;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * VAL-04 / DEL-08: is this slug free? The answer feeds the ✓ / ✕ next to every slug field and
 * names the reason, including an item in the Recycle bin that holds the slug. A phase that adds a
 * slug-bearing entity registers it in SOURCES (key → model, column); `site` is the top-level URL space.
 */
final class SlugAvailability
{
    public const string PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** The top-level URL space of the website (custom pages), checked against reserved slugs. */
    public const string SITE = 'site';

    /**
     * Source key → [model class, slug column]; each slug-bearing entity adds itself here.
     *
     * @return array<string, array{0: class-string<Model>, 1: string}>
     */
    public static function sources(): array
    {
        return [
            'project' => [Project::class, 'slug'],
            'location' => [Location::class, 'slug'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function sourceKeys(): array
    {
        return [self::SITE, ...array_keys(self::sources())];
    }

    /**
     * @return array{slug: string, available: bool, reason: string|null, bin: array{label: string, url: string}|null, suggestion: string|null}
     */
    public function check(string $source, string $slug, ?int $ignoreId = null): array
    {
        if (preg_match(self::PATTERN, $slug) !== 1) {
            return $this->answer($slug, false, __('Use lowercase letters, numbers and single hyphens, e.g. my-new-page.'));
        }

        if ($source === self::SITE && ReservedSlugs::isReserved($slug)) {
            return $this->answer($slug, false, __('“:slug” is reserved by the website. Choose another URL.', ['slug' => $slug]), suggestion: $slug.'-2');
        }

        $registered = self::sources()[$source] ?? null;

        if ($registered === null) {
            return $this->answer($slug, true);
        }

        [$class, $column] = $registered;
        $query = $class::query()->where($column, $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        // Items in the bin count too (DEL-08); a model without soft deletes has no such scope.
        $holder = $query->withoutGlobalScope(SoftDeletingScope::class)->first();

        if ($holder === null) {
            return $this->answer($slug, true);
        }

        if ($holder instanceof Trashable && method_exists($holder, 'trashed') && $holder->trashed()) {
            return $this->answer(
                $slug,
                false,
                __('This URL belongs to “:name” in the Recycle bin — restore it or delete it forever.', ['name' => $holder->binLabel()]),
                ['label' => $holder->binLabel(), 'url' => route('admin.recycle-bin.index', ['search' => $holder->binLabel()])],
            );
        }

        return $this->answer($slug, false, __('This URL is already used by another item.'), suggestion: $slug.'-2');
    }

    /**
     * @param  array{label: string, url: string}|null  $bin
     * @return array{slug: string, available: bool, reason: string|null, bin: array{label: string, url: string}|null, suggestion: string|null}
     */
    private function answer(string $slug, bool $available, ?string $reason = null, ?array $bin = null, ?string $suggestion = null): array
    {
        return ['slug' => $slug, 'available' => $available, 'reason' => $reason, 'bin' => $bin, 'suggestion' => $suggestion];
    }
}

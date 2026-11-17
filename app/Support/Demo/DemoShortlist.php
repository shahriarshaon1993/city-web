<?php

namespace App\Support\Demo;

/**
 * Shortlist and compare (PG-20 / PG-21, FR-SHORT-002/003) template copy and the `?ids=` parsing; the
 * projects themselves come from App\Queries\Site\ShortlistProjects since 13.3. Copy moves to
 * Settings in 16.x.
 */
final class DemoShortlist
{
    /** FR-SHORT-001, same as SHORTLIST_MAX in useShortlist.ts. */
    public const int SHORTLIST_MAX = 20;

    /** FR-SHORT-003, same as COMPARE_MAX in useCompare.ts. */
    public const int COMPARE_MAX = 3;

    /**
     * PG-20 / PG-21 copy (template text; Settings in 16.x).
     *
     * @return array<string, string>
     */
    public static function copy(): array
    {
        return [
            'shortlist_title' => 'Your shortlist',
            'shortlist_description' => 'The projects you saved on this device.',
            'shortlist_note' => 'Saved on this device only. Clearing your browser data removes the list.',
            'shortlist_empty_title' => 'Nothing saved yet',
            'shortlist_empty_body' => 'Tap the heart on any project to keep it here and compare it later.',
            'shortlist_unavailable_body' => 'Your browser does not allow this site to save data, so the shortlist cannot be kept. Private browsing often causes this.',
            'shortlist_enquire_overline' => 'Your shortlist',
            'shortlist_enquire_title' => 'Enquire about your shortlist',
            'shortlist_enquire_text' => 'Choose the projects you want to hear about. Our sales team calls you back within one working day.',
            'shortlist_compare_hint' => 'Tick “Compare” on two or three projects to compare them.',
            'compare_title' => 'Compare projects',
            'compare_description' => 'Compare up to three Gemcon City projects side by side.',
            'compare_intro' => 'The key facts of up to three projects, side by side. Remove a project or add another at any time.',
            'compare_empty_title' => 'Choose projects to compare',
            'compare_empty_body' => 'Tick “Compare” on two or three project cards, then open the comparison from the bar at the bottom of the screen.',
            'compare_legend' => 'Tinted rows: the values differ',
            'compare_add' => 'Add a project',
            'compare_amenities' => 'Amenities',
            'price_on_request' => 'Price on request',
        ];
    }

    /**
     * `?ids=3,1,7` → distinct positive IDs in the given order, at most `$max`.
     *
     * @return list<int>
     */
    public static function ids(mixed $raw, int $max): array
    {
        if (! is_string($raw)) {
            return [];
        }

        $ids = [];

        foreach (explode(',', $raw) as $part) {
            $part = trim($part);

            if (preg_match('/^[1-9]\d{0,8}$/', $part) === 1 && ! in_array((int) $part, $ids, true)) {
                $ids[] = (int) $part;
            }
        }

        return array_slice($ids, 0, $max);
    }
}

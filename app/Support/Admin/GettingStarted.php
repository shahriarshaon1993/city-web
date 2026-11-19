<?php

namespace App\Support\Admin;

use App\Models\Location;
use App\Models\MenuItem;
use App\Models\Office;
use App\Models\Page;
use App\Models\Project;
use App\Support\Settings\Settings;

/**
 * HELP-05: the "Getting started" checklist of a new site. Every step is ticked from what is in
 * the database (never from a click), so a step done elsewhere ticks itself.
 */
final class GettingStarted
{
    public const string DISMISSED_GROUP = 'dashboard';

    public const string DISMISSED_KEY = 'getting_started_dismissed';

    /**
     * @return list<array{key: string, label: string, hint: string, href: string, done: bool}>
     */
    public static function steps(): array
    {
        $contact = Settings::get('contact', 'emails');

        return [
            [
                'key' => 'company',
                'label' => 'Add your logo and contact details',
                'hint' => 'The logo, hotline and email shown in the header, footer and enquiry panels.',
                'href' => url('admin/settings/general'),
                'done' => (Settings::get('general', 'logo_dark') || Settings::get('general', 'logo_light'))
                    && is_array($contact) && $contact !== [],
            ],
            [
                'key' => 'offices',
                'label' => 'Add your offices',
                'hint' => 'Addresses and opening hours for the contact page and footer.',
                'href' => route('admin.offices.index'),
                'done' => Office::query()->exists(),
            ],
            [
                'key' => 'locations',
                'label' => 'Add the cities and areas you build in',
                'hint' => 'Projects are grouped by area, and each area gets its own page.',
                'href' => route('admin.locations.index'),
                'done' => Location::query()->exists(),
            ],
            [
                'key' => 'project',
                'label' => 'Publish your first project',
                'hint' => 'A project appears on the site only once it is published.',
                'href' => route('admin.projects.index'),
                'done' => Project::query()->published()->exists(),
            ],
            [
                'key' => 'home',
                'label' => 'Build the home page',
                'hint' => 'Add blocks to the home page: hero, featured projects, news.',
                'href' => self::homeUrl(),
                'done' => Page::query()->where('slug', Page::HOME)->whereHas('blocks')->exists(),
            ],
            [
                'key' => 'menus',
                'label' => 'Set up the menus',
                'hint' => 'The header, footer and mobile navigation.',
                'href' => route('admin.menus.index'),
                'done' => MenuItem::query()->exists(),
            ],
            [
                'key' => 'legal',
                'label' => 'Publish the legal pages',
                'hint' => 'Privacy policy and terms, linked under every consent checkbox.',
                'href' => route('admin.pages.index'),
                'done' => Page::query()->published()->where('template', Page::LEGAL)->exists(),
            ],
            [
                'key' => 'consent',
                'label' => 'Check the form consent text',
                'hint' => 'The sentence next to each consent checkbox is saved with every lead.',
                'href' => url('admin/settings/forms'),
                'done' => Settings::stored('forms', 'consent') !== null,
            ],
            [
                'key' => 'analytics',
                'label' => 'Connect analytics',
                'hint' => 'A Google Tag Manager or Google Analytics ID. Nothing loads before a visitor consents.',
                'href' => url('admin/settings/analytics'),
                'done' => filled(Settings::get('analytics', 'gtm_id')) || filled(Settings::get('analytics', 'ga4_id')),
            ],
        ];
    }

    /** Dismissed on purpose, or every step done: the card is gone. */
    public static function visible(): bool
    {
        if (Settings::stored(self::DISMISSED_GROUP, self::DISMISSED_KEY) === true) {
            return false;
        }

        return in_array(false, array_column(self::steps(), 'done'), true);
    }

    private static function homeUrl(): string
    {
        $home = Page::query()->where('slug', Page::HOME)->value('id');

        return $home === null ? route('admin.pages.index') : route('admin.pages.edit', $home);
    }
}

<?php

namespace App\Support\Settings\Definitions;

use App\Support\Settings\Field;
use App\Support\Settings\ScriptSnippet;
use App\Support\Settings\SettingsGroup;

/**
 * Settings tabs for search engines, behaviour and integrations (spec/cms.md §8.2: SEO, Search,
 * Motion, Maintenance, Analytics, Scripts, Webhook) plus the error-page copy (DEC-41).
 */
final class SystemGroups
{
    /**
     * @return list<SettingsGroup>
     */
    public static function all(): array
    {
        return [
            self::seo(),
            self::search(),
            self::motion(),
            self::errors(),
            self::maintenance(),
            self::analytics(),
            self::scripts(),
            self::webhook(),
        ];
    }

    private static function seo(): SettingsGroup
    {
        $project = ['name', 'bedrooms_range', 'segment_label', 'segment_label_lower', 'area', 'city', 'size_range', 'status_label', 'handover'];
        $location = ['area', 'city', 'segment_label'];

        return new SettingsGroup('seo', 'SEO', 'How page titles and descriptions are built, and the codes that prove you own the site to Google and Bing.', 'Search engines', SettingsGroup::MANAGE, [
            'Page titles' => [
                Field::text('title_template', 'Title template')->required()->max(80)->tokens(['title'], ['title'])->default('{title} | Gemcon City')
                    ->help('Every page title is built from this. {title} is replaced by the page name.'),
                Field::text('home_title', 'Home page title')->required()->max(70)->default('Gemcon City Limited – Real Estate Developer in Bangladesh')
                    ->help('The home page is not built from the template. Up to 70 characters.'),
                Field::textarea('home_description', 'Home page description')->required()->max(170)->rows(3)
                    ->default('Apartments, commercial spaces and land projects by Gemcon City Limited in Dhaka and across Bangladesh. Explore ongoing and upcoming projects and book a site visit.')
                    ->help('The grey text under the title in Google. Aim for 120–160 characters; the limit is 170.'),
            ],
            'Project pages' => [
                Field::text('project_title_template', 'Title template')->required()->max(120)->tokens($project)
                    ->default('{name} – {bedrooms_range} Bed {segment_label} in {area}, {city} | Gemcon City')
                    ->help('Parts without a value are left out. You can use '.self::tokenList($project).'.'),
                Field::textarea('project_description_template', 'Description template')->required()->max(300)->rows(3)->tokens($project)
                    ->default('{name} by Gemcon City: {size_range} sft {segment_label_lower} in {area}, {city}. {status_label}, handover {handover}. Book a site visit.')
                    ->help('Shortened to fit Google. You can use '.self::tokenList($project).'.'),
            ],
            'Location pages' => [
                Field::text('location_title_template', 'Area title template')->required()->max(120)->tokens($location)
                    ->default('Flats & Commercial Spaces in {area}, {city} | Gemcon City')
                    ->help('You can use '.self::tokenList(['area', 'city']).'.'),
                Field::text('location_segment_title_template', 'Area and project type title template')->required()->max(120)->tokens($location)
                    ->default('{segment_label} in {area}, {city} | Gemcon City')
                    ->help('You can use '.self::tokenList($location).'.'),
            ],
            'Visibility and verification' => [
                Field::bool('noindex', 'Ask search engines not to index the site')
                    ->help('Always on for local and staging copies, whatever this says. Turn it on in production only while the site is not ready to be found.'),
                Field::text('gsc_verification', 'Google Search Console code')->max(100)->mono()
                    ->pattern('/^[A-Za-z0-9_\-]{20,100}$/', 'Paste only the code from the meta tag\'s content (letters, numbers, - and _).')
                    ->help('The content value of the google-site-verification tag. Leave empty if you verify another way.'),
                Field::text('bing_verification', 'Bing Webmaster code')->max(64)->mono()
                    ->pattern('/^[A-Fa-f0-9]{32}$/', 'Paste only the 32-character code from the msvalidate.01 tag.')
                    ->help('The content value of the msvalidate.01 tag.'),
            ],
        ], [
            'Page titles' => 'Titles of 50–60 characters read best in search results.',
            'Visibility and verification' => 'robots.txt is edited under SEO → Robots.txt.',
        ]);
    }

    private static function search(): SettingsGroup
    {
        return new SettingsGroup('search', 'Search', 'The size ranges and paging of the project search.', 'Site', SettingsGroup::MANAGE, [
            'Project search' => [
                Field::list('size_bands', 'Size ranges')->ofObject([
                    Field::text('label', 'Label')->required()->max(30),
                    Field::int('min', 'From (sft)')->min(0)->max(100000),
                    Field::int('max', 'To (sft)')->min(0)->max(100000),
                ])->minItems(1)->maxItems(8)->default([
                    ['label' => '< 1,200 sft', 'min' => null, 'max' => 1200],
                    ['label' => '1,200–1,600 sft', 'min' => 1200, 'max' => 1600],
                    ['label' => '1,600–2,000 sft', 'min' => 1600, 'max' => 2000],
                    ['label' => '2,000–2,500 sft', 'min' => 2000, 'max' => 2500],
                    ['label' => '2,500+ sft', 'min' => 2500, 'max' => null],
                ])->help('The choices in the Size filter, smallest first. Leave "From" empty for the first range and "To" empty for the last.'),
                Field::int('price_filter_min_projects', 'Show the price filter from … projects')->required()->min(0)->max(100)->default(5)
                    ->help('The price filter appears once this many projects show a price. Prices marked hidden never count.'),
                Field::int('results_per_page', 'Projects per page')->required()->min(6)->max(48)->default(12)
                    ->help('Between 6 and 48.'),
            ],
        ]);
    }

    private static function motion(): SettingsGroup
    {
        return new SettingsGroup('motion', 'Motion', 'Animations and smooth scrolling. A visitor who asks their device for reduced motion never sees them.', 'Site', SettingsGroup::MANAGE, [
            'Animation' => [
                Field::bool('animations_enabled', 'Animations')->default(true)
                    ->help('Reveal effects, counters and page transitions. Turn off to make the whole site static.'),
                Field::bool('smooth_scroll_enabled', 'Smooth scrolling')->default(true),
                Field::bool('hero_video_mobile', 'Play the hero video on phones')
                    ->help('Off by default: a video costs a phone visitor data and battery. They see the poster photo instead.'),
            ],
        ]);
    }

    private static function errors(): SettingsGroup
    {
        $pair = fn (int $status, string $title, string $message): array => [
            Field::text($status.'_title', 'Heading')->required()->max(70)->default($title),
            Field::textarea($status.'_message', 'Message')->required()->max(300)->rows(2)->default($message),
        ];

        return new SettingsGroup('errors', 'Error pages', 'What visitors read when a page is missing, expired or broken.', 'Site', SettingsGroup::MANAGE, [
            'Page not found (404)' => [
                ...$pair(404, 'We could not find that page', 'The page may have moved or no longer exists. Try a search, or start from one of our popular projects.'),
                Field::text('popular_heading', 'Heading above the suggested projects')->required()->max(60)->default('Popular projects'),
            ],
            'Removed (410)' => $pair(410, 'This page is no longer available', 'The project, article or job you were looking for has been removed.'),
            'Expired (419)' => $pair(419, 'This page has expired', 'For your security the form timed out. Please reload the page and try again.'),
            'Too many requests (429)' => $pair(429, 'Too many requests', 'Please wait a moment before trying again.'),
            'Server error (500)' => $pair(500, 'Something went wrong', 'We have been notified and are fixing it. Please try again in a few minutes.'),
            'Maintenance (503)' => [
                Field::text('503_title', 'Heading')->required()->max(70)->default('We will be right back')
                    ->help('The message under it is on the Maintenance tab.'),
            ],
        ]);
    }

    private static function maintenance(): SettingsGroup
    {
        return new SettingsGroup('maintenance', 'Maintenance', 'What visitors see while the site is switched off for maintenance, and who can still get in.', 'Site', SettingsGroup::INTEGRATIONS, [
            'Maintenance page' => [
                Field::textarea('message', 'Message')->required()->max(300)->rows(3)
                    ->default('We are updating the website and will be back shortly. For urgent enquiries please call our hotline.'),
                Field::list('allowed_ips', 'Allowed IP addresses')->of(Field::text('ip', 'IP address')->max(45)->rule('ip'))->maxItems(20)
                    ->help('These addresses still see the real site during maintenance. One per row, e.g. 203.0.113.10.'),
            ],
        ]);
    }

    private static function analytics(): SettingsGroup
    {
        return new SettingsGroup('analytics', 'Analytics', 'Connect Google Tag Manager or Google Analytics. Nothing loads until a visitor accepts analytics cookies.', 'Integrations', SettingsGroup::INTEGRATIONS, [
            'Google' => [
                Field::text('gtm_id', 'Google Tag Manager container ID')->max(12)->mono()->upper()->placeholder('GTM-ABC1234')
                    ->default(config('services.analytics.gtm_id'))
                    ->pattern('/^GTM-[A-Z0-9]{6,8}$/', 'Enter a container ID like GTM-ABC1234.')
                    ->help('Preferred. If you also set a Google Analytics ID below, Analytics loads through Tag Manager only.'),
                Field::text('ga4_id', 'Google Analytics 4 measurement ID')->max(14)->mono()->upper()->placeholder('G-ABCDEFGH12')
                    ->default(config('services.analytics.ga4_id'))
                    ->pattern('/^G-[A-Z0-9]{8,12}$/', 'Enter a measurement ID like G-ABCDEFGH12.')
                    ->help('Used only when there is no Tag Manager ID.'),
            ],
        ], [
            'Google' => 'The site sends its own page_view on every page, so turn off the automatic one in your Tag Manager container.',
        ]);
    }

    private static function scripts(): SettingsGroup
    {
        $help = 'Only <script src="https://…"> tags from '.implode(', ', ScriptSnippet::EXAMPLE_HOSTS).' and similar approved hosts, and <noscript> blocks, are accepted. Use the Analytics tab for Google Tag Manager.';

        return new SettingsGroup('scripts', 'Scripts', 'Extra tracking tags, for example an advertising pixel. They load only for visitors who accepted marketing cookies.', 'Integrations', SettingsGroup::INTEGRATIONS, [
            'Custom code' => [
                Field::textarea('head_html', 'In the <head>')->max(5000)->rows(8)->mono()->rule(ScriptSnippet::rule(...))->help($help),
                Field::textarea('body_end_html', 'Before </body>')->max(5000)->rows(8)->mono()->rule(ScriptSnippet::rule(...))->help('Same rules as above.'),
            ],
        ]);
    }

    private static function webhook(): SettingsGroup
    {
        return new SettingsGroup('webhook', 'Webhook', 'Send every new lead to another system, such as a CRM.', 'Integrations', SettingsGroup::INTEGRATIONS, [
            'Lead webhook' => [
                Field::bool('enabled', 'Send leads to the webhook')
                    ->help('Off by default.'),
                Field::url('url', 'Webhook URL')->placeholder('https://')->allowHttp(app()->isLocal())
                    ->help(app()->isLocal() ? 'Must start with https:// (http:// is accepted on this local copy only). Each lead is sent as JSON with a signature header.' : 'Must start with https://. Each lead is sent as JSON with a signature header.'),
                Field::secret('secret', 'Signing secret')->max(200)
                    ->help('Every request carries X-Signature: sha256=… (HMAC-SHA256 of the body with this secret) so the receiver can check it came from this site. Required: nothing is sent without it. Never shown again after saving.'),
            ],
        ], [
            'Lead webhook' => 'Sent when a lead is created or changes (status, owner, follow-up, note, repeat enquiry). A failed delivery is retried after 1, 5 and 15 minutes. Recent deliveries are listed below the form and kept for 30 days.',
        ]);
    }

    /**
     * @param  list<string>  $tokens
     */
    private static function tokenList(array $tokens): string
    {
        return implode(', ', array_map(fn (string $token): string => '{'.$token.'}', $tokens));
    }
}

<?php

namespace App\Support\Settings\Definitions;

use App\Support\Settings\Field;
use App\Support\Settings\Settings;
use App\Support\Settings\SettingsGroup;

/**
 * Settings tabs about the company and the site's chrome (spec/cms.md §8.2: General, Contact,
 * Social, Header, Footer, Legal & cookies) plus the Contact page copy (PG-17, LAY-CONTACT).
 * Defaults are the placeholder copy of the demo (DEC-17); the client replaces it in the admin.
 */
final class SiteGroups
{
    /**
     * @return list<SettingsGroup>
     */
    public static function all(): array
    {
        return [
            self::general(),
            self::contact(),
            self::contactPage(),
            self::social(),
            self::header(),
            self::footer(),
            self::legal(),
        ];
    }

    private static function general(): SettingsGroup
    {
        return new SettingsGroup('general', 'General', 'The company name and the logos, favicon and share image used across the site.', 'Company', SettingsGroup::MANAGE, [
            'Identity' => [
                Field::text('site_name', 'Site name')->required()->max(60)->default('Gemcon City')
                    ->help('Shown in the browser tab, in search results and when a page is shared. Up to 60 characters.'),
                Field::text('tagline', 'Tagline')->max(120)->default('Homes and workplaces built to last')
                    ->help('One short line about the company. Up to 120 characters.'),
            ],
            'Logos and icons' => [
                Field::media('logo_light', 'Logo for dark backgrounds')
                    ->mediaLimits('PNG, JPG, WebP or SVG')
                    ->help('The light-coloured logo, shown over photos in the transparent header and on dark bands. Leave empty to use the built-in logo.'),
                Field::media('logo_dark', 'Logo for light backgrounds')
                    ->mediaLimits('PNG, JPG, WebP or SVG')
                    ->help('The dark-coloured logo, shown on the white header and in the footer. Leave empty to use the built-in logo.'),
                Field::media('logo_mark', 'Logo mark (square)')
                    ->mediaLimits('A square PNG, JPG, WebP or SVG', square: true)
                    ->help('The symbol alone, shown in the mobile header and the error pages. Must be square.'),
                Field::media('favicon', 'Favicon', ['image'])
                    ->mediaLimits('PNG, at least 512 × 512 px, square', 512, 512, true, ['image/png'])
                    ->help('The icon in the browser tab. A square PNG of at least 512 × 512 px; leave empty to keep the built-in icon.'),
                Field::media('default_og_image', 'Default share image', ['image'])
                    ->mediaLimits('At least 1200 × 630 px', 1200, 630)
                    ->help('Shown when a page is shared on Facebook, WhatsApp or LinkedIn and has no image of its own. At least 1200 × 630 px; leave empty for the built-in photo.'),
            ],
            'Parent company' => [
                Field::text('parent_company_name', 'Parent company name')->max(80)->default('Gemcon Group')
                    ->help('Shown in the footer when "Show parent company" is on (Footer tab).'),
                Field::url('parent_company_url', 'Parent company website')->placeholder('https://')
                    ->help('The footer links the name here. Must start with https://.'),
            ],
        ]);
    }

    private static function contact(): SettingsGroup
    {
        return new SettingsGroup('contact', 'Contact', 'The phone numbers, emails and WhatsApp number shown in the header, footer, contact page and enquiry panels.', 'Company', SettingsGroup::MANAGE, [
            'Phone and email' => [
                Field::phone('hotline', 'Hotline')->required()->default('+8809600000000')->placeholder('+880 9600-000000')
                    ->help('The number behind every "Call" button, in the header and in the mobile bar.'),
                Field::list('phones', 'Other phone numbers')->of(Field::phone('phone', 'Phone number'))->maxItems(5)
                    ->default(['+8809600000000', '+8801700000000'])
                    ->help('Listed on the contact page and in the footer. Up to 5.'),
                Field::list('emails', 'Email addresses')->of(Field::email('email', 'Email address'))->maxItems(5)
                    ->default(['info@gemconcity.com', 'sales@gemconcity.com'])
                    ->help('Listed on the contact page and in the footer. Up to 5.'),
                Field::text('office_hours_text', 'Opening hours')->max(120)->default('Saturday–Thursday, 9:30 am – 6:30 pm')
                    ->help('One line, shown beside the phone numbers. Up to 120 characters.'),
            ],
            'WhatsApp' => [
                Field::phone('whatsapp_number', 'WhatsApp number')->default('+8801700000000')->placeholder('+8801700000000')
                    ->pattern('/^\+[1-9][0-9]{7,14}$/', 'Enter the number with the country code and no spaces, e.g. +8801712345678.')
                    ->help('With the country code and no spaces or dashes. The WhatsApp buttons open a chat with this number; leave empty to hide them.'),
            ],
        ]);
    }

    private static function contactPage(): SettingsGroup
    {
        return new SettingsGroup('contact_page', 'Contact page', 'The headings and intro text of the Contact page.', 'Company', SettingsGroup::MANAGE, [
            'Page and search results' => [
                Field::text('title', 'Page title')->required()->max(70)->default('Contact us')
                    ->help('The page heading and the title in search results. Up to 70 characters.'),
                Field::textarea('description', 'Meta description')->required()->max(170)->rows(3)
                    ->default('Call, message or visit Gemcon City Limited. Opening hours, offices and a form for questions about projects, site visits and after-sales.')
                    ->help('The grey text under the title in Google. Aim for 120–160 characters; the limit is 170.'),
                Field::textarea('intro', 'Intro')->required()->max(400)->rows(3)
                    ->default('Questions about a project, a site visit or your booking? Write to us, call the hotline or visit a sales lounge. We reply within one working day.')
                    ->help('Shown under the page heading. Up to 400 characters.'),
            ],
            'Headings' => [
                Field::text('form_heading', 'Form heading')->required()->max(60)->default('Send us a message'),
                Field::text('form_intro', 'Form intro')->max(160)->default('Tell us how we can help and which number suits you best.'),
                Field::text('details_heading', 'Contact details heading')->required()->max(60)->default('Talk to us directly'),
                Field::text('social_heading', 'Social links heading')->required()->max(60)->default('Follow our progress'),
                Field::text('offices_heading', 'Offices heading')->required()->max(60)->default('Visit us'),
            ],
            'WhatsApp message' => [
                Field::text('whatsapp_text', 'Message text')->required()->max(200)->default("Hi, I'd like to speak to the Gemcon City sales team.")
                    ->help('Written into the chat when a visitor taps WhatsApp on the contact page.'),
                Field::text('whatsapp_text_project', 'Message text about a project')->required()->max(200)->tokens(['project'])
                    ->default("Hi, I'd like to speak to the Gemcon City sales team about {project}.")
                    ->help('Used when the visitor came from a project. {project} is replaced by the project name.'),
            ],
        ]);
    }

    private static function social(): SettingsGroup
    {
        return new SettingsGroup('social', 'Social', 'Profile links shown in the footer and on the contact page. Leave a field empty to hide that network.', 'Company', SettingsGroup::MANAGE, [
            'Profiles' => [
                self::socialLink('facebook', 'Facebook', 'https://www.facebook.com/gemconcity', ['facebook.com', 'fb.com']),
                self::socialLink('instagram', 'Instagram', 'https://www.instagram.com/gemconcity', ['instagram.com']),
                self::socialLink('linkedin', 'LinkedIn', 'https://www.linkedin.com/company/gemconcity', ['linkedin.com']),
                self::socialLink('youtube', 'YouTube', 'https://www.youtube.com/@gemconcity', ['youtube.com', 'youtu.be']),
                Field::url('x', 'X (Twitter)')->hosts(['x.com', 'twitter.com'])->placeholder('https://'),
            ],
        ]);
    }

    /**
     * @param  list<string>  $hosts
     */
    private static function socialLink(string $key, string $label, string $url, array $hosts): Field
    {
        return Field::url($key, $label)->hosts($hosts)->default($url)->placeholder('https://');
    }

    private static function header(): SettingsGroup
    {
        return new SettingsGroup('header', 'Header', 'How the top bar behaves and which button it shows.', 'Site', SettingsGroup::MANAGE, [
            'Behaviour' => [
                Field::bool('sticky', 'Stay at the top while scrolling')->default(true),
                Field::bool('transparent_on_hero', 'Transparent over the home page photo')->default(true)
                    ->help('The header is see-through on pages that open with a photo and turns white when the visitor scrolls.'),
                Field::bool('show_shortlist', 'Show the shortlist heart')->default(true),
                Field::bool('show_phone', 'Show the phone number')->default(true)
                    ->help('The hotline from the Contact tab, beside the button.'),
            ],
            'Call-to-action button' => [
                Field::text('cta_label', 'Button text')->required()->max(20)->default('Enquire')
                    ->help('Up to 20 characters.'),
                Field::link('cta_link', 'Button link')->required()->default('/contact')->placeholder('/contact')
                    ->help('A page on this site such as /contact, or a full https:// link.'),
            ],
        ]);
    }

    private static function footer(): SettingsGroup
    {
        return new SettingsGroup('footer', 'Footer', 'The text and sign-up box at the bottom of every page. The link columns are edited under Menus.', 'Site', SettingsGroup::MANAGE, [
            'Company text' => [
                Field::textarea('about_text', 'About text')->max(300)->rows(3)
                    ->default('Gemcon City Limited develops apartments, commercial spaces and land projects across Bangladesh, with clear titles, honest timelines and after-sales care.')
                    ->help('Shown under the logo. Up to 300 characters.'),
                Field::text('copyright_text', 'Copyright line')->required()->max(160)->tokens(['year'])
                    ->default('© {year} Gemcon City Limited. All rights reserved.')
                    ->help('{year} is replaced by the current year.'),
                Field::bool('show_parent_company', 'Show the parent company')->default(true),
                Field::text('offices_heading', 'Offices heading')->required()->max(40)->default('Offices'),
                Field::text('locations_heading', 'Areas heading')->required()->max(40)->default('Popular areas'),
            ],
            'Newsletter box' => [
                Field::bool('newsletter_enabled', 'Show the newsletter sign-up')->default(true),
                Field::text('newsletter_title', 'Title')->required()->max(70)->default('Project updates in your inbox'),
                Field::textarea('newsletter_text', 'Text')->max(200)->rows(2)->default('New launches, construction progress and offers. No spam, unsubscribe any time.'),
                Field::text('newsletter_success_title', 'Thank-you title')->required()->max(70)->default('You are subscribed')
                    ->help('Shown in place of the form after someone subscribes.'),
                Field::textarea('newsletter_success_text', 'Thank-you text')->max(200)->rows(2)
                    ->default('Thank you. We will send you project updates from time to time; every email has an unsubscribe link.'),
            ],
        ]);
    }

    private static function legal(): SettingsGroup
    {
        $category = fn (string $key, string $label, string $text): Field => Field::object($key, $label, [
            Field::text('label', 'Name')->required()->max(30)->default($label),
            Field::textarea('description', 'Description')->required()->max(200)->rows(2)->default($text),
        ]);

        return new SettingsGroup('legal', 'Legal & cookies', 'The cookie banner and the consent choices visitors make.', 'Site', SettingsGroup::MANAGE, [
            'Cookie banner' => [
                Field::bool('cookie_banner_enabled', 'Show the cookie banner')->default(true)
                    ->help('Turn off only if the site sets no analytics or marketing cookies.'),
                Field::text('banner_title', 'Title')->required()->max(60)->default('Your privacy choices'),
                Field::textarea('banner_text', 'Text')->required()->max(500)->rows(4)
                    ->default('We use essential cookies to run this site. With your permission we also use analytics cookies to understand what is useful, and marketing cookies to measure our campaigns.'),
                Field::link('policy_link', 'Privacy policy link')->required()->default('/privacy-policy')->placeholder('/privacy-policy'),
                Field::int('consent_version', 'Consent version')->required()->min(1)->max(9999)->default(1)
                    ->rule(function (string $attribute, mixed $value, \Closure $fail): void {
                        if ((int) $value < (int) Settings::get('legal', 'consent_version', 1)) {
                            $fail('The version cannot go down. Raise it to ask every visitor for consent again.');
                        }
                    })
                    ->help('Raise this number after you change what the cookies do; every visitor is then asked again.'),
            ],
            'Cookie categories' => [
                $category('category_necessary', 'Essential', 'Keep the site working: security, your saved projects and this choice. Always on.'),
                $category('category_analytics', 'Analytics', 'Anonymous statistics about which pages and projects are useful, so we can improve them.'),
                $category('category_marketing', 'Marketing', 'Measure whether our adverts lead to enquiries. No data is sold.'),
            ],
        ], [
            'Cookie categories' => 'The wording visitors see in the banner. Essential is always on; the other two start off.',
        ]);
    }
}

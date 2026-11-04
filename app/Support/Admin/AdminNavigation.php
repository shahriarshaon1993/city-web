<?php

namespace App\Support\Admin;

use App\Models\User;

/**
 * Sidebar groups and items (design/admin.md §12.1) with the permissions of
 * spec/admin.md §10.2. The registry is the single source for the sidebar,
 * the route permission middleware and the placeholder pages, so what a role
 * sees and what it may open cannot drift apart (ARC-005).
 */
final class AdminNavigation
{
    /**
     * @return array<string, list<NavItem>> group label → items, in display order
     */
    public static function groups(): array
    {
        return [
            'Overview' => [
                new NavItem('dashboard', 'Dashboard', 'admin', 'layout-dashboard', ['dashboard.view'], 'Your numbers, follow-ups and shortcuts in one place.', '18.1', placeholder: false),
            ],
            'Content' => [
                new NavItem('projects', 'Projects', 'admin/projects', 'building-2', ['projects.view'], 'Every development on the site, with units, media and publishing.', '13.2'),
                new NavItem('locations', 'Locations', 'admin/locations', 'map-pin', ['catalog.view'], 'Cities and areas, plus the amenities and facet pages.', '13.2'),
                new NavItem('pages', 'Pages', 'admin/pages', 'layout-template', ['pages.view', 'legal.view'], 'The page builder: home, about, services and legal pages.', '16.2'),
                new NavItem('menus', 'Menus', 'admin/menus', 'list-tree', ['menus.view'], 'Header, footer and mobile navigation.', '12.2'),
                new NavItem('news', 'News', 'admin/news', 'newspaper', ['news.view'], 'Articles, categories and tags.', '16.3'),
                new NavItem('testimonials', 'Testimonials', 'admin/testimonials', 'quote', ['social.view'], 'Customer quotes shown on the site.', '16.4'),
                new NavItem('partners', 'Partners', 'admin/partners', 'handshake', ['social.view'], 'Partner and client logos.', '16.4'),
                new NavItem('faqs', 'FAQs', 'admin/faqs', 'circle-help', ['social.view'], 'Questions and answers, grouped by topic.', '16.4'),
                new NavItem('team', 'Team', 'admin/team', 'users', ['social.view'], 'Leadership and team profiles.', '16.4'),
                new NavItem('offices', 'Offices', 'admin/offices', 'map', ['social.view'], 'Office addresses, hours and contact details.', '16.4'),
                new NavItem('careers', 'Careers', 'admin/careers', 'briefcase', ['jobs.view'], 'Open roles and their descriptions.', '17.1'),
            ],
            'Sales' => [
                new NavItem('leads', 'Leads', 'admin/leads', 'phone-call', ['leads.view-all', 'leads.view-assigned'], 'Enquiries from the website: table, board and follow-ups.', '15.2'),
                new NavItem('subscribers', 'Subscribers', 'admin/subscribers', 'mail', ['subscribers.manage'], 'Newsletter sign-ups, with export and unsubscribe.', '15.2'),
                new NavItem('applications', 'Applications', 'admin/applications', 'file-user', ['applications.manage'], 'Job applications and CVs.', '17.1'),
            ],
            'Library' => [
                new NavItem('media', 'Media', 'admin/media', 'images', ['media.view', 'media.upload', 'media.update'], 'Photos, videos and documents, with folders and focal points.', '11.2'),
            ],
            'SEO' => [
                new NavItem('redirects', 'Redirects', 'admin/redirects', 'corner-up-right', ['redirects.manage', 'seo.view'], 'Send old addresses to their new pages.', '12.3'),
                new NavItem('not-found', '404s', 'admin/404s', 'unlink', ['redirects.manage', 'seo.view'], 'Addresses visitors asked for that do not exist.', '12.3'),
                new NavItem('sitemaps', 'Sitemaps & snapshots', 'admin/sitemaps', 'network', ['seo.view', 'seo.manage'], 'Sitemap status, pre-rendered pages and robots.txt.', '19.2'),
            ],
            'System' => [
                new NavItem('settings', 'Settings', 'admin/settings', 'settings', ['settings.manage', 'settings.integrations'], 'Company details, contact, header, footer, forms and integrations.', '12.1'),
                new NavItem('users', 'Users & roles', 'admin/users', 'user-cog', ['users.manage', 'roles.manage'], 'Invite people, set their role and see what each role may do.', '10.3'),
                new NavItem('activity-log', 'Activity log', 'admin/activity-log', 'history', ['activity.view-all', 'activity.view-own'], 'Who changed what, and when.', '10.3'),
                new NavItem('errors', 'Errors', 'admin/errors', 'triangle-alert', ['maintenance.caches', 'maintenance.manage'], 'Server and browser errors from the site.', '18.2'),
                new NavItem('maintenance', 'Maintenance', 'admin/maintenance', 'wrench', ['maintenance.manage', 'maintenance.caches'], 'Caches, application health and maintenance mode.', '18.2'),
            ],
        ];
    }

    /**
     * @return list<NavItem>
     */
    public static function items(): array
    {
        return array_merge(...array_values(self::groups()));
    }

    public static function find(string $key): ?NavItem
    {
        foreach (self::items() as $item) {
            if ($item->key === $key) {
                return $item;
            }
        }

        return null;
    }

    public static function groupOf(NavItem $item): string
    {
        foreach (self::groups() as $label => $items) {
            foreach ($items as $candidate) {
                if ($candidate->key === $item->key) {
                    return $label;
                }
            }
        }

        return '';
    }

    /**
     * Shared `admin.navigation` prop: only the groups and items the user may
     * open. `count` stays null until the phase that owns the number (11.2,
     * 15.2, 17.1, 13.2).
     *
     * @return list<array{label: string, items: list<array{key: string, label: string, href: string, icon: string, count: int|null}>}>
     */
    public static function forUser(User $user): array
    {
        $groups = [];

        foreach (self::groups() as $label => $items) {
            $visible = [];

            foreach ($items as $item) {
                if ($user->canAny($item->permissions)) {
                    $visible[] = [
                        'key' => $item->key,
                        'label' => $item->label,
                        'href' => url($item->path),
                        'icon' => $item->icon,
                        'count' => null,
                    ];
                }
            }

            if ($visible !== []) {
                $groups[] = ['label' => $label, 'items' => $visible];
            }
        }

        return $groups;
    }
}

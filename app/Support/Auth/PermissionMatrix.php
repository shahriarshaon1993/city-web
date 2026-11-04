<?php

namespace App\Support\Auth;

/**
 * Permission names and the role → permission mapping of spec/admin.md §10.2.
 *
 * Naming: `{group}.{ability}`. A matrix cell "view" is `{group}.view`; a ✓ is
 * `.view` + `.manage` (or the finer abilities of that row). Super Admin passes
 * `Gate::before` (ARC-005) but is synced with every permission so the roles
 * screen shows the full set. Row-level limits the matrix words in brackets
 * ("assigned" leads, "unused only" media, "cannot assign Super Admin") are
 * enforced by the Policies of the phase that builds each screen.
 */
final class PermissionMatrix
{
    /**
     * Permission group → names. Order = display order on the roles screen.
     *
     * @var array<string, list<string>>
     */
    public const array GROUPS = [
        'Dashboard' => ['dashboard.view'],
        'Projects' => ['projects.view', 'projects.write', 'projects.publish', 'projects.delete'],
        'Locations, facets, amenities' => ['catalog.view', 'catalog.manage'],
        'Pages & blocks' => ['pages.view', 'pages.manage'],
        'Legal pages' => ['legal.view', 'legal.manage'],
        'Menus' => ['menus.view', 'menus.manage'],
        'News, categories, tags' => ['news.view', 'news.manage'],
        'Testimonials, partners, FAQs, team, offices' => ['social.view', 'social.manage'],
        'Media' => ['media.view', 'media.upload', 'media.update', 'media.delete', 'media.delete-unused'],
        'Leads' => [
            'leads.view-all', 'leads.view-assigned', 'leads.view-contact', 'leads.update',
            'leads.assign', 'leads.export', 'leads.delete',
        ],
        'Subscribers' => ['subscribers.manage'],
        'Careers' => ['jobs.view', 'jobs.manage', 'applications.manage'],
        'SEO' => ['seo.view', 'seo.manage', 'redirects.manage'],
        'Settings' => ['settings.manage', 'settings.integrations'],
        'Users & roles' => ['users.manage', 'roles.manage'],
        'Activity log' => ['activity.view-all', 'activity.view-own'],
        'Maintenance' => ['maintenance.manage', 'maintenance.caches'],
        'Import' => ['import.content', 'import.leads'],
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }

    /**
     * @return list<string>
     */
    public static function forRole(SystemRole $role): array
    {
        return match ($role) {
            SystemRole::SuperAdmin => self::all(),
            SystemRole::Admin => array_values(array_diff(self::all(), [
                'settings.integrations', 'roles.manage', 'maintenance.manage', 'activity.view-own',
            ])),
            SystemRole::ContentEditor => [
                'dashboard.view',
                'projects.view', 'projects.write', 'projects.publish',
                'catalog.view', 'catalog.manage',
                'pages.view', 'pages.manage',
                'menus.view', 'menus.manage',
                'news.view', 'news.manage',
                'social.view', 'social.manage',
                'media.view', 'media.upload', 'media.update', 'media.delete-unused',
                'subscribers.manage',
                'jobs.view', 'jobs.manage',
                'redirects.manage',
                'activity.view-own',
                'import.content',
            ],
            SystemRole::SalesManager => [
                'dashboard.view',
                'projects.view',
                'media.upload',
                'leads.view-all', 'leads.view-assigned', 'leads.view-contact', 'leads.update',
                'leads.assign', 'leads.export',
                'subscribers.manage',
                'activity.view-own',
                'import.leads',
            ],
            SystemRole::SalesExecutive => [
                'dashboard.view',
                'projects.view',
                'leads.view-assigned', 'leads.view-contact', 'leads.update',
                'activity.view-own',
            ],
            // Leads: all of them, but without phone/email (no `leads.view-contact`).
            SystemRole::Viewer => [
                'dashboard.view',
                'projects.view',
                'catalog.view', 'pages.view', 'legal.view', 'menus.view', 'news.view', 'social.view',
                'media.view',
                'leads.view-all',
                'jobs.view',
                'seo.view',
            ],
        };
    }
}

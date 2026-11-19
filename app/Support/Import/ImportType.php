<?php

namespace App\Support\Import;

use App\Models\User;
use App\Support\Import\Definitions\FaqsImport;
use App\Support\Import\Definitions\LeadsImport;
use App\Support\Import\Definitions\LocationsImport;
use App\Support\Import\Definitions\RedirectsImport;
use App\Support\Import\Definitions\SubscribersImport;

/**
 * The five things the import wizard can bring in (ADM-IO-001), with who may import each (the
 * "Import" row of the permission matrix plus the permission of the thing itself) and where the result
 * is listed.
 */
enum ImportType: string
{
    case Leads = 'leads';
    case Subscribers = 'subscribers';
    case Redirects = 'redirects';
    case Locations = 'locations';
    case Faqs = 'faqs';

    public function label(): string
    {
        return match ($this) {
            self::Leads => 'Leads',
            self::Subscribers => 'Subscribers',
            self::Redirects => 'Redirects',
            self::Locations => 'Locations',
            self::Faqs => 'FAQs',
        };
    }

    public function definition(): ImportDefinition
    {
        return match ($this) {
            self::Leads => new LeadsImport,
            self::Subscribers => new SubscribersImport,
            self::Redirects => new RedirectsImport,
            self::Locations => new LocationsImport,
            self::Faqs => new FaqsImport,
        };
    }

    public function canBeUsedBy(User $user): bool
    {
        return match ($this) {
            self::Leads => $user->can('import.leads'),
            self::Subscribers => $user->canAny(['import.leads', 'import.content']) && $user->can('subscribers.manage'),
            self::Redirects => $user->can('import.content') && $user->can('redirects.manage'),
            self::Locations => $user->can('import.content') && $user->can('catalog.manage'),
            self::Faqs => $user->can('import.content') && $user->can('social.manage'),
        };
    }

    /** The admin list that shows what was imported. */
    public function listUrl(): string
    {
        return route(match ($this) {
            self::Leads => 'admin.leads.index',
            self::Subscribers => 'admin.subscribers.index',
            self::Redirects => 'admin.redirects.index',
            self::Locations => 'admin.locations.index',
            self::Faqs => 'admin.faqs.index',
        }, absolute: false);
    }

    /** @return list<self> */
    public static function usableBy(User $user): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => $type->canBeUsedBy($user)));
    }
}

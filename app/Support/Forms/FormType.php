<?php

namespace App\Support\Forms;

use App\Http\Requests\Site\Forms\BrochureRequest;
use App\Http\Requests\Site\Forms\CallbackRequest;
use App\Http\Requests\Site\Forms\ContactRequest;
use App\Http\Requests\Site\Forms\LandownerRequest;
use App\Http\Requests\Site\Forms\LeadFormRequest;
use App\Http\Requests\Site\Forms\NrbRequest;
use App\Http\Requests\Site\Forms\ProjectInquiryRequest;
use App\Http\Requests\Site\Forms\SiteVisitRequest;

/**
 * The seven public lead types (spec/leads.md, DM-LEAD `type`). The value is
 * the stored type; the URL slug (`/forms/{slug}`, `/thank-you/{slug}`) uses
 * hyphens (AC-LEAD-01).
 */
enum FormType: string
{
    case ProjectInquiry = 'project_inquiry';
    case SiteVisit = 'site_visit';
    case BrochureDownload = 'brochure_download';
    case Callback = 'callback';
    case Contact = 'contact';
    case Landowner = 'landowner';
    case Nrb = 'nrb';

    public function slug(): string
    {
        return str_replace('_', '-', $this->value);
    }

    public static function fromSlug(string $slug): ?self
    {
        return self::tryFrom(str_replace('-', '_', $slug));
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_map(fn (self $type): string => $type->slug(), self::cases());
    }

    /**
     * @return class-string<LeadFormRequest>
     */
    public function requestClass(): string
    {
        return match ($this) {
            self::ProjectInquiry => ProjectInquiryRequest::class,
            self::SiteVisit => SiteVisitRequest::class,
            self::BrochureDownload => BrochureRequest::class,
            self::Callback => CallbackRequest::class,
            self::Contact => ContactRequest::class,
            self::Landowner => LandownerRequest::class,
            self::Nrb => NrbRequest::class,
        };
    }
}

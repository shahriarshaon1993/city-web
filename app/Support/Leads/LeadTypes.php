<?php

namespace App\Support\Leads;

use App\Models\Lead;

/**
 * Plain-word names of the DM-LEAD `type` values for the admin (the seven public forms of spec/leads.md
 * plus the `manual` type of DEC-33), and where a lead came from.
 */
final class LeadTypes
{
    /** @var array<string, string> */
    public const array LABELS = [
        'project_inquiry' => 'Project enquiry',
        'site_visit' => 'Site visit',
        'brochure_download' => 'Brochure',
        'callback' => 'Callback',
        'contact' => 'Contact',
        'landowner' => 'Landowner',
        'nrb' => 'NRB',
        Lead::MANUAL_TYPE => 'Added by hand',
        Lead::IMPORT_TYPE => 'Imported',
    ];

    public static function label(string $type): string
    {
        return self::LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * The campaign source when there is one, else the site the visitor came from, else "Direct".
     */
    public static function source(Lead $lead): string
    {
        if ($lead->type === Lead::MANUAL_TYPE) {
            return 'Entered by staff';
        }

        $campaign = implode(' / ', array_filter([$lead->utm_source, $lead->utm_medium]));

        return $campaign !== '' ? $campaign : ($lead->referrer_host ?: 'Direct');
    }
}

<?php

namespace App\Support\Catalog;

use App\Enums\ProgressStage;

/**
 * Template copy of the project and location pages (PG-04, PG-05/06): section headings, tab labels,
 * enquiry texts. Not catalogue data, so it outlives the demo content; 16.x may move it to Settings.
 * `{project}`, `{area}`, `{city}`, `{segment}` and `{count}` are replaced by the pages.
 */
final class CatalogCopy
{
    public const string PRICE_ON_REQUEST = 'Price on request';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function progressStages(): array
    {
        return array_map(
            fn (ProgressStage $stage): array => ['value' => $stage->value, 'label' => $stage->label()],
            ProgressStage::cases(),
        );
    }

    /**
     * ProjectCopy of types/project.ts.
     *
     * @return array<string, mixed>
     */
    public static function project(): array
    {
        return [
            'overview_heading' => 'Overview',
            'overview_tab' => 'Overview',
            'facts_heading' => 'At a glance',
            'facts_tab' => 'At a glance',
            'amenities_heading' => 'Amenities',
            'amenities_tab' => 'Amenities',
            'units_heading' => 'Unit types and floor plans',
            'units_tab' => 'Plans',
            'gallery_heading' => 'Gallery',
            'gallery_tab' => 'Gallery',
            'price_from_label' => 'Starting from',
            'video_heading' => 'Video and virtual tour',
            'video_tab' => 'Video',
            'tour_label' => 'Take the virtual tour',
            'team_heading' => 'Design team',
            'progress_heading' => 'Construction progress',
            'progress_tab' => 'Progress',
            'progress_timeline_heading' => 'All updates',
            'location_heading' => 'Location',
            'location_tab' => 'Location',
            'nearby_heading' => 'Nearby',
            'documents_heading' => 'Brochure and documents',
            'documents_tab' => 'Documents',
            'faq_heading' => 'Questions and answers',
            'similar_heading' => 'Similar projects',
            'price_on_request' => self::PRICE_ON_REQUEST,
            'sales_name' => 'Gemcon sales team',
            'enquire_title' => 'Enquire about {project}',
            'enquire_text' => 'Tell us what you are looking for and our sales team will call you back within one working day.',
            'visit_title' => 'Book a site visit to {project}',
            'visit_text' => 'Pick a day and time that suits you; our sales team confirms the visit by phone. Video visits are available for buyers abroad.',
            'brochure_title' => 'Download the {project} brochure',
            'brochure_text' => 'Leave your name and phone number and the download starts at once.',
            'dialog_alternative' => 'Prefer to talk now?',
            'progress_stages' => self::progressStages(),
        ];
    }

    /**
     * PG-05/PG-06 copy.
     *
     * @return array<string, string>
     */
    public static function locations(): array
    {
        return [
            'directory_title' => 'Locations',
            'directory_intro' => 'We build in the neighbourhoods people want to live and work in, from established addresses like Gulshan and Dhanmondi to growing towns in the north and east of Dhaka and the hills of Chattogram. Choose an area to see its projects, prices and what it is like to live there.',
            'directory_description' => 'Explore Gemcon City projects by location: apartments, commercial spaces and land in Dhaka, Chattogram and Narayanganj, area by area.',
            'area_title' => 'Flats and commercial spaces in {area}',
            'segment_title' => '{segment} in {area}',
            'segment_template' => '<p>{count} {segment_lower} projects by Gemcon City in {area}, {city}, with sizes, prices and handover dates on each project page.</p>',
            'facts_heading' => 'At a glance',
            'projects_heading' => 'Projects in {area}',
            'map_heading' => '{area} on the map',
            'nearby_heading' => 'Nearby areas',
            'faq_heading' => 'Questions about {area}',
            'empty_title' => 'No projects in {area} yet',
            'empty_body' => 'We are not building here at the moment. Here are our newest projects in other areas.',
            'newest_heading' => 'Newest projects',
            'no_projects' => 'No projects yet',
        ];
    }
}

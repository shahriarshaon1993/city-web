<?php

namespace App\Support\Careers;

/**
 * Labels and copy of PG-15/16 (CMS-000a: copy comes from props, not from Vue). Not content: the job
 * posts themselves are edited under Careers in the admin; nothing here is.
 */
final class CareersCopy
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'title' => 'Careers at Gemcon City',
            'description' => 'Open roles at Gemcon City Limited in sales, engineering, architecture, finance and marketing. Apply online with your CV.',
            'roles_heading' => 'Open roles',
            'roles_count' => '{n} open roles',
            'roles_count_one' => '1 open role',
            'all_label' => 'All',
            'department_label' => 'Department',
            'location_label' => 'Location',
            'type_label' => 'Type',
            'apply_by_label' => 'Apply by',
            'no_match_title' => 'No roles match these filters',
            'no_match_body' => 'Try another department or location, or see every open role.',
            'no_match_action' => 'Show all roles',
            'no_openings_title' => 'No openings right now',
            'no_openings_body' => 'We have no open roles at the moment, but we are always glad to meet good people. Send us your CV and we will contact you when a role fits.',
            'cv_heading' => 'Send us your CV',
            'cv_intro' => 'Tell us a little about yourself and attach your CV. The HR team reads every application and keeps it on file for twelve months.',
            'about_heading' => 'About the role',
            'responsibilities_heading' => 'What you will do',
            'requirements_heading' => 'What you bring',
            'benefits_heading' => 'What we offer',
            'summary_heading' => 'Role at a glance',
            'department_term' => 'Department',
            'location_term' => 'Location',
            'type_term' => 'Employment type',
            'experience_term' => 'Experience',
            'salary_term' => 'Salary',
            'deadline_term' => 'Deadline',
            'apply_now' => 'Apply now',
            'apply_heading' => 'Apply for this role',
            'apply_intro' => 'Fill in your details and attach your CV. You will hear from the HR team within ten working days if your application is shortlisted.',
            'apply_side' => '<p>Only the HR team and the hiring manager see your application. Questions about a role? Email <a href="mailto:careers@gemconcity.com">careers@gemconcity.com</a>.</p>',
            'closed_title' => 'Applications closed',
            'closed_text' => 'This role stopped taking applications on {date}.',
            'closed_action' => 'See open roles',
            'other_roles_heading' => 'Other open roles',
            'success_title' => 'Thank you, we have your application',
            'success_text' => 'The HR team reads every application. If yours is shortlisted, we will contact you within ten working days.',
            'success_toast' => 'Your application has been sent.',
            'cv_success_title' => 'Thank you, we have your CV',
            'cv_success_text' => 'We keep it on file for twelve months and will contact you when a role fits.',
        ];
    }
}

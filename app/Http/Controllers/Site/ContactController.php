<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoBlocks;
use App\Support\Demo\DemoContent;
use App\Support\Demo\DemoProjects;
use App\Support\Forms\FormOptions;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-17 `/contact` (LAY-CONTACT): the contact form, the contact card and the
 * offices. `?project={slug}&type=site_visit|brochure_download` is where the
 * project page's panel links without JavaScript (DEC-56): it prefills the
 * project and the "Sales" subject. `?subject=` preselects a subject.
 */
class ContactController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $copy = DemoContent::settings('contact_page');
        $slug = $request->query('project');
        $row = collect(DemoProjects::rows())->firstWhere('slug', is_string($slug) ? $slug : null);

        $subject = $request->query('subject');
        $subject = is_string($subject) && in_array($subject, FormOptions::values('subject'), true)
            ? $subject
            : ($row !== null || in_array($request->query('type'), ['site_visit', 'brochure_download'], true) ? 'sales' : null);

        return Inertia::render('Site/Contact', [
            'header' => DemoBlocks::block(1, 'page_header', [
                'title' => $copy['title'],
                'intro' => $copy['intro'],
                'image' => null,
                'breadcrumbs' => Breadcrumbs::to($copy['title']),
            ]),
            'offices' => DemoBlocks::block(2, 'offices', [
                'heading' => $copy['offices_heading'],
                'offices' => DemoContent::offices(),
            ], background: 'muted', spacing: 'lg'),
            'copy' => [
                'form_heading' => $copy['form_heading'],
                'form_intro' => $copy['form_intro'],
                'details_heading' => $copy['details_heading'],
                'social_heading' => $copy['social_heading'],
                'whatsapp_text' => $row === null
                    ? $copy['whatsapp_text']
                    : str_replace('{project}', $row['name'], $copy['whatsapp_text_project']),
            ],
            'project' => $row === null ? null : ['slug' => $row['slug'], 'name' => $row['name']],
            'subject' => $subject,
            'seo' => SeoData::page($copy['title'], $copy['description'])->toArray($request),
        ]);
    }
}

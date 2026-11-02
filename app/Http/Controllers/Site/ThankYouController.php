<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoContent;
use App\Support\Forms\FormType;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-22 `/thank-you/{form}`. 7.1 only lands the redirect of the lead forms
 * on a plain confirmation; 7.2 builds the full page (next steps, related
 * projects, WhatsApp, `generate_lead`).
 */
class ThankYouController extends Controller
{
    public function __invoke(Request $request, string $form): Response
    {
        $type = FormType::fromSlug($form) ?? abort(404);
        $copy = DemoContent::settings('forms')['thank_you'][$type->value];

        return Inertia::render('Site/ThankYou', [
            'form' => $type->slug(),
            'title' => $copy['title'],
            'message' => $copy['message'],
            'seo' => SeoData::page($copy['title'])->noindex(follow: false)->toArray($request),
        ]);
    }
}

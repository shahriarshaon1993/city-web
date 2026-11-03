<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoFaqs;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-18 `/faq` (LAY-FAQ): every FAQ with `faq` in show_on, grouped by
 * category (FR-SOC-003). Topic tabs and the search box filter in the
 * browser; without JavaScript every group is shown and the topics are jump
 * links (owner 8.2). The FAQPage JSON-LD arrives with 19.1.
 */
class FaqController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $copy = DemoFaqs::copy();

        return Inertia::render('Site/Faq', [
            'faq' => [
                'categories' => DemoFaqs::categories(),
                'items' => DemoFaqs::items(),
            ],
            'cta' => DemoFaqs::cta(),
            'copy' => $copy,
            'seo' => SeoData::page($copy['title'], $copy['description'])->toArray($request),
        ]);
    }
}

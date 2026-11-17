<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Queries\Site\ProjectCards;
use App\Queries\Site\ProjectDetail;
use App\Support\Forms\FormType;
use App\Support\Seo\SeoData;
use App\Support\Settings\Settings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-22 `/thank-you/{form}` (LAY-THANKS): confirmation copy, three next
 * steps, WhatsApp, a way back and — for the project forms — related projects.
 * `lead` is the one-request flash FormController sets after a submit
 * (FR-LEAD-005); without it (a reload, a direct visit) the page still
 * renders but `generate_lead` is not fired. A shortlist enquiry fires one
 * `generate_lead` per project, as it creates one lead each (FR-SHORT-004).
 * Always `noindex,nofollow`.
 */
class ThankYouController extends Controller
{
    private const array PROJECT_FORMS = [FormType::ProjectInquiry, FormType::SiteVisit, FormType::BrochureDownload];

    public function __invoke(Request $request, string $form): Response
    {
        $type = FormType::fromSlug($form) ?? abort(404);
        $settings = Settings::group('forms');
        $copy = $settings['thank_you'][$type->value];

        $lead = $request->session()->get('lead');
        $lead = is_array($lead) && ($lead['lead_type'] ?? null) === $type->value ? $lead : null;
        // FR-SHORT-004: a shortlist enquiry names several projects (one lead each).
        $slugs = array_values(array_filter(
            is_array($lead['project_slugs'] ?? null) ? $lead['project_slugs'] : [],
            fn (mixed $slug): bool => $this->projectCard($slug) !== null,
        ));
        $project = $this->projectCard($lead['project_slug'] ?? (count($slugs) === 1 ? $slugs[0] : null));

        $whatsappText = $project === null
            ? $settings['thank_you_whatsapp']
            : str_replace('{project}', $project['name'], $settings['thank_you_whatsapp_project']);

        return Inertia::render('Site/ThankYou', [
            'form' => $type->slug(),
            'title' => $copy['title'],
            'message' => $copy['message'],
            'steps' => $copy['steps'],
            'project' => $project === null ? null : ['slug' => $project['slug'], 'name' => $project['name'], 'url' => $project['url']],
            'related' => in_array($type, self::PROJECT_FORMS, true)
                ? ['heading' => $settings['thank_you_related_heading'], 'projects' => $this->related($project)]
                : null,
            'whatsapp_text' => $whatsappText,
            'lead' => $lead === null ? null : [
                'reference' => (string) $lead['reference'],
                'lead_type' => $type->value,
                'project_slug' => $project['slug'] ?? null,
                'project_slugs' => $slugs,
                'form_location' => is_string($lead['form_location'] ?? null) ? $lead['form_location'] : null,
            ],
            'seo' => SeoData::page($copy['title'])->noindex(follow: false)->toArray($request),
        ]);
    }

    /**
     * The card of a published project, or null for an unknown or unpublished slug.
     *
     * @return array<string, mixed>|null
     */
    private function projectCard(mixed $slug): ?array
    {
        if (! is_string($slug)) {
            return null;
        }

        $project = ProjectCards::query()->where('slug', $slug)->first();

        return $project === null ? null : ProjectCards::cards($project->newCollection([$project]))[0];
    }

    /**
     * Similar projects of the one the visitor asked about, else the featured ones.
     *
     * @param  array<string, mixed>|null  $project
     * @return list<array<string, mixed>>
     */
    private function related(?array $project): array
    {
        if ($project !== null) {
            $similar = ProjectDetail::find($project['slug'])['project']['similar'] ?? [];

            if ($similar !== []) {
                return $similar;
            }
        }

        return array_slice(array_values(array_filter(
            ProjectCards::featured(4),
            fn (array $card): bool => $card['slug'] !== ($project['slug'] ?? null),
        )), 0, 3);
    }
}

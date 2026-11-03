<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoContent;
use App\Support\Demo\DemoProjects;
use App\Support\Demo\DemoProjectSections;
use App\Support\Forms\FormType;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-22 `/thank-you/{form}` (LAY-THANKS): confirmation copy, three next
 * steps, WhatsApp, a way back and — for the project forms — related projects.
 * `lead` is the one-request flash FormController sets after a submit
 * (FR-LEAD-005); without it (a reload, a direct visit) the page still
 * renders but `generate_lead` is not fired. Always `noindex,nofollow`.
 *
 * @phpstan-import-type DemoProject from DemoProjects
 */
class ThankYouController extends Controller
{
    private const array PROJECT_FORMS = [FormType::ProjectInquiry, FormType::SiteVisit, FormType::BrochureDownload];

    public function __invoke(Request $request, string $form): Response
    {
        $type = FormType::fromSlug($form) ?? abort(404);
        $settings = DemoContent::settings('forms');
        $copy = $settings['thank_you'][$type->value];

        $lead = $request->session()->get('lead');
        $lead = is_array($lead) && ($lead['lead_type'] ?? null) === $type->value ? $lead : null;
        $row = $this->projectRow($lead['project_slug'] ?? null);
        $project = $row === null ? null : DemoProjects::card($row);

        $whatsappText = $row === null
            ? $settings['thank_you_whatsapp']
            : str_replace('{project}', $row['name'], $settings['thank_you_whatsapp_project']);

        return Inertia::render('Site/ThankYou', [
            'form' => $type->slug(),
            'title' => $copy['title'],
            'message' => $copy['message'],
            'steps' => $copy['steps'],
            'project' => $project === null ? null : ['slug' => $project['slug'], 'name' => $project['name'], 'url' => $project['url']],
            'related' => in_array($type, self::PROJECT_FORMS, true)
                ? ['heading' => $settings['thank_you_related_heading'], 'projects' => $this->related($row)]
                : null,
            'whatsapp_text' => $whatsappText,
            'lead' => $lead === null ? null : [
                'reference' => (string) $lead['reference'],
                'lead_type' => $type->value,
                'project_slug' => $row['slug'] ?? null,
                'form_location' => is_string($lead['form_location'] ?? null) ? $lead['form_location'] : null,
            ],
            'seo' => SeoData::page($copy['title'])->noindex(follow: false)->toArray($request),
        ]);
    }

    /**
     * @return DemoProject|null
     */
    private function projectRow(mixed $slug): ?array
    {
        if (! is_string($slug)) {
            return null;
        }

        foreach (DemoProjects::rows() as $row) {
            if ($row['slug'] === $slug) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Similar projects of the one the visitor asked about, else the featured ones.
     *
     * @param  DemoProject|null  $row
     * @return list<array<string, mixed>>
     */
    private function related(?array $row): array
    {
        if ($row !== null) {
            $similar = DemoProjectSections::similar($row);

            if ($similar !== []) {
                return $similar;
            }
        }

        $featured = array_filter(
            DemoProjects::rows(),
            fn (array $other): bool => $other['featured'] && $other['slug'] !== ($row['slug'] ?? null),
        );

        return array_map(DemoProjects::card(...), array_slice(array_values($featured), 0, 3));
    }
}

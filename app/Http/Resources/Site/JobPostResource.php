<?php

namespace App\Http\Resources\Site;

use App\Models\JobPost;
use App\Support\Careers\CareersCopy;
use App\Support\Seo\Breadcrumbs;
use Illuminate\Http\Request;

/**
 * PG-16 (`JobPost` in resources/js/types/careers.ts). The salary is null unless set (FR-CAR-001); a
 * section with no text is left out. Section HTML was cleaned when the post was saved.
 *
 * @mixin JobPost
 */
class JobPostResource extends JobRowResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $copy = CareersCopy::labels();
        $sections = [
            ['id' => 'about', 'heading' => $copy['about_heading'], 'body' => $this->description],
            ['id' => 'responsibilities', 'heading' => $copy['responsibilities_heading'], 'body' => $this->responsibilities],
            ['id' => 'requirements', 'heading' => $copy['requirements_heading'], 'body' => $this->requirements],
            ['id' => 'benefits', 'heading' => $copy['benefits_heading'], 'body' => $this->benefits],
        ];

        return [
            ...parent::toArray($request),
            'summary' => $this->summary,
            'experience' => $this->experience,
            'salary_text' => $this->salary_text,
            'is_open' => ! $this->isClosed(),
            'closed_at' => $this->isClosed() ? $this->closedOn()->timezone('Asia/Dhaka')->toDateString() : null,
            'sections' => array_values(array_filter($sections, fn (array $section): bool => trim(strip_tags((string) $section['body'])) !== '')),
            'breadcrumbs' => Breadcrumbs::to($this->title, [[$copy['title'], '/careers']]),
        ];
    }
}

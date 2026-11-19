<?php

namespace App\Queries\Admin;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Http\Resources\Admin\MediaAssetResource;
use App\Models\JobPost;
use App\Models\MediaAsset;
use App\Models\Office;
use App\Support\Careers\JobDates;
use Illuminate\Http\Request;

/**
 * The editor payload of a job post (ADM-SCR-10, FR-CAR-001): the form in the shape of
 * `SaveJobPostRequest`. The deadline is a `YYYY-MM-DD` date in Asia/Dhaka.
 */
final class JobPostEditor
{
    /**
     * @return array<string, mixed>
     */
    public static function form(JobPost $job): array
    {
        $job->loadMissing('seoMeta');

        return [
            'title' => $job->title,
            'slug' => $job->slug,
            'department' => $job->department,
            'office_id' => $job->office_id,
            'employment_type' => $job->employment_type->value,
            'experience' => (string) $job->experience,
            'salary_text' => (string) $job->salary_text,
            'salary_min_bdt' => $job->salary_min_bdt,
            'salary_max_bdt' => $job->salary_max_bdt,
            'summary' => $job->summary,
            'description' => (string) $job->description,
            'responsibilities' => (string) $job->responsibilities,
            'requirements' => (string) $job->requirements,
            'benefits' => (string) $job->benefits,
            'deadline' => $job->deadline->toDateString(),
            'status' => $job->status->value,
            'seo' => ProjectEditor::seo($job->seoMeta),
        ];
    }

    /**
     * The form of a job post that does not exist yet (CRUD-03): a draft with a deadline 30 days away.
     *
     * @return array<string, mixed>
     */
    public static function blank(): array
    {
        return [
            'title' => '',
            'slug' => '',
            'department' => '',
            'office_id' => Office::query()->where('is_head_office', true)->value('id'),
            'employment_type' => EmploymentType::FullTime->value,
            'experience' => '',
            'salary_text' => '',
            'salary_min_bdt' => null,
            'salary_max_bdt' => null,
            'summary' => '',
            'description' => '',
            'responsibilities' => '',
            'requirements' => '',
            'benefits' => '',
            'deadline' => JobDates::today()->addDays(30)->toDateString(),
            'status' => JobStatus::Draft->value,
            'seo' => ProjectEditor::seo(null),
        ];
    }

    /**
     * The library item the SEO panel points at, by id, for its preview.
     *
     * @param  array<string, mixed>  $form
     * @return array<int, array<string, mixed>>
     */
    public static function assets(Request $request, array $form): array
    {
        $id = $form['seo']['og_image_asset_id'] ?? null;

        if ($id === null) {
            return [];
        }

        return MediaAsset::query()->whereKey($id)->with('media')->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [$asset->id => (new MediaAssetResource($asset))->resolve($request)])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function options(): array
    {
        $used = JobPost::withTrashed()->distinct()->orderBy('department')->pluck('department')->all();

        return [
            'offices' => Office::query()->orderByDesc('is_head_office')->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'city'])
                ->map(fn (Office $office): array => ['value' => $office->id, 'label' => "{$office->name} · {$office->city}"])->all(),
            'employment_types' => array_map(fn (EmploymentType $type): array => ['value' => $type->value, 'label' => $type->label()], EmploymentType::cases()),
            'statuses' => array_map(fn (JobStatus $status): array => ['value' => $status->value, 'label' => $status->label()], JobStatus::cases()),
            'departments' => array_values(array_unique([...JobPost::DEPARTMENT_SUGGESTIONS, ...$used])),
            'today' => JobDates::today()->toDateString(),
            'limits' => [
                'title' => JobPost::TITLE_MAX,
                'summary' => JobPost::SUMMARY_MAX,
                'department' => 60,
                'experience' => 80,
                'salary_text' => 80,
            ],
        ];
    }
}

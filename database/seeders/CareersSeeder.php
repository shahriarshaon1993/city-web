<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Models\JobPost;
use App\Models\Office;
use App\Support\Careers\JobDates;
use Database\Seeders\Data\CareerSeedData;
use Illuminate\Database\Seeder;

/**
 * The placeholder job posts of the client's review (outside production only; the client enters the real
 * ones under Careers). Safe to run again: a table that already has rows is left alone.
 */
class CareersSeeder extends Seeder
{
    public function run(): void
    {
        if (JobPost::withTrashed()->exists()) {
            return;
        }

        $offices = Office::query()->orderBy('sort_order')->orderBy('id')->pluck('id')->values();
        $today = JobDates::today();

        foreach (CareerSeedData::JOBS as $position => $row) {
            $deadline = $today->addDays($row['deadline_days']);
            $status = JobStatus::from($row['status']);

            $job = new JobPost([
                'title' => $row['title'],
                'slug' => $row['slug'],
                'department' => $row['department'],
                'office_id' => $row['office'] === null ? null : $offices->get($row['office'] - 1),
                'employment_type' => EmploymentType::from($row['type']),
                'experience' => $row['experience'],
                'salary_text' => $row['salary'],
                'summary' => $row['summary'],
                'description' => $row['description'],
                'responsibilities' => $this->list($row['responsibilities']),
                'requirements' => $this->list($row['requirements']),
                'benefits' => $this->list($row['benefits']),
                'deadline' => $deadline,
                'status' => $status,
                'closed_at' => $status === JobStatus::Closed ? JobDates::closesAfter($deadline) : null,
                'published_at' => $status === JobStatus::Draft ? null : $today->subDays(12 - $position)->setTime(10, 0),
            ]);
            $job->save();
        }
    }

    /**
     * @param  list<string>  $items
     */
    private function list(array $items): ?string
    {
        return $items === [] ? null : '<ul>'.implode('', array_map(fn (string $item): string => '<li>'.e($item).'</li>', $items)).'</ul>';
    }
}

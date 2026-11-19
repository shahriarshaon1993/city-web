<?php

namespace App\Actions\Careers;

use App\Models\JobPost;
use App\Support\Careers\JobDates;

/**
 * What a job post needs before it can be Open (VAL-05: draft saves are never blocked, only opening).
 * One list of plain sentences, used by the editor's Open status and by the list's "Open" verb.
 */
final class JobPostChecklist
{
    /**
     * @return list<string>
     */
    public function problems(JobPost $job): array
    {
        $problems = [];

        if ($job->deadline->isBefore(JobDates::today())) {
            $problems[] = 'The deadline has passed. Move it to today or later before opening the role.';
        }

        if (trim(strip_tags((string) $job->description)) === '') {
            $problems[] = 'Add a description of the role before opening it.';
        }

        if (trim(strip_tags((string) $job->responsibilities)) === '' && trim(strip_tags((string) $job->requirements)) === '') {
            $problems[] = 'Add the responsibilities or the requirements before opening the role.';
        }

        return $problems;
    }
}

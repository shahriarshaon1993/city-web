<?php

namespace App\Actions\Careers;

use App\Actions\Activity\LogActivity;
use App\Enums\JobStatus;
use App\Models\JobPost;
use App\Models\User;
use App\Queries\Admin\JobPostEditor;
use App\Support\Careers\JobDates;
use Illuminate\Support\Facades\DB;

/**
 * CRUD-05: Duplicate job post = a Draft "Copy of {title}" with a free slug (`-copy`, `-copy-2`…) that
 * keeps the text, office, type and SEO panel. The deadline moves to 30 days from today; applications,
 * slug history and the published state are not copied.
 */
final class DuplicateJobPost
{
    public function __construct(
        private readonly SaveJobPost $saveJobPost,
        private readonly LogActivity $logActivity,
    ) {}

    public function handle(User $actor, JobPost $source): JobPost
    {
        return DB::transaction(function () use ($actor, $source): JobPost {
            $form = JobPostEditor::form($source);
            $form['title'] = mb_substr("Copy of {$source->title}", 0, JobPost::TITLE_MAX);
            $form['slug'] = $this->freeSlug($source->slug);
            $form['status'] = JobStatus::Draft->value;
            $form['deadline'] = JobDates::today()->addDays(30)->toDateString();

            $copy = $this->saveJobPost->handle($actor, $form, null, false);

            $this->logActivity->handle('jobs', 'created', "Job post duplicated: {$copy->title}", $actor, $copy, ['label' => $copy->title, 'source' => $source->title]);

            return $copy;
        });
    }

    /** Free among all job posts, the bin included (DEL-08). */
    private function freeSlug(string $slug): string
    {
        $base = mb_substr($slug, 0, JobPost::TITLE_MAX - 9);
        $candidate = "{$base}-copy";

        for ($number = 2; JobPost::withTrashed()->where('slug', $candidate)->exists(); $number++) {
            $candidate = "{$base}-copy-{$number}";
        }

        return $candidate;
    }
}

<?php

/**
 * Phase 17.1 check: job posts and applications (FR-CAR-001…005). Opening rules, the 00:05 auto-close, the
 * closed → 410 window, a stored application with its private CV, AC-CAR-01 (6 MB CV), the flag for a spammy
 * cover letter, the permission split (CV download), the 12-month purge, the office delete guard and the
 * public pages over HTTP. Everything is rolled back; the fake disk keeps real CV folders untouched.
 * Run: php docs/checks/17.1-careers.php   (needs `db:seed --class=CareersSeeder` first)
 */

use App\Actions\Careers\CloseExpiredJobPosts;
use App\Actions\Careers\DuplicateJobPost;
use App\Actions\Careers\PurgeOldApplications;
use App\Actions\Careers\SaveJobPost;
use App\Actions\Careers\SetJobPostStatus;
use App\Actions\Careers\SubmitJobApplication;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Enums\JobStatus;
use App\Http\Requests\Admin\SaveJobPostRequest;
use App\Http\Requests\Site\JobApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\Office;
use App\Models\User;
use App\Notifications\NewApplicationNotification;
use App\Support\Careers\JobDates;
use App\Support\Leads\LeadContext;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

if (! function_exists('app')) {
    require __DIR__.'/../../vendor/autoload.php';
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
}

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

$status = function (string $path) use ($app): int {
    return $app->handle(Request::create($path, 'GET'))->getStatusCode();
};

DB::beginTransaction();
Storage::fake(JobApplication::DISK);

try {
    $actor = User::query()->role('Super Admin')->first() ?? User::factory()->create();
    $today = JobDates::today();

    echo "-- seeded sample\n";
    $say('open jobs on the list', JobPost::query()->acceptingApplications()->count(), 6);
    $say('closed 5 days ago is 200 (Applications closed)', $status('/careers/quantity-surveyor'), 200);
    $say('closed 45 days ago is 410', $status('/careers/customer-relations-manager'), 410);
    $say('draft is 404', $status('/careers/project-manager'), 404);
    $say('unknown is 404', $status('/careers/no-such-role'), 404);
    $say('/careers is 200', $status('/careers'), 200);
    $say('unknown filter value 301s away', $status('/careers?department=nope'), 301);

    $draft = JobPost::query()->where('slug', 'project-manager')->firstOrFail();
    $signed = URL::temporarySignedRoute('site.careers.show', now()->addMinutes(60), ['slug' => $draft->slug]);
    $say('signed preview of a draft is 200', $status($signed), 200);

    echo "-- editor rules\n";
    $form = [
        'title' => 'Check role', 'slug' => 'check-role', 'department' => 'Sales', 'office_id' => null,
        'employment_type' => 'full_time', 'summary' => 'A role made by the 17.1 check script.',
        'description' => '<p>Text.</p>', 'responsibilities' => '<ul><li>One</li></ul>', 'requirements' => '',
        'deadline' => $today->addDays(5)->toDateString(), 'status' => 'open',
    ];
    $validate = fn (array $data): array => Validator::make($data, SaveJobPostRequest::create('/', 'POST', $data)->rules())->errors()->keys();
    $say('a valid open role has no errors', $validate($form), []);
    $say('an open role with a past deadline is refused', in_array('deadline', $validate([...$form, 'deadline' => $today->subDay()->toDateString()]), true), true);
    $say('a draft with a past deadline is allowed', in_array('deadline', $validate([...$form, 'status' => 'draft', 'deadline' => $today->subDay()->toDateString()]), true), false);
    $say('a slug used by another role is refused', in_array('slug', $validate([...$form, 'slug' => 'architect']), true), true);

    echo "-- save, close, duplicate\n";
    $job = app(SaveJobPost::class)->handle($actor, $form);
    $say('created open', $job->status, JobStatus::Open);
    $say('published_at stamped', $job->published_at !== null, true);
    $say('accepting applications', $job->isAcceptingApplications(), true);

    $job->forceFill(['deadline' => $today->subDay()])->save();
    $say('past deadline counts as closed before the scheduler', $job->fresh()->isClosed(), true);
    $say('close-expired closes it', app(CloseExpiredJobPosts::class)->handle() >= 1, true);
    $say('now stored as closed', $job->fresh()->status, JobStatus::Closed);
    $say('closed_at stamped', $job->fresh()->closed_at !== null, true);

    try {
        app(SetJobPostStatus::class)->handle($actor, $job->fresh(), JobStatus::Open);
        $say('reopening with a past deadline is refused', false, true);
    } catch (ValidationException $exception) {
        $say('reopening with a past deadline is refused', str_contains($exception->validator->errors()->first(), 'deadline'), true);
    }

    $copy = app(DuplicateJobPost::class)->handle($actor, $job->fresh());
    $say('duplicate is a draft', $copy->status, JobStatus::Draft);
    $say('duplicate slug', $copy->slug, 'check-role-copy');

    echo "-- applications\n";
    $open = JobPost::query()->acceptingApplications()->firstOrFail();
    $cv = UploadedFile::fake()->createWithContent('My CV.pdf', "%PDF-1.4\n%fake\n");
    $data = [
        'job' => $open->slug, 'name' => 'Rahim Uddin', 'phone_country' => 'BD', 'phone' => '01712345678',
        'email' => 'Rahim@Example.com', 'current_position' => 'Sales lead', 'years_experience' => 6,
        'cover_letter' => 'I would like to apply.', 'cv' => $cv, 'consent' => true,
    ];
    $result = app(SubmitJobApplication::class)->handle($data, new LeadContext('203.0.113.9', 'check', null, null));
    $application = JobApplication::query()->where('reference', $result['reference'])->firstOrFail();
    $say('stored for the role', $application->job_post_id, $open->id);
    $say('phone normalised', $application->phone, '+8801712345678');
    $say('email lowercased', $application->email, 'rahim@example.com');
    $say('status new', $application->status->value, 'new');
    $say('CV is on the private disk', Storage::disk(JobApplication::DISK)->exists($application->cv_path), true);
    $say('CV name kept for the download', $application->cv_name, 'My CV.pdf');
    $say('CV folder is careers/{reference}', str_starts_with($application->cv_path, "careers/{$application->reference}/"), true);
    $say('no raw IP stored', $application->ip_hash !== null && ! str_contains((string) json_encode($application->getAttributes()), '203.0.113.9'), true);
    $say('consent text stored', $application->consent_text !== '', true);
    $say('not flagged (Turnstile is off here)', $application->review_reason, null);
    $say('HR was notified', DB::table('notifications')->where('type', NewApplicationNotification::class)->count() >= 1, true);

    $spam = app(SubmitJobApplication::class)->handle([...$data, 'phone' => '01812345678', 'cv' => UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4'), 'cover_letter' => 'http://a.com http://b.com http://c.com http://d.com'], new LeadContext('203.0.113.9', 'check', null, null));
    $say('a spammy cover letter is stored and flagged', JobApplication::query()->where('reference', $spam['reference'])->value('review_reason'), 'too_many_links');

    $general = app(SubmitJobApplication::class)->handle([...$data, 'job' => null, 'phone' => '01912345678', 'cv' => UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4')], new LeadContext(null, null, null, null));
    $say('general CV drop has no job', JobApplication::query()->where('reference', $general['reference'])->value('job_post_id'), null);

    $rules = (new JobApplicationRequest)->rules();
    $big = Validator::make(['cv' => UploadedFile::fake()->create('big.pdf', 6 * 1024)], ['cv' => $rules['cv']]);
    $say('AC-CAR-01: a 6 MB CV is refused on the cv field', $big->errors()->keys(), ['cv']);
    $say('AC-CAR-01: the message names 5 MB', str_contains($big->errors()->first('cv'), '5 MB'), true);
    $exe = Validator::make(['cv' => UploadedFile::fake()->create('cv.exe', 10)], ['cv' => $rules['cv']]);
    $say('a .exe CV is refused', $exe->errors()->keys(), ['cv']);
    $closedJob = Validator::make(['job' => 'quantity-surveyor'], ['job' => $rules['job']]);
    $say('a closed role takes no applications', $closedJob->errors()->keys(), ['job']);

    echo "-- permissions\n";
    $hr = User::factory()->create(['email' => 'hr-check@example.test', 'is_active' => true]);
    $editor = User::factory()->create(['email' => 'editor-check@example.test', 'is_active' => true]);
    $editor->syncRoles('Content Editor');
    $hr->givePermissionTo(['jobs.view', 'jobs.manage', 'applications.manage']);
    $say('Content Editor manages jobs', $editor->can('create', JobPost::class), true);
    $say('Content Editor cannot open applications', $editor->can('viewAny', JobApplication::class), false);
    $say('Content Editor cannot download a CV', $editor->can('view', $application), false);
    $say('an HR user can', $hr->can('view', $application), true);

    echo "-- retention and bin\n";
    $old = JobApplication::query()->where('reference', $general['reference'])->firstOrFail();
    DB::table('job_applications')->where('id', $old->id)->update(['updated_at' => now()->subDays(JobApplication::RETENTION_DAYS + 1)]);
    $path = $old->cv_path;
    $say('purge removes applications idle for 12 months', app(PurgeOldApplications::class)->handle(), 1);
    $say('row is gone', JobApplication::withTrashed()->whereKey($old->id)->exists(), false);
    $say('its CV is gone', Storage::disk(JobApplication::DISK)->exists($path), false);
    $say('a newer application stays', JobApplication::query()->whereKey($application->id)->exists(), true);

    $office = Office::query()->firstOrFail();
    $withJob = app(SaveJobPost::class)->handle($actor, [...$form, 'slug' => 'office-guard', 'office_id' => $office->id]);
    $say('an office with job posts cannot be deleted', str_contains((string) $office->binUsageBlock($actor), 'job'), true);

    $entry = app(MoveToRecycleBin::class)->handle($actor, $withJob);
    $say('Delete = Recycle bin, comes back as draft', $withJob->fresh()->status, JobStatus::Draft);
    $say('the bin entry keeps 90 days', (int) $entry->deleted_at->diffInDays($entry->purge_at), 90);
    $say('the application keeps 365 days in the bin', JobApplication::binRetentionDays(), 365);
} finally {
    DB::rollBack();
}

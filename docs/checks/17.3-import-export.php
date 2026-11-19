<?php

/**
 * Phase 17.3 check: the import wizard (ADM-IO-001), queued table exports (ADM-TBL-012) and the personal-data
 * export / erase (NFR-PRIV-03). A Facebook-style leads file with one bad phone (UTF-8 and UTF-16 tab), the mapping
 * guess, the preview, a queued run that ends in a report and an error CSV, the other four import types, an export
 * over 5,000 rows that becomes a queued file with a signed link and a notification, and an erase that leaves no PII.
 * Everything is rolled back; the fake disk keeps real files untouched.
 * Run: php docs/checks/17.3-import-export.php
 */

use App\Actions\Imports\ProcessImportSlice;
use App\Actions\Imports\PurgeDataTransfers;
use App\Actions\Imports\SaveImportMapping;
use App\Actions\Imports\StartImport;
use App\Actions\Imports\UploadImportFile;
use App\Actions\Privacy\ErasePersonalData;
use App\Actions\Privacy\ExportPersonalData;
use App\Actions\Tables\ExportTable;
use App\Actions\Tables\GenerateExport;
use App\Enums\ImportStatus;
use App\Jobs\RunExport;
use App\Jobs\RunImport;
use App\Models\ExportRun;
use App\Models\ImportRun;
use App\Models\JobApplication;
use App\Models\Lead;
use App\Models\Location;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use App\Queries\Admin\ImportPreview;
use App\Queries\Admin\PersonalDataLookup;
use App\Queries\Admin\SubscribersTable;
use App\Support\Import\ImportType;
use App\Support\Privacy\PersonalDataTerm;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

if (! function_exists('app')) {
    require __DIR__.'/../../vendor/autoload.php';
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
}

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

DB::beginTransaction();
Storage::fake(ImportRun::DISK);
Queue::fake();

try {
    $actor = User::query()->role('Super Admin')->first() ?? User::factory()->create();
    $actor->forceFill(['is_active' => true])->save();
    $upload = fn (string $name, string $content): UploadedFile => UploadedFile::fake()->createWithContent($name, $content);
    $run = function (ImportRun $import, array $confirm = []) use ($actor): ImportRun {
        app(StartImport::class)->handle($actor, $import, $confirm);
        $import->refresh()->forceFill(['status' => ImportStatus::Running])->save();

        while (! app(ProcessImportSlice::class)->handle($import->refresh())) {
            // another slice
        }

        return $import->refresh();
    };

    echo "-- leads: a Facebook-style file\n";
    $csv = "id,created_time,campaign_name,full_name,phone_number,email,city\n"
        ."l:1,2026-11-02T10:15:00+06:00,Summer launch,Rahim Uddin,p:+8801712345678,Rahim@Example.com,Dhaka\n"
        ."l:2,2026-11-02T11:00:00+06:00,Summer launch,Karim Ali,p:01898765432,,Dhaka\n"
        ."l:3,2026-11-02T12:00:00+06:00,Summer launch,Bad Phone,p:12345,,Dhaka\n"
        ."l:4,2026-11-03T09:00:00+06:00,Summer launch,Nusrat Jahan,p:+447911123456,,London\n";
    $import = app(UploadImportFile::class)->handle($actor, ImportType::Leads, $upload('leads.csv', $csv));
    $say('4 data rows counted', $import->total_rows, 4);
    $say('status uploaded', $import->status, ImportStatus::Uploaded);
    $say('name guessed from full_name', $import->mapping['name'], 3);
    $say('phone guessed from phone_number', $import->mapping['phone'], 4);
    $say('campaign guessed', $import->mapping['campaign'], 2);
    $say('date guessed from created_time', $import->mapping['received'], 1);
    $say('project not guessed', $import->mapping['project'], null);

    $preview = app(ImportPreview::class)->build($import);
    $say('preview judges every row', array_values(array_map(fn ($row) => $row['outcome'], $preview['rows'])), ['create', 'create', 'fail', 'create']);
    $say('preview counts', $preview['counts'], ['create' => 3, 'update' => 0, 'skip' => 0, 'fail' => 1]);
    $say('preview names the bad phone', str_contains((string) $preview['rows'][2]['message'], 'phone'), true);
    $say('nothing saved by the preview', Lead::query()->where('type', Lead::IMPORT_TYPE)->count(), 0);

    app(SaveImportMapping::class)->handle($import, ['name' => 3, 'phone' => null]);
    try {
        app(StartImport::class)->handle($actor, $import->refresh(), []);
        $say('start without a phone column is refused', false, true);
    } catch (ValidationException $exception) {
        $say('start without a phone column is refused', str_contains($exception->validator->errors()->first('mapping'), 'Phone'), true);
    }

    app(SaveImportMapping::class)->handle($import, ['name' => 3, 'phone' => 4, 'email' => 5, 'campaign' => 2, 'received' => 1]);
    $import->refresh();
    app(StartImport::class)->handle($actor, $import, []);
    Queue::assertPushed(RunImport::class);
    $say('queued after confirm', $import->refresh()->status, ImportStatus::Queued);

    $import->forceFill(['status' => ImportStatus::Running])->save();
    while (! app(ProcessImportSlice::class)->handle($import->refresh())) {
        // another slice
    }
    $import->refresh();
    $say('finished', $import->status, ImportStatus::Finished);
    $say('created 3, failed 1', [$import->created_count, $import->failed_count, $import->skipped_count], [3, 1, 0]);
    $say('source file deleted after the run', Storage::disk(ImportRun::DISK)->exists($import->sourcePath()), false);
    $say('error file exists', $import->error_path !== null && Storage::disk(ImportRun::DISK)->exists($import->error_path), true);
    $errors = Storage::disk(ImportRun::DISK)->get((string) $import->error_path);
    $say('error file keeps the row and the reason', str_contains($errors, 'Bad Phone') && str_contains($errors, 'Problem') && str_contains($errors, 'phone'), true);

    $rahim = Lead::query()->where('phone', '+8801712345678')->first();
    $say('lead is Imported and unassigned', [$rahim?->type, $rahim?->assigned_to, $rahim?->status->value], ['import', null, 'new']);
    $say('lead keeps the file date', $rahim?->created_at?->toDateString(), '2026-11-02');
    $say('email lowercased', $rahim?->email, 'rahim@example.com');
    $say('local number normalised', Lead::query()->where('phone', '+8801898765432')->exists(), true);
    $say('foreign number kept as E.164', Lead::query()->where('phone', '+447911123456')->exists(), true);
    $say('the person who started it is notified', $actor->notifications()->where('type', 'App\\Notifications\\ImportFinishedNotification')->count() >= 1, true);
    $say('activity log has counts, no PII', DB::table('activity_log')->where('log_name', 'imports')->where('event', 'finished')->where('description', 'like', '%Rahim%')->count(), 0);

    echo "-- leads: the same file again adds nothing\n";
    $again = app(UploadImportFile::class)->handle($actor, ImportType::Leads, $upload('leads.csv', $csv));
    $again = $run($again);
    $say('3 skipped, 1 failed, 0 created', [$again->created_count, $again->skipped_count, $again->failed_count], [0, 3, 1]);

    echo "-- UTF-16 tab file (Facebook Lead Center)\n";
    $tsv = "full_name\tphone_number\n"."Salma Akter\tp:01911223344\n";
    $utf16 = "\xFF\xFE".mb_convert_encoding($tsv, 'UTF-16LE', 'UTF-8');
    $utf = app(UploadImportFile::class)->handle($actor, ImportType::Leads, $upload('lead-center.csv', $utf16));
    $say('delimiter detected', $utf->options['delimiter'], "\t");
    $say('mapping found in a UTF-16 file', [$utf->mapping['name'], $utf->mapping['phone']], [0, 1]);
    $utf = $run($utf);
    $say('one lead created', [$utf->created_count, Lead::query()->where('phone', '+8801911223344')->exists()], [1, true]);

    echo "-- file rules\n";
    try {
        app(UploadImportFile::class)->handle($actor, ImportType::Leads, $upload('empty.csv', "full_name,phone_number\n"));
        $say('a file with no rows is refused', false, true);
    } catch (ValidationException $exception) {
        $say('a file with no rows is refused', str_contains($exception->validator->errors()->first('file'), 'no data rows'), true);
    }
    $say('refused uploads leave no run behind', ImportRun::query()->where('original_name', 'empty.csv')->count(), 0);

    echo "-- subscribers (consent is required)\n";
    NewsletterSubscriber::query()->create(['email' => 'gone@example.com', 'status' => 'unsubscribed', 'token' => 'x', 'consent_text' => 'c']);
    $subs = app(UploadImportFile::class)->handle($actor, ImportType::Subscribers, $upload('subs.csv', "Email,Date\nnew@example.com,2026-05-14\ngone@example.com,\nnot-an-email,\nnew@example.com,\n"));
    try {
        app(StartImport::class)->handle($actor, $subs, []);
        $say('no consent tick is refused', false, true);
    } catch (ValidationException $exception) {
        $say('no consent tick is refused', $exception->validator->errors()->has('consent'), true);
    }
    $subs = $run($subs->refresh(), ['consent' => true]);
    $say('1 new, 2 skipped (unsubscribed, repeat), 1 failed', [$subs->created_count, $subs->skipped_count, $subs->failed_count], [1, 2, 1]);
    $say('an unsubscribed person is not re-added', NewsletterSubscriber::query()->where('email', 'gone@example.com')->value('status')->value, 'unsubscribed');
    $say('imported address is active', NewsletterSubscriber::query()->where('email', 'new@example.com')->value('status')->value, 'active');

    echo "-- redirects, locations, FAQs\n";
    $redirects = app(UploadImportFile::class)->handle($actor, ImportType::Redirects, $upload('redirects.csv', "Source,Target,Code,Active\n/check-old,/check-new,301,yes\n/check-gone,,410,\n/loop,/loop,301,\n"));
    $redirects = $run($redirects);
    $say('redirects: 2 created, 1 failed (loop)', [$redirects->created_count, $redirects->failed_count], [2, 1]);

    $locations = app(UploadImportFile::class)->handle($actor, ImportType::Locations, $upload('locations.csv', "Name,Type,City,URL\nCheck City,city,,check-city\nCheck Area,area,Check City,check-area\nOrphan,area,No Such City,check-orphan\n"));
    $preview = app(ImportPreview::class)->build($locations);
    $say('locations preview: an area may name a city created earlier in the file', array_map(fn ($row) => $row['outcome'], $preview['rows']), ['create', 'create', 'fail']);
    $locations = $run($locations);
    $say('locations: 2 created, 1 failed', [$locations->created_count, $locations->failed_count], [2, 1]);
    $say('the area is a draft under the city', Location::query()->where('slug', 'check-area')->first()?->publish_status->value, 'draft');

    $faqs = app(UploadImportFile::class)->handle($actor, ImportType::Faqs, $upload('faqs.csv', "Question,Answer,Category,Published\nCan I pay in instalments over time?,Yes.,Payments,yes\nCan I pay in instalments over time?,Duplicate.,Payments,\nIs it legal for everyone?,Yes.,Nonsense,\n"));
    $faqs = $run($faqs);
    $say('faqs: 1 created, 1 skipped, 1 failed', [$faqs->created_count, $faqs->skipped_count, $faqs->failed_count], [1, 1, 1]);

    echo "-- queued export over 5,000 rows\n";
    $now = now();
    $batch = [];
    for ($i = 1; $i <= 5001; $i++) {
        $batch[] = ['email' => "bulk{$i}@example.com", 'status' => 'active', 'token' => "t{$i}", 'consent_text' => 'c', 'created_at' => $now, 'updated_at' => $now];
    }
    foreach (array_chunk($batch, 1000) as $chunk) {
        DB::table('newsletter_subscribers')->insert($chunk);
    }
    $table = app(SubscribersTable::class);
    $request = Request::create('/admin/export/'.$table->key(), 'GET', ['format' => 'csv', 'columns' => 'email,status']);
    $response = app(ExportTable::class)->handle($table, $request, $actor, 'csv', ['email', 'status']);
    $say('over 5,000 rows does not stream', $response instanceof RedirectResponse, true);
    Queue::assertPushed(RunExport::class);
    $export = ExportRun::query()->latest('id')->firstOrFail();
    $say('queued run remembers the columns', $export->columns, ['email', 'status']);

    app(GenerateExport::class)->handle($export);
    $export->refresh();
    $say('export ready', $export->status, 'ready');
    $say('file written', Storage::disk(ExportRun::DISK)->exists((string) $export->path), true);
    $say('file has every row plus the header', substr_count(Storage::disk(ExportRun::DISK)->get((string) $export->path), "\n") >= 5002, true);
    $say('link lasts 24 hours', (int) round(now()->diffInHours($export->expires_at, false)), 24);
    $note = $actor->notifications()->where('type', 'App\\Notifications\\ExportReadyNotification')->latest()->first();
    $say('notification carries a signed relative link', str_starts_with((string) ($note->data['url'] ?? ''), '/admin/exports/'.$export->id.'/download?'), true);
    $say('the signature is valid', URL::hasValidSignature(Request::create($note->data['url']), absolute: false), true);
    $say('a tampered link is not', URL::hasValidSignature(Request::create($note->data['url'].'1'), absolute: false), false);

    $export->forceFill(['expires_at' => now()->subMinute()])->save();
    app(PurgeDataTransfers::class)->handle();
    $say('expired file is purged', Storage::disk(ExportRun::DISK)->exists((string) $export->path), false);
    $say('expired run is not downloadable', $export->refresh()->isDownloadable(), false);

    DB::table('newsletter_subscribers')->where('email', 'like', 'bulk%@example.com')->delete();
    $small = app(ExportTable::class)->handle($table, $request, $actor, 'csv', ['email', 'status']);
    $say('a small export still streams', $small instanceof StreamedResponse, true);

    echo "-- personal data: find, export, erase\n";
    $cv = UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\n");
    $lead = Lead::query()->create([
        'reference' => 'ERASE-LEAD-1', 'type' => 'contact', 'status' => 'new', 'name' => 'Erase Me', 'phone' => '+8801755500011',
        'email' => 'erase.me@example.com', 'source_url' => '/', 'consent_text' => 'ok', 'message' => 'Call me',
    ]);
    $lead->activities()->create(['kind' => 'note', 'payload' => ['text' => 'Spoke to Erase Me']]);
    Storage::disk(Lead::class === '' ? 'local' : 'local')->put('leads/ERASE-LEAD-1/plan.pdf', 'x');
    $application = JobApplication::query()->create([
        'reference' => 'ERASE-APP-1', 'name' => 'Erase Me', 'phone' => '+8801755500011', 'email' => 'erase.me@example.com',
        'cv_path' => 'careers/ERASE-APP-1/cv.pdf', 'cv_name' => 'cv.pdf', 'cv_size' => 9, 'status' => 'new', 'consent_text' => 'ok',
    ]);
    Storage::disk(JobApplication::DISK)->put('careers/ERASE-APP-1/cv.pdf', 'x');
    $subscriber = NewsletterSubscriber::query()->create(['email' => 'erase.me@example.com', 'status' => 'active', 'token' => 'erase', 'consent_text' => 'ok']);
    $subscriber->delete();
    $actor->notify(new NewLeadNotification($lead));

    $parsed = PersonalDataTerm::fromInput('01755 500011', 'Erase.Me@Example.com');
    $term = $parsed['term'];
    $say('phone and email are normalised', $term?->toArray(), ['phone' => '+8801755500011', 'email' => 'erase.me@example.com']);
    $say('a phone nobody can dial is refused', array_keys(PersonalDataTerm::fromInput('123', null)['errors']), ['phone']);
    $say('an empty search is refused', array_keys(PersonalDataTerm::fromInput('', '')['errors']), ['phone']);

    $found = app(PersonalDataLookup::class)->summary($term);
    $say('finds the lead, the application and the binned subscription', [count($found['leads']), count($found['applications']), count($found['subscriptions']), $found['subscriptions'][0]['in_bin']], [1, 1, 1, true]);
    $say('a phone alone finds no subscription', count(app(PersonalDataLookup::class)->summary(new PersonalDataTerm('+8801755500011', null))['subscriptions']), 0);

    $export = app(ExportPersonalData::class)->handle($actor, $term);
    $say('export holds the lead, its timeline, the application and the subscription', [count($export['leads']), count($export['leads'][0]['timeline']), count($export['applications']), count($export['newsletter_subscriptions'])], [1, 1, 1, 1]);
    $say('export log has counts only', DB::table('activity_log')->where('log_name', 'privacy')->where('event', 'exported')->where('properties', 'like', '%erase.me%')->count(), 0);

    $counts = app(ErasePersonalData::class)->handle($actor, $term);
    $say('erase counts', $counts, ['leads' => 1, 'applications' => 1, 'subscriptions' => 1]);
    $say('lead gone, timeline gone', [Lead::withTrashed()->where('reference', 'ERASE-LEAD-1')->count(), DB::table('lead_activities')->where('payload', 'like', '%Erase Me%')->count()], [0, 0]);
    $say('landowner documents gone', Storage::disk('local')->exists('leads/ERASE-LEAD-1/plan.pdf'), false);
    $say('application and CV gone', [JobApplication::withTrashed()->where('reference', 'ERASE-APP-1')->count(), Storage::disk(JobApplication::DISK)->exists('careers/ERASE-APP-1/cv.pdf')], [0, false]);
    $say('subscription gone, also from the bin index', [NewsletterSubscriber::withTrashed()->where('email', 'erase.me@example.com')->count(), DB::table('recycle_bin_entries')->where('type', 'newsletter_subscriber')->where('label', 'like', '%erase.me%')->count()], [0, 0]);
    $say('the inbox notice naming them is gone', $actor->notifications()->where('data', 'like', '%Erase Me%')->count(), 0);
    $entry = DB::table('activity_log')->where('log_name', 'privacy')->where('event', 'erased')->latest('id')->first();
    $say('the erase entry has counts and no PII', [$entry?->description, str_contains((string) $entry?->properties, 'erase'), str_contains((string) $entry?->properties, '8801755')], ['Personal data erased on request', false, false]);
} finally {
    DB::rollBack();
}

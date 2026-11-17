<?php

/**
 * Phase 15.1 check: lead storage, E.164, duplicate touch, assignment, spam rules, Turnstile outcomes,
 * per-phone limit, gated brochure link, private documents, honeypot, IP hashing, newsletter opt-in.
 * Everything is rolled back (files and rate-limiter keys are removed by hand).
 * Run: php artisan tinker docs/checks/15.1-leads.php < /dev/null   (needs `db:seed` first)
 */

use App\Actions\Leads\HashOldLeadIps;
use App\Actions\Leads\SubmitLead;
use App\Actions\Newsletter\ConfirmNewsletterSubscription;
use App\Actions\Newsletter\SubscribeToNewsletter;
use App\Actions\Newsletter\UnsubscribeFromNewsletter;
use App\Actions\Settings\UpdateSettings;
use App\Enums\LeadStatus;
use App\Mail\ConfirmNewsletterSubscription as ConfirmMail;
use App\Models\Lead;
use App\Models\NewsletterSubscriber;
use App\Models\Project;
use App\Models\User;
use App\Policies\LeadPolicy;
use App\Support\Forms\FormType;
use App\Support\Leads\LeadContext;
use App\Support\Leads\SpamProtectionProps;
use App\Support\Settings\Settings;
use App\Support\Settings\SettingsRegistry;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Spatie\Honeypot\EncryptedTime;
use Spatie\Honeypot\Exceptions\SpamException;
use Spatie\Honeypot\SpamProtection;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

$ctx = fn (?string $token = null): LeadContext => new LeadContext('203.0.113.9', 'check-agent', null, $token);
$inquiry = fn (array $extra = []): array => [
    'name' => 'Rahim Uddin', 'phone_country' => 'BD', 'phone' => '01712-345678', 'email' => 'Rahim@Example.com',
    'whatsapp_same' => true, 'message' => 'Please call me.', 'timeline' => '1_3_months', 'purpose' => 'investment',
    'utm_source' => 'google', 'utm_campaign' => 'launch', 'referrer_host' => 'www.google.com',
    'source_url' => '/projects/x', 'form_location' => 'enquiry_dialog', ...$extra,
];

DB::beginTransaction();
$files = [];

try {
    $submit = app(SubmitLead::class);
    $projects = Project::query()->published()->orderBy('id')->limit(2)->get();
    $project = $projects[0];
    $other = $projects[1];
    $inquiry = fn (array $extra = []) => [...$inquiry(), 'project' => $project->slug, ...$extra];
    RateLimiter::clear('lead-phone:+8801712345678');

    // --- AC-LEAD-01 / 04: stored, E.164, consent text, UTM, assignee -----------------------------------
    $result = $submit->handle(FormType::ProjectInquiry, $inquiry(), $ctx());
    $lead = Lead::query()->where('reference', $result['reference'])->firstOrFail();
    $say('phone normalised (AC-LEAD-04)', $lead->phone, '+8801712345678');
    $say('status new', $lead->status, LeadStatus::New);
    $say('project stored', $lead->project_id, $project->id);
    $say('whatsapp = phone', $lead->whatsapp, '+8801712345678');
    $say('email lower-cased', $lead->email, 'rahim@example.com');
    $say('UTM stored', [$lead->utm_source, $lead->utm_campaign, $lead->referrer_host], ['google', 'launch', 'www.google.com']);
    $say('answers hold the step-1 fields', $lead->answers == ['timeline' => '1_3_months', 'purpose' => 'investment'], true);
    $say('consent text stored', $lead->consent_text, Settings::get('forms', 'consent')['project_inquiry']);
    $say('IP and agent stored', [$lead->ip, $lead->user_agent], ['203.0.113.9', 'check-agent']);
    $say('assigned to a sales executive (or nobody if none)', $lead->assigned_to === null ? true : User::find($lead->assigned_to)->hasRole('Sales Executive'), true);
    $say('timeline: submission (+ assignment)', $lead->activities->pluck('kind.value')->all(), $lead->assigned_to === null ? ['submission'] : ['submission', 'assignment']);

    // --- FR-LEAD-004: duplicate within 24 h = touch -----------------------------------------------------
    $again = $submit->handle(FormType::ProjectInquiry, $inquiry(['phone' => '+8801712345678', 'message' => 'Second try']), $ctx());
    $say('same lead reference', $again['reference'], $result['reference']);
    $say('still one lead for phone+project', Lead::query()->where('phone', '+8801712345678')->where('project_id', $project->id)->count(), 1);
    $lead->refresh();
    $say('touch counted', $lead->touches_count, 1);
    $say('touch on the timeline', $lead->activities()->where('kind', 'touch')->count(), 1);
    $other = $submit->handle(FormType::ProjectInquiry, $inquiry(['project' => $other->slug]), $ctx());
    $say('other project = new lead', $other['reference'] !== $result['reference'], true);

    // --- Assignment: least open leads among Sales Executives -------------------------------------------
    $executives = User::role('Sales Executive')->where('is_active', true)->count();
    echo "info  active sales executives: {$executives}\n";

    // --- FR-LEAD-003d content rules -----------------------------------------------------------------------
    $spam = $submit->handle(FormType::Contact, ['name' => 'Spammer', 'phone_country' => 'BD', 'phone' => '01811111111', 'subject' => 'general', 'message' => 'http://a.example http://b.example http://c.example http://d.example'], $ctx());
    $spamLead = Lead::query()->where('reference', $spam['reference'])->firstOrFail();
    $say('>3 links = spam, unassigned', [$spamLead->status, $spamLead->assigned_to], [LeadStatus::Spam, null]);
    $russian = $submit->handle(FormType::Contact, ['name' => 'Ivan', 'phone_country' => 'BD', 'phone' => '01822222222', 'subject' => 'general', 'message' => 'Привет, пожалуйста позвоните мне'], $ctx());
    $say('foreign script only = spam', Lead::query()->where('reference', $russian['reference'])->value('status')->value, LeadStatus::Spam->value);
    $bangla = $submit->handle(FormType::Contact, ['name' => 'করিম', 'phone_country' => 'BD', 'phone' => '01833333333', 'subject' => 'general', 'message' => 'আমি একটি ফ্ল্যাট কিনতে চাই, দয়া করে ফোন করুন।'], $ctx());
    $say('Bengali is fine', Lead::query()->where('reference', $bangla['reference'])->value('status')->value, LeadStatus::New->value);

    // --- FR-LEAD-003a Turnstile outcomes --------------------------------------------------------------------
    $update = app(UpdateSettings::class);
    $actor = User::role('Super Admin')->firstOrFail();
    $setting = function (string $group, array $values, array $clear = []) use ($update, $actor): void {
        $update->handle($actor, SettingsRegistry::find($group), [...Settings::group($group), ...$values], $clear);
        Settings::flushMemo();
    };
    $setting('forms', ['turnstile_secret' => 'test-secret']);

    $cloudflare = Http::response(['success' => true]);
    Http::fake(function () use (&$cloudflare) {
        return $cloudflare;
    });
    $ok = $submit->handle(FormType::Callback, ['name' => 'A Person', 'phone_country' => 'BD', 'phone' => '01944444444', 'contact_time' => 'morning'], $ctx('good-token'));
    $say('token ok = new', Lead::query()->where('reference', $ok['reference'])->value('status')->value, 'new');

    $missing = $submit->handle(FormType::Callback, ['name' => 'A Person', 'phone_country' => 'BD', 'phone' => '01955555555', 'contact_time' => 'morning'], $ctx());
    $say('no token = needs_review', Lead::query()->where('reference', $missing['reference'])->value('status')->value, 'needs_review');

    $cloudflare = Http::response([], 500);
    $down = $submit->handle(FormType::Callback, ['name' => 'A Person', 'phone_country' => 'BD', 'phone' => '01966666666', 'contact_time' => 'morning'], $ctx('any'));
    $say('Cloudflare down = needs_review', Lead::query()->where('reference', $down['reference'])->value('status')->value, 'needs_review');

    $cloudflare = Http::response(['success' => false]);
    try {
        $submit->handle(FormType::Callback, ['name' => 'A Person', 'phone_country' => 'BD', 'phone' => '01977777777', 'contact_time' => 'morning'], $ctx('bad'));
        $say('rejected token is refused', 'accepted', 'ValidationException');
    } catch (ValidationException $e) {
        $say('rejected token is refused', array_keys($e->errors()), ['turnstile']);
    }
    $say('refused submit stored nothing', Lead::query()->where('phone', '+8801977777777')->count(), 0);
    $setting('forms', [], ['turnstile_secret']);

    // --- FR-LEAD-003c: 20 a day per phone -------------------------------------------------------------------
    $phone = '01388888888';
    RateLimiter::clear('lead-phone:+8801388888888');
    for ($i = 0; $i < 20; $i++) {
        $submit->handle(FormType::Callback, ['name' => 'Loop', 'phone_country' => 'BD', 'phone' => $phone, 'contact_time' => 'morning'], $ctx());
    }
    try {
        $submit->handle(FormType::Callback, ['name' => 'Loop', 'phone_country' => 'BD', 'phone' => $phone, 'contact_time' => 'morning'], $ctx());
        $say('21st submit for one phone', 'accepted', 429);
    } catch (ThrottleRequestsException $e) {
        $say('21st submit for one phone', [$e->getStatusCode(), isset($e->getHeaders()['Retry-After'])], [429, true]);
    }
    RateLimiter::clear('lead-phone:+8801388888888');

    // --- AC-PROJ-05: brochure link ---------------------------------------------------------------------------
    $withDoc = Project::query()->published()->whereHas('documents')->first();
    if ($withDoc === null) {
        echo "skip  no published project has a document; add one in the admin to check the brochure link\n";
    } else {
        $brochure = $submit->handle(FormType::BrochureDownload, ['name' => 'B', 'phone_country' => 'BD', 'phone' => '01399999999', 'project' => $withDoc->slug], $ctx());
        $url = $brochure['download_url'];
        $say('brochure link is signed and points at the download route', is_string($url) && str_contains($url, 'signature=') && str_contains($url, "/projects/{$withDoc->slug}/documents/"), true);
        $request = fn () => Request::create($url);
        $say('link valid now', URL::hasValidSignature($request()), true);
        Carbon::setTestNow(now()->addMinutes(9));
        $say('link valid after 9 minutes', URL::hasValidSignature($request()), true);
        Carbon::setTestNow(now()->addMinutes(2));
        $say('link expired after 11 minutes', URL::hasValidSignature($request()), false);
        Carbon::setTestNow();
    }

    // --- FR-LEAD-011: private documents ---------------------------------------------------------------------
    $landowner = $submit->handle(FormType::Landowner, [
        'name' => 'Land Owner', 'phone_country' => 'BD', 'phone' => '01311111111', 'land_location' => 'Purbachal', 'land_size_katha' => 5,
        'documents' => [UploadedFile::fake()->create('deed.pdf', 20, 'application/pdf')],
    ], $ctx());
    $landLead = Lead::query()->where('reference', $landowner['reference'])->firstOrFail();
    $attachment = $landLead->attachments[0] ?? null;
    $files[] = "leads/{$landLead->reference}";
    $say('document stored under leads/{reference}', $attachment !== null && str_starts_with($attachment['path'], "leads/{$landLead->reference}/"), true);
    $say('document is on the private disk, not public', Storage::disk('local')->exists($attachment['path']) && ! Storage::disk('public')->exists($attachment['path']), true);
    $say('landowner answers', $landLead->answers['land_location'] ?? null, 'Purbachal');

    // --- LeadPolicy (AC-LEAD-05 groundwork) -----------------------------------------------------------------
    $policy = new LeadPolicy;
    $manager = User::role('Sales Manager')->first();
    $executive = User::role('Sales Executive')->first();
    if ($manager && $executive) {
        $mine = new Lead(['assigned_to' => $executive->id]);
        $theirs = new Lead(['assigned_to' => $executive->id + 1000]);
        $say('manager sees any lead', $policy->view($manager, $theirs), true);
        $say('executive sees own lead', $policy->view($executive, $mine), true);
        $say('executive blocked on another lead (403)', $policy->view($executive, $theirs), false);
    }

    // --- Honeypot ---------------------------------------------------------------------------------------------
    $props = SpamProtectionProps::shared()['honeypot'];
    $check = function (array $fields): string {
        try {
            app(SpamProtection::class)->check($fields);

            return 'passes';
        } catch (SpamException) {
            return 'spam';
        }
    };
    $past = EncryptedTime::create(now()->subSeconds(5));
    $say('honeypot filled = spam', $check([$props['name'] => 'bot', $props['valid_from_name'] => $past]), 'spam');
    $say('too fast = spam', $check([$props['name'] => '', $props['valid_from_name'] => $props['valid_from']]), 'spam');
    $say('empty trap + old enough = passes', $check([$props['name'] => '', $props['valid_from_name'] => $past]), 'passes');
    $say('no honeypot fields at all = passes (not required)', $check(['name' => 'x']), 'passes');

    // --- NFR-PRIV-04: IP hashing after 90 days ----------------------------------------------------------------
    $old = Lead::query()->where('reference', $result['reference'])->firstOrFail();
    $old->forceFill(['created_at' => now()->subDays(91)])->saveQuietly();
    $changed = app(HashOldLeadIps::class)->handle();
    $old->refresh();
    $say('old IP hashed', [$changed >= 1, $old->ip, strlen((string) $old->ip_hash)], [true, null, 64]);

    // --- Newsletter ------------------------------------------------------------------------------------------------
    $subscribe = app(SubscribeToNewsletter::class);
    Mail::fake();
    $say('single opt-in = active', $subscribe->handle('Reader@Example.com', '/news', $ctx())->value, 'active');
    $row = NewsletterSubscriber::query()->where('email', 'reader@example.com')->firstOrFail();
    $say('consent + hashed IP stored', [$row->consent_text !== '', strlen((string) $row->ip_hash)], [true, 64]);
    $say('repeat sign-up of an active address is a silent success', $subscribe->handle('reader@example.com', '/news', $ctx())->value, 'active');
    $say('still one row', NewsletterSubscriber::query()->where('email', 'reader@example.com')->count(), 1);
    app(UnsubscribeFromNewsletter::class)->handle($row->token);
    $say('unsubscribed', $row->refresh()->status->value, 'unsubscribed');
    $say('unknown token = false', app(UnsubscribeFromNewsletter::class)->handle(str_repeat('a', 40)), false);

    $setting('notifications', ['email_enabled' => true]);
    $say('double opt-in = pending', $subscribe->handle('reader@example.com', '/news', $ctx())->value, 'pending');
    Mail::assertQueued(ConfirmMail::class);
    $say('confirm link activates', app(ConfirmNewsletterSubscription::class)->handle($row->token), true);
    $say('now active with confirmed_at', [$row->refresh()->status->value, $row->confirmed_at !== null], ['active', true]);
    $say('confirm with a bad token', app(ConfirmNewsletterSubscription::class)->handle(str_repeat('b', 40)), false);
} finally {
    Carbon::setTestNow();
    foreach ($files as $dir) {
        Storage::disk('local')->deleteDirectory($dir);
    }
    RateLimiter::clear('lead-phone:+8801712345678');
    DB::rollBack();
    Settings::flushMemo();
}

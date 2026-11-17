<?php

/**
 * Phase 15.3 check: dashboard notifications (new lead / assigned / follow-up due), the inbox endpoints,
 * optional team email + autoresponder (default off), the signed webhook with retries, its log and
 * pruning, and the events the lead Actions raise. Everything is rolled back; mail, HTTP and the
 * queue are faked.
 * Run: php artisan tinker docs/checks/15.3-notifications.php < /dev/null   (needs `db:seed` first)
 */

use App\Actions\Leads\ChangeLeadStatus;
use App\Actions\Leads\CreateManualLead;
use App\Actions\Leads\ReassignLead;
use App\Actions\Leads\SendLeadAutoresponder;
use App\Actions\Notifications\DeleteNotifications;
use App\Actions\Notifications\MarkNotificationsRead;
use App\Actions\Notifications\NotifyFollowUpsDue;
use App\Actions\Notifications\NotifyLeadAssigned;
use App\Actions\Notifications\NotifyNewLead;
use App\Actions\Webhooks\DeliverWebhook;
use App\Actions\Webhooks\PruneWebhookDeliveries;
use App\Actions\Webhooks\QueueLeadWebhook;
use App\Enums\LeadChange;
use App\Enums\LeadStatus;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Http\Controllers\Admin\NotificationController;
use App\Jobs\SendLeadWebhook;
use App\Mail\LeadAutoresponderMail;
use App\Mail\NewLeadMail;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Notifications\FollowUpsDueNotification;
use App\Notifications\LeadAssignedNotification;
use App\Notifications\NewLeadNotification;
use App\Support\Notifications\NotificationPresenter;
use App\Support\Settings\Settings;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

$set = function (string $group, string $key, mixed $value, bool $secret = false): void {
    Setting::query()->updateOrCreate(['group' => $group, 'key' => $key], ['value' => $secret ? Crypt::encryptString($value) : $value, 'is_secret' => $secret]);
    Settings::bump();
};

DB::beginTransaction();

try {
    $make = function (string $role) {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    };
    $manager = $make('Sales Manager');
    $exec = $make('Sales Executive');
    $viewer = $make('Viewer');
    $mine = fn (User $user, string $class): int => $user->notifications()->where('type', $class)->count();

    $lead = fn (array $extra = []): Lead => Lead::create([
        'reference' => (string) Str::ulid(), 'type' => 'project_inquiry', 'status' => LeadStatus::New,
        'name' => 'Check Person', 'phone' => '+8801719990101', 'source_url' => '/x', 'consent_text' => 'ok',
        'last_touched_at' => now(), ...$extra,
    ]);

    // --- §10.8 dashboard notifications -----------------------------------------------------------------------
    $fresh = $lead(['assigned_to' => $exec->id, 'name' => 'Fresh Lead']);
    app(NotifyNewLead::class)->handle($fresh);
    $say('assignee gets "assigned to you"', $mine($exec, LeadAssignedNotification::class), 1);
    $say('assignee does not also get "new lead"', $mine($exec, NewLeadNotification::class), 0);
    $say('manager (can assign) gets "new lead"', $mine($manager, NewLeadNotification::class), 1);
    $say('viewer is not notified', $viewer->notifications()->count(), 0);
    $data = $manager->notifications()->first()->data;
    $say('notification carries no phone number', str_contains(json_encode($data), '01719990101'), false);
    $say('notification links to the lead', $data['url'], '/admin/leads?lead='.$fresh->id);

    app(NotifyNewLead::class)->handle($lead(['status' => LeadStatus::Spam]));
    $say('spam is never announced', $manager->notifications()->count(), 1);

    $byManager = $lead(['assigned_to' => $exec->id, 'name' => 'By Manager']);
    app(NotifyNewLead::class)->handle($byManager, $manager);
    $say('the actor is not told about their own action', $manager->notifications()->count(), 1);

    $reassigned = $lead(['name' => 'Moved']);
    app(ReassignLead::class)->handle($manager, $reassigned, $exec, announce: false);
    app(NotifyLeadAssigned::class)->handle($reassigned->refresh(), $manager);
    $say('reassignment tells the new owner', $mine($exec, LeadAssignedNotification::class), 3);
    app(NotifyLeadAssigned::class)->handle($reassigned, $exec);
    $say('assigning to yourself tells nobody', $mine($exec, LeadAssignedNotification::class), 3);

    // --- FR-LEAD-008b/c email: off by default ------------------------------------------------------------------
    Mail::fake();
    app(NotifyNewLead::class)->handle($lead(['assigned_to' => $exec->id, 'email' => 'visitor@example.com']));
    $say('team email is off by default', Mail::queued(NewLeadMail::class)->count(), 0);
    $say('autoresponder is off by default', app(SendLeadAutoresponder::class)->handle($lead(['email' => 'visitor@example.com'])), false);

    $set('notifications', 'email_enabled', true);
    $set('notifications', 'project_inquiry', ['sales@example.com', 'SALES@example.com', 'boss@example.com']);
    $withMail = $lead(['assigned_to' => $exec->id, 'email' => 'visitor@example.com', 'message' => "Hello\nthere"]);
    app(NotifyNewLead::class)->handle($withMail);
    $recipients = Mail::queued(NewLeadMail::class)->map(fn ($mail) => $mail->to[0]['address'])->sort()->values()->all();
    $say('email goes to the assignee + configured list, de-duplicated', $recipients, collect([$exec->email, 'sales@example.com', 'boss@example.com'])->sort()->values()->all());
    $say('the visitor is Reply-To', Mail::queued(NewLeadMail::class)->first()->envelope()->replyTo[0]->address, 'visitor@example.com');
    $say('the mail is plain text', str_contains(Mail::queued(NewLeadMail::class)->first()->render(), 'Check Person'), true);

    $set('notifications', 'autoresponder_enabled', true);
    $set('notifications', 'autoresponder_subject', 'Thanks {name} about {project}');
    $set('notifications', 'autoresponder_body', "Hello {name},\nre {project}");
    $say('autoresponder sends to a new lead', app(SendLeadAutoresponder::class)->handle($withMail), true);
    $auto = Mail::queued(LeadAutoresponderMail::class)->first();
    $say('tokens filled (no project = site name)', $auto->mailSubject, 'Thanks Check Person about '.config('app.name'));
    $say('timeline records the email', $withMail->activities()->where('kind', 'email')->count(), 1);
    $say('no email address = no autoresponse', app(SendLeadAutoresponder::class)->handle($lead()), false);
    $say('spam / needs review / manual get none', [
        app(SendLeadAutoresponder::class)->handle($lead(['email' => 'a@example.com', 'status' => LeadStatus::Spam])),
        app(SendLeadAutoresponder::class)->handle($lead(['email' => 'a@example.com', 'status' => LeadStatus::NeedsReview])),
        app(SendLeadAutoresponder::class)->handle($lead(['email' => 'a@example.com', 'type' => 'manual'])),
    ], [false, false, false]);
    $injected = $lead(['email' => 'a@example.com', 'name' => "Evil\r\nBcc: x@example.com"]);
    app(SendLeadAutoresponder::class)->handle($injected);
    $say('a visitor name cannot add a header line', str_contains(Mail::queued(LeadAutoresponderMail::class)->last()->mailSubject, "\n"), false);

    // --- §10.8 follow-up due 09:00 ------------------------------------------------------------------------------
    $noon = Carbon::now('Asia/Dhaka')->setTime(12, 0);
    $one = $lead(['assigned_to' => $manager->id, 'name' => 'Due One', 'follow_up_at' => $noon->copy()->utc()]);
    $lead(['assigned_to' => $manager->id, 'name' => 'Won One', 'status' => LeadStatus::Won, 'follow_up_at' => $noon->copy()->utc()]);
    $lead(['assigned_to' => $manager->id, 'name' => 'Tomorrow', 'follow_up_at' => $noon->copy()->addDay()->utc()]);
    $before = $mine($manager, FollowUpsDueNotification::class);
    $count = app(NotifyFollowUpsDue::class)->handle();
    $digest = $manager->notifications()->where('type', FollowUpsDueNotification::class)->first();
    $say('one open lead due today = one digest, titled with the name', [$mine($manager, FollowUpsDueNotification::class) - $before, $digest->data['title']], [1, 'Follow-up due today: Due One']);
    $say('a single lead links to it', $digest->data['url'], '/admin/leads?lead='.$one->id);
    $say('running again the same day repeats nothing', app(NotifyFollowUpsDue::class)->handle() < $count || $mine($manager, FollowUpsDueNotification::class) === $before + 1, true);

    $two = $lead(['assigned_to' => $exec->id, 'name' => 'Due A', 'follow_up_at' => $noon->copy()->utc()]);
    $lead(['assigned_to' => $exec->id, 'name' => 'Due B', 'follow_up_at' => $noon->copy()->addHour()->utc()]);
    app(NotifyFollowUpsDue::class)->handle();
    $say('two due = one digest with a count', $exec->notifications()->where('type', FollowUpsDueNotification::class)->first()->data['title'], '2 follow-ups due today');

    // --- inbox actions + presenter -------------------------------------------------------------------------------
    $row = NotificationPresenter::row($manager->notifications()->first());
    $say('presenter shape', array_keys($row), ['id', 'kind', 'kind_label', 'icon', 'title', 'body', 'url', 'read', 'created_at']);
    $unread = $manager->unreadNotifications()->count();
    $say('mark one read', [app(MarkNotificationsRead::class)->handle($manager, $row['id']), $manager->unreadNotifications()->count()], [1, $unread - 1]);
    $say("someone else's notification cannot be marked read", app(MarkNotificationsRead::class)->handle($viewer, $row['id']), 0);
    $say('mark all read', [app(MarkNotificationsRead::class)->handle($manager) > 0, $manager->unreadNotifications()->count()], [true, 0]);
    $say('delete read removes only read ones', [app(DeleteNotifications::class)->handle($manager) > 0, $manager->notifications()->count()], [true, 0]);
    $say("someone else's notification cannot be deleted", app(DeleteNotifications::class)->handle($viewer, (string) $exec->notifications()->first()->id), 0);

    $http = fn (User $user, string $method, string $uri) => (function () use ($user, $method, $uri) {
        auth()->setUser($user);
        $request = Request::create($uri, $method, [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(Kernel::class)->handle($request);

        return [$response->getStatusCode(), json_decode($response->getContent(), true)];
    })();
    [$status, $feed] = $http($exec, 'GET', '/admin/notifications/feed');
    $say('feed: 200 with the unread count and items', [$status, $feed['unread'] === $exec->unreadNotifications()->count(), count($feed['items']) > 0], [200, true, true]);
    $firstId = $feed['items'][0]['id'];
    $asUser = function (User $user): Request {
        $request = Request::create('/admin/notifications', 'POST');
        $request->setUserResolver(fn () => $user);

        return $request;
    };
    $controller = app(NotificationController::class);
    try {
        $controller->read($asUser($viewer), $firstId, app(MarkNotificationsRead::class));
        $say("someone else's notification is a 404", 'answered', 404);
    } catch (NotFoundHttpException) {
        $say("someone else's notification is a 404", 404, 404);
    }
    $answer = $controller->read($asUser($exec), $firstId, app(MarkNotificationsRead::class))->getData(true);
    $say('read: count drops', $answer['unread'] === $exec->unreadNotifications()->count(), true);

    // --- FR-LEAD-012 webhook ------------------------------------------------------------------------------------
    Queue::fake();
    $payloadLead = $lead(['assigned_to' => $exec->id, 'name' => 'Hook Lead', 'phone' => '+8801719990202', 'ip' => '203.0.113.9', 'user_agent' => 'secret-agent']);
    $say('webhook off = nothing queued', app(QueueLeadWebhook::class)->handle($payloadLead, 'lead.created'), null);

    $set('webhook', 'enabled', true);
    $set('webhook', 'url', 'https://crm.example.test/hook');
    $set('webhook', 'secret', 'topsecret', true);
    $delivery = app(QueueLeadWebhook::class)->handle($payloadLead, 'lead.created');
    $say('a delivery row and a queued job', [$delivery !== null, Queue::pushed(SendLeadWebhook::class)->count()], [true, 1]);
    $job = Queue::pushed(SendLeadWebhook::class)->first();
    $body = json_decode($job->body, true);
    $say('body: event, lead, phone and no private data', [$body['event'], $body['lead']['name'], $body['lead']['phone'], str_contains($job->body, '203.0.113.9'), str_contains($job->body, 'secret-agent')], ['lead.created', 'Hook Lead', '+8801719990202', false, false]);
    $say('retries: 1, 5, 15 minutes after 4 tries', [SendLeadWebhook::RETRY_MINUTES, (new SendLeadWebhook(1, ''))->tries], [[1, 5, 15], 4]);

    Http::fake(['crm.example.test/*' => Http::sequence()->push('boom', 500)->push('ok', 200)]);
    $say('500 = not delivered (retry)', app(DeliverWebhook::class)->handle($delivery, $job->body), false);
    $delivery->refresh();
    $say('log shows the 500 and 1 attempt', [$delivery->status_code, $delivery->attempts, $delivery->delivered_at, $delivery->response_excerpt], [500, 1, null, 'boom']);
    $say('second try answers 200 = delivered', app(DeliverWebhook::class)->handle($delivery, $job->body), true);
    $delivery->refresh();
    $say('log shows delivered after 2 attempts', [$delivery->status_code, $delivery->attempts, $delivery->delivered_at !== null], [200, 2, true]);
    $sent = Http::recorded()->last()[0];
    $say('X-Signature = sha256 HMAC of the exact body', $sent->header('X-Signature')[0], 'sha256='.hash_hmac('sha256', $job->body, 'topsecret'));
    $say('same body bytes on the wire', $sent->body(), $job->body);
    $say('event + delivery headers', [$sent->header('X-Gemcon-Event')[0], $sent->header('X-Gemcon-Delivery')[0]], ['lead.created', (string) $delivery->id]);

    Http::fake(['crm.example.test/*' => Http::response('', 301, ['Location' => 'https://evil.test/'])]);
    $say('a redirect is a failure, never followed', app(DeliverWebhook::class)->handle(WebhookDelivery::create(['event' => 'lead.updated', 'lead_id' => $payloadLead->id]), '{}'), false);

    Setting::query()->where(['group' => 'webhook', 'key' => 'secret'])->delete();
    Settings::bump();
    $unsigned = WebhookDelivery::create(['event' => 'lead.updated', 'lead_id' => $payloadLead->id]);
    Http::fake();
    $say('no secret = not sent, not retried', [app(DeliverWebhook::class)->handle($unsigned, '{}'), $unsigned->refresh()->status_code], [true, 0]);
    Http::assertNothingSent();

    $old = WebhookDelivery::create(['event' => 'lead.created', 'lead_id' => null]);
    $old->forceFill(['created_at' => now()->subDays(31)])->save();
    $say('prune removes entries older than 30 days', [app(PruneWebhookDeliveries::class)->handle() >= 1, WebhookDelivery::query()->whereKey($old->id)->exists(), WebhookDelivery::query()->whereKey($delivery->id)->exists()], [true, false, true]);

    // --- real wiring: Action → event → listeners (after-commit events are released by hand) -----------------------
    $release = fn () => app('db.transactions')->commit(DB::getDefaultConnection(), 1, 0);
    $assignedBefore = $mine($exec, LeadAssignedNotification::class);
    $jobsBefore = Queue::pushed(SendLeadWebhook::class)->count();
    $wired = app(CreateManualLead::class)->handle($manager, ['name' => 'Wired Walkin', 'channel' => 'walk_in'], '+8801719990404', $exec, null)['lead'];
    $release();
    $say('manual lead → the owner is notified by the listener', $mine($exec, LeadAssignedNotification::class) - $assignedBefore, 1);
    $say('manual lead → one webhook job', Queue::pushed(SendLeadWebhook::class)->count() - $jobsBefore, 1);
    app(ChangeLeadStatus::class)->handle($exec, $wired, LeadStatus::Contacted);
    $release();
    $say('status change → a lead.updated webhook job', Queue::pushed(SendLeadWebhook::class)->count() - $jobsBefore, 2);
    $say('…whose body says what changed', json_decode(Queue::pushed(SendLeadWebhook::class)->last()->body, true)['change'], 'status');

    // --- the Actions raise the events (a rolled-back transaction never commits, so they are faked) ----------------
    Event::fake([LeadCreated::class, LeadUpdated::class]);
    $created = app(CreateManualLead::class)->handle($manager, ['name' => 'Event Walkin', 'channel' => 'walk_in'], '+8801719990303', $exec, Carbon::now()->addDay());
    // ShouldDispatchAfterCommit events wait for the (never-committing) outer transaction: release them by hand.
    $flush = fn () => app('db.transactions')->commit(DB::getDefaultConnection(), 1, 0);
    $flush();
    $say('manual lead: one LeadCreated and no extra updates', [Event::dispatched(LeadCreated::class)->count(), Event::dispatched(LeadUpdated::class)->count()], [1, 0]);
    $repeat = app(CreateManualLead::class)->handle($manager, ['name' => 'Event Walkin', 'channel' => 'phone_call'], '+8801719990303', null, null);
    $flush();
    $say('repeat enquiry: LeadUpdated touched', Event::dispatched(LeadUpdated::class, fn ($event) => $event->change === LeadChange::Touched)->count(), 1);
    app(ChangeLeadStatus::class)->handle($manager, $created['lead'], LeadStatus::Contacted);
    app(ReassignLead::class)->handle($manager, $created['lead'], $manager);
    $flush();
    $say('status and owner changes raise LeadUpdated', [
        Event::dispatched(LeadUpdated::class, fn ($event) => $event->change === LeadChange::Status)->count(),
        Event::dispatched(LeadUpdated::class, fn ($event) => $event->change === LeadChange::Assigned)->count(),
    ], [1, 1]);
} finally {
    DB::rollBack();
    Settings::bump();
}

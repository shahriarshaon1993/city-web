<?php

/**
 * Phase 15.2 check: leads admin rules — scoping (AC-LEAD-05), policies, status + reasons + Undo, follow-up,
 * reassign, notes, manual lead (DEC-33), masking for the Viewer, export phones as text, built-in views,
 * board columns, Recycle bin for leads, hard erase for subscribers. Everything is rolled back.
 * Run: php artisan tinker docs/checks/15.2-leads-admin.php < /dev/null   (needs `db:seed` first)
 */

use App\Actions\Leads\AddLeadNote;
use App\Actions\Leads\ChangeLeadStatus;
use App\Actions\Leads\CreateManualLead;
use App\Actions\Leads\ReassignLead;
use App\Actions\Leads\SetLeadFollowUp;
use App\Actions\Leads\UndoLeadStatus;
use App\Actions\Newsletter\EraseSubscriber;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\RecycleBin\RestoreFromRecycleBin;
use App\Actions\Tables\ExportTable;
use App\Enums\LeadStatus;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Resources\Admin\LeadRowResource;
use App\Models\Lead;
use App\Models\NewsletterSubscriber;
use App\Models\Project;
use App\Models\RecycleBinEntry;
use App\Models\User;
use App\Queries\Admin\LeadsTable;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

$as = function (User $user, array $query = []): Request {
    $request = Request::create('/admin/leads', 'GET', $query);
    auth()->setUser($user);
    app()->instance('request', $request);

    return $request;
};

DB::beginTransaction();

try {
    $admin = User::role('Super Admin')->firstOrFail();
    $make = function (string $role) {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    };
    $manager = $make('Sales Manager');
    $exec = $make('Sales Executive');
    $other = $make('Sales Executive');
    $viewer = $make('Viewer');
    $project = Project::query()->published()->firstOrFail();

    $lead = fn (array $extra = []): Lead => Lead::create([
        'reference' => (string) Str::ulid(), 'type' => 'project_inquiry', 'status' => LeadStatus::New,
        'name' => 'Check Person', 'phone' => '+8801719990001', 'source_url' => '/x', 'consent_text' => 'ok',
        'last_touched_at' => now(), ...$extra,
    ]);
    $mine = $lead(['assigned_to' => $exec->id, 'name' => 'Mine One', 'phone' => '+8801719990001', 'email' => 'mine@example.com']);
    $theirs = $lead(['assigned_to' => $other->id, 'name' => 'Theirs One', 'phone' => '+8801719990002']);

    // --- AC-LEAD-05 + policies --------------------------------------------------------------------------
    $say('exec sees only their own', Lead::query()->visibleTo($exec)->pluck('id')->all(), [$mine->id]);
    $say('exec opening another lead is refused', $exec->can('view', $theirs), false);
    $say('manager sees both', Lead::query()->visibleTo($manager)->whereIn('id', [$mine->id, $theirs->id])->count(), 2);
    $say('viewer: view yes, update no', [$viewer->can('view', $theirs), $viewer->can('update', $theirs)], [true, false]);
    $say('exec may update own, not assign', [$exec->can('update', $mine), $exec->can('assign', $mine)], [true, false]);
    $say('only an Admin deletes', [$manager->can('delete', $mine), $admin->can('delete', $mine)], [false, true]);
    $say('export: manager yes, exec no, viewer no', [$manager->can('export', Lead::class), $exec->can('export', Lead::class), $viewer->can('export', Lead::class)], [true, false, false]);

    // --- FR-LEAD-006 status, reason, Undo --------------------------------------------------------------------
    $change = app(ChangeLeadStatus::class);
    $say('moved to contacted', $change->handle($exec, $mine, LeadStatus::Contacted), true);
    $say('same status = no change', $change->handle($exec, $mine, LeadStatus::Contacted), false);
    $change->handle($exec, $mine, LeadStatus::Lost, 'price', 'wanted a lower rate');
    $mine->refresh();
    $say('lost reason stored', [$mine->status, $mine->lost_reason], [LeadStatus::Lost, 'Price too high — wanted a lower rate']);
    app(UndoLeadStatus::class)->handle($exec, $mine, LeadStatus::Contacted, null, null);
    $mine->refresh();
    $say('undo restores status and clears reason', [$mine->status, $mine->lost_reason], [LeadStatus::Contacted, null]);
    $change->handle($exec, $mine, LeadStatus::Won, 'booked', null);
    $say('won note stored', $mine->refresh()->won_note, 'Booking made');
    $change->handle($exec, $mine, LeadStatus::Negotiation);
    $say('reopening clears won note', $mine->refresh()->won_note, null);
    $say('timeline has the status lines', $mine->activities()->where('kind', 'status')->count(), 5);

    // --- follow-up, overdue ----------------------------------------------------------------------------------
    app(SetLeadFollowUp::class)->handle($exec, $mine, Carbon::now()->subDay());
    $say('past follow-up on an open lead = overdue', $mine->refresh()->followUpIsOverdue(), true);
    $say('same follow-up = no change', app(SetLeadFollowUp::class)->handle($exec, $mine, $mine->follow_up_at), false);
    $say('overdue filter finds it', app(LeadsTable::class)->props($as($manager, ['filter' => ['follow_up' => 'overdue']]), $manager)['rows'][0]['id'] ?? null, $mine->id);
    app(SetLeadFollowUp::class)->handle($exec, $mine, null);
    $say('cleared', $mine->refresh()->follow_up_at, null);

    // --- reassign, notes ---------------------------------------------------------------------------------------
    $say('reassign to the other exec', app(ReassignLead::class)->handle($manager, $mine, $other), true);
    $say('same owner = no change', app(ReassignLead::class)->handle($manager, $mine->refresh(), $other), false);
    app(ReassignLead::class)->handle($manager, $mine, $exec);
    $note = app(AddLeadNote::class)->handle($exec, $mine, '<p>Call <strong>after 5</strong><script>alert(1)</script></p>');
    $say('note is sanitised', str_contains($note->payload['html'], '<script'), false);
    try {
        app(AddLeadNote::class)->handle($exec, $mine, '<p></p>');
        $say('empty note refused', 'accepted', 'refused');
    } catch (ValidationException) {
        $say('empty note refused', 'refused', 'refused');
    }

    // --- DEC-33 manual lead -------------------------------------------------------------------------------------
    $manual = app(CreateManualLead::class);
    $first = $manual->handle($exec, ['name' => 'Walk In', 'channel' => 'walk_in', 'project_id' => $project->id, 'message' => 'Asked about 3 bed'], '+8801719990003', null, null);
    $new = $first['lead']->refresh();
    $say('manual lead created', [$first['touched'], $new->type, $new->status, $new->answers], [false, 'manual', LeadStatus::New, ['channel' => 'walk_in']]);
    $say('consent text names the user', $new->consent_text, "Recorded by {$exec->name} (Walk-in)");
    $say('creator who only sees own leads owns it', $new->assigned_to, $exec->id);
    $again = $manual->handle($manager, ['name' => 'Walk In', 'channel' => 'phone_call', 'project_id' => $project->id], '+8801719990003', null, null);
    $say('same phone + project today = touch', [$again['touched'], $again['lead']->id, $again['lead']->refresh()->touches_count], [true, $new->id, 1]);
    $say('still one lead', Lead::query()->where('phone', '+8801719990003')->count(), 1);
    $byManager = $manual->handle($manager, ['name' => 'Chosen', 'channel' => 'email'], '+8801719990004', $other, Carbon::now()->addDay());
    $say('manager picks the owner and a follow-up', [$byManager['lead']->assigned_to, $byManager['lead']->follow_up_at !== null], [$other->id, true]);

    // --- masking for the Viewer -----------------------------------------------------------------------------------
    $row = fn (User $user, Lead $lead): array => (new LeadRowResource($lead->load(['project', 'assignee'])))->toArray($as($user));
    $say('manager sees the phone', $row($manager, $mine)['phone'], '+8801719990001');
    $say('viewer sees it masked', $row($viewer, $mine)['phone'], '+880•••••••001');
    $say('viewer email masked', $row($viewer, $mine)['email'], 'm•••@example.com');
    $say('viewer flagged as hidden', $row($viewer, $mine)['contact_visible'], false);
    $table = app(LeadsTable::class);
    $say('manager finds a lead by 01719990001', count($table->props($as($manager, ['search' => '01719990001']), $manager)['rows']), 1);
    $say('viewer cannot find it by phone', count($table->props($as($viewer, ['search' => '01719990001']), $viewer)['rows']), 0);
    $say('viewer finds by name', count($table->props($as($viewer, ['search' => 'Mine One']), $viewer)['rows']), 1);

    // --- export: phones stay text (FR-LEAD-010) ---------------------------------------------------------------------
    $request = $as($manager, ['search' => 'Mine One']);
    $cells = iterator_to_array($table->exportRows($request, $manager, ['name', 'phone']), false);
    $say('export row', $cells[0], ['Mine One', '+8801719990001']);
    $safe = new ReflectionMethod(ExportTable::class, 'safe');
    $exporter = app(ExportTable::class);
    $say('phone column keeps the +', $safe->invoke($exporter, '+8801719990001', true), '+8801719990001');
    $say('a formula in a normal column is neutralised', $safe->invoke($exporter, '+cmd|calc', false), "'+cmd|calc");
    $say('a formula in a phone column is neutralised', $safe->invoke($exporter, '+1+1', true), "'+1+1");
    $say('table export flag', [$table->canExport($manager), $table->canExport($exec)], [true, false]);

    // --- built-in views + board -----------------------------------------------------------------------------------------
    $views = $table->views($manager);
    $say('three built-in views, negative ids', array_map(fn (array $view): array => [$view['id'], $view['builtin']], array_slice($views, 0, 3)), [[-1, true], [-2, true], [-3, true]]);
    $say('NRB view carries this month', str_starts_with($views[2]['state']['filter']['created_from'], now('Asia/Dhaka')->format('Y-m-')), true);
    $columns = $table->board($as($exec), $exec);
    $say('board: nine columns', count($columns), 9);
    $say('board: exec sees only their leads', array_sum(array_column($columns, 'count')), Lead::query()->visibleTo($exec)->count());
    $say('board card hides nothing the table hides', array_key_exists('phone', $columns[0]['cards'][0] ?? ['phone' => 1]), true);

    // --- HTTP smoke: the screens, the JSON for the drawer, 403, validation ------------------------------------------
    // The CSRF middleware only steps aside under the testing environment.
    $environment = app()->environment();
    app()->instance('env', 'testing');
    $http = function (User $user, string $method, string $url, array $data = [], array $headers = []) {
        auth()->setUser($user);
        $request = Request::create($url, $method, $data, [], [], array_merge(['HTTP_ACCEPT' => 'text/html'], $headers));

        return app(Kernel::class)->handle($request);
    };
    $json = ['HTTP_ACCEPT' => 'application/json'];
    $page = function (User $user, string $url) use ($http): array {
        $version = app(HandleInertiaRequests::class)->version(Request::create($url));
        $response = $http($user, 'GET', $url, [], ['HTTP_X_INERTIA' => 'true', 'HTTP_X_INERTIA_VERSION' => (string) $version, 'HTTP_ACCEPT' => 'text/html, application/xhtml+xml']);

        return [$response->getStatusCode(), json_decode((string) $response->getContent(), true)];
    };
    [$status, $body] = $page($manager, '/admin/leads');
    $say('table page renders', [$status, $body['component'] ?? null], [200, 'admin/leads/Index']);
    $say('table page has its props', isset($body['props']['rows'], $body['props']['reasons'], $body['props']['statuses']), true);
    [$status, $body] = $page($manager, '/admin/leads/board');
    $say('board page renders', [$status, $body['component'] ?? null, count($body['props']['columns'] ?? [])], [200, 'admin/leads/Board', 9]);
    [$status, $body] = $page($manager, '/admin/leads?lead='.$mine->id);
    $say('?lead= opens the drawer data', [$status, $body['props']['openLead']['id'] ?? null], [200, $mine->id]);
    $say('viewer: drawer phone is masked', $page($viewer, '/admin/leads?lead='.$mine->id)[1]['props']['openLead']['phone'] ?? null, '+880•••••••001');
    $say('exec opening another lead by URL = 403', $page($exec, '/admin/leads?lead='.$theirs->id)[0], 403);
    $drawer = $http($manager, 'GET', '/admin/leads/'.$mine->id, [], $json);
    $say('drawer JSON', [$drawer->getStatusCode(), json_decode((string) $drawer->getContent(), true)['data']['id'] ?? null], [200, $mine->id]);
    $say('drawer JSON for another exec = 403', $http($exec, 'GET', '/admin/leads/'.$theirs->id, [], $json)->getStatusCode(), 403);
    $say('browser visit of a lead redirects to the list', $http($manager, 'GET', '/admin/leads/'.$mine->id)->headers->get('Location'), url('/admin/leads?lead='.$mine->id));
    $won = $http($exec, 'PATCH', '/admin/leads/'.$mine->id.'/status', ['status' => 'won'], $json);
    $say('won without a reason = 422 on reason', [$won->getStatusCode(), array_keys(json_decode((string) $won->getContent(), true)['errors'] ?? [])], [422, ['reason']]);
    $say('viewer cannot change status (403)', $http($viewer, 'PATCH', '/admin/leads/'.$mine->id.'/status', ['status' => 'contacted'], $json)->getStatusCode(), 403);
    $say('exec cannot assign (403)', $http($exec, 'PATCH', '/admin/leads/'.$mine->id.'/assignee', ['assignee_id' => $other->id], $json)->getStatusCode(), 403);
    $say('manager cannot delete (403)', $http($manager, 'DELETE', '/admin/leads/'.$mine->id, [], $json)->getStatusCode(), 403);
    $say('export refused for the exec (403)', $http($exec, 'GET', '/admin/export/leads', ['format' => 'csv'])->getStatusCode(), 403);
    $say('viewer opens Leads, not Subscribers', [$page($viewer, '/admin/leads')[0], $page($viewer, '/admin/subscribers')[0]], [200, 403]);
    $bad = $http($manager, 'POST', '/admin/leads', ['name' => 'X', 'phone' => '123', 'phone_country' => 'BD', 'channel' => 'walk_in'], $json);
    $say('manual lead with a bad phone = 422 on phone', [$bad->getStatusCode(), array_keys(json_decode((string) $bad->getContent(), true)['errors'] ?? [])], [422, ['phone']]);
    $http($manager, 'POST', '/admin/leads', ['name' => 'By Form', 'phone' => '01719990009', 'phone_country' => 'BD', 'channel' => 'phone_call'], $json);
    $say('manual lead from the dialog', Lead::query()->where('phone', '+8801719990009')->value('type'), 'manual');
    [$status, $body] = $page($manager, '/admin/subscribers');
    $say('subscribers page renders', [$status, $body['component'] ?? null], [200, 'admin/Subscribers']);
    app()->instance('env', $environment);

    // --- Recycle bin + subscribers ----------------------------------------------------------------------------------
    $entry = app(MoveToRecycleBin::class)->handle($admin, $theirs);
    $say('lead in the bin for 365 days', [$theirs->refresh()->trashed(), (int) round(now()->diffInDays($entry->purge_at, false))], [true, 365]);
    app(RestoreFromRecycleBin::class)->handle($admin, $entry, []);
    $say('restored', Lead::query()->whereKey($theirs->id)->exists(), true);

    $subscriber = NewsletterSubscriber::create(['email' => 'erase-me@example.com', 'status' => 'active', 'token' => Str::random(40), 'consent_text' => 'ok']);
    $binned = app(MoveToRecycleBin::class)->handle($admin, $subscriber);
    $say('subscriber binned', $subscriber->refresh()->trashed(), true);
    app(RestoreFromRecycleBin::class)->handle($admin, $binned, []);
    app(EraseSubscriber::class)->handle($admin, $subscriber->refresh());
    $say('erase = gone for good, no bin row', [NewsletterSubscriber::withTrashed()->where('email', 'erase-me@example.com')->exists(), RecycleBinEntry::query()->where('type', 'newsletter_subscriber')->where('trashable_id', $subscriber->id)->exists()], [false, false]);
} catch (Throwable $e) {
    echo get_class($e).': '.$e->getMessage().'
'.implode('
', array_slice(explode('
', $e->getTraceAsString()), 0, 8)).'
';
} finally {
    DB::rollBack();
}

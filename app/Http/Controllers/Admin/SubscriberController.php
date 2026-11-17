<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Newsletter\EraseSubscriber;
use App\Actions\Newsletter\UnsubscribeSubscriber;
use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Enums\SubscriberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkSubscribersRequest;
use App\Models\NewsletterSubscriber;
use App\Queries\Admin\RecycleBinTable;
use App\Queries\Admin\SubscribersTable;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-12 Subscribers: list, export (the generic table export), unsubscribe, delete (Recycle bin
 * with Undo) and Erase (hard delete, no Undo). Controllers only authorise, validate, call an Action
 * and respond (DEC-32); each mutation flashes one toast (TOAST-01).
 */
class SubscriberController extends Controller
{
    public function index(Request $request, SubscribersTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', NewsletterSubscriber::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/Subscribers', [
            ...$table->props($request, $actor),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, NewsletterSubscriber::binType()),
            'breadcrumbs' => [
                ['title' => 'Sales', 'href' => null],
                ['title' => 'Subscribers', 'href' => null],
            ],
        ]);
    }

    public function unsubscribe(Request $request, NewsletterSubscriber $subscriber, UnsubscribeSubscriber $unsubscribeSubscriber): RedirectResponse
    {
        Gate::authorize('update', $subscriber);

        if (! $unsubscribeSubscriber->handle($request->user(), $subscriber)) {
            Toast::info('No change')->description('This address is already unsubscribed')->flash();

            return back();
        }

        Toast::success('Subscriber unsubscribed')->description($subscriber->email)->flash();

        return back();
    }

    /** DEL-02: Delete = Recycle bin for 365 days, with Undo (TOAST-02). */
    public function destroy(Request $request, NewsletterSubscriber $subscriber, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        Gate::authorize('delete', $subscriber);

        $entry = $moveToRecycleBin->handle($request->user(), $subscriber);

        Toast::success('Subscriber moved to Recycle bin')
            ->description($subscriber->email)
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return back();
    }

    /** ADM-SCR-12 / DEL: Erase is final — the address is gone and there is nothing to undo. */
    public function erase(Request $request, NewsletterSubscriber $subscriber, EraseSubscriber $eraseSubscriber): RedirectResponse
    {
        Gate::authorize('erase', $subscriber);

        $eraseSubscriber->handle($request->user(), $subscriber);

        Toast::success('Subscriber erased')->description('The address was removed for good.')->flash();

        return back();
    }

    public function bulk(BulkSubscribersRequest $request, SubscribersTable $table, UnsubscribeSubscriber $unsubscribeSubscriber, MoveManyToRecycleBin $moveMany): RedirectResponse
    {
        $actor = $request->user();
        $subscribers = $table->selection($request, $actor)->limit(BulkSubscribersRequest::MAX_IDS * 20)->get();

        if ($request->string('action')->toString() === 'delete') {
            $result = $moveMany->handle($actor, $subscribers);
            $count = count($result['entries']);
            $title = $count === 0 ? 'No subscribers were deleted' : $count.' '.($count === 1 ? 'subscriber' : 'subscribers').' moved to Recycle bin';
            $toast = $result['refused'] === []
                ? Toast::success($title)
                : Toast::warning($title.' · '.count($result['refused']).' skipped')->description(implode(' ', array_slice($result['refused'], 0, 3)));

            if ($count > 0) {
                $toast->undo(route('admin.recycle-bin.restore-many'), [
                    'ids' => array_map(fn ($entry): int => $entry->id, $result['entries']),
                    'undo' => true,
                ]);
            }

            $toast->flash();

            return back();
        }

        $changed = 0;

        foreach ($subscribers as $subscriber) {
            $changed += $subscriber->status !== SubscriberStatus::Unsubscribed && $unsubscribeSubscriber->handle($actor, $subscriber) ? 1 : 0;
        }

        $noun = $changed === 1 ? 'subscriber' : 'subscribers';
        Toast::success($changed === 0 ? 'No subscribers were changed' : "{$changed} {$noun} unsubscribed")->flash();

        return back();
    }
}

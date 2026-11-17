<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Notifications\DeleteNotifications;
use App\Actions\Notifications\MarkNotificationsRead;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Notifications\NotificationKind;
use App\Support\Notifications\NotificationPresenter;
use App\Support\Toasts\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-21 / §10.8: a person's own notifications. No permission is needed (everyone has an
 * inbox), and every query starts from `$request->user()`, so nobody can reach someone else's.
 */
class NotificationController extends Controller
{
    private const int PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $kind = NotificationKind::tryFrom((string) $request->query('type'));
        $unreadOnly = $request->query('status') === 'unread';

        $page = $user->notifications()
            ->when($kind !== null, fn ($query) => $query->where('type', $kind->notificationClass()))
            ->when($unreadOnly, fn ($query) => $query->whereNull('read_at'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('admin/Notifications', [
            'rows' => $page->getCollection()->map(NotificationPresenter::row(...))->values()->all(),
            'meta' => [
                'page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
                'prev_url' => $page->previousPageUrl(),
                'next_url' => $page->nextPageUrl(),
            ],
            'filters' => ['type' => $kind?->value, 'status' => $unreadOnly ? 'unread' : 'all'],
            'kinds' => array_map(fn (NotificationKind $case): array => ['value' => $case->value, 'label' => $case->label()], NotificationKind::cases()),
            'unread' => $user->unreadNotifications()->count(),
            'stats' => $this->stats($user),
            'hasRead' => $user->readNotifications()->exists(),
            'breadcrumbs' => [
                ['title' => 'Overview', 'href' => null],
                ['title' => 'Notifications', 'href' => null],
            ],
        ]);
    }

    /**
     * The summary band and the type filter counts: total / unread per type, and what arrived today (Dhaka).
     *
     * @return array{total: int, today: int, kinds: array<string, array{total: int, unread: int}>}
     */
    private function stats(User $user): array
    {
        $rows = $user->notifications()
            ->selectRaw('type, count(*) as total, sum(case when read_at is null then 1 else 0 end) as unread')
            ->groupBy('type')
            ->reorder()
            ->get();

        $kinds = [];

        foreach ($rows as $row) {
            $kind = NotificationKind::fromClass($row->type);

            if ($kind !== null) {
                $kinds[$kind->value] = ['total' => (int) $row->total, 'unread' => (int) $row->unread];
            }
        }

        return [
            'total' => $user->notifications()->count(),
            'today' => $user->notifications()->where('created_at', '>=', now('Asia/Dhaka')->startOfDay()->utc())->count(),
            'kinds' => $kinds,
        ];
    }

    /**
     * What the bell polls every 60 s (FR-LEAD-008a): the unread count and the newest items.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->limit(15)->get()->map(NotificationPresenter::row(...))->values()->all(),
        ]);
    }

    public function read(Request $request, string $notification, MarkNotificationsRead $markRead): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->notifications()->whereKey($notification)->exists(), 404);

        $markRead->handle($user, $notification);

        return response()->json(['unread' => $user->unreadNotifications()->count()]);
    }

    public function readAll(Request $request, MarkNotificationsRead $markRead): JsonResponse|RedirectResponse
    {
        $markRead->handle($request->user());

        if ($request->expectsJson()) {
            return response()->json(['unread' => 0]);
        }

        Toast::success('All notifications marked as read')->flash();

        return back();
    }

    public function destroy(Request $request, string $notification, DeleteNotifications $delete): RedirectResponse
    {
        abort_unless($request->user()->notifications()->whereKey($notification)->exists(), 404);

        $delete->handle($request->user(), $notification);

        Toast::success('Notification deleted')->flash();

        return back();
    }

    public function clear(Request $request, DeleteNotifications $delete): RedirectResponse
    {
        $count = $delete->handle($request->user());

        Toast::success($count === 1 ? '1 read notification deleted' : "{$count} read notifications deleted")->flash();

        return back();
    }
}

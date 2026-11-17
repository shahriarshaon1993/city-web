<?php

namespace App\Queries\Admin;

use App\Enums\SubscriberStatus;
use App\Http\Resources\Admin\SubscriberRowResource;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-12 newsletter subscribers (spec/leads.md DM-SUBS): pending, active, unsubscribed. The
 * export carries the visible columns of the list.
 *
 * @extends AdminTable<NewsletterSubscriber>
 */
final class SubscribersTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'subscribers';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['subscribers.manage'];
    }

    public function url(): string
    {
        return route('admin.subscribers.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return NewsletterSubscriber::query();
    }

    protected function filters(): array
    {
        $statuses = array_map(fn (SubscriberStatus $status): string => $status->value, SubscriberStatus::cases());

        return [
            AllowedFilter::callback('status', function (Builder $query, mixed $value) use ($statuses): void {
                $values = array_values(array_intersect(array_map(strval(...), Arr::wrap($value)), $statuses));

                if ($values !== []) {
                    $query->whereIn('status', $values);
                }
            }),
            ...$this->dateRange('created', 'created_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['email', 'status', 'confirmed_at', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $query->where('email', 'like', '%'.addcslashes($term, '%_\\').'%');
    }

    protected function resource(): string
    {
        return SubscriberRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'email' => ['label' => 'Email', 'value' => fn (NewsletterSubscriber $subscriber): string => $subscriber->email],
            'status' => ['label' => 'Status', 'value' => fn (NewsletterSubscriber $subscriber): string => ucfirst($subscriber->status->value)],
            'confirmed_at' => ['label' => 'Confirmed', 'value' => fn (NewsletterSubscriber $subscriber): ?string => $subscriber->confirmed_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
            'source' => ['label' => 'Signed up on', 'value' => fn (NewsletterSubscriber $subscriber): string => (string) $subscriber->source_url],
            'created_at' => ['label' => 'Added', 'value' => fn (NewsletterSubscriber $subscriber): ?string => $subscriber->created_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
            'consent' => ['label' => 'Consent text', 'value' => fn (NewsletterSubscriber $subscriber): string => $subscriber->consent_text],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $counts = NewsletterSubscriber::query()->select('status')->selectRaw('count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'status' => array_map(fn (SubscriberStatus $status): array => [
                'value' => $status->value,
                'label' => ucfirst($status->value),
                'count' => (int) ($counts[$status->value] ?? 0),
            ], SubscriberStatus::cases()),
        ];
    }
}

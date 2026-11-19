<?php

namespace App\Support\Import\Definitions;

use App\Actions\Newsletter\ImportSubscriber;
use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Support\Import\ImportDefinition;
use App\Support\Import\ImportField;
use App\Support\Import\ImportValues;
use App\Support\Import\RowResult;
use Carbon\CarbonInterface;

/**
 * ADM-IO-001 subscribers. Only people who agreed to the newsletter may be imported, so the wizard asks
 * the importer to confirm it. Someone who unsubscribed is never added again by a file; an address that
 * is already subscribed (or waiting in the Recycle bin) is skipped.
 */
final class SubscribersImport extends ImportDefinition
{
    public function help(): string
    {
        return 'Email addresses of people who already agreed to the newsletter. They are active at once; no confirmation mail is sent.';
    }

    public function fields(): array
    {
        return [
            new ImportField('email', 'Email', true, ['email address', 'e mail', 'mail'], 'rahim@example.com'),
            new ImportField('subscribed', 'Subscribed on', false, ['date', 'subscribed at', 'confirmed at', 'created time', 'created at'], '2026-05-14', 'Empty = today'),
        ];
    }

    public function options(): array
    {
        return [['key' => 'consent', 'label' => 'I confirm that everyone in this file agreed to receive our newsletter.', 'required' => true]];
    }

    public function check(array $row): RowResult
    {
        $email = mb_strtolower((string) ImportValues::text($row['email'] ?? '', 190));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return RowResult::fail($email === '' ? 'Email is empty.' : "“{$email}” is not an email address.");
        }

        $subscribed = ImportValues::date($row['subscribed'] ?? '');

        if ($subscribed === false) {
            return RowResult::fail('The date is not one we can read. Use 2026-05-14.');
        }

        $existing = NewsletterSubscriber::withTrashed()->where('email', $email)->first();

        if ($existing?->trashed()) {
            return RowResult::skip('This address is in the Recycle bin. Restore it there if it should be subscribed.');
        }

        if ($existing !== null) {
            return RowResult::skip($existing->status === SubscriberStatus::Unsubscribed
                ? 'This person unsubscribed earlier, so they are not added again.'
                : 'Already subscribed.');
        }

        return RowResult::create(['email' => $email, 'confirmed_at' => $subscribed ?: null]);
    }

    protected function write(User $actor, RowResult $checked, array $options): RowResult
    {
        /** @var string $email */
        $email = $checked->data['email'];
        /** @var CarbonInterface|null $confirmedAt */
        $confirmedAt = $checked->data['confirmed_at'];

        app(ImportSubscriber::class)->handle($actor, $email, $confirmedAt, (string) ($options['file'] ?? 'a file'));

        return $checked;
    }
}

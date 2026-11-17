<?php

namespace App\Support\Leads;

/**
 * FR-LEAD-006: the select that goes with closing a lead as won or lost, and DEC-33: the channel of a
 * lead that staff add by hand. Keys are stored in the timeline, labels are what people read.
 */
final class LeadReasons
{
    /** @var array<string, string> */
    public const array LOST = [
        'price' => 'Price too high',
        'competitor' => 'Chose another developer',
        'no_response' => 'Could not reach them',
        'not_interested' => 'No longer interested',
        'location' => 'Location does not suit',
        'financing' => 'Financing fell through',
        'other' => 'Other',
    ];

    /** @var array<string, string> */
    public const array WON = [
        'booked' => 'Booking made',
        'agreement' => 'Agreement signed',
        'down_payment' => 'Down payment received',
        'other' => 'Other',
    ];

    /** @var array<string, string> */
    public const array CHANNELS = [
        'phone_call' => 'Phone call',
        'walk_in' => 'Walk-in',
        'email' => 'Email',
        'event' => 'Event',
        'other' => 'Other',
    ];

    /**
     * @param  array<string, string>  $list
     * @return list<array{value: string, label: string}>
     */
    public static function options(array $list): array
    {
        return array_map(
            fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
            array_keys($list),
            array_values($list),
        );
    }
}

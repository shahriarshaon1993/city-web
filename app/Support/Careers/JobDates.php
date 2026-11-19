<?php

namespace App\Support\Careers;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * FR-CAR-003 dates, all in Asia/Dhaka (ARC-016): a job is open through its deadline day and closes at
 * 00:05 the next morning, when the scheduler runs.
 */
final class JobDates
{
    public const string TIMEZONE = 'Asia/Dhaka';

    /** Today in Dhaka, at 00:00. */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }

    /** The moment the scheduler closes a job with this deadline: 00:05 the next day. */
    public static function closesAfter(CarbonInterface $deadline): CarbonImmutable
    {
        return CarbonImmutable::parse($deadline->format('Y-m-d'), self::TIMEZONE)->addDay()->setTime(0, 5);
    }
}

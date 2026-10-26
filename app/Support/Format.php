<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Display formatting shared with `resources/js/lib/format.ts` (ARC-016, ARC-017).
 * Both twins must return identical strings; change them together.
 */
final class Format
{
    public const string TIMEZONE = 'Asia/Dhaka';

    private const string TAKA = '৳';

    /** Keeps "৳" and units on the same line as their number. */
    private const string NBSP = "\u{00A0}";

    private const string EN_DASH = '–';

    private const array MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /**
     * South-Asian grouping (1,45,00,000), trailing decimal zeros trimmed.
     */
    public static function number(int|float $value, int $maxDecimals = 0): string
    {
        $scale = 10 ** $maxDecimals;
        $scaled = (int) round(abs($value) * $scale);
        $integer = intdiv($scaled, $scale);
        $fraction = rtrim(str_pad((string) ($scaled % $scale), $maxDecimals, '0', STR_PAD_LEFT), '0');

        $digits = (string) $integer;
        $grouped = strlen($digits) <= 3
            ? $digits
            : preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($digits, 0, -3)).','.substr($digits, -3);

        return ($value < 0 && $scaled > 0 ? '-' : '').$grouped.($fraction === '' ? '' : '.'.$fraction);
    }

    /**
     * Full taka amount: "৳ 1,45,00,000".
     */
    public static function bdt(int|float $amount): string
    {
        return self::TAKA.self::NBSP.self::number($amount);
    }

    /**
     * Compact taka amount: "৳ 1.45 crore", "৳ 85 lakh"; below one lakh the full form.
     */
    public static function bdtCompact(int|float $amount): string
    {
        [$value, $unit] = self::compactParts($amount);

        return self::TAKA.self::NBSP.$value.($unit === null ? '' : self::NBSP.$unit);
    }

    /**
     * Price range: "৳ 85–95 lakh", "৳ 85 lakh – 1.45 crore" (full: "৳ 85,00,000 – 1,45,00,000").
     */
    public static function bdtRange(int|float $min, int|float|null $max, bool $compact = true): string
    {
        if ($max === null || $max == $min) {
            return $compact ? self::bdtCompact($min) : self::bdt($min);
        }

        if (! $compact) {
            return self::bdt($min).self::NBSP.self::EN_DASH.' '.self::number($max);
        }

        [$minValue, $minUnit] = self::compactParts($min);
        [$maxValue, $maxUnit] = self::compactParts($max);

        if ($minUnit !== null && $minUnit === $maxUnit) {
            return self::TAKA.self::NBSP.$minValue.self::EN_DASH.$maxValue.self::NBSP.$maxUnit;
        }

        return self::bdtCompact($min).self::NBSP.self::EN_DASH.' '.$maxValue.($maxUnit === null ? '' : self::NBSP.$maxUnit);
    }

    /**
     * Area: "1,850 sft".
     */
    public static function sft(int|float $value): string
    {
        return self::number($value).self::NBSP.'sft';
    }

    /**
     * Area range with a closed en dash: "1,650–2,100 sft".
     */
    public static function sftRange(int|float $min, int|float|null $max): string
    {
        if ($max === null || $max == $min) {
            return self::sft($min);
        }

        return self::number($min).self::EN_DASH.self::number($max).self::NBSP.'sft';
    }

    /**
     * Land area: "5 katha", "3.5 katha".
     */
    public static function katha(int|float $value): string
    {
        return self::number($value, 2).self::NBSP.'katha';
    }

    /**
     * Date in Dhaka time, `DD MMM YYYY`: "25 Oct 2026".
     */
    public static function date(DateTimeInterface|string $value): string
    {
        $date = self::inDhaka($value);

        return $date->format('d').' '.self::MONTHS[$date->month - 1].' '.$date->format('Y');
    }

    /**
     * Month precision (handover dates): "Dec 2027".
     */
    public static function month(DateTimeInterface|string $value): string
    {
        $date = self::inDhaka($value);

        return self::MONTHS[$date->month - 1].' '.$date->format('Y');
    }

    /**
     * Download size: "940 KB", "2.4 MB" (KB rounded up, so never "0 KB").
     */
    public static function fileSize(int $bytes): string
    {
        if ($bytes < 1024 * 1024) {
            return self::number(max(1, (int) ceil($bytes / 1024))).self::NBSP.'KB';
        }

        return self::number($bytes / 1024 / 1024, 1).self::NBSP.'MB';
    }

    /**
     * Display form of an E.164 number: Bangladeshi "+880 9600-000000"
     * (DEC-40); other numbers unchanged.
     */
    public static function phone(string $e164): string
    {
        if (preg_match('/^\+880(\d{4})(\d{6})$/', $e164, $parts) === 1) {
            return '+880'.self::NBSP.$parts[1].'-'.$parts[2];
        }

        return $e164;
    }

    /**
     * @return array{0: string, 1: 'lakh'|'crore'|null}
     */
    private static function compactParts(int|float $amount): array
    {
        $hundredthsOfLakh = (int) round($amount / 1_000);

        if ($hundredthsOfLakh < 100) {
            return [self::number($amount), null];
        }

        if ($hundredthsOfLakh < 10_000) {
            return [self::number($hundredthsOfLakh / 100, 2), 'lakh'];
        }

        return [self::number(round($amount / 100_000) / 100, 2), 'crore'];
    }

    private static function inDhaka(DateTimeInterface|string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, 'UTC')->setTimezone(self::TIMEZONE);
    }
}

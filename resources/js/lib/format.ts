// Display formatting shared with `app/Support/Format.php` (ARC-016, ARC-017).
// Both twins must return identical strings; change them together. No Intl
// number formatting, so the server, Node SSR and every browser agree.

const TAKA = '৳';
/** Keeps "৳" and units on the same line as their number. */
const NBSP = ' ';
const EN_DASH = '–';
const TIMEZONE = 'Asia/Dhaka';
const MONTHS = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

type CompactUnit = 'lakh' | 'crore' | null;

/** South-Asian grouping (1,45,00,000), trailing decimal zeros trimmed. */
export function formatNumber(value: number, maxDecimals = 0): string {
    const scale = 10 ** maxDecimals;
    const scaled = Math.round(Math.abs(value) * scale);
    const integer = Math.floor(scaled / scale);
    const fraction = String(scaled % scale)
        .padStart(maxDecimals, '0')
        .replace(/0+$/, '');

    const digits = String(integer);
    const grouped =
        digits.length <= 3
            ? digits
            : `${digits.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',')},${digits.slice(-3)}`;

    return `${value < 0 && scaled > 0 ? '-' : ''}${grouped}${fraction === '' ? '' : `.${fraction}`}`;
}

/** Full taka amount: "৳ 1,45,00,000". */
export function formatBdt(amount: number): string {
    return `${TAKA}${NBSP}${formatNumber(amount)}`;
}

/** Compact taka amount: "৳ 1.45 crore", "৳ 85 lakh"; below one lakh the full form. */
export function formatBdtCompact(amount: number): string {
    const [value, unit] = compactParts(amount);

    return `${TAKA}${NBSP}${value}${unit === null ? '' : `${NBSP}${unit}`}`;
}

/** Price range: "৳ 85–95 lakh", "৳ 85 lakh – 1.45 crore" (full: "৳ 85,00,000 – 1,45,00,000"). */
export function formatBdtRange(
    min: number,
    max: number | null,
    compact = true,
): string {
    if (max === null || max === min) {
        return compact ? formatBdtCompact(min) : formatBdt(min);
    }

    if (!compact) {
        return `${formatBdt(min)}${NBSP}${EN_DASH} ${formatNumber(max)}`;
    }

    const [minValue, minUnit] = compactParts(min);
    const [maxValue, maxUnit] = compactParts(max);

    if (minUnit !== null && minUnit === maxUnit) {
        return `${TAKA}${NBSP}${minValue}${EN_DASH}${maxValue}${NBSP}${maxUnit}`;
    }

    return `${formatBdtCompact(min)}${NBSP}${EN_DASH} ${maxValue}${maxUnit === null ? '' : `${NBSP}${maxUnit}`}`;
}

/** Area: "1,850 sft". */
export function formatSft(value: number): string {
    return `${formatNumber(value)}${NBSP}sft`;
}

/** Area range with a closed en dash: "1,650–2,100 sft". */
export function formatSftRange(min: number, max: number | null): string {
    if (max === null || max === min) {
        return formatSft(min);
    }

    return `${formatNumber(min)}${EN_DASH}${formatNumber(max)}${NBSP}sft`;
}

/** Land area: "5 katha", "3.5 katha". */
export function formatKatha(value: number): string {
    return `${formatNumber(value, 2)}${NBSP}katha`;
}

/** Date in Dhaka time, `DD MMM YYYY`: "25 Oct 2026". */
export function formatDate(value: string | Date): string {
    const { day, month, year } = dhakaParts(value);

    return `${day} ${MONTHS[month - 1]} ${year}`;
}

/** Month precision (handover dates): "Dec 2027". */
export function formatMonth(value: string | Date): string {
    const { month, year } = dhakaParts(value);

    return `${MONTHS[month - 1]} ${year}`;
}

/** Display form of an E.164 number: Bangladeshi "+880 9600-000000" (DEC-40); other numbers unchanged. */
export function formatPhone(e164: string): string {
    const parts = /^\+880(\d{4})(\d{6})$/.exec(e164);

    return parts === null ? e164 : `+880${NBSP}${parts[1]}-${parts[2]}`;
}

function compactParts(amount: number): [string, CompactUnit] {
    const hundredthsOfLakh = Math.round(amount / 1_000);

    if (hundredthsOfLakh < 100) {
        return [formatNumber(amount), null];
    }

    if (hundredthsOfLakh < 10_000) {
        return [formatNumber(hundredthsOfLakh / 100, 2), 'lakh'];
    }

    return [formatNumber(Math.round(amount / 100_000) / 100, 2), 'crore'];
}

const dhakaFormatter = new Intl.DateTimeFormat('en-US', {
    timeZone: TIMEZONE,
    year: 'numeric',
    month: 'numeric',
    day: '2-digit',
});

function dhakaParts(value: string | Date): {
    day: string;
    month: number;
    year: string;
} {
    const parts = dhakaFormatter.formatToParts(toDate(value));
    const part = (type: Intl.DateTimeFormatPartTypes): string =>
        parts.find((p) => p.type === type)?.value ?? '';

    return {
        day: part('day'),
        month: Number(part('month')),
        year: part('year'),
    };
}

/** Date-only strings are UTC midnight, like Carbon::parse($value, 'UTC'). */
function toDate(value: string | Date): Date {
    if (value instanceof Date) {
        return value;
    }

    // Laravel serialises microseconds (".000000Z"); trim to milliseconds.
    return new Date(value.replace(/(\.\d{3})\d+/, '$1'));
}

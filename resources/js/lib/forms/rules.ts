// Client mirror of the Form Request rules (VAL-08, DEC-63): early feedback
// only, the server stays the truth. Messages are plain words with an
// example where a format is expected (VAL-02) and match lang/en/validation.php.

import { BD, normalizePhone } from '@/lib/forms/phone';

/** One check; returns the message or null. `data` is the whole form. */
export type Rule<T = Record<string, unknown>> = (
    value: unknown,
    data: T,
) => string | null;

export type Rules<T> = Partial<Record<keyof T & string, Rule<T>[]>>;

export function isEmpty(value: unknown): boolean {
    if (typeof value === 'string') {
        return value.trim() === '';
    }

    if (Array.isArray(value)) {
        return value.length === 0;
    }

    return value === null || value === undefined || value === false;
}

export const required =
    (message: string): Rule =>
    (value) =>
        isEmpty(value) ? message : null;

export const minLength =
    (min: number, message: string): Rule =>
    (value) =>
        typeof value === 'string' &&
        value.trim() !== '' &&
        value.trim().length < min
            ? message
            : null;

export const maxLength =
    (max: number, message: string): Rule =>
    (value) =>
        typeof value === 'string' && value.length > max ? message : null;

export const email =
    (message = 'Enter a valid email address, e.g. name@example.com.'): Rule =>
    (value) =>
        typeof value === 'string' &&
        value.trim() !== '' &&
        !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value.trim())
            ? message
            : null;

export const accepted =
    (message = 'Tick the box so we are allowed to contact you.'): Rule =>
    (value) =>
        value === true ? null : message;

/** Number within `min` (exclusive when `gtMin`) … `max`. */
export const numberBetween =
    (
        min: number,
        max: number,
        messages: { number: string; range: string },
        gtMin = false,
    ): Rule =>
    (value) => {
        if (isEmpty(value)) {
            return null;
        }

        const number = Number(String(value).trim());

        if (!Number.isFinite(number)) {
            return messages.number;
        }

        return (gtMin ? number <= min : number < min) || number > max
            ? messages.range
            : null;
    };

/**
 * FR-LEAD-002: valid for the country in `countryKey` (Bangladesh by
 * default); `dial` maps a country code to its dial code.
 */
export const phone =
    <T extends Record<string, unknown>>(
        dial: (country: string) => string | undefined,
        countryKey = 'phone_country',
    ): Rule<T> =>
    (value, data) => {
        if (isEmpty(value)) {
            return null;
        }

        const picked = data[countryKey];
        const country = typeof picked === 'string' ? picked : BD;

        return normalizePhone(String(value), country, dial(country)) === null
            ? country === BD
                ? 'Enter a Bangladeshi mobile number, e.g. 01712-345678.'
                : 'Enter a phone number with its area code, e.g. 50 123 4567, or start with + and the country code.'
            : null;
    };

/** ISO `YYYY-MM-DD` inside [min, max]; both are compared as strings. */
export const dateWithin =
    (min: string, max: string, message: string): Rule =>
    (value) =>
        isEmpty(value) || (String(value) >= min && String(value) <= max)
            ? null
            : message;

/** First message of the rules that fail, in order. */
export function firstError<T>(
    rules: Rule<T>[] | undefined,
    value: unknown,
    data: T,
): string | null {
    for (const rule of rules ?? []) {
        const message = rule(value, data);

        if (message !== null) {
            return message;
        }
    }

    return null;
}

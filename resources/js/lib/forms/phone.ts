// FR-LEAD-002 in the browser: mirrors App\Support\Forms\PhoneNumber so the
// field can warn early (VAL-08: the server stays the truth).

export const BD = 'BD';

/** Bangladesh mobile `01XXX-XXXXXX`; a typed `+88` / `88` prefix is dropped. */
function bdDigits(input: string): string {
    let digits = input.replace(/\D/g, '');

    if (digits.startsWith('880')) {
        digits = digits.slice(2);
    } else if (digits.startsWith('88') && digits.length > 11) {
        digits = digits.slice(2);
    }

    return digits.slice(0, 11);
}

/**
 * Formats while typing: Bangladesh as `01712-345678`, other countries keep
 * digits, spaces and a leading `+`.
 */
export function formatPhoneInput(input: string, country: string): string {
    if (country === BD) {
        const digits = bdDigits(input);

        return digits.length > 5
            ? `${digits.slice(0, 5)}-${digits.slice(5)}`
            : digits;
    }

    const plus = input.trim().startsWith('+') ? '+' : '';

    return (plus + input.replace(/[^\d\s]/g, '').replace(/\s+/g, ' ')).slice(
        0,
        20,
    );
}

/** E.164 or null when the number is not valid for the country. */
export function normalizePhone(
    input: string,
    country: string,
    dial: string | undefined,
): string | null {
    const cleaned = input.replace(/[^\d+]/g, '');

    if (country === BD) {
        const match = /^(?:\+?88)?(01[3-9]\d{8})$/.exec(cleaned);

        return match ? `+88${match[1]}` : null;
    }

    if (dial === undefined) {
        return null;
    }

    const e164 = cleaned.startsWith('+')
        ? cleaned
        : `+${dial}${cleaned.replace(/^0+/, '')}`;

    return /^\+[1-9]\d{7,14}$/.test(e164) ? e164 : null;
}

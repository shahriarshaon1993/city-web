import type { Office } from '@/types/site';

// DM-OFF opening hours (FR-SOC-005) as display lines. Days follow the
// Bangladeshi week (Saturday first); consecutive days collapse to a range.

const WEEK = ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
const NBSP = ' ';

function time(value: string): string {
    const [hours, minutes] = value.split(':').map(Number);
    const suffix = hours >= 12 ? 'pm' : 'am';
    const hour = hours % 12 === 0 ? 12 : hours % 12;
    const clock = minutes === 0 ? '' : `:${String(minutes).padStart(2, '0')}`;

    return `${hour}${clock}${NBSP}${suffix}`;
}

function dayRanges(days: string[]): string {
    const indexes = WEEK.flatMap((day, index) =>
        days.includes(day) ? [index] : [],
    );
    const ranges: string[] = [];
    let start = 0;

    for (let i = 1; i <= indexes.length; i++) {
        if (i < indexes.length && indexes[i] === indexes[i - 1] + 1) {
            continue;
        }

        const run = indexes.slice(start, i).map((index) => WEEK[index]);
        ranges.push(
            run.length > 2
                ? `${run[0]}–${run[run.length - 1]}`
                : run.join(', '),
        );
        start = i;
    }

    return ranges.join(', ');
}

/** e.g. "Sat–Thu, 9:30 am – 6:30 pm". */
export function officeHoursLines(office: Office): string[] {
    return office.hours.map(
        (slot) =>
            `${dayRanges(slot.days)}, ${time(slot.opens)} – ${time(slot.closes)}`,
    );
}

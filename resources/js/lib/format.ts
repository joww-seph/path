// Times are always shown in Paoay's time zone, wherever the visitor's phone is set.
const timeZone = 'Asia/Manila';

const dateFormatter = new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeZone,
});

const dateTimeFormatter = new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone,
});

const timeFormatter = new Intl.DateTimeFormat('en-PH', {
    timeStyle: 'short',
    timeZone,
});

const pesoFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    maximumFractionDigits: 2,
    minimumFractionDigits: 0,
});

export function formatDate(value: string | null | undefined): string {
    return value ? dateFormatter.format(new Date(value)) : '—';
}

export function formatDateTime(value: string | null | undefined): string {
    return value ? dateTimeFormatter.format(new Date(value)) : '—';
}

export function formatTime(value: string | null | undefined): string {
    return value ? timeFormatter.format(new Date(value)) : '—';
}

/**
 * Turn a 24-hour "HH:MM" string into "8:00 AM".
 */
export function formatClock(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const [hours, minutes] = value.split(':').map(Number);
    const suffix = hours >= 12 ? 'PM' : 'AM';

    return `${hours % 12 || 12}:${String(minutes).padStart(2, '0')} ${suffix}`;
}

export function formatPeso(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return pesoFormatter.format(Number(value));
}

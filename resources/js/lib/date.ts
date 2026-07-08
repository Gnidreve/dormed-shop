// Single source of truth for the German date format used across the app
// (DD.MM, zero-padded) — e.g. "07.07", not the browser-default "7.7".
export function formatDate(
    date: Date | string,
    options?: { withYear?: boolean },
): string {
    const d = typeof date === 'string' ? new Date(date) : date;

    return d.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        ...(options?.withYear ? { year: 'numeric' } : {}),
    });
}

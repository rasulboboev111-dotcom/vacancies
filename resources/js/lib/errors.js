// Shared helper for surfacing Inertia validation errors in a single line.

/**
 * Return the first human-readable message from an Inertia error bag, or the
 * fallback. Inertia error values are usually strings but can be arrays — this
 * unwraps to a string so callers can drop it straight into an alert.
 */
export function firstError(errors, fallback = '') {
    const first = Object.values(errors ?? {})[0];
    const value = Array.isArray(first) ? first[0] : first;
    return value != null ? String(value) : fallback;
}

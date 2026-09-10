/** Opt-in client for the new draft endpoint. Does not bind or change existing forms. */
export class ApiError extends Error {
    constructor(message, status) { super(message); this.name = 'ApiError'; this.status = status; }
}

export function createDraftClient({
    fetchImpl = globalThis.fetch.bind(globalThis),
    csrfToken = typeof document === 'undefined' ? '' : document.querySelector('meta[name="csrf-token"]')?.content ?? '',
} = {}) {
    let token = csrfToken;
    let pending = Promise.resolve();
    const request = async (method, payload) => {
        const response = await fetchImpl('/dashboard/api/work-draft.php', {
            method, credentials: 'same-origin', cache: 'no-store',
            headers: method === 'POST' ? { 'Content-Type': 'application/json', 'X-CSRF-Token': token } : {},
            ...(payload === undefined ? {} : { body: JSON.stringify(payload) }),
        });
        let data;
        try { data = await response.json(); }
        catch (_) { throw new ApiError('The server returned an unexpected response.', response.status); }
        if (!response.ok) throw new ApiError(data.error || 'The request failed.', response.status);
        if (typeof data.csrfToken === 'string') token = data.csrfToken;
        return data;
    };
    // Serialize writes so an older autosave cannot arrive after a newer save/clear.
    // A rejected request does not prevent a later explicit retry.
    const enqueue = operation => {
        const result = pending.then(operation, operation);
        pending = result.catch(() => {});
        return result;
    };
    const write = payload => enqueue(async () => {
        if (!token) await request('GET');
        return request('POST', payload);
    });
    return {
        load: () => enqueue(() => request('GET')),
        save: text => write({ action: 'save', text }),
        clear: () => write({ action: 'clear' }),
    };
}

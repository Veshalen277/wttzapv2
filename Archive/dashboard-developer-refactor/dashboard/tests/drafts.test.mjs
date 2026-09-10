import assert from 'node:assert/strict';
import { createDraftClient, ApiError } from '../js/api/drafts.mjs';
const calls = [];
let active = 0;
const client = createDraftClient({ csrfToken: '', fetchImpl: async (url, options) => {
    assert.equal(active++, 0, 'Requests must not overlap');
    calls.push(options);
    await new Promise(resolve => setTimeout(resolve, 1));
    active--;
    return { ok: true, status: 200, json: async () => options.method === 'GET' ? { csrfToken: 'token', draft: { text: '' } } : { saved: true } };
}});
await Promise.all([client.save('old'), client.save('new'), client.clear()]);
assert.deepEqual(calls.map(c => c.method), ['GET', 'POST', 'POST', 'POST']);
assert.deepEqual(calls.slice(1).map(c => JSON.parse(c.body).action), ['save', 'save', 'clear']);
assert.equal(JSON.parse(calls[2].body).text, 'new');
assert.equal(calls[1].headers['X-CSRF-Token'], 'token');
let failures = 0;
const retry = createDraftClient({ csrfToken: 'token', fetchImpl: async () => {
    const ok = failures++ > 0;
    return { ok, status: ok ? 200 : 403, json: async () => ok ? { saved: true } : { error: 'Refresh' } };
}});
await assert.rejects(retry.save('x'), error => error instanceof ApiError && error.status === 403);
assert.deepEqual(await retry.save('x'), { saved: true });
console.log('PASS: draft ordering, CSRF bootstrap, clear ordering, errors and explicit retry.');

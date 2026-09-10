const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
class Element {
    constructor(id = '', classes = []) {
        this.id = id; this.attrs = {}; this.listeners = {}; this.textContent = ''; this.hidden = false;
        this.children = []; this.classes = new Set(classes);
        this.classList = { contains: x => this.classes.has(x), toggle: (x, on) => {
            const value = on ?? !this.classes.has(x); value ? this.classes.add(x) : this.classes.delete(x); return value;
        }};
    }
    setAttribute(k, v) { this.attrs[k] = v; }
    getAttribute(k) { return this.attrs[k]; }
    hasAttribute(k) { return k in this.attrs; }
    addEventListener(k, fn) { this.listeners[k] = fn; }
    contains(target) { return target === this || this.children.includes(target); }
    focus() { this.focused = true; }
    append(child) { this.children.push(child); }
    replaceChildren() { this.children = []; }
    querySelector(selector) { return selector === 'h2' ? this.heading : this.children.find(child => child.tag === selector); }
    querySelectorAll() { return this.children; }
}
function setup({ mobile = false, storageBlocked = false } = {}) {
    const work = new Element('', ['is-selected']), stock = new Element(), direct = new Element();
    const sections = [work, stock, direct];
    sections.forEach((e, i) => { e.textContent = ['Work', 'Stock', 'Cashup'][i]; e.attrs['aria-controls'] = 'panel-' + i; if (i < 2) e.attrs['data-has-submenu'] = 'true'; });
    const panels = sections.map((section, i) => {
        const panel = new Element('panel-' + i); panel.heading = section;
        const link = new Element(); link.textContent = ['My Work Report', 'Inventory', 'Daily Cashup'][i]; link.attrs.href = '/dashboard/' + i + '.php'; panel.children.push(link); return panel;
    });
    const sidebar = new Element('portal-sidebar'), toggle = new Element(), backdrop = new Element(), body = new Element();
    const search = new Element(), results = new Element(), status = new Element(); results.hidden = true;
    const profile = new Element(), summary = new Element(); summary.tag = 'summary'; profile.children.push(summary);
    const nodes = { 'portal-sidebar': sidebar, 'portal-menu-search': search, 'portal-search-results': results, 'portal-search-status': status };
    const listeners = {}, media = { matches: mobile, addEventListener(k, fn) { this.changed = fn; } };
    const document = { body, querySelectorAll: s => s === '.portal-section' ? sections : s === '.portal-panel' ? panels : [],
        getElementById: id => nodes[id], querySelector: s => ({ '.portal-sidebar-toggle': toggle, '.portal-backdrop': backdrop, '.portal-profile': profile })[s],
        addEventListener: (k, fn) => { listeners[k] = fn; }, createElement: tag => { const e = new Element(); e.tag = tag; return e; } };
    const saved = {};
    const localStorage = { getItem: key => { if (storageBlocked) throw Error('blocked'); return saved[key]; }, setItem: (key, value) => { if (storageBlocked) throw Error('blocked'); saved[key] = value; } };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../js/navigation.js'), 'utf8'), { document, window: { matchMedia: () => media }, localStorage });
    return { work, stock, direct, panels, toggle, sidebar, backdrop, body, listeners, media, search, results, status, profile, summary };
}
function click(element, extra = {}) { const event = { button: 0, preventDefault() { this.prevented = true; }, ...extra }; element.listeners.click(event); return event; }
const app = setup();
assert.equal(app.panels[0].hidden, false);
assert.equal(app.panels[1].hidden, true);
assert.equal(app.toggle.attrs['aria-expanded'], 'true');
app.toggle.listeners.click(); assert.equal(app.body.classList.contains('portal-sidebar-collapsed'), true);
assert.equal(click(app.stock).prevented, true); assert.equal(app.panels[1].hidden, false); assert.equal(app.body.classList.contains('portal-sidebar-collapsed'), false);
assert.equal(click(app.work, { ctrlKey: true }).prevented, undefined); assert.equal(app.panels[1].hidden, false);
assert.equal(click(app.direct).prevented, undefined);
app.media.matches = true; app.media.changed(); assert.equal(app.toggle.attrs['aria-expanded'], 'false');
click(app.work); assert.equal(app.backdrop.hidden, false);
app.listeners.keydown({ key: 'Escape' }); assert.equal(app.backdrop.hidden, true); assert.equal(app.toggle.focused, true);
app.search.value = 'inventory'; app.search.listeners.input(); assert.equal(app.results.children.length, 1); assert.equal(app.results.children[0].href, '/dashboard/1.php');
app.search.listeners.keydown({ key: 'ArrowDown', preventDefault() {} }); assert.equal(app.results.children[0].focused, true);
app.listeners.keydown({ key: 'Escape' }); assert.equal(app.results.hidden, true); assert.equal(app.search.focused, true);
app.search.value = '<script>'; app.search.listeners.input(); assert.equal(app.results.children[0].tag, 'p'); assert.equal(app.results.children[0].textContent, 'No matching pages');
app.search.value = ''; app.search.listeners.input(); assert.equal(app.results.hidden, true);
app.profile.open = true; app.listeners.keydown({ key: 'Escape' }); assert.equal(app.profile.open, false); assert.equal(app.summary.focused, true);
const blocked = setup({ storageBlocked: true, mobile: true }); click(blocked.stock); assert.equal(blocked.sidebar.classList.contains('is-open'), true);
console.log('PASS: desktop collapse, contextual sections, native links, modifier clicks, responsive state, mobile dismissal, search matching/empty state/keyboard behavior, account dismissal, and blocked storage.');

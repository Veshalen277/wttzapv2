(() => {
  'use strict';
  if (!window.HTMLDialogElement || !HTMLDialogElement.prototype.showModal) return;
  const dialog = document.createElement('dialog');
  dialog.className = 'inventory-page inventory-drawer';
  dialog.setAttribute('aria-labelledby', 'inventory-editor-title');
  dialog.innerHTML = '<button type="button" class="inventory-editor-close" aria-label="Close editor">×</button><div data-editor-body></div>';
  document.body.append(dialog);
  const body = dialog.querySelector('[data-editor-body]');
  const closeButton = dialog.querySelector('button');
  let opener, dirty = false, busy = false, controller, generation = 0, originalOverflow;
  const status = document.createElement('div');
  status.className = 'inventory-save-status';
  status.setAttribute('role', 'status');
  status.hidden = true;
  document.body.append(status);
  function announce(message) {
    status.textContent = message;
    status.hidden = false;
  }
  function focusContent() {
    const target = body.querySelector('[role="alert"], #inventory-editor-title');
    if (target) target.focus({preventScroll: true});
    dialog.scrollTop = 0;
  }
  function close() {
    if (busy) return;
    if (dirty && !window.confirm('Discard your unsaved changes?')) return;
    controller?.abort();
    generation++;
    dialog.close();
  }
  dialog.addEventListener('close', () => {
    document.documentElement.style.overflow = originalOverflow;
    if (opener?.isConnected) opener.focus({preventScroll: true});
    dirty = false;
  });
  dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
  closeButton.addEventListener('click', close);
  body.addEventListener('input', () => { dirty = true; });
  body.addEventListener('change', () => { dirty = true; });
  body.addEventListener('click', event => {
    if (event.target.closest('[data-inventory-cancel]')) { event.preventDefault(); close(); }
  });
  document.addEventListener('click', async event => {
    const link = event.target.closest('a[data-inventory-editor]');
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    opener = link;
    dirty = false;
    busy = false;
    controller?.abort();
    controller = new AbortController();
    const ticket = ++generation;
    body.innerHTML = '<h2 id="inventory-editor-title" tabindex="-1">Loading item…</h2>';
    originalOverflow = document.documentElement.style.overflow;
    document.documentElement.style.overflow = 'hidden';
    dialog.showModal();
    focusContent();
    try {
      const response = await fetch(link.href, {headers: {'X-Inventory-Editor': '1'}, signal: controller.signal, cache: 'no-store'});
      const data = await response.json();
      if (ticket !== generation) return;
      if (typeof data.html !== 'string') throw new Error('Invalid editor response');
      body.innerHTML = data.html;
      focusContent();
    } catch (error) {
      if (error.name === 'AbortError' || ticket !== generation) return;
      body.innerHTML = '<h2 id="inventory-editor-title" tabindex="-1">Unable to open editor</h2><p>Please try the full editor page. You may need to sign in again.</p>';
      const fallback = document.createElement('a');
      fallback.href = link.href;
      fallback.textContent = 'Open full editor';
      body.append(fallback);
      focusContent();
    }
  });
  async function refreshList() {
    // Read fresh HTML; import only the inventory views, never scripts or the site shell.
    const url = new URL(location.href);
    url.hash = '';
    const response = await fetch(url, {cache: 'no-store'});
    if (!response.ok) throw new Error('Refresh failed');
    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
    const selectors = ['#catalog', '.inventory-summary', '#history'];
    const updates = selectors.map(selector => [document.querySelector(selector), page.querySelector(selector)]);
    if (updates.some(([old, fresh]) => !old || !fresh)) throw new Error('Session or view unavailable');
    const row = opener?.closest('[data-inventory-row]');
    const rowID = row?.getAttribute('data-inventory-row');
    const rowTop = row?.getBoundingClientRect().top;
    const position = window.scrollY;
    const mode = opener?.getAttribute('data-editor-mode');
    for (const [old, fresh] of updates) {
      const scrollBox = old.querySelector('.inventory-table');
      const left = scrollBox?.scrollLeft || 0;
      old.replaceWith(fresh);
      const nextBox = fresh.querySelector('.inventory-table');
      if (nextBox) nextBox.scrollLeft = left;
    }
    const replacement = rowID ? document.querySelector(`[data-inventory-row="${rowID}"]`) : null;
    const targetY = replacement ? position + replacement.getBoundingClientRect().top - rowTop : position;
    // Override Bootstrap's smooth scrolling for this one position restoration.
    const root = document.documentElement;
    const oldBehavior = root.style.getPropertyValue('scroll-behavior');
    const oldPriority = root.style.getPropertyPriority('scroll-behavior');
    root.style.setProperty('scroll-behavior', 'auto', 'important');
    window.scrollTo(window.scrollX, targetY);
    if (oldBehavior) root.style.setProperty('scroll-behavior', oldBehavior, oldPriority);
    else root.style.removeProperty('scroll-behavior');
    opener = replacement?.querySelector(`[data-editor-mode="${mode}"]`) || document.querySelector('#inventory-search');
  }
  body.addEventListener('submit', async event => {
    const form = event.target.closest('[data-inventory-form]');
    if (!form) return;
    event.preventDefault();
    if (busy || !form.reportValidity()) return;
    const payload = new FormData(form);
    busy = true;
    closeButton.disabled = true;
    const controls = [...form.elements].map(control => [control, control.disabled]);
    for (const [control] of controls) control.disabled = true;
    form.setAttribute('aria-busy', 'true');
    let saved = false;
    try {
      const response = await fetch(form.action, {method: 'POST', headers: {'X-Inventory-Editor': '1'}, body: payload});
      const data = await response.json();
      if (data.ok === true) {
        saved = true;
        dirty = false;
        let message = data.message;
        try { await refreshList(); }
        catch { message += ' The list could not refresh. Reload the page to see the saved result; do not submit it again.'; }
        busy = false;
        dialog.close();
        announce(message);
      } else if (typeof data.html === 'string') {
        body.innerHTML = data.html;
        focusContent();
      } else throw new Error('Unexpected response');
    } catch {
      const error = document.createElement('p');
      error.className = 'alert alert-danger';
      error.setAttribute('role', 'alert');
      error.textContent = 'The save could not be confirmed. Your entries are still here. Check inventory history before retrying; the request may have reached the server.';
      body.prepend(error);
    } finally {
      busy = false;
      closeButton.disabled = false;
      if (!saved && form.isConnected) {
        for (const [control, disabled] of controls) control.disabled = disabled;
        form.removeAttribute('aria-busy');
      }
    }
  });
})();

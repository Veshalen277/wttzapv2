/** Apply before styles load; each signed-in account has its own browser preference. */
(() => {
  'use strict';
  const root = document.documentElement;
  const key = `wtt.theme.${root.dataset.themeUser || 'guest'}`;
  const system = window.matchMedia('(prefers-color-scheme: dark)');
  const valid = value => ['light', 'dark', 'morning', 'afternoon'].includes(value);
  let choice = null;
  try { const saved = localStorage.getItem(key); if (valid(saved)) choice = saved; } catch (_) {}
  const apply = () => {
    const mode = choice || (system.matches ? 'dark' : 'light');
    root.dataset.appearance = mode;
    root.dataset.theme = mode === 'dark' ? 'dark' : 'light';
    root.setAttribute('data-bs-theme', root.dataset.theme);
    const select = document.getElementById('theme-select');
    if (select) select.value = choice || 'system';
  };
  apply();
  const initialize = () => {
    apply();
    document.getElementById('theme-select')?.addEventListener('change', event => {
      choice = valid(event.target.value) ? event.target.value : null;
      try { if (choice) localStorage.setItem(key, choice); else localStorage.removeItem(key); } catch (_) {}
      apply();
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
  else initialize();
  system.addEventListener('change', () => { if (!choice) apply(); });
  window.addEventListener('storage', event => {
    if (event.key !== key && event.key !== null) return;
    choice = valid(event.newValue) ? event.newValue : null;
    apply();
  });
})();

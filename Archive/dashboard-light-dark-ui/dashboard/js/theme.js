/** Apply before styles load; each signed-in account has its own browser preference. */
(() => {
  'use strict';
  const root = document.documentElement;
  const key = `wtt.theme.${root.dataset.themeUser || 'guest'}`;
  const system = window.matchMedia('(prefers-color-scheme: dark)');
  const valid = value => value === 'light' || value === 'dark';
  let choice = null;
  try { const saved = localStorage.getItem(key); if (valid(saved)) choice = saved; } catch (_) {}
  const apply = () => {
    const mode = choice || (system.matches ? 'dark' : 'light');
    root.dataset.theme = mode;
    root.setAttribute('data-bs-theme', mode);
    const button = document.getElementById('theme-toggle');
    if (button) {
      button.setAttribute('aria-pressed', String(mode === 'dark'));
      button.title = mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
    }
  };
  apply();
  const initialize = () => {
    apply();
    document.getElementById('theme-toggle')?.addEventListener('click', () => {
      choice = root.dataset.theme === 'dark' ? 'light' : 'dark';
      try { localStorage.setItem(key, choice); } catch (_) {}
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

(() => {
  'use strict';
  const input = document.getElementById('login-password');
  const toggle = document.getElementById('show-password');
  if (!input || !toggle) return;
  toggle.hidden = false;
  toggle.addEventListener('click', () => {
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    toggle.textContent = visible ? 'Hide' : 'Show';
    toggle.setAttribute('aria-pressed', String(visible));
    toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
  });
})();

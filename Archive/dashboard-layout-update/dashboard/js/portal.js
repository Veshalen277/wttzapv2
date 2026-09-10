/** Shared portal interactions. Keep default presentation controlled by existing CSS. */
(() => {
    'use strict';

    const initialize = () => {
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.setAttribute('aria-pressed', String(document.body.classList.contains('dark-mode')));
            themeToggle.addEventListener('click', () => {
                const isDark = document.body.classList.toggle('dark-mode');
                document.body.classList.toggle('light-mode', !isDark);
                themeToggle.setAttribute('aria-pressed', String(isDark));
            });
        }
    };

    // Delegation also covers cards added after the page loads.
    const updateChevron = (event, expanded) => {
        const card = event.target.closest('.card');
        const icon = card?.querySelector('.bi-chevron-down');
        if (icon) icon.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
    };
    document.addEventListener('shown.bs.collapse', event => updateChevron(event, true));
    document.addEventListener('hidden.bs.collapse', event => updateChevron(event, false));

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();

/** Section selection is local; page links preserve PHP forms, redirects and history. */
(() => {
    'use strict';
    // Reclaim old menu-only columns while keeping activity cards and other content.
    document.querySelectorAll('.portal-legacy-menu').forEach(marker => {
        let candidate = marker;
        while (candidate.parentElement?.tagName === 'DIV') {
            const parent = candidate.parentElement;
            if (parent.classList.contains('row')) break;
            const copy = parent.cloneNode(true);
            copy.querySelectorAll('h2, h3, h4, h5, h6').forEach(heading => {
                if (/^(menu|navigation|quick links)$/i.test(heading.textContent.trim())) heading.remove();
            });
            if (copy.textContent.trim() || copy.querySelector('input, textarea, select, button, a, img, table, iframe, canvas, script')) break;
            candidate = parent;
        }
        const row = candidate.parentElement;
        const wasColumn = [...candidate.classList].some(name => /^col(?:-|$)/.test(name));
        candidate.remove();
        if (wasColumn && row?.classList.contains('row')) {
            [...row.children].forEach(column => {
                if (![...column.classList].some(name => /^col(?:-|$)/.test(name))) return;
                [...column.classList].filter(name => /^col(?:-(?:sm|md|lg|xl|xxl))?-\d+$/.test(name)).forEach(name => column.classList.remove(name));
                column.classList.add('col-12', 'col-lg');
            });
        }
    });

    const sections = [...document.querySelectorAll('.portal-section')];
    const panels = [...document.querySelectorAll('.portal-panel')];
    const sidebar = document.getElementById('portal-sidebar');
    const toggle = document.querySelector('.portal-sidebar-toggle');
    if (!sidebar || !toggle) return;

    const setOpen = open => {
        sidebar.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
    };
    const selectSection = section => {
        sections.forEach(link => {
            const selected = link === section;
            link.classList.toggle('is-selected', selected);
            link.setAttribute('aria-expanded', String(selected));
        });
        panels.forEach(panel => { panel.hidden = panel.id !== section.getAttribute('aria-controls'); });
        toggle.textContent = `${section.textContent.trim()} menu`;
    };
    sections.forEach(section => {
        section.addEventListener('click', event => {
            // Leave modifier clicks and direct-page sections as ordinary links.
            if (!section.hasAttribute('data-has-submenu') || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            event.preventDefault();
            selectSection(section);
            if (window.matchMedia('(max-width: 900px)').matches) setOpen(true);
        });
    });
    const selected = sections.find(section => section.classList.contains('is-selected'));
    if (selected) selectSection(selected);
    toggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('is-open')));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
            setOpen(false);
            toggle.focus();
        }
    });
    document.addEventListener('click', event => {
        if (!sidebar.contains(event.target) && !toggle.contains(event.target) && !event.target.closest('.portal-sections')) setOpen(false);
    });
})();

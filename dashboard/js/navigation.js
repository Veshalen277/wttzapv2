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
    const backdrop = document.querySelector('.portal-backdrop');
    const profile = document.querySelector('.portal-profile');
    const media = window.matchMedia('(max-width: 900px)');
    if (!sidebar || !toggle) return;

    // The saved preference is only desktop presentation, never permissions or page state.
    let desktopCollapsed = false;
    try { desktopCollapsed = localStorage.getItem('wtt.sidebar.collapsed') === 'true'; } catch (_) {}
    const setOpen = open => {
        sidebar.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Collapse section sidebar' : 'Expand section sidebar');
        if (backdrop) backdrop.hidden = !media.matches || !open;
        if (!media.matches) {
            desktopCollapsed = !open;
            document.body.classList.toggle('portal-sidebar-collapsed', !open);
            try { localStorage.setItem('wtt.sidebar.collapsed', String(!open)); } catch (_) {}
        }
    };
    const synchronizeViewport = () => {
        document.body.classList.toggle('portal-sidebar-collapsed', !media.matches && desktopCollapsed);
        setOpen(media.matches ? false : !desktopCollapsed);
    };
    const selectSection = section => {
        sections.forEach(link => {
            const selected = link === section;
            link.classList.toggle('is-selected', selected);
            link.setAttribute('aria-expanded', String(selected));
        });
        panels.forEach(panel => { panel.hidden = panel.id !== section.getAttribute('aria-controls'); });
    };
    sections.forEach(section => {
        section.addEventListener('click', event => {
            if (!section.hasAttribute('data-has-submenu') || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            event.preventDefault();
            selectSection(section);
            setOpen(true);
        });
    });
    const selected = sections.find(section => section.classList.contains('is-selected'));
    if (selected) selectSection(selected);
    synchronizeViewport();
    media.addEventListener('change', synchronizeViewport);
    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    backdrop?.addEventListener('click', () => { setOpen(false); toggle.focus(); });

    const search = document.getElementById('portal-menu-search');
    const results = document.getElementById('portal-search-results');
    const searchStatus = document.getElementById('portal-search-status');
    const closeSearch = () => {
        if (results) results.hidden = true;
        search?.setAttribute('aria-expanded', 'false');
    };
    if (search && results) {
        const pages = panels.flatMap(panel => [...panel.querySelectorAll('.portal-submenu-link')].map(link => ({
            title: link.textContent.trim(), href: link.getAttribute('href'), section: panel.querySelector('h2').textContent.trim(),
        })));
        search.addEventListener('input', () => {
            const query = search.value.trim().toLocaleLowerCase();
            results.replaceChildren();
            if (!query) { closeSearch(); if (searchStatus) searchStatus.textContent = ''; return; }
            const matches = pages.filter(page => `${page.title} ${page.section}`.toLocaleLowerCase().includes(query));
            matches.forEach(page => {
                const link = document.createElement('a');
                link.href = page.href;
                link.textContent = page.title;
                const label = document.createElement('small');
                label.textContent = page.section;
                link.append(label);
                results.append(link);
            });
            if (!matches.length) {
                const message = document.createElement('p');
                message.textContent = 'No matching pages';
                results.append(message);
            }
            results.hidden = false;
            search.setAttribute('aria-expanded', 'true');
            if (searchStatus) searchStatus.textContent = `${matches.length} matching pages`;
        });
        search.addEventListener('keydown', event => {
            if ((event.key === 'ArrowDown' || event.key === 'Enter') && !results.hidden) {
                const first = results.querySelector('a');
                if (first) { event.preventDefault(); first.focus(); }
            }
        });
    }
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        if (results && !results.hidden) { closeSearch(); search.focus(); return; }
        if (profile?.open) { profile.open = false; profile.querySelector('summary').focus(); return; }
        if (media.matches && sidebar.classList.contains('is-open')) { setOpen(false); toggle.focus(); }
    });
    document.addEventListener('click', event => {
        if (media.matches && !sidebar.contains(event.target) && !toggle.contains(event.target) && !event.target.closest('.portal-sections')) setOpen(false);
        if (profile && !profile.contains(event.target)) profile.open = false;
        if (!event.target.closest('.portal-search')) closeSearch();
    });
})();

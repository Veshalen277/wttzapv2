/** Shared portal interactions. Keep default presentation controlled by existing CSS. */
(() => {
    'use strict';

    // Delegation also covers cards added after the page loads.
    const updateChevron = (event, expanded) => {
        const card = event.target.closest('.card');
        const icon = card?.querySelector('.bi-chevron-down');
        if (icon) icon.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
    };
    document.addEventListener('shown.bs.collapse', event => updateChevron(event, true));
    document.addEventListener('hidden.bs.collapse', event => updateChevron(event, false));

})();

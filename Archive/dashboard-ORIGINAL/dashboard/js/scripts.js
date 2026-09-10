/** Legacy page helpers with optional dependencies and non-overlapping polling. */
(() => {
    'use strict';

    if (window.tinymce) {
        window.tinymce.init({ selector: 'textarea' });
    }

    const initialize = () => {
        const selectAll = document.getElementById('selectAllBoxes');
        selectAll?.addEventListener('change', () => {
            document.querySelectorAll('.checkBoxes').forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        });

        let requestPending = false;
        let timer;
        const schedule = () => {
            window.clearTimeout(timer);
            if (!document.hidden && document.querySelector('.usersonline')) {
                timer = window.setTimeout(loadUsersOnline, 500);
            }
        };
        const loadUsersOnline = async () => {
            if (requestPending || document.hidden || !document.querySelector('.usersonline')) return;
            requestPending = true;
            const controller = new AbortController();
            const timeout = window.setTimeout(() => controller.abort(), 10000);
            try {
                const response = await fetch('functions.php?onlineusers=result', {
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                if (!response.ok) return;
                const count = await response.text();
                document.querySelectorAll('.usersonline').forEach(element => {
                    element.textContent = count;
                });
            } catch (error) {
                // Preserve the last successful count when offline or on timeout.
            } finally {
                window.clearTimeout(timeout);
                requestPending = false;
                schedule();
            }
        };

        // Retain compatibility with pages that call the original public helper.
        window.loadUsersOnline = loadUsersOnline;
        document.addEventListener('visibilitychange', schedule);
        window.addEventListener('pagehide', () => window.clearTimeout(timer));
        window.addEventListener('pageshow', schedule);
        schedule();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();

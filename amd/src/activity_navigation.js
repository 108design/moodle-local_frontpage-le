// This file is part of Moodle - https://moodle.org/
// @copyright 2026 Andreas Giesen <andreas@108design.com>
// @license 108design source-available software license; see LICENSE.md
// Copyright 2026 Andreas Giesen. Use under the Software License in LICENSE.md.

/** Carry the verified FP origin through native activity links/forms and adapt Flexible Sections' back link. */
export const init = ({origin, module, returnurl, returnlabel}) => {
    const base = new URL(M.cfg.wwwroot);
    const modulePath = `${base.pathname.replace(/\/$/, '')}/mod/${module}/`;
    const propagate = raw => {
        let url;
        try {
            url = new URL(raw, document.baseURI);
        } catch {
            return null;
        }
        if (url.origin !== base.origin || !url.pathname.startsWith(modulePath)) {
            return null;
        }
        url.searchParams.set('fpnav', String(origin));
        return url.href;
    };
    const update = () => {
        // Course index and primary navigation remain course-level exits from this FP sequence.
        document.querySelectorAll('#region-main a[href], .secondary-navigation a[href]').forEach(link => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#')
                    || link.classList.contains('local-frontpage-activity-navigation-boundary')) {
                return;
            }
            const target = propagate(href);
            if (target && link.href !== target) {
                link.href = target;
            }
        });
        // Explicit course exits must not be recovered from the same-module referrer.
        document.querySelectorAll('#course-index a[href], .primary-navigation a[href]').forEach(link => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#')) {
                return; // Native course-index collapse controls are local anchors, not course exits.
            }
            const target = propagate(href);
            if (target) {
                const url = new URL(target);
                url.searchParams.set('fpnav', '0');
                if (link.href !== url.href) {
                    link.href = url.href;
                }
            }
        });
        document.querySelectorAll('form').forEach(form => {
            const target = propagate(form.getAttribute('action') || window.location.href);
            if (!target) {
                return;
            }
            form.action = target;
            // GET forms replace action query parameters, so keep the origin as a successful form control too.
            let input = form.querySelector('input[name="fpnav"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'fpnav';
                form.append(input);
            }
            input.value = String(origin);
        });
        document.querySelectorAll('[formaction]').forEach(button => {
            const target = propagate(button.getAttribute('formaction'));
            if (target) {
                button.setAttribute('formaction', target);
            }
        });
        document.querySelectorAll('.flexsections-activity-page-navigate-back a').forEach(link => {
            if (link.href === returnurl && link.dataset.fpnavReturn) {
                return;
            }
            const icon = link.querySelector('.icon');
            link.replaceChildren(...(icon ? [icon] : []), document.createTextNode(returnlabel));
            link.href = returnurl;
            link.dataset.fpnavReturn = '1';
        });
    };
    update();
    const observer = new MutationObserver(update);
    observer.observe(document.body, {childList: true, subtree: true});
    window.addEventListener('pagehide', () => observer.disconnect());
    window.addEventListener('pageshow', event => {
        if (event.persisted) {
            update();
            observer.observe(document.body, {childList: true, subtree: true});
        }
    });
};

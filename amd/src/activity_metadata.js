// This file is part of Moodle - https://moodle.org/
/**
 * Place the native Moodle activity information alongside the activity heading.
 *
 * @module local_frontpage/activity_metadata
 * @copyright 2026 Andreas Giesen
 * @license 108design source-available software license; see LICENSE.md
 */

/**
 * Reuse Moodle's actual description, conditions and completion controls.
 *
 * @param {object} config Display strings and authoring state.
 */
export const init = config => {
    const header = document.querySelector('[data-for="page-activity-header"]');
    const pageHeading = document.getElementById('page-header');
    const body = document.body;
    const compact = body.classList.contains('local-frontpage-course-page');
    const editor = body.classList.contains('local-frontpage-editor-mode');
    if (!compact && pageHeading?.classList.contains('header-maxwidth')) {
        const shell = pageHeading.parentElement;
        // Use the theme's own header width for the native tabs and standard Moodle content column.
        shell.querySelector(':scope > .secondary-navigation')
            ?.classList.add('header-maxwidth', 'local-frontpage-aligned-tabs');
        if (!editor) {
            shell.querySelector(':scope > #page-content > #region-main-box')
                ?.classList.add('header-maxwidth', 'local-frontpage-aligned-content');
        }
    }
    if (!header || header.classList.contains('local-frontpage-moodle-activity')) {
        return;
    }

    const intro = header.querySelector('#intro');
    const conditions = header.querySelector('[data-region="completionrequirements"]');
    const controls = [...pageHeading?.querySelectorAll('[data-region="completion-status"], [data-completion="manual"]') ?? []];
    if (!intro && !conditions && !controls.length &&
            !header.querySelector('[data-region="activity-dates"], [data-completion="manual"]')) {
        return;
    }

    const authoring = config.authoring || editor;
    const status = controls.some(control => control.matches('[data-region="completion-status"]'));
    if (conditions) {
        const count = conditions.querySelectorAll('.activity-completion-list > li, [role="listitem"]').length;
        const details = document.createElement('details');
        details.className = 'local-frontpage-moodle-completion';
        // On older Moodle versions individual learner states are part of the conditions themselves.
        details.open = !authoring && !status && Boolean(conditions.querySelector('[aria-label]'));
        const summary = document.createElement('summary');
        summary.className = 'btn btn-secondary';
        const label = document.createElement('span');
        label.textContent = count ? (count === 1 ? config.single : config.multiple).replace('{count}', count) : config.label;
        const chevrons = document.createElement('span');
        chevrons.className = 'local-frontpage-moodle-completion-chevrons';
        chevrons.setAttribute('aria-hidden', 'true');
        // The icon markup comes from the active Moodle renderer.
        chevrons.innerHTML = config.collapsedIcon + config.expandedIcon;
        summary.append(chevrons, label);

        const panel = document.createElement('div');
        panel.className = 'dropdown-menu local-frontpage-moodle-completion-panel';
        panel.tabIndex = 0;
        panel.setAttribute('role', 'group');
        panel.setAttribute('aria-label', config.label);
        if (authoring) {
            const note = document.createElement('p');
            note.className = 'small text-muted mb-2';
            note.textContent = config.note;
            panel.append(note);
        }
        const row = document.createElement('div');
        row.className = 'local-frontpage-moodle-activity-row';
        conditions.before(row);
        if (intro) {
            row.append(intro);
        }
        panel.append(conditions);
        details.append(summary, panel);
        row.append(details);
        details.addEventListener('keydown', event => {
            if (event.key === 'Escape' && details.open) {
                event.stopPropagation();
                details.open = false;
                summary.focus();
            }
        });
        details.addEventListener('focusout', event => {
            if (event.relatedTarget && !details.contains(event.relatedTarget)) {
                details.open = false;
            }
        });
        document.addEventListener('click', event => {
            if (details.open && !details.contains(event.target)) {
                details.open = false;
            }
        });
    }

    // Before 5.3 the description was a separate sibling after the activity information.
    if (intro && intro.parentElement === header) {
        header.prepend(intro);
    }
    if (controls.length) {
        const actions = document.createElement('div');
        actions.className = 'local-frontpage-moodle-completion-status';
        actions.append(...controls);
        header.prepend(actions);
    }

    header.classList.add('local-frontpage-moodle-activity');
    const breadcrumb = pageHeading?.querySelector('#page-navbar');
    if (breadcrumb?.textContent.trim() && breadcrumb.getClientRects().length) {
        if (compact) {
            // The Full course canvas uses a compact breadcrumb-only native heading.
            pageHeading.after(header);
        } else {
            breadcrumb.after(header);
        }
    } else if (pageHeading) {
        pageHeading.before(header);
    } else {
        document.getElementById('region-main')?.prepend(header);
    }
};

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

    const authoring = config.authoring || document.body.classList.contains('local-frontpage-editor-mode');
    const status = controls.some(control => control.matches('[data-region="completion-status"]'));
    if (conditions) {
        const count = conditions.querySelectorAll('.activity-completion-list > li, [role="listitem"]').length;
        const details = document.createElement('details');
        details.className = 'local-frontpage-moodle-completion';
        // On older Moodle versions individual learner states are part of the conditions themselves.
        details.open = !authoring && !status && Boolean(conditions.querySelector('[aria-label]'));
        const summary = document.createElement('summary');
        summary.textContent = count ? (count === 1 ? config.single : config.multiple).replace('{count}', count) : config.label;
        details.append(summary);
        if (authoring) {
            const note = document.createElement('p');
            note.className = 'small text-muted mb-2';
            note.textContent = config.note;
            details.append(note);
        }
        conditions.before(details);
        details.append(conditions);
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
        if (document.body.classList.contains('local-frontpage-course-page')) {
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

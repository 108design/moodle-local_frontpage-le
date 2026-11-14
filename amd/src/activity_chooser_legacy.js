// This file is part of Moodle - https://moodle.org/
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
import * as ActivityChooser from 'core_course/activitychooser';
import * as FormChangeChecker from 'core_form/changechecker';

const excludedModules = new Set(['frontpage', 'label']);

/** Remove module types Frontpage cannot embed whenever core redraws a chooser tab. */
const removeExcludedModules = (root = document) => {
    root.querySelectorAll?.('.modchooser [data-region="chooser-option-container"]').forEach((option) => {
        if (excludedModules.has(option.dataset.internal || '')) {
            option.remove();
        }
    });
};

/** Resolve a Moodle module name from any supported legacy chooser selection gesture. */
const selectedModule = (event) => {
    const addAction = event.target.closest?.('.modchooser [data-action="add-chooser-option"]');
    const option = event.target.closest?.('.modchooser [data-region="chooser-option-container"]');
    if (event.type === 'click' && !addAction) {
        return '';
    }
    if (event.type === 'keydown' && event.key !== 'Enter') {
        return '';
    }
    if (!['click', 'dblclick', 'keydown'].includes(event.type)) {
        return '';
    }
    let modname = option?.dataset.internal || '';
    if (!modname && addAction?.href) {
        modname = new URL(addAction.href, window.location.origin).searchParams.get('add') || '';
    }
    return excludedModules.has(modname) ? '' : modname;
};

/**
 * Open Moodle's 4.5-5.0 activity chooser while routing selections through Frontpage's return ticket.
 *
 * @param {Object} config
 */
export const init = (config) => {
    const container = document.getElementById(config.buttonid);
    const button = container?.querySelector('[data-action="open-chooser"]');
    if (!button) {
        return;
    }
    button.type = 'button';
    const form = button.closest('form');

    const routeSelection = (event) => {
        const modname = selectedModule(event);
        if (!modname) {
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        if (config.suppressformwarning && form) {
            FormChangeChecker.markFormSubmitted(form);
        }
        const target = new URL(config.selecturl, window.location.origin);
        target.searchParams.set('modname', modname);
        window.location.assign(target.toString());
    };
    document.addEventListener('click', routeSelection, true);
    document.addEventListener('dblclick', routeSelection, true);
    document.addEventListener('keydown', routeSelection, true);

    const observer = new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
            removeExcludedModules(node.matches?.('.modchooser') ? node : node.parentElement || document);
        }
    })));
    observer.observe(document.body, {childList: true, subtree: true});

    ActivityChooser.init(config.courseid, {tabmode: 0});
};

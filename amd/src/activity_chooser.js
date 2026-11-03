/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
import * as Dialogue from 'core_courseformat/local/activitychooser/dialogue';
import * as FormChangeChecker from 'core_form/changechecker';
import Notification from 'core/notification';
import * as Repository from 'core_courseformat/local/activitychooser/repository';

/**
 * Open Moodle's activity chooser while routing selections through Frontpage's return-ticket endpoint.
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

    if (config.suppressformwarning && form) {
        const markFormSubmitted = (event) => {
            const modal = event.target.closest('.modchooser');
            if (!modal) {
                return;
            }
            const isAddClick = event.type === 'click'
                && event.target.closest('[data-action="add-selected-chooser-option"]');
            const isOptionDoubleClick = event.type === 'dblclick'
                && event.target.closest('[data-modname]');
            const isOptionEnter = event.type === 'keydown'
                && event.key === 'Enter'
                && event.target.closest('[data-modname]');
            if (isAddClick || isOptionDoubleClick || isOptionEnter) {
                FormChangeChecker.markFormSubmitted(form);
            }
        };
        document.addEventListener('click', markFormSubmitted, true);
        document.addEventListener('dblclick', markFormSubmitted, true);
        document.addEventListener('keydown', markFormSubmitted, true);
    }

    button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();

        const footerData = Repository.getModalFooterData(config.courseid, config.sectionnum);
        const modulesData = Repository.getSectionModulesData(
            config.courseid,
            config.sectionid,
            null,
            null,
        ).then((modules) => modules
            .filter((module) => module.componentname !== 'mod_frontpage' && module.componentname !== 'mod_label')
            .map((module) => {
                const modname = module.componentname?.startsWith('mod_')
                    ? module.componentname.substring(4)
                    : '';
                if (!modname) {
                    return null;
                }
                const target = new URL(config.selecturl, window.location.origin);
                target.searchParams.set('modname', modname);
                return {...module, link: target.toString()};
            })
            .filter(Boolean));

        Dialogue.displayActivityChooserModal(footerData, modulesData).catch(Notification.exception);
    });
};

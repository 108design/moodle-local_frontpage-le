// This file is part of Moodle - https://moodle.org/
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
import * as FormChangeChecker from 'core_form/changechecker';

export const init = (url) => {
    const cancel = document.getElementById('id_cancel');
    cancel?.addEventListener('click', (event) => {
        event.preventDefault();
        if (cancel.form) {FormChangeChecker.markFormSubmitted(cancel.form);}
        window.location.assign(url);
    });
};

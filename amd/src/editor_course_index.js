/**
 * Close the native course index when entering the Frontpage activity editor.
 *
 * @module local_frontpage/editor_course_index
 * @copyright 2026 Andreas Giesen
 * @license 108design source-available software license; see LICENSE.md
 */
import Drawers from 'theme_boost/drawers';

/**
 * Close the drawer for this editor visit without changing the saved Moodle preference.
 */
export const init = () => {
    const drawer = document.querySelector('[data-region="fixed-drawer"][data-preference="drawer-open-index"]');
    if (drawer?.classList.contains('show')) {
        Drawers.getDrawerInstanceForNode(drawer).closeDrawer({focusOnOpenButton: false, updatePreferences: false});
    }
    document.body.classList.remove('local-frontpage-close-course-index');
};

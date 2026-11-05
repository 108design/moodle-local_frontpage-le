<?php
/**
 * Connect native Moodle hooks to the component runtime.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

/**
 * Connect native Moodle hooks to the component runtime.
 */
final class hook_callbacks {
    /**
     * Prepare the component state before http headers.
     *
     * @param \core\hook\output\before_http_headers $hook Native Moodle output hook.
     * @return void
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $PAGE, $CFG;
        if (during_initial_install() || !get_config('local_frontpage', 'version')) {
            return;
        }
        $pending = activity_return::pending();
        $base = (new \moodle_url($CFG->wwwroot))->get_path();
        $path = substr($PAGE->url->get_path(), strlen(rtrim($base, '/')));
        if (
            ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && $pending
                && activity_return::matches_moodle_return($path, (int)($PAGE->course->id ?? 0), $pending)
        ) {
            redirect(activity_return::landing_url($pending));
        }
        $url = site_home::redirect_url($PAGE);
        if ($url) {
            redirect($url);
        }
    }
    /**
     * Return the component output appended to the native page footer.
     *
     * @param \core\hook\output\before_standard_footer_html_generation $hook Native Moodle output hook.
     * @return void
     */
    public static function footer(\core\hook\output\before_standard_footer_html_generation $hook): void {
        $hook->add_html(\local_frontpage\output\context_help::footer());
    }
}

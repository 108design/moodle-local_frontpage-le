<?php
// This file is part of Moodle - https://moodle.org/
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
     * Keep a wholly Frontpage-managed section out of the course view and index.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook Native Moodle output hook.
     */
    public static function hide_resource_section(\core\hook\output\before_standard_head_html_generation $hook): void {
        global $PAGE, $SCRIPT;

        $courseid = (int)$PAGE->course->id;
        if ($courseid <= SITEID || during_initial_install()) {
            return;
        }
        $section = course_resource_manager::get_hidden_resource_section($courseid);
        if (!$section) {
            return;
        }
        $id = (int)$section->id;
        $selectors = ["#course-index-section-{$id}"];
        if ($SCRIPT === '/course/view.php') {
            $selectors[] = ".course-content [data-for=\"section\"][data-id=\"{$id}\"]";
        }
        $hook->add_html(\html_writer::tag('style', implode(', ', $selectors) . ' { display: none !important; }'));
    }

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

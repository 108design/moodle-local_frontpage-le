<?php
/**
 * Warn editors before they change a Frontpage resource section through Moodle.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\output;

use context_course;
use core\hook\output\before_http_headers;
use core\notification;
use moodle_url;

/** Adds the warning to Moodle's native notification region. */
final class resource_section_warning {
    /**
     * Show the warning on the direct section view and before editing or deleting it.
     *
     * @param before_http_headers $hook Native Moodle output hook.
     */
    public static function prepare(before_http_headers $hook): void {
        global $DB, $PAGE, $SCRIPT;

        if (!in_array($SCRIPT, ['/course/section.php', '/course/editsection.php'], true)
                || !$PAGE->has_set_url() || !$PAGE->url->compare(new moodle_url($SCRIPT), URL_MATCH_BASE)) {
            return;
        }
        $sectionid = (int)$PAGE->url->get_param('id');
        if (!$sectionid) {
            return;
        }
        $section = $DB->get_record('course_sections', ['id' => $sectionid], 'id, course, component');
        if (!$section || ($section->component !== 'local_frontpage' && !(
            $section->component === null
            && (int)get_config('local_frontpage', 'resourcesection_' . $section->course) === $sectionid
        ))) {
            return;
        }
        $context = context_course::instance((int)$section->course);
        if (has_capability('moodle/course:manageactivities', $context)
                || has_capability('moodle/course:update', $context)) {
            notification::error(get_string('resourcesectiondeletionwarning', 'local_frontpage'));
        }
    }
}

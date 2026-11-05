<?php
/**
 * Keep page resource records in sync with native course-module events.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage;

/**
 * Keep page resource records in sync with native course-module events.
 */
final class observer {
    /**
     * Record the native module created for a pending editor request.
     *
     * @param \core\event\course_module_created $event Native Moodle event.
     * @return void
     */
    public static function course_module_created(\core\event\course_module_created $event): void {
        global $DB, $CFG;
        $cm = get_coursemodule_from_id('', (int)$event->objectid, 0, false, MUST_EXIST);
        if ($cm->modname === 'frontpage') {
            return;
        }
        $pending = \local_frontpage\local\activity_return::pending();
        $ticket = $pending && $cm->modname === $pending['modname']

        && \local_frontpage\local\activity_return::record_created(
            $cm->id,
            $cm->course,
            $cm->section,
            $event->userid
        );
        $section = \local_frontpage\local\course_resource_manager::get_section((int)$cm->course);
        $ownedsection = $section && (int)$section->id === (int)$cm->section;
        if ($ticket || $ownedsection) {
            require_once($CFG->dirroot . '/course/lib.php');
            set_coursemodule_visible($cm->id, (int)$cm->visible, 0);
            \local_frontpage\local\course_resource_manager::register(
                $cm->id,
                \local_frontpage\local\course_resource_manager::ORIGIN_CREATED,
                $event->userid,
                $ticket ? (int)$pending['pageid'] : null
            );
        }
    }
    /**
     * Update managed resource metadata after native module deletion.
     *
     * @param \core\event\course_module_deleted $event Native Moodle event.
     * @return void
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        \local_frontpage\local\course_resource_manager::mark_deleted((int)$event->objectid);
    }
}

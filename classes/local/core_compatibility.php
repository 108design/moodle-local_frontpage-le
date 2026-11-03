<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local;

/** Narrow compatibility boundary for Moodle APIs that changed after 4.5. */
final class core_compatibility {
    /** Moodle 5.1 moved the activity chooser from core_course to core_courseformat. */
    public static function uses_legacy_activity_chooser(?string $branch = null): bool {
        global $CFG;

        $branch = $branch ?? (string) ($CFG->branch ?? '');
        return $branch !== '' && version_compare($branch, '501', '<');
    }

    /** AMD entry point matching the installed Moodle activity chooser generation. */
    public static function activity_chooser_module(?string $branch = null): string {
        return self::uses_legacy_activity_chooser($branch)
            ? 'local_frontpage/activity_chooser_legacy'
            : 'local_frontpage/activity_chooser';
    }

    /** Core template matching the installed Moodle activity chooser generation. */
    public static function activity_chooser_template(?string $branch = null): string {
        return self::uses_legacy_activity_chooser($branch)
            ? 'core_course/addresourceoractivitybutton'
            : 'core_courseformat/local/content/addresourceoractivitybutton';
    }

    /** Delete through the current native API, retaining the supported older API. */
    public static function delete_course_module(int $cmid): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        if (method_exists(\core_courseformat\local\cmactions::class, 'delete')) {
            \core_courseformat\formatactions::cm((int)$cm->course)->delete($cmid);
        } else {course_delete_module($cmid);}
    }
    
}

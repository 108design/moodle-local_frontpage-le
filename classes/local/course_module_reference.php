<?php
/**
 * Resolves Moodle-owned activities and resources referenced by a course page.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

use context;
use invalid_parameter_exception;

/**
 * Resolves Moodle-owned activities and resources referenced by a course page.
 */
final class course_module_reference {
    /**
     * Build authoring choices from the page's own course.
     *
     * @param context $pagecontext Native context owning the page.
     * @param \stdClass|null $page Page record or native page context.
     * @return array
     */
    public static function choices(context $pagecontext, ?\stdClass $page = null): array {
        $courseid = self::course_id($pagecontext, $page);
        $modinfo = get_fast_modinfo($courseid);
        $managed = [];
        $existing = [];
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname === 'frontpage' || !empty($cm->deletioninprogress) || empty($cm->url)) {
                continue;
            }
            $label = get_string('coursemodulechoicelabel', 'local_frontpage', (object) [
                'type' => get_string('modulename', 'mod_' . $cm->modname),
                'name' => $cm->get_formatted_name(),
            ]);
            if (course_resource_manager::is_managed((int) $cm->id)) {
                $managed[(int) $cm->id] = $label;
            } else {
                $existing[(int) $cm->id] = $label;
            }
        }
        $choices = [];
        if ($managed) {
            $choices[get_string('choosefrontpageresource', 'local_frontpage')] = $managed;
        }
        if ($existing) {
            $choices[get_string('linkexistingactivityadvanced', 'local_frontpage')] = $existing;
        }
        return $choices;
    }

    /**
     * Resolve and verify that a reference belongs to the same course.
     *
     * @param context $pagecontext Native context owning the page.
     * @param int $cmid Course-module ID.
     * @param \stdClass|null $page Page record or native page context.
     * @return \cm_info
     */
    public static function get(context $pagecontext, int $cmid, ?\stdClass $page = null): \cm_info {
        if ($cmid <= 0) {
            throw new invalid_parameter_exception('A course module reference is required.');
        }
        $modinfo = get_fast_modinfo(self::course_id($pagecontext, $page));
        $cm = $modinfo->get_cm($cmid);
        if ($cm->modname === 'frontpage' || !empty($cm->deletioninprogress) || empty($cm->url)) {
            throw new invalid_parameter_exception('The referenced course module is not available for this page.');
        }
        return $cm;
    }

    /**
     * Resolve the only course whose modules may be referenced by this page.
     *
     * @param context $context Native Moodle context.
     * @param \stdClass|null $page Page record or native page context.
     * @return int
     */
    private static function course_id(context $context, ?\stdClass $page): int {
        if ($page) {
            $courseid = page_scope::course_id($page, $context);
            if ($courseid > 0) {
                return $courseid;
            }
        }
        if ($context->contextlevel === CONTEXT_MODULE) {
            $pagecm = get_coursemodule_from_id('frontpage', $context->instanceid, 0, false, MUST_EXIST);
            return (int) $pagecm->course;
        }
        throw new invalid_parameter_exception('This Frontpage page does not have a course module scope.');
    }
}

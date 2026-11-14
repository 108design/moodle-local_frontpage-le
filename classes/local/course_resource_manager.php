<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Owns the protected course section, its modules, and the revision reference index.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

use context_course;
use core_courseformat\formatactions;
use stdClass;

/**
 * Owns the protected course section, its modules, and the revision reference index.
 */
final class course_resource_manager {
    /**
     * Component.
     */
    public const COMPONENT = 'local_frontpage';
    /**
     * Origin created.
     */
    public const ORIGIN_CREATED = 'frontpage_created';
    /**
     * Origin adopted.
     */
    public const ORIGIN_ADOPTED = 'adopted';
    /**
     * Orphan grace days.
     */
    public const ORPHAN_GRACE_DAYS = 30;

    /**
     * Use an ordinary native section so whole-course backups include its activities.
     *
     * @param int $courseid Native course ID.
     * @return \section_info
     */
    public static function ensure_section(int $courseid): \section_info {
        global $DB, $CFG;
        require_capability('moodle/course:manageactivities', context_course::instance($courseid));
        $lock = \core\lock\lock_config::get_lock_factory(self::COMPONENT)->get_lock(
            'course_resources_' . $courseid,
            10
        );
        if (!$lock) {
            throw new \moodle_exception('locktimeout', 'local_frontpage');
        }
        try {
            $section = self::get_section($courseid);
            if ($section && !$section->visible) {
                return $section;
            }
            require_once($CFG->dirroot . '/course/lib.php');
            $transaction = $DB->start_delegated_transaction();
            $existing = $section ? self::owned_section_modules($courseid, (int)$section->id) : null;
            $updates = ['visible' => 0];
            if ($existing === null) {
                // An older section may contain normal Moodle activities placed there independently.
                $section = course_create_section($courseid);
                $existing = [];
                $updates += [
                    'name' => get_string('frontpageresourcessection', 'local_frontpage'),
                    'summary' => get_string('frontpageresourcessectionsummary', 'local_frontpage'),
                    'summaryformat' => FORMAT_HTML,
                ];
            }
            course_update_section($courseid, $section, $updates);
            foreach ($existing as $module) {
                // Core hides all children when hiding the section. Restore each native access setting.
                set_coursemodule_visible((int)$module->id, (int)$module->visible, 0, false);
            }
            self::remember_section($courseid, (int)$section->id);
            $transaction->allow_commit();
            rebuild_course_cache($courseid, true);
            return get_fast_modinfo($courseid)->get_section_info_by_id((int)$section->id, MUST_EXIST);
        } finally {
            $lock->release();
        }
    }

    /**
     * Return the modules of a section only if Frontpage owns all of them.
     *
     * @param int $courseid Native course ID.
     * @param int $sectionid Native section ID.
     * @return array|null Native modules, or null for a mixed section.
     */
    private static function owned_section_modules(int $courseid, int $sectionid): ?array {
        global $DB;
        $modules = $DB->get_records_sql(
            "SELECT cm.id, cm.visible, r.id AS resourceid
               FROM {course_modules} cm
          LEFT JOIN {local_frontpage_resource} r
                 ON r.cmid = cm.id AND r.courseid = cm.course AND r.origin = :origin
              WHERE cm.course = :courseid AND cm.section = :sectionid",
            ['origin' => self::ORIGIN_CREATED, 'courseid' => $courseid, 'sectionid' => $sectionid]
        );
        foreach ($modules as $module) {
            if (!$module->resourceid) {
                return null;
            }
        }
        return $modules;
    }

    /**
     * Store the section association.
     *
     * @param int $courseid Native course ID.
     * @param int $sectionid Native course section ID.
     * @return void
     */
    public static function remember_section(int $courseid, int $sectionid): void {
        global $DB;
        if (!$DB->record_exists('course_sections', ['id' => $sectionid, 'course' => $courseid, 'component' => null])) {
            throw new \moodle_exception('invalidsection');
        }
        // Course-scoped ownership metadata; the native backup plugin remaps the section id.
        set_config('resourcesection_' . $courseid, $sectionid, self::COMPONENT);
    }

    /**
     * Return the section.
     *
     * @param int $courseid Native course ID.
     * @return \section_info|null
     */
    public static function get_section(int $courseid): ?\section_info {
        global $DB;
        $sectionid = (int)get_config(self::COMPONENT, 'resourcesection_' . $courseid);
        if (
            !$sectionid
            || !$DB->record_exists(
                'course_sections',
                [ 'id' => $sectionid, 'course' => $courseid, 'component' => null ]
            )
        ) {
            return null;
        }
        return get_fast_modinfo($courseid)->get_section_info_by_id($sectionid);
    }

    /**
     * Find the hidden resource section if every activity in it belongs to Frontpage.
     *
     * @param int $courseid Native course ID.
     * @return \section_info|null
     */
    public static function get_hidden_resource_section(int $courseid): ?\section_info {
        $section = self::get_section($courseid);
        if (!$section || $section->visible || self::owned_section_modules($courseid, (int)$section->id) === null) {
            return null;
        }
        return $section;
    }

    /**
     * Register the native resource and its page association.
     *
     * @param int $cmid Course-module ID.
     * @param string $origin Originating Frontpage course module.
     * @param int|null $userid User ID.
     * @param int|null $sourcepageid Sourcepageid.
     * @return void
     */
    public static function register(int $cmid, string $origin, ?int $userid = null, ?int $sourcepageid = null): void {
        global $DB, $USER;

        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        if ($cm->modname === 'frontpage' || !in_array($origin, [self::ORIGIN_CREATED, self::ORIGIN_ADOPTED], true)) {
            throw new \invalid_parameter_exception('Unsupported course resource ownership.');
        }
        $section = get_fast_modinfo((int) $cm->course)->get_section_info_by_id((int) $cm->section, MUST_EXIST);
        $now = time();
        $record = $DB->get_record('local_frontpage_resource', ['cmid' => $cmid]);
        if ($record) {
            $DB->update_record('local_frontpage_resource', (object) [
                'id' => $record->id,
                'courseid' => $cm->course,
                'sectionid' => $section->id,
                'status' => 'active',
                'sourcepageid' => $sourcepageid ?: $record->sourcepageid,
                'orphanedsince' => null,
                'timemodified' => $now,
            ]);
            return;
        }
        $DB->insert_record('local_frontpage_resource', (object) [
            'courseid' => $cm->course,
            'sectionid' => $section->id,
            'cmid' => $cmid,
            'origin' => $origin,
            'status' => 'active',
            'sourcepageid' => $sourcepageid,
            'orphanedsince' => null,
            'createdby' => $userid ?? (int) $USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Check whether managed.
     *
     * @param int $cmid Course-module ID.
     * @return bool
     */
    public static function is_managed(int $cmid): bool {
        global $DB;
        return $DB->record_exists('local_frontpage_resource', [
            'cmid' => $cmid,
            'origin' => self::ORIGIN_CREATED,
            'status' => 'active',
        ]);
    }

    /**
     * Mark the deleted.
     *
     * @param int $cmid Course-module ID.
     * @return void
     */
    public static function mark_deleted(int $cmid): void {
        global $DB;
        $DB->set_field('local_frontpage_resource', 'status', 'deleted', ['cmid' => $cmid]);
        $DB->set_field('local_frontpage_resource', 'timemodified', time(), ['cmid' => $cmid]);
    }

    /**
     * Mark unreferenced owned resources as orphaned and clear that state when they are referenced again.
     *
     * @param int|null $courseid Native course ID.
     * @return void
     */
    public static function refresh_orphan_state(?int $courseid = null): void {
        global $DB;

        $params = ['origin' => self::ORIGIN_CREATED, 'status' => 'active'];
        $coursesql = '';
        if ($courseid !== null) {
            $coursesql = ' AND r.courseid = :courseid';
            $params['courseid'] = $courseid;
        }
        $sql = 'SELECT r.id, r.orphanedsince, COUNT(ref.id) AS referencecount
                  FROM {local_frontpage_resource} r
             LEFT JOIN {local_frontpage_cmref} ref ON ref.cmid = r.cmid
                 WHERE r.origin = :origin AND r.status = :status' . $coursesql . '
              GROUP BY r.id, r.orphanedsince';
        $now = time();
        foreach ($DB->get_records_sql($sql, $params) as $resource) {
            if ((int) $resource->referencecount === 0 && empty($resource->orphanedsince)) {
                $DB->set_field('local_frontpage_resource', 'orphanedsince', $now, ['id' => $resource->id]);
            } else if ((int) $resource->referencecount > 0 && !empty($resource->orphanedsince)) {
                $DB->set_field('local_frontpage_resource', 'orphanedsince', null, ['id' => $resource->id]);
            }
        }
    }

    /**
     * Return resources which completed the orphan grace period and still have no retained references.
     *
     * @param int $courseid Native course ID.
     * @return array
     */
    public static function get_cleanup_candidates(int $courseid): array {
        global $DB;

        $cutoff = time() - (self::ORPHAN_GRACE_DAYS * DAYSECS);
        $sql = 'SELECT r.*, p.title AS sourcepagetitle
                  FROM {local_frontpage_resource} r
                  JOIN {course_modules} cm ON cm.id = r.cmid AND cm.course = r.courseid
                       AND cm.deletioninprogress = 0
             LEFT JOIN {local_frontpage_page} p ON p.id = r.sourcepageid
                 WHERE r.courseid = :courseid
                   AND r.origin = :origin
                   AND r.status = :status
                   AND r.orphanedsince IS NOT NULL
                   AND r.orphanedsince <= :cutoff
                   AND NOT EXISTS (
                       SELECT 1 FROM {local_frontpage_cmref} ref WHERE ref.cmid = r.cmid
                   )
              ORDER BY r.sourcepageid, r.timecreated, r.id';
        return $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'origin' => self::ORIGIN_CREATED,
            'status' => 'active',
            'cutoff' => $cutoff,
        ]);
    }

    /**
     * Count the cleanup candidates.
     *
     * @param int $courseid Native course ID.
     * @return int
     */
    public static function count_cleanup_candidates(int $courseid): int {
        return count(self::get_cleanup_candidates($courseid));
    }

    /**
     * Return the cleanup candidate.
     *
     * @param int $resourceid Resourceid.
     * @param int $courseid Native course ID.
     * @return \stdClass
     */
    public static function get_cleanup_candidate(int $resourceid, int $courseid): \stdClass {
        $candidates = self::get_cleanup_candidates($courseid);
        if (!isset($candidates[$resourceid])) {
            throw new \moodle_exception('frontpageresourcenotdeletable', 'local_frontpage');
        }
        return $candidates[$resourceid];
    }

    /**
     * Synchronise the revision.
     *
     * @param int $revisionid Revision ID.
     * @param int $pageid Page ID.
     * @param string $documentjson Encoded page document.
     * @return void
     */
    public static function sync_revision(int $revisionid, int $pageid, string $documentjson): void {
        global $DB;

        $document = json_decode($documentjson, true);
        $segments = is_array($document) ? ($document['segments'] ?? []) : [];
        if (!empty($document['aboveHeaderSegment'])) {
            $segments[] = $document['aboveHeaderSegment'];
        }
        $DB->delete_records('local_frontpage_cmref', ['revisionid' => $revisionid]);
        foreach ($segments as $segment) {
            if (
                ($segment['type'] ?? '') !== 'course_module' || empty($segment['id'])
                || empty($segment['props']['cmid'])
            ) {
                continue;
            }
            $DB->insert_record('local_frontpage_cmref', (object) [
                'revisionid' => $revisionid,
                'pageid' => $pageid,
                'segmentid' => $segment['id'],
                'cmid' => (int) $segment['props']['cmid'],
            ]);
            $DB->set_field('local_frontpage_resource', 'orphanedsince', null, [
                'cmid' => (int) $segment['props']['cmid'],
                'origin' => self::ORIGIN_CREATED,
                'status' => 'active',
            ]);
        }
    }
}

<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Revision-owned settings for a standalone Frontpage activity's child navigation.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>.
// Use and modification are permitted only under the Software License in LICENSE.md.
namespace local_frontpage\local;

/**
 * Revision-owned settings for a standalone Frontpage activity's child navigation.
 */
final class activity_navigation_settings {
    /**
     * Key.
     */
    public const KEY = 'activitynavigation';

    /**
     * Validate and normalise the revision-owned activity navigation settings.
     *
     * @param mixed $value Input value to process.
     * @return array
     */
    public static function normalise(mixed $value): array {
        if (
            !is_array($value) || array_is_list($value)
                || !in_array($value['mode'] ?? 'inherit', ['inherit', 'on', 'off'], true)
                || !in_array($value['boundary'] ?? 'frontpage', ['frontpage', 'course'], true)
                || array_diff(array_keys($value), ['mode', 'boundary'])
        ) {
            throw new \invalid_parameter_exception('Invalid Frontpage activity navigation settings.');
        }
        return ['mode' => $value['mode'] ?? 'inherit', 'boundary' => $value['boundary'] ?? 'frontpage'];
    }

    /**
     * Inheritance is offered only where the installed Core and course format support it.
     *
     * @param \stdClass $course Native course record.
     * @return bool
     */
    public static function supports_inheritance(\stdClass $course): bool {
        global $CFG;
        require_once($CFG->dirroot . '/course/format/lib.php');
        return class_exists(\core_courseformat\local\linearnavigationsettings::class)
            && method_exists(course_get_format($course), 'uses_linear_navigation')
            && course_get_format($course)->uses_linear_navigation();
    }

    /**
     * Resolve the navigation settings stored in the page document.
     *
     * @param array $document Validated document data.
     * @return array
     */
    public static function for_document(array $document): array {
        return self::normalise($document[self::KEY] ?? ['mode' => 'inherit', 'boundary' => 'frontpage']);
    }

    /**
     * Report or render the current enablement state.
     *
     * @param array $settings Validated settings.
     * @param \stdClass $course Native course record.
     * @return bool
     */
    public static function enabled(array $settings, \stdClass $course): bool {
        return match ($settings['mode']) {
            'off' => false,
            'on' => true,
            default => !self::supports_inheritance($course)
                || \core_courseformat\local\linearnavigationsettings::is_linear_navigation_enabled($course),
        };
    }
}

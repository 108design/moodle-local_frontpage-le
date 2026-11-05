<?php
/**
 * Validates a deliberately bounded custom CSS length used by typed Frontpage controls.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

use invalid_parameter_exception;

/**
 * Validates a deliberately bounded custom CSS length used by typed Frontpage controls.
 */
final class css_length_value {
    /**
     * Validate and normalise a supported CSS length.
     *
     * @param string $value Input value to process.
     * @return string
     */
    public static function normalise(string $value): string {
        $value = width_value::normalise($value);
        if (in_array($value, ['none', 'min-content', 'max-content', 'fit-content', 'stretch'], true)) {
            throw new invalid_parameter_exception('Enter a CSS length, percentage, or supported CSS math expression.');
        }
        return $value;
    }
}

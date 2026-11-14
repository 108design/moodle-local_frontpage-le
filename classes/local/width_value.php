<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Validates the deliberately limited CSS max-width values supported by Frontpage.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

use invalid_parameter_exception;

/**
 * Validates the deliberately limited CSS max-width values supported by Frontpage.
 */
final class width_value {
    /**
     * Units accepted in plain and CSS-math width values.
     */
    private const UNITS = '(?:px|rem|em|ch|ex|vw|vh|vmin|vmax|%|cm|mm|q|in|pt|pc)';

    /**
     * Normalise a safe CSS max-width value.
     *
     * This accepts ordinary non-negative lengths/percentages and CSS math functions. It intentionally rejects
     * variables, URLs, declarations, global keywords, and other constructs that are unnecessary for a width field.
     *
     * @param string $value Submitted value.
     * @return string Normalised value.
     */
    public static function normalise(string $value): string {
        $value = trim($value);
        if ($value === '') {
            throw new invalid_parameter_exception('A custom width must not be empty.');
        }
        if (in_array(strtolower($value), ['none', 'min-content', 'max-content', 'fit-content', 'stretch'], true)) {
            return strtolower($value);
        }
        if (preg_match('/^(?:0|(?:\d+(?:\.\d+)?|\.\d+)' . self::UNITS . ')$/i', $value)) {
            return strtolower($value);
        }
        if (
            strlen($value) > 160
                || !preg_match('/^(?:calc|min|max|clamp)\([0-9a-z.%+*\/(),\s-]+\)$/i', $value)
                || preg_match('/[;{}\\"\'@#]/', $value)
                || !self::has_balanced_parentheses($value)
        ) {
            throw new invalid_parameter_exception('The custom width is not a supported CSS width value.');
        }

        $words = [];
        preg_match_all('/[a-z]+/i', $value, $words);
        $allowedwords = ['calc', 'min', 'max', 'clamp', 'px', 'rem', 'em', 'ch', 'ex', 'vw', 'vh',
            'vmin', 'vmax', 'cm', 'mm', 'q', 'in', 'pt', 'pc'];
        foreach ($words[0] as $word) {
            if (!in_array(strtolower($word), $allowedwords, true)) {
                throw new invalid_parameter_exception('The custom width is not a supported CSS width value.');
            }
        }

        return preg_replace('/\s+/', ' ', strtolower($value));
    }

    /**
     * Resolve a supported width exposed by the active theme, when one is known.
     *
     * @return string|null
     */
    public static function active_theme_width(): ?string {
        global $PAGE;

        $themename = $PAGE->theme->name ?? '';
        if ($themename === '') {
            return null;
        }
        $configured = get_config('theme_' . $themename, 'coursecontentmaxwidth');
        if (!is_string($configured) || trim($configured) === '') {
            return null;
        }
        try {
            return self::normalise($configured);
        } catch (invalid_parameter_exception) {
            return null;
        }
    }

    /**
     * Check parentheses without attempting to implement a complete CSS parser.
     *
     * @param string $value Input value to process.
     * @return bool
     */
    private static function has_balanced_parentheses(string $value): bool {
        $depth = 0;
        foreach (str_split($value) as $character) {
            if ($character === '(') {
                $depth++;
            } else if ($character === ')' && --$depth < 0) {
                return false;
            }
        }
        return $depth === 0;
    }
}

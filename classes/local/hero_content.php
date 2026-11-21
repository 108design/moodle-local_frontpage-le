<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Optional Hero identity and actions for both page hosts.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

/**
 * Optional Hero identity and actions for both page hosts.
 */
final class hero_content {
    /**
     * Text fields.
     */
    private const TEXT_FIELDS = ['mark', 'markalt', 'primarylabel', 'primaryurl', 'secondarylabel', 'secondaryurl'];









    /**
     * Validate and normalise the supplied value.
     *
     * @param array $props Validated segment properties.
     * @return array
     */
    public static function normalise(array $props): array {
        if (isset($props['headinglevel']) && !in_array($props['headinglevel'], [1, 2], true)) {
            throw new \invalid_parameter_exception('Invalid Hero heading level.');
        }
        foreach (self::TEXT_FIELDS as $key) {
            if (!isset($props[$key])) {
                continue;
            }
            if (!is_string($props[$key]) || strlen($props[$key]) > 2048) {
                throw new \invalid_parameter_exception('Invalid Hero content.');
            }
            $props[$key] = trim($props[$key]);
        }
        $mark = $props['mark'] ?? '';
        if ($mark !== '' && !preg_match('/^\{\{image-url:[a-zA-Z0-9][a-zA-Z0-9._ -]{0,199}\}\}$/', $mark)) {
            self::validate_url($mark, true);
        }
        foreach (['primary', 'secondary'] as $kind) {
            $label = $props[$kind . 'label'] ?? '';
            $url = $props[$kind . 'url'] ?? '';
            if (($label === '') !== ($url === '')) {
                throw new \invalid_parameter_exception('A Hero action needs both a label and a URL.');
            }
            if ($url !== '') {
                self::validate_url($url);
            }
        }
        return $props;
    }

    /**
     * Validate the URL accepted by the segment content.
     *
     * @param string $url Local or validated destination URL.
     * @param bool $image Image URL used by the generated styles.
     * @return void
     */
    private static function validate_url(string $url, bool $image = false): void {
        // Reject controls, protocol-relative URLs and browser-normalised backslash redirects.
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            throw new \invalid_parameter_exception('Invalid Hero URL.');
        }
        if (!$image && preg_match('/^#[a-zA-Z][a-zA-Z0-9_.:-]*$/', $url)) {
            return;
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return;
        }
        $parts = parse_url($url);
        if (
            $parts && in_array(strtolower($parts['scheme'] ?? ''), $image ? ['https'] : ['https', 'http'], true)
                && empty($parts['user']) && empty($parts['pass']) && filter_var($url, FILTER_VALIDATE_URL)
        ) {
            return;
        }
        throw new \invalid_parameter_exception('Invalid Hero URL.');
    }
}

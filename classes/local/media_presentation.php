<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local;

/** Typed custom image controls shared by page and library Media forms. */
final class media_presentation {
    public const FIELDS = ['aspectvalue', 'fitvalue', 'focalx', 'focaly'];
    public const FITS = ['cover', 'contain', 'fill', 'none', 'scale-down'];

    public static function aspect(string $value): string {
        $value = trim($value);
        $number = '(?:[0-9]{1,6}(?:\.[0-9]{1,6})?|\.[0-9]{1,6})';
        if (!preg_match('/^(' . $number . ')(?:\s*\/\s*(' . $number . '))?$/D', $value, $m)
                || (float) $m[1] <= 0 || (isset($m[2]) && (float) $m[2] <= 0)) {
            throw new \invalid_parameter_exception(get_string('mediaaspectinvalid', 'local_frontpage'));
        }
        return $m[1] . (isset($m[2]) ? ' / ' . $m[2] : '');
    }

    public static function normalise(array $props): array {
        foreach (self::FIELDS as $field) {
            if (isset($props[$field]) && !is_string($props[$field])) {
                throw new \invalid_parameter_exception('A custom Media setting must be text.');
            }
        }
        if (($props['aspect'] ?? '') === 'custom') {
            $props['aspectvalue'] = self::aspect($props['aspectvalue'] ?? '');
        } else { unset($props['aspectvalue']); }
        if (($props['fit'] ?? '') === 'custom') {
            $props['fitvalue'] = trim($props['fitvalue'] ?? '');
            if (!in_array($props['fitvalue'], self::FITS, true)) {
                throw new \invalid_parameter_exception(get_string('mediafitinvalid', 'local_frontpage'));
            }
        } else { unset($props['fitvalue']); }
        if (($props['focal'] ?? '') === 'custom') {
            $position = header_background_position::normalise('custom', $props['focalx'] ?? '', $props['focaly'] ?? '');
            $props['focalx'] = $position['x']; $props['focaly'] = $position['y'];
        } else { unset($props['focalx'], $props['focaly']); }
        return $props;
    }

    

    

    public static function style(array $props): string {
        $props = self::normalise($props);
        $css = '';
        if (($props['aspect'] ?? '') === 'custom') { $css .= '--fp-media-aspect:' . $props['aspectvalue'] . ';'; }
        if (($props['fit'] ?? '') === 'custom') { $css .= '--fp-media-fit:' . $props['fitvalue'] . ';'; }
        if (($props['focal'] ?? '') === 'custom') { $css .= '--fp-media-focal:' . $props['focalx'] . '% ' . $props['focaly'] . '%;'; }
        return $css;
    }
}

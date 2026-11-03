<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local;

/** Typed Hero background size and focus shared by page and library forms. */
final class hero_media_presentation {
    public const FIELDS = ['backgroundsizevalue', 'backgroundpositionx', 'backgroundpositiony'];
    public const POSITIONS = ['center' => [50, 50], 'top' => [50, 0], 'bottom' => [50, 100],
        'left' => [0, 50], 'right' => [100, 50], 'top_left' => [0, 0], 'top_right' => [100, 0],
        'bottom_left' => [0, 100], 'bottom_right' => [100, 100]];

    public static function size(string $value): string {
        $parts = preg_split('/\s+/', strtolower(trim($value)));
        if (count($parts) < 1 || count($parts) > 2) { self::invalid_size(); }
        foreach ($parts as $part) {
            if ($part !== 'auto' && (!preg_match('/^([0-9]{1,6}(?:\.[0-9]{1,6})?|\.[0-9]{1,6})(px|rem|em|%|vw|vh|vmin|vmax)$/D', $part, $m)
                    || (float) $m[1] <= 0)) { self::invalid_size(); }
        }
        return implode(' ', $parts);
    }

    private static function invalid_size(): never {
        throw new \invalid_parameter_exception(get_string('herobackgroundsizeinvalid', 'local_frontpage'));
    }

    public static function normalise(array $props): array {
        foreach (self::FIELDS as $field) {
            if (isset($props[$field]) && !is_string($props[$field])) { self::invalid_size(); }
        }
        if (($props['backgroundsize'] ?? '') === 'custom') {
            $props['backgroundsizevalue'] = self::size($props['backgroundsizevalue'] ?? '');
        } else { unset($props['backgroundsizevalue']); }
        if (($props['backgroundposition'] ?? '') === 'custom') {
            $p = header_background_position::normalise('custom', $props['backgroundpositionx'] ?? '', $props['backgroundpositiony'] ?? '');
            $props['backgroundpositionx'] = $p['x']; $props['backgroundpositiony'] = $p['y'];
        } else { unset($props['backgroundpositionx'], $props['backgroundpositiony']); }
        return $props;
    }

    

    

    

    

    public static function style(array $props): string {
        $props = self::normalise($props);
        $mode = $props['backgroundsize'] ?? 'cover';
        $size = $mode === 'custom' ? $props['backgroundsizevalue'] : $mode;
        $position = $props['backgroundposition'] ?? 'center';
        [$x, $y] = $position === 'custom' ? [$props['backgroundpositionx'], $props['backgroundpositiony']]
            : (self::POSITIONS[$position] ?? self::POSITIONS['center']);
        $css = '--fp-hero-size:' . $size . ';--fp-hero-x:' . $x . '%;--fp-hero-y:' . $y . '%;';

        return $css;
    }
}

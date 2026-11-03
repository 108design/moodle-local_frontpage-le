<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local;

use invalid_parameter_exception;

/** Validates preset and percentage-based header background positions. */
final class header_background_position {
    public const INHERIT = 'inherit';
    public const CUSTOM = 'custom';

    /**
     * Normalise one position and its optional percentage coordinates.
     *
     * @return array{mode:string,x:string,y:string}
     */
    public static function normalise(string $mode, string $x = '', string $y = '',
            bool $allowinherit = false): array {
        $allowed = ['center', 'top', 'bottom', self::CUSTOM];
        if ($allowinherit) {
            array_unshift($allowed, self::INHERIT);
        }
        if (!in_array($mode, $allowed, true)) {
            throw new invalid_parameter_exception('The header background position is not supported.');
        }
        if ($mode !== self::CUSTOM) {
            return ['mode' => $mode, 'x' => '', 'y' => ''];
        }
        return [
            'mode' => self::CUSTOM,
            'x' => self::normalise_coordinate($x),
            'y' => self::normalise_coordinate($y),
        ];
    }

    /** Normalise a unitless percentage coordinate between 0 and 100. */
    private static function normalise_coordinate(string $value): string {
        $value = trim($value);
        if (!preg_match('/^(?:\d{1,3})(?:\.\d{1,6})?$/', $value)) {
            throw new invalid_parameter_exception('A header position percentage must be between 0 and 100.');
        }
        $number = (float) $value;
        if ($number < 0 || $number > 100) {
            throw new invalid_parameter_exception('A header position percentage must be between 0 and 100.');
        }
        return rtrim(rtrim(number_format($number, 6, '.', ''), '0'), '.');
    }
}

<?php
/**
 * Validate the file types accepted by media segments.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

/**
 * Validate the file types accepted by media segments.
 */
final class media_files {
    /**
     * Asset.
     */
    public const ASSET = '{{segment-image}}';
    /**
     * Check whether the file is a supported media type.
     *
     * @param \stored_file $file File.
     * @return bool
     */
    public static function is_media(\stored_file $file): bool {
        return in_array($file->get_mimetype(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    }
}

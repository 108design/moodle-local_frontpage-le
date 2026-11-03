<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local;
final class media_files {
    public const ASSET = '{{segment-image}}';
    public static function is_media(\stored_file $file): bool {
        return in_array($file->get_mimetype(), ['image/jpeg','image/png','image/gif','image/webp'], true);
    }
}

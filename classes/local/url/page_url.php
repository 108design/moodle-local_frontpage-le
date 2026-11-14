<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Build public and editing URLs for native and site-home pages.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local\url;

/**
 * Build public and editing URLs for native and site-home pages.
 */
final class page_url {
    /**
     * Return the public URL for the page and its native owner.
     *
     * @param \stdClass $page Page record or native page context.
     * @return \moodle_url
     */
    public static function get_public_url(\stdClass $page): \moodle_url {
        $context = \local_frontpage\local\page_access::context($page);
        return $page->ownercomponent === 'mod_frontpage'
            ? new \moodle_url('/mod/frontpage/view.php', ['id' => $context->instanceid])
            : new \moodle_url('/local/frontpage/view.php', ['id' => $page->id]);
    }
    /**
     * Return the content editor URL for the page.
     *
     * @param \stdClass $page Page record or native page context.
     * @return \moodle_url
     */
    public static function get_edit_url(\stdClass $page): \moodle_url {
        return new \moodle_url('/local/frontpage/edit.php', ['id' => $page->id]);
    }
}

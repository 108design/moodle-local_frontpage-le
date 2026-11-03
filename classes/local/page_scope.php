<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local;
final class page_scope {
    public static function course_id(\stdClass $page, \context $context): int {
        page_access::context($page);
        if ($page->ownercomponent === 'local_frontpage') {return SITEID;}
        return (int)get_coursemodule_from_id('frontpage', $context->instanceid, 0, false, MUST_EXIST)->course;
    }
}

<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local\service;
final class factory {
    public static function page_repository(): \local_frontpage\local\persistence\page_repository {
        global $DB;
        return new \local_frontpage\local\persistence\page_repository($DB);
    }
    public static function page_service(): page_service {return new page_service();}
}

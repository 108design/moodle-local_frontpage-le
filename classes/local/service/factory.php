<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Construct the component repositories and application services.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local\service;

/**
 * Construct the component repositories and application services.
 */
final class factory {
    /**
     * Construct the repository for pages and their revisions.
     *
     * @return \local_frontpage\local\persistence\page_repository
     */
    public static function page_repository(): \local_frontpage\local\persistence\page_repository {
        global $DB;
        return new \local_frontpage\local\persistence\page_repository($DB);
    }
    /**
     * Construct the service coordinating page writes and publication.
     *
     * @return page_service
     */
    public static function page_service(): page_service {
        return new page_service();
    }
}

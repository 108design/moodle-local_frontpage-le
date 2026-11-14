<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Database metadata for Frontpage.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

defined('MOODLE_INTERNAL') || die();
$observers = [
    [
        'eventname' => '\core\event\course_module_created',
         'callback' => '\local_frontpage\observer::course_module_created',
    ],

    [
        'eventname' => '\core\event\course_module_deleted',
         'callback' => '\local_frontpage\observer::course_module_deleted',
    ],

];

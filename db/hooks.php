<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Native hook registrations for Frontpage.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

defined('MOODLE_INTERNAL') || die();
$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => [\local_frontpage\local\hook_callbacks::class, 'hide_resource_section'],
    ],
    [
        'hook' => \core\hook\output\before_http_headers::class,
        'callback' => [\local_frontpage\output\resource_section_warning::class, 'prepare'],
    ],
    [
        'hook' => \core\hook\output\before_http_headers::class,
        'callback' => [\local_frontpage\output\activity_metadata::class, 'prepare'],
    ],
    ['hook' => \core\hook\output\before_http_headers::class,
        'callback' => [\local_frontpage\local\activity_navigation::class, 'prepare']],
    ['hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => [\local_frontpage\local\activity_navigation::class, 'footer'], 'priority' => 200],
    [
        'hook' => \core\hook\output\before_http_headers::class,
         'callback' => [ \local_frontpage\local\hook_callbacks::class, 'before_http_headers' ],
    ],

    [
        'hook' => \core\hook\output\before_standard_footer_html_generation::class,
         'callback' => [ \local_frontpage\local\hook_callbacks::class, 'footer' ],
    ],

];

// Product identity on native administration and upgrade surfaces.
$callbacks[] = ['hook' => \core\hook\output\before_standard_head_html_generation::class,
    'callback' => [\local_frontpage\local\admin_branding::class, 'head']];
$callbacks[] = ['hook' => \core\hook\output\before_http_headers::class,
    'callback' => [\local_frontpage\local\admin_branding::class, 'prepare']];

// Independent signed update-package guard.
$callbacks[] = [
    'hook' => \core\hook\after_config::class,
    'callback' => [\local_frontpage\local\update_guard::class, 'after_config'],
    'priority' => 1002,
];

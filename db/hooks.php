<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
defined('MOODLE_INTERNAL') || die();
$callbacks=[
    ['hook'=>\core\hook\output\before_http_headers::class,'callback'=>[\local_frontpage\local\hook_callbacks::class,'before_http_headers']],
    ['hook'=>\core\hook\output\before_standard_footer_html_generation::class,'callback'=>[\local_frontpage\local\hook_callbacks::class,'footer']],
];

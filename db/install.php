<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
defined('MOODLE_INTERNAL') || die();
function xmldb_local_frontpage_install(): void {
    $origin=get_config('local_frontpage','distributionorigin');
    if($origin && $origin!=='marketplace'){throw new moodle_exception('invaliddata','error');}
    set_config('distributionorigin','marketplace','local_frontpage');
}

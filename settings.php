<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
defined('MOODLE_INTERNAL') || die();
if($hassiteconfig){$ADMIN->add('localplugins',new admin_externalpage('local_frontpage',get_string('pluginname','local_frontpage'),
    new moodle_url('/local/frontpage/pages.php'),'local/frontpage:managepages'));}

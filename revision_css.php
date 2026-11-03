<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
define('NO_DEBUG_DISPLAY',true);
require_once(__DIR__.'/../../config.php');
$pageid=required_param('pageid',PARAM_INT);$revisionid=required_param('revisionid',PARAM_INT);
$page=\local_frontpage\local\service\factory::page_repository()->get_page($pageid);
$css=\local_frontpage\local\page_css::content($page,$revisionid);
header('Content-Type: text/css; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Vary: Cookie');
echo $css;

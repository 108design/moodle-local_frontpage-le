<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
require_once(__DIR__.'/../../config.php');$id=required_param('id',PARAM_INT);$page=\local_frontpage\local\service\factory::page_repository()->get_page($id);
\local_frontpage\local\page_access::require_manage($page);$url=new moodle_url('/local/frontpage/delete.php',['id'=>$id]);
if($_SERVER['REQUEST_METHOD']==='POST'){require_sesskey();\local_frontpage\local\service\factory::page_service()->delete_page($id);redirect(new moodle_url('/local/frontpage/pages.php'));}
\local_frontpage\local\screen::start($url,$page->title,'pages');echo $OUTPUT->header();echo $OUTPUT->confirm(get_string('confirmdeletepage','local_frontpage'),new single_button($url,get_string('delete'),'post'),new moodle_url('/local/frontpage/pages.php'));echo $OUTPUT->footer();

<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
require_once(__DIR__.'/../../config.php');
use local_frontpage\local\service\factory;
use local_frontpage\local\screen;
$id=required_param('id',PARAM_INT);$page=factory::page_repository()->get_page($id);\local_frontpage\local\page_access::require_manage($page);
$draft=factory::page_repository()->get_revision($id,$page->draftrevisionid);$url=new moodle_url('/local/frontpage/revisions.php',['id'=>$id]);
screen::start($url,get_string('revisions','local_frontpage'),'revisionhistory',$page);echo $OUTPUT->header();
echo html_writer::link(new moodle_url('/local/frontpage/edit.php',['id'=>$id]),get_string('editcontent','local_frontpage'),['class'=>'btn btn-secondary mb-4']);
$table=new html_table();$table->head=[get_string('revision','local_frontpage'),get_string('status'),get_string('date'),get_string('actions')];
foreach($DB->get_records('local_frontpage_revision',['pageid'=>$id],'revisionnumber DESC') as $r){
    $actions=html_writer::link(new moodle_url('/local/frontpage/view.php',['id'=>$id,'revisionid'=>$r->id]),get_string('previewdraft','local_frontpage'),['class'=>'btn btn-secondary']);
    if($r->id!=$draft->id){$actions.=screen::button(new moodle_url('/local/frontpage/action.php'),get_string('restore','local_frontpage'),[
        'pageid'=>$id,'revisionid'=>$r->id,'baserevisionid'=>$draft->id,'baseversion'=>$draft->draftversion,'action'=>'restore']);}
    if($r->id!=$draft->id&&$r->id!=$page->publishedrevisionid){$actions.=screen::button(new moodle_url('/local/frontpage/action.php'),get_string('deleterevision','local_frontpage'),[
        'pageid'=>$id,'revisionid'=>$r->id,'baserevisionid'=>$draft->id,'baseversion'=>$draft->draftversion,'action'=>'prune']);}
    $table->data[]=[$r->revisionnumber,get_string($r->timepublished?'published':'draft','local_frontpage'),userdate($r->timecreated),html_writer::div($actions,'d-flex gap-2')];
}echo html_writer::div(html_writer::table($table),'table-responsive');echo $OUTPUT->footer();

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
use local_frontpage\output\authoring;
echo html_writer::div(authoring::link(new moodle_url('/local/frontpage/edit.php',['id'=>$id]),get_string('editcontent','local_frontpage'),'t/left'),'mb-4');
$table=new html_table();$table->attributes['class']='generaltable local-frontpage-manager-table';
$table->head=[get_string('revision','local_frontpage'),get_string('status'),get_string('date'),get_string('actions')];
$draftcss=json_decode($draft->documentjson,true)['css']??'';
foreach($DB->get_records('local_frontpage_revision',['pageid'=>$id],'revisionnumber DESC') as $r){
    $actions=authoring::link(new moodle_url('/local/frontpage/view.php',['id'=>$id,'revisionid'=>$r->id]),get_string('preview'),'i/preview',true);
    $params=['pageid'=>$id,'revisionid'=>$r->id,'baserevisionid'=>$draft->id,'baseversion'=>$draft->draftversion];
    if($r->id!=$draft->id){
        $changedcss=(json_decode($r->documentjson,true)['css']??'')!==$draftcss;
        $actions.=$changedcss&&!\local_frontpage\local\page_access::can_edit_css($page)
            ?authoring::disabled(get_string('restore','local_frontpage'),'i/restore',get_string('pagecsspermissionneeded','local_frontpage'))
            :authoring::post(get_string('restorerevisionlabel','local_frontpage',$r->revisionnumber),'i/restore',$params+['action'=>'restore']);
    }
    if($r->id!=$draft->id&&$r->id!=$page->publishedrevisionid){
        $actions.=authoring::post(get_string('deleterevision','local_frontpage'),'t/delete',$params+['action'=>'prune']);
    }
    $state=$r->id==$draft->id?'currentdraft':($r->id==$page->publishedrevisionid?'currentpublication':($r->timepublished?'previouspublication':'draftcheckpoint'));
    $tone=$state==='currentpublication'?'bg-success':($state==='currentdraft'?'bg-primary':'bg-secondary');
    $table->data[]=[$r->revisionnumber,html_writer::span(get_string($state,'local_frontpage'),'badge text-white local-frontpage-revision-state '.$tone),userdate($r->timecreated),html_writer::div($actions,'local-frontpage-page-actionbar')];
}
echo html_writer::div(html_writer::table($table),'table-responsive');echo $OUTPUT->footer();

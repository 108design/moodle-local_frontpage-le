<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
require_once(__DIR__.'/../../config.php');
use local_frontpage\local\service\factory;
use local_frontpage\local\page_access;
use local_frontpage\local\screen;
$id=required_param('id',PARAM_INT);$page=factory::page_repository()->get_page($id);page_access::require_manage($page);
$revision=factory::page_repository()->get_revision($id,(int)$page->draftrevisionid);
$url=new moodle_url('/local/frontpage/edit.php',['id'=>$id]);screen::start($url,$page->title,'editor',$page);
$actions=['pageid'=>$id,'baserevisionid'=>$revision->id,'baseversion'=>$revision->draftversion];
echo $OUTPUT->header();
$buttons=html_writer::link(new moodle_url('/local/frontpage/view.php',['id'=>$id,'preview'=>1]),get_string('previewdraft','local_frontpage'),['class'=>'btn btn-secondary']);
if(has_capability($page->ownercomponent==='mod_frontpage'?'mod/frontpage:publishcontent':'local/frontpage:publishpages',page_access::context($page))){
$buttons.=screen::button(new moodle_url('/local/frontpage/action.php'),get_string('publish','local_frontpage'),$actions+['action'=>'publish']);}
$buttons.=html_writer::link(new moodle_url('/local/frontpage/revisions.php',['id'=>$id]),get_string('revisions','local_frontpage'),['class'=>'btn btn-secondary']);
$buttons.=html_writer::link(new moodle_url('/local/frontpage/page_files.php',['id'=>$id]),get_string('pagefiles','local_frontpage'),['class'=>'btn btn-secondary']);
$buttons.=html_writer::link(new moodle_url('/local/frontpage/resources.php',['courseid'=>\local_frontpage\local\page_scope::course_id($page,page_access::context($page))]),get_string('resources','local_frontpage'),['class'=>'btn btn-secondary']);
$buttons.=html_writer::link(new moodle_url('/local/frontpage/activity_create.php',$actions+['segmentid'=>'segment_'.bin2hex(random_bytes(8))]),get_string('createfrontpageresource','local_frontpage'),['class'=>'btn btn-secondary']);
echo html_writer::div($buttons,'d-flex flex-wrap gap-2 mb-4');
echo $OUTPUT->notification(get_string('draftnotice','local_frontpage'),'info');
$document=json_decode($revision->documentjson,true);
foreach($document['segments'] as $position=>$segment){if($segment['type']==='course_navigation'){continue;}
    $label=$segment['props']['editorlabel']??get_string('segmenttype'.str_replace('_','',$segment['type']),'local_frontpage');
    $row=html_writer::link(new moodle_url('/local/frontpage/segment.php',$actions+['segmentid'=>$segment['id']]),format_string($label),['class'=>'btn btn-secondary']);
    foreach(['up','down','duplicate','toggle','remove'] as $action){
        if($action==='up'&&($position===0||$document['segments'][$position-1]['type']==='course_navigation')||$action==='down'&&$position===count($document['segments'])-1){continue;}
        $row.=screen::button(new moodle_url('/local/frontpage/action.php'),get_string('action_'.$action,'local_frontpage'),$actions+['segmentid'=>$segment['id'],'action'=>$action]);}
    echo html_writer::div($row,'d-flex flex-wrap gap-2 mb-3');
}
echo $OUTPUT->heading(get_string('addsegment','local_frontpage'),3);$add='';
foreach(\local_frontpage\local\document\validator::TYPES as $type){if($type==='course_navigation'){continue;}$add.=html_writer::link(new moodle_url('/local/frontpage/segment.php',$actions+['type'=>$type]),get_string('segmenttype'.str_replace('_','',$type),'local_frontpage'),['class'=>'btn btn-outline-secondary']);}
echo html_writer::div($add,'d-flex flex-wrap gap-2 mb-4');
echo (new \local_frontpage\output\page_renderer($OUTPUT,$page,(int)$revision->id,true))->render($revision->documentjson);
echo $OUTPUT->footer();

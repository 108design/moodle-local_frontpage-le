<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
require_once(__DIR__.'/../../config.php');
use local_frontpage\local\service\factory;
use local_frontpage\local\page_access;
$id=required_param('id',PARAM_INT);$preview=optional_param('preview',0,PARAM_BOOL);$revisionid=optional_param('revisionid',0,PARAM_INT);
$page=factory::page_repository()->get_page($id);
if($page->ownercomponent==='local_frontpage'&&!$preview&&!$revisionid){
    $assigned=\local_frontpage\local\site_home::assigned_page();
    if($assigned && (int)$assigned->id!==(int)$page->id){redirect(\local_frontpage\local\site_home::entry_url($assigned));}
}
if($page->ownercomponent==='mod_frontpage'&&!$preview&&!$revisionid){redirect(\local_frontpage\local\url\page_url::get_public_url($page));}
$context=page_access::require_view($page,(bool)($preview||$revisionid));
$revision=factory::page_repository()->get_revision($id,$revisionid?:($preview?$page->draftrevisionid:$page->publishedrevisionid));
$PAGE->set_url(new moodle_url('/local/frontpage/view.php',['id'=>$id,'preview'=>$preview,'revisionid'=>$revisionid]));
$PAGE->set_context($context);$PAGE->set_pagelayout('standard');$PAGE->set_title($page->title);$PAGE->set_heading($page->title);
if($context->contextlevel===CONTEXT_MODULE){$cm=get_coursemodule_from_id('frontpage',$context->instanceid,0,false,MUST_EXIST);$PAGE->set_cm($cm,get_course($cm->course));$PAGE->set_pagelayout('incourse');}
if($preview||$revisionid){\local_frontpage\output\context_help::prepare('editor');}
\local_frontpage\local\page_css::prepare($page,$revision);
echo $OUTPUT->header();
if($preview||$revisionid){echo $OUTPUT->notification(get_string('previewnotice','local_frontpage'),'info');echo html_writer::link(
    \local_frontpage\local\url\page_url::get_edit_url($page),get_string('editcontent','local_frontpage'),['class'=>'btn btn-secondary mb-4']);}
echo (new \local_frontpage\output\page_renderer($OUTPUT,$page,(int)$revision->id,(bool)($preview||$revisionid)))->render($revision->documentjson);
echo $OUTPUT->footer();

<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
require_once(__DIR__.'/../../config.php');require_login();
use local_frontpage\local\service\factory;
use local_frontpage\local\activity_return;
$token=required_param('token',PARAM_ALPHANUM);$pending=activity_return::pending($token);
if(!$pending||!hash_equals($pending['token'],$token)){throw new invalid_parameter_exception('Expired return ticket.');}
$page=factory::page_repository()->find_page($pending['pageid']);
if(!$page){$course=get_course($pending['courseid']);require_login($course);require_capability('moodle/course:manageactivities',context_course::instance($course->id));
    activity_return::consume($token);redirect(new moodle_url('/local/frontpage/resources.php',['courseid'=>$course->id]),get_string('resourceretained','local_frontpage'));}
\local_frontpage\local\page_access::require_manage($page);
$back=\local_frontpage\local\url\page_url::get_edit_url($page);
if(empty($pending['createdcmid'])){activity_return::consume($token);redirect($back);}
if($_SERVER['REQUEST_METHOD']!=='POST'){
    \local_frontpage\local\screen::start(new moodle_url('/local/frontpage/activity_return.php',['token'=>$token]),$page->title,'resources',$page);
    echo $OUTPUT->header();echo $OUTPUT->confirm(get_string('confirmattachresource','local_frontpage'),
        new single_button(new moodle_url('/local/frontpage/activity_return.php',['token'=>$token,'cmid'=>optional_param('cmid',0,PARAM_INT)]),get_string('continue'),'post'),new single_button(new moodle_url('/local/frontpage/activity_return.php',['token'=>$token,'attach'=>0]),get_string('cancel'),'post'));
    echo $OUTPUT->footer();exit;
}
require_sesskey();$data=activity_return::consume($token);
if(!optional_param('attach',1,PARAM_BOOL)){redirect($back);}
if(($specific=optional_param('cmid',0,PARAM_INT))&&in_array($specific,$data['createdcmids'],true)){$data['createdcmid']=$specific;}
if(!empty($data['createdcmid'])){
    $cm=\local_frontpage\local\course_module_reference::get(\local_frontpage\local\page_access::context($page),$data['createdcmid'],$page);
    if(!\local_frontpage\local\course_resource_manager::is_managed($cm->id)){throw new invalid_parameter_exception('Unregistered resource.');}
    $revision=factory::page_repository()->get_revision($page->id,$data['baserevisionid']);$doc=json_decode($revision->documentjson,true);
    $doc['segments'][]=['id'=>$data['segmentid'],'type'=>'course_module','props'=>['cmid'=>(int)$cm->id,'usedescription'=>true]];
    try{factory::page_service()->save_draft($page->id,json_encode($doc,JSON_THROW_ON_ERROR),$USER->id,$data['baserevisionid'],$data['baseversion']);}
    catch(moodle_exception $e){if($e->errorcode!=='draftconflict'){throw $e;}redirect($back,get_string('resourceretained','local_frontpage'),null,\core\output\notification::NOTIFY_WARNING);}
}
redirect($back);

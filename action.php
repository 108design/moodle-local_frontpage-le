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
if($_SERVER['REQUEST_METHOD']!=='POST'){throw new invalid_parameter_exception('POST required.');}require_sesskey();
$id=required_param('pageid',PARAM_INT);$base=required_param('baserevisionid',PARAM_INT);$version=required_param('baseversion',PARAM_INT);
$action=required_param('action',PARAM_ALPHA);$segmentid=optional_param('segmentid','',PARAM_ALPHANUMEXT);
$page=factory::page_repository()->get_page($id);page_access::require_manage($page);
if($action==='publish'){page_access::require_publish($page);}
$url=new moodle_url('/local/frontpage/action.php', ['pageid'=>$id,'baserevisionid'=>$base,'baseversion'=>$version,'action'=>$action,'segmentid'=>$segmentid,'revisionid'=>optional_param('revisionid',0,PARAM_INT)]);
if(in_array($action,['publish','remove','prune'],true)&&!optional_param('confirm',0,PARAM_BOOL)){
    screen::start($url,$page->title,'editor',$page);echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string($action==='publish'?'confirmpublish':($action==='prune'?'confirmprune':'confirmremove'),'local_frontpage'),
        new single_button(new moodle_url($url,['confirm'=>1]),get_string('continue'),'post'),new moodle_url('/local/frontpage/edit.php',['id'=>$id]));
    echo $OUTPUT->footer();exit;
}
if($action==='publish'){factory::page_service()->publish($id,$USER->id,$base,$version);
} else if($action==='prune'){factory::page_service()->prune($id,required_param('revisionid',PARAM_INT),$base,$version);
} else if($action==='restore'){
    factory::page_service()->restore($id,required_param('revisionid',PARAM_INT),$USER->id,$base,$version);
} else {
    $revision=factory::page_repository()->get_revision($id,$base);
    $changed=\local_frontpage\local\document\mutator::apply($revision->documentjson,$segmentid,$action);
    factory::page_service()->save_draft($id,$changed['documentjson'],$USER->id,$base,$version,
        fn($new)=>\local_frontpage\local\revision_files::duplicate_segments($new->id,$changed['filemap']));
}
redirect(new moodle_url('/local/frontpage/edit.php',['id'=>$id]));

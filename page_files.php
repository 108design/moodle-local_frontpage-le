<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
require_once(__DIR__.'/../../config.php');require_once($CFG->libdir.'/formslib.php');
use local_frontpage\local\service\factory;
$id=required_param('id',PARAM_INT);$page=factory::page_repository()->get_page($id);$context=\local_frontpage\local\page_access::require_manage($page);
$base=optional_param('baserevisionid',(int)$page->draftrevisionid,PARAM_INT);$rev=factory::page_repository()->get_revision($id,$base);
$url=new moodle_url('/local/frontpage/page_files.php',['id'=>$id]);\local_frontpage\local\screen::start($url,get_string('pagefiles','local_frontpage'),'pagefiles',$page);
$form=new \local_frontpage\form\basic_form($url,['kind'=>'files']);$back=new moodle_url('/local/frontpage/edit.php',['id'=>$id]);
if($form->is_cancelled()){redirect($back);}if($data=$form->get_data()){
    factory::page_service()->save_draft($id,$rev->documentjson,$USER->id,$data->baserevisionid,$data->baseversion,
        fn($new)=>file_save_draft_area_files($data->assets,$context->id,'local_frontpage','pageassets',$new->id,['maxfiles'=>-1,'subdirs'=>0]));redirect($back);
}
$draft=file_get_submitted_draft_itemid('assets');file_prepare_draft_area($draft,$context->id,'local_frontpage','pageassets',$base,['maxfiles'=>-1,'subdirs'=>0]);
$form->set_data(['id'=>$id,'baserevisionid'=>$base,'baseversion'=>$rev->draftversion,'assets'=>$draft]);echo $OUTPUT->header();$form->display();echo $OUTPUT->footer();

<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/formslib.php');
use local_frontpage\local\service\factory;
use local_frontpage\local\screen;
require_login();require_capability('local/frontpage:managepages',context_system::instance());
$url=new moodle_url('/local/frontpage/pages.php');
screen::start($url,get_string('pluginname','local_frontpage'),'pages');
$form=new \local_frontpage\form\basic_form($url,['kind'=>'title']);
if($data=$form->get_data()) {$p=factory::page_service()->create_home($data->title);redirect(new moodle_url('/local/frontpage/edit.php',['id'=>$p->id]));}
echo $OUTPUT->header();echo screen::tabs('pages');
$table=new html_table();$table->head=[get_string('title','local_frontpage'),get_string('assignments','local_frontpage'),get_string('status'),get_string('lastmodified'),get_string('actions')];
foreach(factory::page_repository()->get_pages() as $p) {
    $aud=[];foreach(['guestfrontpageid','loggedinfrontpageid'] as $key){if((int)get_config('local_frontpage',$key)==$p->id){$aud[]=get_string($key,'local_frontpage');}}
    $links=[];foreach(['edit.php'=>'editcontent','view.php'=>'previewdraft','page_settings.php'=>'pageoptions','revisions.php'=>'revisions','page_files.php'=>'pagefiles','delete.php'=>'deletepage'] as $route=>$label){
        $params=['id'=>$p->id];if($route==='view.php'){$params['preview']=1;}
        $icons=['edit.php'=>'t/edit','view.php'=>'i/preview','page_settings.php'=>'local/page-settings','revisions.php'=>'i/restore','page_files.php'=>'fa/folder-open','delete.php'=>'t/delete'];
        if($route==='delete.php'&&$aud){
            $links[]=\local_frontpage\output\authoring::disabled(get_string($label,'local_frontpage'),'t/delete',get_string('assignedpage','local_frontpage'));
            continue;
        }
        $links[]=\local_frontpage\output\authoring::link(new moodle_url('/local/frontpage/'.$route,$params),get_string($label,'local_frontpage'),$icons[$route],true);
    }
    $table->data[]=[html_writer::link(new moodle_url('/local/frontpage/edit.php',['id'=>$p->id]),format_string($p->title),['class'=>'local-frontpage-manager-title']),html_writer::link(new moodle_url('/local/frontpage/preferences.php'),implode(', ',$aud)?:get_string('unassigned','local_frontpage')),
        html_writer::span(\local_frontpage\output\authoring::icon(empty($p->publishedrevisionid)?'fa/pencil':'fa/check-circle').get_string(empty($p->publishedrevisionid)?'draft':'published','local_frontpage'),'badge '.(empty($p->publishedrevisionid)?'bg-warning text-dark':'bg-success text-white')),userdate($p->timemodified),html_writer::div(implode(' ',$links),'local-frontpage-manager-actions local-frontpage-page-actionbar')];
}
echo html_writer::div(html_writer::table($table),'table-responsive mb-4');
if(has_capability('moodle/course:manageactivities',context_course::instance(SITEID))){
    echo html_writer::div(\local_frontpage\output\authoring::link(new moodle_url('/local/frontpage/resources.php',['courseid'=>SITEID]),get_string('resources','local_frontpage'),'i/folder'),'mb-4');
}
if(count(factory::page_repository()->get_pages())<5){echo $OUTPUT->heading(get_string('createpage','local_frontpage'),3);$form->display();}
echo $OUTPUT->footer();

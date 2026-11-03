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
        $links[]=html_writer::link(new moodle_url('/local/frontpage/'.$route,$params),get_string($label,'local_frontpage'),['class'=>'btn btn-secondary btn-sm']);
    }
    $table->data[]=[format_string($p->title),html_writer::link(new moodle_url('/local/frontpage/preferences.php'),implode(', ',$aud)?:get_string('unassigned','local_frontpage')),
        get_string(empty($p->publishedrevisionid)?'draft':'published','local_frontpage'),userdate($p->timemodified),html_writer::div(implode(' ',$links),'d-flex flex-wrap gap-2')];
}
echo html_writer::div(html_writer::table($table),'table-responsive mb-4');
echo html_writer::link(new moodle_url('/local/frontpage/resources.php',['courseid'=>SITEID]),get_string('resources','local_frontpage'),['class'=>'btn btn-secondary mb-4']);
if(count(factory::page_repository()->get_pages())<2){echo $OUTPUT->heading(get_string('createpage','local_frontpage'),3);$form->display();}
echo $OUTPUT->footer();

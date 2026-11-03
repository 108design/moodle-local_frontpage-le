<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
require_once(__DIR__.'/../../config.php');require_once($CFG->libdir.'/formslib.php');
use local_frontpage\local\screen;
require_login();require_capability('local/frontpage:managepages',context_system::instance());
$url=new moodle_url('/local/frontpage/preferences.php');screen::start($url,get_string('settings'),'pluginsettings');
$form=new \local_frontpage\form\basic_form($url,['kind'=>'settings']);
if($data=$form->get_data()) {
    \local_frontpage\local\service\factory::page_service()->locked(SITEID,function() use ($data){
        global $DB;
        foreach(['guestfrontpageid','loggedinfrontpageid'] as $key){$id=(int)$data->$key;
            if($id && !$DB->record_exists('local_frontpage_page',['id'=>$id,'ownercomponent'=>'local_frontpage'])){throw new invalid_parameter_exception('Invalid Site Home page.');}
            set_config($key,$id,'local_frontpage');
        }
        set_config('sitehomeenabled',(int)$data->sitehomeenabled,'local_frontpage');
    });redirect($url);
}
$form->set_data(get_config('local_frontpage'));echo $OUTPUT->header();echo screen::tabs('settings');$form->display();echo $OUTPUT->footer();

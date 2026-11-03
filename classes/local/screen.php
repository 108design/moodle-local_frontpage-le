<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local;
final class screen {
    public static function start(\moodle_url $url, string $title, string $help, ?\stdClass $page=null): void {
        global $PAGE;
        $PAGE->set_url($url);$PAGE->set_title($title);$PAGE->set_heading($title);
        if ($page && $page->ownercomponent==='mod_frontpage') {
            $context=page_access::context($page);
            $cm=get_coursemodule_from_id('frontpage',$context->instanceid,0,false,MUST_EXIST);
            $PAGE->set_cm($cm,get_course($cm->course));$PAGE->set_pagelayout('incourse');
        } else {$PAGE->set_context(\context_system::instance());$PAGE->set_pagelayout('admin');
            if($url->get_path()!=='/local/frontpage/pages.php'){$PAGE->navbar->add(get_string('pluginname','local_frontpage'),new \moodle_url('/local/frontpage/pages.php'));}
        }
        \local_frontpage\output\context_help::prepare($help);
    }
    public static function tabs(string $active): string {
        return print_tabs([[new \tabobject('pages',new \moodle_url('/local/frontpage/pages.php'),get_string('pages','local_frontpage'),'',true),
            new \tabobject('settings',new \moodle_url('/local/frontpage/preferences.php'),get_string('settings'),'',true)]],$active,null,null,true);
    }
    public static function button(\moodle_url $url, string $label, array $params=[]): string {
        global $OUTPUT;
        return $OUTPUT->single_button(new \moodle_url($url,$params),$label,'post');
    }
}

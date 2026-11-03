<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local;
final class site_home {
    public static function assigned_page(): ?\stdClass {
        global $DB;
        if(during_initial_install()||!get_config('local_frontpage','version')||!get_config('local_frontpage','sitehomeenabled')){return null;}
        $key=isloggedin()&&!isguestuser()?'loggedinfrontpageid':'guestfrontpageid';$id=(int)get_config('local_frontpage',$key);
        $page=$id?$DB->get_record('local_frontpage_page',['id'=>$id,'ownercomponent'=>'local_frontpage']):false;
        return $page&&page_access::can_view_published($page)?$page:null;
    }
    public static function entry_url(\stdClass $page): \moodle_url {return \local_frontpage\local\url\page_url::get_public_url($page);}
    public static function native_url(): \moodle_url {return new \moodle_url('/index.php',['frontpage_native'=>1]);}
    public static function redirect_url(\moodle_page $page): ?\moodle_url {
        global $SCRIPT;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
                || optional_param('frontpage_native', 0, PARAM_BOOL)
                || $page->pagetype !== 'site-index') {
            return null;
        }
        // Core's error renderer can reset PAGE->url to '/' before a controller sets its URL,
        // also inferring site-index. Only the real site-home script may claim this redirect.
        // Moodle's SCRIPT is relative to wwwroot, including subdirectory installations.
        if (!in_array($SCRIPT, ['/', '/index.php'], true)) {
            return null;
        }
        // Moodle's index.php sets PAGE->url to '/', including when the request was '/index.php'.
        // Build both paths through moodle_url so installations below a URL subdirectory work too.
        if (!in_array($page->url->get_path(), [
                (new \moodle_url('/'))->get_path(), (new \moodle_url('/index.php'))->get_path(),
            ], true)) {
            return null;
        }
        $assigned = self::assigned_page();
        return $assigned ? self::entry_url($assigned) : null;
    }
}

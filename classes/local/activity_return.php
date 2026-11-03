<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local;
final class activity_return {
    private const LIFETIME=7200;
    public static function begin(array $data): string {
        global $SESSION,$USER;
        $token=bin2hex(random_bytes(24));$data['token']=$token;$data['userid']=(int)$USER->id;
        $data['expires']=time()+self::LIFETIME;$data['createdcmid']=0;$data['createdcmids']=[];
        $SESSION->local_frontpage_activity_returns[$token]=$data;return $token;
    }
    public static function pending(?string $token=null): ?array {
        global $SESSION,$USER,$SCRIPT;
        foreach($SESSION->local_frontpage_activity_returns??[] as $key=>$data){
            if(($data['expires']??0)<time()||($data['userid']??0)!==(int)$USER->id){unset($SESSION->local_frontpage_activity_returns[$key]);}
        }
        $token=$token??optional_param('frontpagereturntoken','',PARAM_ALPHANUM);
        if($token!==''){return $SESSION->local_frontpage_activity_returns[$token]??null;}
        if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'){
            $cmid=optional_param('id',0,PARAM_INT);
            foreach($SESSION->local_frontpage_activity_returns??[] as $data){
                if($SCRIPT==='/mod/'.$data['modname'].'/view.php'&&in_array($cmid,$data['createdcmids']??[],true)){
                    $data['createdcmid']=$cmid;return $data;
                }
            }
        }
        return null;
    }
    public static function record_created(int $cmid,int $courseid,int $sectionid,int $userid): bool {
        global $SESSION;
        $data=self::pending();
        if(!$data||$data['mode']!=='create'||(int)$data['courseid']!==$courseid
                ||(int)$data['sectionid']!==$sectionid||(int)$data['userid']!==$userid){return false;}
        $data['createdcmid']=$cmid;$data['createdcmids'][]=$cmid;
        $SESSION->local_frontpage_activity_returns[$data['token']]=$data;return true;
    }
    public static function consume(string $token): ?array {
        global $SESSION;$data=self::pending($token);
        if($data){unset($SESSION->local_frontpage_activity_returns[$token]);}return $data;
    }
    public static function landing_url(array $data): \moodle_url {
        return new \moodle_url('/local/frontpage/activity_return.php',['token'=>$data['token'],'cmid'=>$data['createdcmid']??0]);
    }
    public static function matches_moodle_return(string $path,int $pagecourseid,array $data): bool {
        return $path==='/mod/'.$data['modname'].'/view.php'&&$pagecourseid===(int)$data['courseid'];
    }
}

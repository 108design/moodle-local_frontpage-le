<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local\document;
final class mutator {
    public static function apply(string $json,string $id,string $action): array {
        $doc=json_decode($json,true);$map=[];$index=null;
        foreach($doc['segments'] as $i=>$s){if($s['id']===$id){$index=$i;}}
        if($index===null||$doc['segments'][$index]['type']==='course_navigation'){throw new \invalid_parameter_exception('Invalid segment action.');}
        if($action==='remove') {\local_frontpage\local\grid_structure::release_segment($doc,$id);array_splice($doc['segments'],$index,1);
        } else if($action==='toggle') {$doc['segments'][$index]['props']['enabled']=!($doc['segments'][$index]['props']['enabled']??true);
        } else if(in_array($action,['up','down'],true)) {
            $next=$index+($action==='up'?-1:1);
            if(!isset($doc['segments'][$next])||$doc['segments'][$next]['type']==='course_navigation'){throw new \invalid_parameter_exception('Cannot move further.');}
            [$doc['segments'][$index],$doc['segments'][$next]]=[$doc['segments'][$next],$doc['segments'][$index]];
        } else if($action==='duplicate') {
            $source=$doc['segments'][$index];$copies=[];
            if($source['type']==='grid') {
                foreach($source['props']['segmentids'] as $childid){if($childid===''){continue;}
                    foreach($doc['segments'] as $child){if($child['id']===$childid){$map[$childid]='segment_'.bin2hex(random_bytes(8));$child['id']=$map[$childid];$copies[]=$child;}}
                }
                $source['props']['segmentids']=array_map(fn($old)=>$map[$old]??'',$source['props']['segmentids']);
            }
            $map[$id]='segment_'.bin2hex(random_bytes(8));$source['id']=$map[$id];
            array_splice($doc['segments'],$index+1,0,[$source,...$copies]);
        } else {throw new \invalid_parameter_exception('Invalid action.');}
        return ['documentjson'=>(new validator())->validate_and_normalise(json_encode($doc,JSON_THROW_ON_ERROR)),'filemap'=>$map];
    }
}

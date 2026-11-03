<?php
/**
 * Native section and module ownership backup.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
defined('MOODLE_INTERNAL') || die();
final class backup_local_frontpage_plugin extends backup_local_plugin {
    protected function define_section_plugin_structure(): void {
        global $DB;
        $root=new backup_nested_element($this->get_recommended_name());$this->get_plugin_element()->add_child($root);
        $sectionid=(int)$this->task->get_sectionid();$courseid=(int)$this->task->get_courseid();
        $owned=\local_frontpage\local\course_resource_manager::get_section($courseid);
        $belongs=($owned && (int)$owned->id===$sectionid)||$DB->record_exists('local_frontpage_resource',['courseid'=>$courseid,'sectionid'=>$sectionid,'status'=>'active']);
        $section=new backup_nested_element('resourcesection',null,['sectionid']);$root->add_child($section);
        $section->set_source_array($belongs?[(object)['sectionid'=>$sectionid]]:[]);
    }
    protected function define_module_plugin_structure(): void {
        $root=new backup_nested_element($this->get_recommended_name());$this->get_plugin_element()->add_child($root);
        $resource=new backup_nested_element('resource',['id'],['cmid','sectionid','origin','status','sourcepageid','orphanedsince','createdby','timecreated','timemodified']);
        $root->add_child($resource);
        $resource->set_source_table('local_frontpage_resource',['cmid'=>['sqlparam'=>(int)$this->task->get_moduleid()],'status'=>['sqlparam'=>'active']]);
        $resource->annotate_ids('user','createdby');
    }
}

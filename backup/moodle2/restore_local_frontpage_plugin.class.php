<?php
/**
 * Restore course ownership after native section and activity remapping.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

defined('MOODLE_INTERNAL') || die();
/**
 * Restore Frontpage resource metadata through native course and module mappings.
 */
final class restore_local_frontpage_plugin extends restore_local_plugin {
    /**
     * @var array Resources.
     */
    private array $resources = [];
    /**
     * @var ?int Sectionid.
     */
    private ?int $sectionid = null;
    /**
     * Define Frontpage metadata attached to a native course section.
     *
     * @return array
     */
    protected function define_section_plugin_structure(): array {
        return [new restore_path_element('frontpage_resourcesection', $this->get_pathfor('/resourcesection'))];
    }
    /**
     * Define Frontpage metadata attached to a native course module.
     *
     * @return array
     */
    protected function define_module_plugin_structure(): array {
        return [new restore_path_element('frontpage_resource', $this->get_pathfor('/resource'))];
    }
    /**
     * Restore the Frontpage metadata for a resource section.
     *
     * @param array $data Submitted or stored data.
     * @return void
     */
    public function process_frontpage_resourcesection(array $data): void {
        $this->sectionid = (int)$data['sectionid'];
    }
    /**
     * Restore one Frontpage-managed native resource record.
     *
     * @param array $data Submitted or stored data.
     * @return void
     */
    public function process_frontpage_resource(array $data): void {
        $this->resources[] = $data;
    }
    /**
     * Resolve restored Frontpage resource-section metadata.
     *
     * @return void
     */
    public function after_restore_section(): void {
        $courseid = (int)$this->task->get_courseid();
        $manager = \local_frontpage\local\course_resource_manager::class;
        if ($this->sectionid && !$manager::get_section($courseid)) {
            $sectionid = (int)$this->get_mappingid('course_section', $this->sectionid, 0);
            if ($sectionid) {
                $manager::remember_section($courseid, $sectionid);
            }
        }
    }
    /**
     * Resolve restored Frontpage resource ownership and module mappings.
     *
     * @return void
     */
    public function after_restore_module(): void {
        $courseid = (int)$this->task->get_courseid();
        $manager = \local_frontpage\local\course_resource_manager::class;
        foreach ($this->resources as $resource) {
            $cmid = (int)$this->get_mappingid('course_module', (int)$resource['cmid'], 0);
            $cm = $cmid ? get_coursemodule_from_id('', $cmid, $courseid, false, IGNORE_MISSING) : null;
            if (!$cm) {
                continue;
            }
            $pageid = (int)$this->get_mappingid('local_frontpage_page', (int)($resource['sourcepageid'] ?? 0), 0);
            $userid = (int)$this->get_mappingid('user', (int)$resource['createdby'], 0);
            $manager::register($cmid, $resource['origin'], $userid, $pageid ?: null);
        }
        // A restored orphan starts a fresh grace period.
        $manager::refresh_orphan_state($courseid);
    }
    /**
     * Return the content fields requiring native link decoding.
     *
     * @return array
     */
    public static function define_decode_contents(): array {
        return [];
    }
}

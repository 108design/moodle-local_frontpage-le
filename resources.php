<?php
/**
 * Native resources entry point for Frontpage.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
use local_frontpage\local\course_resource_manager as resources;
use local_frontpage\local\service\factory;
use local_frontpage\local\screen;
$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $context);
$url = new moodle_url('/local/frontpage/resources.php', ['courseid' => $courseid]);
$resourceid = optional_param('resourceid', 0, PARAM_INT);
$PAGE->set_url($url);
$PAGE->set_course($course);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('resources', 'local_frontpage'));
$PAGE->set_heading(format_string($course->fullname));
\local_frontpage\output\context_help::prepare('resources');
resources::refresh_orphan_state($courseid);
if ($resourceid) {
    $record = resources::get_cleanup_candidate($resourceid, $courseid);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('confirm', 0, PARAM_BOOL)) {
        require_sesskey();factory::page_service()->locked(
            $courseid,
            function () use (
                $resourceid,
                $courseid,
                $context
            ) {
                require_capability('moodle/course:manageactivities', $context);
                resources::refresh_orphan_state($courseid);
                $record = resources::get_cleanup_candidate($resourceid, $courseid);
                \local_frontpage\local\core_compatibility::delete_course_module((int)$record->cmid);
                resources::mark_deleted((int)$record->cmid);
            }
        );
        redirect($url);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('confirmresourcecleanup', 'local_frontpage'),
        new single_button(
            new moodle_url($url, [ 'resourceid' => $resourceid, 'confirm' => 1 ]),
            get_string('delete'),
            'post'
        ),
        $url
    );
    echo $OUTPUT->footer();
    exit;
}
echo $OUTPUT->header();
echo html_writer::tag('p', get_string('resourceintro', 'local_frontpage'));
$rows = $DB->get_records(
    'local_frontpage_resource',
    [ 'courseid' => $courseid, 'origin' => resources::ORIGIN_CREATED, 'status' => 'active' ]
);
$candidates = resources::get_cleanup_candidates($courseid);
$modinfo = get_fast_modinfo($courseid);
$table = new html_table();
$table->head = [
        get_string('activity'),
         get_string('sourcepage', 'local_frontpage'),
         get_string('retainedreferences', 'local_frontpage'),
         get_string('actions'),
    ];
foreach ($rows as $r) {
    try {
        $cm = $modinfo->get_cm($r->cmid);
    } catch (moodle_exception) {
        continue;
    }
    $source = $r->sourcepageid ? $DB->get_record('local_frontpage_page', ['id' => $r->sourcepageid]) : false;
    $refs = $DB->count_records('local_frontpage_cmref', ['cmid' => $r->cmid]);
    $action = isset($candidates[$r->id]) ? \local_frontpage\output\authoring::link(
        new moodle_url($url, [ 'resourceid' => $r->id ]),
        get_string('cleanupresource', 'local_frontpage'),
        't/delete',
        true
    ) : get_string($refs ? 'resourceprotected' : 'resourcegrace', 'local_frontpage');
    $table->data[] = [
        html_writer::link($cm->url, $cm->get_formatted_name()),
         $source ? format_string($source->title) : get_string('sourceremoved', 'local_frontpage'),
         $refs,
         html_writer::div($action, 'local-frontpage-page-actionbar'),
    ];
}
echo html_writer::div(html_writer::table($table), 'table-responsive');
echo $OUTPUT->footer();

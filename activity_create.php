<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

use local_frontpage\local\activity_return;
use local_frontpage\local\course_resource_manager;
use local_frontpage\local\core_compatibility;
use local_frontpage\local\page_access;
use local_frontpage\local\service\factory;
use local_frontpage\local\url\page_url;
use local_frontpage\local\page_scope;

$pageid = required_param('pageid', PARAM_INT);
$segmentid = required_param('segmentid', PARAM_ALPHANUMEXT);
$insertbefore = optional_param('insertbefore', '', PARAM_ALPHANUMEXT);
$baserevisionid = required_param('baserevisionid', PARAM_INT);
$baseversion = required_param('baseversion', PARAM_INT);
$modname = optional_param('modname', '', PARAM_ALPHANUM);
$returntosegment = optional_param('returntosegment', 0, PARAM_BOOL);
$segmentexisted = optional_param('segmentexisted', 0, PARAM_BOOL);
$placement = optional_param('placement', '', PARAM_ALPHA);

require_login();
$page = factory::page_repository()->get_page($pageid);
$context = page_access::require_manage($page);
if ($context->contextlevel !== CONTEXT_MODULE && empty($page->sitehomescope)) {
    throw new moodle_exception('coursemodulewrongcontext', 'local_frontpage');
}
$pagecm = $context->contextlevel === CONTEXT_MODULE
    ? get_coursemodule_from_id('frontpage', $context->instanceid, 0, false, MUST_EXIST) : null;
$course = get_course(page_scope::course_id($page, $context));
if ($pagecm) {
    require_login($course, false, $pagecm);
}
require_capability('moodle/course:manageactivities', context_course::instance($course->id));
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $modname === '') {
    \local_frontpage\local\screen::start(new moodle_url('/local/frontpage/activity_create.php', $_GET),get_string('createfrontpageresource','local_frontpage'),'resources',$page);
    \local_frontpage\output\context_help::prepare('resources');
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('confirmresourcecreate', 'local_frontpage'),
        new single_button(new moodle_url('/local/frontpage/activity_create.php', ['pageid'=>$pageid, 'segmentid'=>$segmentid,
            'baserevisionid'=>$baserevisionid,'baseversion'=>$baseversion]), get_string('continue'), 'post'),
        page_url::get_edit_url($page));
    echo $OUTPUT->footer(); exit;
}
require_sesskey();
$section = $_SERVER['REQUEST_METHOD']==='POST'
    ?course_resource_manager::ensure_section((int)$course->id):course_resource_manager::get_section((int)$course->id);
if(!$section){throw new invalid_parameter_exception('Open the activity chooser from the editor.');}

$params = compact('pageid', 'segmentid', 'insertbefore', 'placement', 'baserevisionid', 'baseversion',
    'returntosegment', 'segmentexisted');
$url = new moodle_url('/local/frontpage/activity_create.php', $params);
if ($pagecm) {
    $PAGE->set_cm($pagecm, $course);
} else {
    $PAGE->set_context($context);
    $PAGE->set_course($course);
}
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('createfrontpageresource', 'local_frontpage'));
\local_frontpage\output\context_help::prepare('resources');
$PAGE->set_heading(format_string($course->fullname));

$modules = get_module_types_names();
unset($modules['frontpage'], $modules['label']);
foreach ($modules as $name => $label) {
    if (!$DB->record_exists('modules', ['name' => $name, 'visible' => 1])) {
        unset($modules[$name]);
    }
}
asort($modules, SORT_NATURAL | SORT_FLAG_CASE);

if ($modname !== '') {
    require_sesskey();
    if (!isset($modules[$modname])) {
        throw new invalid_parameter_exception('The selected activity type is unavailable.');
    }
    require_capability('mod/' . $modname . ':addinstance', context_course::instance($course->id));
    $token=activity_return::begin([
        'mode' => 'create',
        'pageid' => $pageid,
        'segmentid' => $segmentid,
        'insertbefore' => $insertbefore,
        'baserevisionid' => $baserevisionid,
        'baseversion' => $baseversion,
        'courseid' => (int) $course->id,
        'sectionid' => (int) $section->id,
        'modname' => $modname,
        'pagecmid' => $pagecm ? (int) $pagecm->id : 0,
        'returntosegment' => (bool) $returntosegment,
        'segmentexisted' => (bool) $segmentexisted,
        'placement' => $placement,
    ]);
    redirect(new moodle_url('/course/modedit.php', [
        'add' => $modname,
        'course' => $course->id,
        'sectionid' => $section->id,
        'section' => $section->section,
        'return' => 0,
        'frontpagereturntoken'=>$token,
    ]));
}

$chooserid = 'local-frontpage-activity-chooser';
$selecturl = new moodle_url($url, ['sesskey' => sesskey()]);
$PAGE->requires->js_call_amd(core_compatibility::activity_chooser_module(), 'init', [[
    'buttonid' => $chooserid,
    'courseid' => (int) $course->id,
    'sectionid' => (int) $section->id,
    'sectionnum' => (int) $section->section,
    'selecturl' => $selecturl->out(false),
]]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('createfrontpageresource', 'local_frontpage'));
echo $OUTPUT->notification(get_string('frontpageresourcecreatehelp', 'local_frontpage'), 'info');
echo html_writer::start_div('d-flex justify-content-center', ['id' => $chooserid]);
echo $OUTPUT->render_from_template(core_compatibility::activity_chooser_template(), [
    'sectionnum' => (int) $section->section,
    'sectionid' => (int) $section->id,
    'hasactionlinks' => false,
]);
echo html_writer::end_div();
echo html_writer::div(html_writer::link(page_url::get_edit_url($page), get_string('cancel'),
    ['class' => 'btn btn-secondary']), 'mt-3');
echo $OUTPUT->footer();

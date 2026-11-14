<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Edit the revision-owned activity navigation settings.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

// Central page_access enforces the owner context and the required audience or capability.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_frontpage\local\{activity_navigation_settings, page_access, screen};
use local_frontpage\local\service\factory;

$id = required_param('id', PARAM_INT);
$page = factory::page_repository()->get_page($id);
$context = page_access::require_manage($page);
if ($page->ownercomponent !== 'mod_frontpage') {
    throw new invalid_parameter_exception('Activity navigation requires a Frontpage course activity.');
}
$cm = get_coursemodule_from_id('frontpage', $context->instanceid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$revision = factory::page_repository()->get_revision($id, (int) $page->draftrevisionid);
$document = json_decode($revision->documentjson, true, 512, JSON_THROW_ON_ERROR);
$url = new moodle_url('/local/frontpage/page_settings.php', ['id' => $id]);
if ($SCRIPT === '/local/frontpage/activity_navigation.php' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $url->set_anchor('id_activitynavigationheader');
    redirect($url);
}
screen::start($url, get_string('pagesettings', 'local_frontpage'), 'activitynavigation', $page);
$back = \local_frontpage\local\url\page_url::get_edit_url($page);
$form = new \local_frontpage\form\activity_navigation_form($url, ['course' => $course]);
if ($form->is_cancelled()) {
    redirect($back);
}
if ($data = $form->get_data()) {
    $document[activity_navigation_settings::KEY] = activity_navigation_settings::normalise([
        'mode' => $data->activitynavigationmode, 'boundary' => $data->activitynavigationboundary,
    ]);
    factory::page_service()->save_draft(
        $id,
        json_encode($document, JSON_THROW_ON_ERROR),
        (int) $USER->id,
        (int) $data->baserevisionid,
        (int) $data->baseversion
    );
    redirect($back);
}
$settings = activity_navigation_settings::for_document($document);
$form->set_data([
    'pageid' => $id, 'baserevisionid' => $revision->id, 'baseversion' => $revision->draftversion,
    'activitynavigationmode' => $settings['mode'] === 'inherit'
        && !activity_navigation_settings::supports_inheritance($course) ? 'on' : $settings['mode'],
    'activitynavigationboundary' => $settings['boundary'],
]);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pagesettings', 'local_frontpage'), 2);
$form->display();
echo $OUTPUT->footer();

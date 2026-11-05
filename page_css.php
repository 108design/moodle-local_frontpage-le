<?php
/**
 * Edit the selected page revision's CSS.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

// Central page_access enforces the owner context and the required audience or capability.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
use local_frontpage\local\service\factory;
use local_frontpage\local\{page_access, screen};
$id = required_param('id', PARAM_INT);
$page = factory::page_repository()->get_page($id);
page_access::require_edit_css($page);
$revision = factory::page_repository()->get_revision($id, (int)$page->draftrevisionid);
$document = json_decode($revision->documentjson, true);
$url = new moodle_url('/local/frontpage/page_css.php', ['id' => $id]);
screen::start($url, get_string('pagecss', 'local_frontpage'), 'pagecss', $page);
$back = \local_frontpage\local\url\page_url::get_edit_url($page);
$form = new \local_frontpage\form\page_css_form($url);
if ($form->is_cancelled()) {
    redirect($back);
}
if ($data = $form->get_data()) {
    $document['css'] = (new \local_frontpage\local\document\validator())->normalise_page_css($data->pagecss);
    factory::page_service()->save_draft(
        $id,
        json_encode($document, JSON_THROW_ON_ERROR),
        $USER->id,
        (int)$data->baserevisionid,
        (int)$data->baseversion
    );
    redirect($back);
}
$form->set_data(
    [
        'pageid' => $id,
         'baserevisionid' => $revision->id,
         'baseversion' => $revision->draftversion,
         'pagecss' => $document['css'] ?? '',
        ]
);
$PAGE->requires->js_call_amd('local_frontpage/source_editor', 'init', [['textareaId' => 'id_pagecss']]);
echo $OUTPUT->header();
echo html_writer::div(html_writer::tag('strong', format_string($page->title))
    . html_writer::div(
        \local_frontpage\output\authoring::link($back, get_string('editcontent', 'local_frontpage'), 't/left')

        . \local_frontpage\output\authoring::link(
            new moodle_url('/local/frontpage/view.php', [ 'id' => $id, 'preview' => 1 ]),
            get_string('previewdraft', 'local_frontpage'),
            'i/preview',
            false,
            [ 'target' => '_blank', 'rel' => 'noopener' ]
        ),
        'local-frontpage-editor-primary'
    ), 'local-frontpage-editor-header');
$form->display();
echo $OUTPUT->footer();

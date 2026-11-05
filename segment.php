<?php
/**
 * Edit one supported page segment.
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
use local_frontpage\local\page_access;
use local_frontpage\local\revision_files;
use local_frontpage\form\segment_form;
$id = required_param('pageid', PARAM_INT);
$page = factory::page_repository()->get_page($id);
$context = page_access::require_manage($page);
$base = optional_param('baserevisionid', (int)$page->draftrevisionid, PARAM_INT);
$revision = factory::page_repository()->get_revision($id, $base);
$doc = json_decode($revision->documentjson, true);
$segmentid = optional_param('segmentid', '', PARAM_ALPHANUMEXT);
$segment = null;
foreach ($doc['segments'] as $s) {
    if ($s['id'] === $segmentid) {
        $segment = $s;
    }
}
if (!$segment && $segmentid !== '' && !optional_param('newsegment', 0, PARAM_BOOL)) {
    throw new invalid_parameter_exception('Segment not found.');
}
$type = $segment['type'] ?? required_param('type', PARAM_ALPHANUMEXT);
if ($type === 'course_navigation' || !in_array($type, \local_frontpage\local\document\validator::TYPES, true)) {
    throw new invalid_parameter_exception('Unsupported segment type.');
}
$segmentid = $segmentid ?: 'segment_' . bin2hex(random_bytes(8));
$props = $segment['props'] ?? [];
$url = new moodle_url(
    '/local/frontpage/segment.php',
    [
        'pageid' => $id,
         'type' => $type,
         'segmentid' => $segment ? $segmentid : '',
         'baserevisionid' => $base,
         'newsegment' => $segment ? 0 : 1,
        ]
);
\local_frontpage\local\screen::start(
    $url,
    get_string('segmenttype' . str_replace('_', '', $type), 'local_frontpage'),
    'segmentsettings',
    $page
);
$form = new segment_form(
    $url,
    [
        'type' => $type,
         'page' => $page,
         'context' => $context,
         'segmentid' => $segmentid,
         'document' => $doc,
         'oldprops' => $props,
        ]
);
$back = new moodle_url('/local/frontpage/edit.php', ['id' => $id]);
if ($form->is_cancelled()) {
    redirect($back);
}
if ($data = $form->get_data()) {
    $data = (array)$data;
    $newprops = segment_form::props($data, $type, $props);
    $new = ['id' => $data['segmentid'], 'type' => $type, 'props' => $newprops];
    if ($type === 'grid') {
        \local_frontpage\local\grid_structure::claim_segments($doc, $new['id'], $newprops['segmentids']);
    }
    $found = false;
    foreach ($doc['segments'] as &$s) {
        if ($s['id'] === $new['id']) {
            $s = $new;
            $found = true;
        }
    }
    unset($s);
    if (!$found) {
        $doc['segments'][] = $new;
    }
    factory::page_service()->save_draft(
        $id,
        json_encode($doc, JSON_THROW_ON_ERROR),
        $USER->id,
        (int)$data['baserevisionid'],
        (int)$data['baseversion'],
        function ($target) use ($data, $type, $context, $new) {
            global $DB;
            if ($type === 'rich_text') {
                $text = file_save_draft_area_files(
                    (int)$data['content']['itemid'],
                    $context->id,
                    'local_frontpage',
                    'editor',
                    $target->id,
                    ['subdirs' => 1, 'maxfiles' => -1],
                    $data['content']['text']
                );
                $saved = json_decode($target->documentjson, true);
                foreach ($saved['segments'] as &$s) {
                    if ($s['id'] === $new['id']) {
                        $s['props']['content'] = $text;
                    }
                }
                unset($s);
                $DB->set_field(
                    'local_frontpage_revision',
                    'documentjson',
                    json_encode($saved, JSON_THROW_ON_ERROR),
                    [ 'id' => $target->id ]
                );
            }
            if (in_array($type, ['hero', 'media'], true)) {
                file_save_draft_area_files(
                    (int)$data['image'],
                    $context->id,
                    'local_frontpage',
                    revision_files::hero_background_area($new['id']),
                    $target->id,
                    ['maxfiles' => 1, 'subdirs' => 0]
                );
                foreach (
                    get_file_storage()->get_area_files(
                        $context->id,
                        'local_frontpage',
                        revision_files::hero_background_area($new['id']),
                        $target->id,
                        'id',
                        false
                    ) as $file
                ) {
                    if (!\local_frontpage\local\media_files::is_media($file)) {
                        throw new invalid_parameter_exception('An image is required.');
                    }
                }
            }
        }
    );
    redirect($back);
}
$defaults = [
        'pageid' => $id,
         'segmentid' => $segmentid,
         'segmenttype' => $type,
         'baserevisionid' => $base,
         'baseversion' => $revision->draftversion,
         'editorlabel' => $props['editorlabel'] ?? '',
    ];
foreach (segment_form::fields($type) as $key => $spec) {
    $defaults[$key] = $props[$key] ?? $spec[1];
}
foreach (segment_form::layout_fields() as $key => $spec) {
    $defaults['layout_' . $key] = $props['layout'][$key] ?? $spec[1];
}
if ($type === 'rich_text') {
    $draft = file_get_submitted_draft_itemid('content');
    $text = file_prepare_draft_area(
        $draft,
        $context->id,
        'local_frontpage',
        'editor',
        $base,
        ['subdirs' => 1, 'maxfiles' => -1],
        $props['content'] ?? ''
    );
    $defaults['content'] = ['text' => $text, 'format' => FORMAT_HTML, 'itemid' => $draft];
}
if (in_array($type, ['hero', 'media'], true)) {
    $draft = file_get_submitted_draft_itemid('image');
    file_prepare_draft_area(
        $draft,
        $context->id,
        'local_frontpage',
        revision_files::hero_background_area($segmentid),
        $base,
        [ 'maxfiles' => 1, 'subdirs' => 0 ]
    );
    $defaults['image'] = $draft;
}
if ($type === 'grid') {
    for ($i = 0; $i < 4; $i++) {
        $defaults['slot' . $i] = $props['segmentids'][$i] ?? '';
        $defaults['weight' . $i] = $props['widths'][$i] ?? 3;
        $defaults['mobilehidden' . $i] = $props['mobilehidden'][$i] ?? false;
    }
}
$defaults += \local_frontpage\local\segment_colour_modes::defaults($props['layout'] ?? []);
$form->set_data($defaults);
echo $OUTPUT->header();
echo html_writer::div(
    \local_frontpage\output\authoring::link(
        $back,
        get_string('editcontent', 'local_frontpage'),
        't/left'
    )
    . \local_frontpage\output\authoring::type_badge($type),
    'local-frontpage-editor-primary mb-4'
);
$form->display();
echo $OUTPUT->footer();

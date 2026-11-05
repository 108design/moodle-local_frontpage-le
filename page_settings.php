<?php
/**
 * Edit the supported site-home page settings.
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
$id = required_param('id', PARAM_INT);
$page = factory::page_repository()->get_page($id);
\local_frontpage\local\page_access::require_manage($page);
if ($page->ownercomponent !== 'local_frontpage') {
    throw new invalid_parameter_exception('Use native activity settings.');
}
$fingerprint = fn(
        $p
    )=>hash(
        'sha256',
        json_encode(
            [ $p->title, $p->contentwidth, $p->contentwidthvalue ?? '', $p->timemodified ]
        )
    );
$url = new moodle_url('/local/frontpage/page_settings.php', ['id' => $id]);
\local_frontpage\local\screen::start($url, get_string('pageoptions', 'local_frontpage'), 'pagesettings', $page);
$form = new \local_frontpage\form\basic_form($url, ['kind' => 'pageoptions']);
$back = new moodle_url('/local/frontpage/pages.php');
if ($form->is_cancelled()) {
    redirect($back);
}
if ($data = $form->get_data()) {
    factory::page_service()->locked(SITEID, function () use ($id, $data, $fingerprint) {
        global $DB, $USER;
        $current = factory::page_repository()->get_page($id);
        \local_frontpage\local\page_access::require_manage($current);
        if (!hash_equals($fingerprint($current), $data->metadatahash)) {
            throw new moodle_exception('draftconflict', 'local_frontpage');
        }
        if (
            !in_array($data->contentwidth, [ 'theme', 'content', 'wide', 'full', 'custom' ], true)
            || trim($data->title) === ''
            || core_text::strlen($data->title) > 255
        ) {
            throw new invalid_parameter_exception('Invalid page options.');
        }
        $width = $data->contentwidth === 'custom' ? \local_frontpage\local\width_value::normalise(
            $data->contentwidthvalue
        ) : null;
        $DB->update_record(
            'local_frontpage_page',
            (object)[
            'id' => $id,
            'title' => $data->title,
            'contentwidth' => $data->contentwidth,

            'contentwidthvalue' => $width, 'modifiedby' => $USER->id, 'timemodified' => time(),
            ]
        );
    });
    redirect($back);
}
$form->set_data((object)(['metadatahash' => $fingerprint($page)] + (array)$page));
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();

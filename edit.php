<?php
/**
 * Render the page content editor for an authorised author.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

// Central page_access enforces the owner context and the required audience or capability.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(__DIR__ . '/../../config.php');
use local_frontpage\local\service\factory;
use local_frontpage\local\page_access;
use local_frontpage\local\screen;
$id = required_param('id', PARAM_INT);
$page = factory::page_repository()->get_page($id);
page_access::require_manage($page);
$revision = factory::page_repository()->get_revision($id, (int)$page->draftrevisionid);
$url = new moodle_url('/local/frontpage/edit.php', ['id' => $id]);
screen::start($url, $page->title, 'editor', $page);
$actions = ['pageid' => $id, 'baserevisionid' => $revision->id, 'baseversion' => $revision->draftversion];
$PAGE->add_body_class('local-frontpage-editor-mode');
use local_frontpage\output\authoring;
$primary = '';
$assigned = $page->ownercomponent !== 'local_frontpage'
        || in_array(
            (int)$id,
            [
            (int)get_config('local_frontpage', 'guestfrontpageid'),
            (int)get_config('local_frontpage', 'loggedinfrontpageid'),
            ],
            true
        );
if (
        $assigned
        && has_capability(
            $page->ownercomponent === 'mod_frontpage' ? 'mod/frontpage:publishcontent' : 'local/frontpage:publishpages',
            page_access::context($page)
        )
) {
    $primary = authoring::post(
        get_string('publish', 'local_frontpage'),
        'i/publish',
        $actions + [ 'action' => 'publish' ],
        true
    );
}
if (!$assigned) {
    $primary .= authoring::link(
        new moodle_url('/local/frontpage/preferences.php'),
        get_string('publishassignmentrequired', 'local_frontpage'),
        'i/settings'
    );
}
$primary .= authoring::link(
    new moodle_url('/local/frontpage/view.php', [ 'id' => $id, 'preview' => 1 ]),
    get_string('previewdraft', 'local_frontpage'),
    'i/preview'
);
$tools = authoring::link(
    new moodle_url('/local/frontpage/segment.php', ['pageid' => $id]),
    get_string('addsegment', 'local_frontpage'), 't/add'
);
if ($page->ownercomponent === 'mod_frontpage') {
    $tools .= authoring::link(
        new moodle_url('/local/frontpage/activity_navigation.php', ['id' => $id]),
        get_string('activitynavigation', 'local_frontpage'),
        'i/settings',
        false
    );
}
if (page_access::can_edit_css($page)) {
    $tools .= authoring::link(
        new moodle_url('/local/frontpage/page_css.php', [ 'id' => $id ]),
        get_string('pagecss', 'local_frontpage'),
        'e/source_code',
        false
    );
}
if ($page->ownercomponent === 'local_frontpage') {
    $tools .= authoring::link(
        new moodle_url('/local/frontpage/page_settings.php', [ 'id' => $id ]),
        get_string('pageoptions', 'local_frontpage'),
        'local/page-settings',
        false
    );
} else if (has_capability('moodle/course:manageactivities', page_access::context($page))) {
    $tools .= authoring::link(
        new moodle_url('/course/modedit.php', [ 'update' => $PAGE->cm->id, 'return' => 1 ]),
        get_string('activitysettings', 'local_frontpage'),
        'i/settings',
        false
    );
}
foreach (
        [
        'revisions.php' => [ 'revisions', 'fa/history' ],
         'page_files.php' => [ 'pagefiles', 'fa/folder-open' ],
        ] as $route => $item
) {
    $tools .= authoring::link(
        new moodle_url('/local/frontpage/' . $route, [ 'id' => $id ]),
        get_string($item[0], 'local_frontpage'),
        $item[1],
        false
    );
}
$resourcecourse = \local_frontpage\local\page_scope::course_id($page, page_access::context($page));
if (has_capability('moodle/course:manageactivities', context_course::instance($resourcecourse))) {
    $tools .= authoring::link(
        new moodle_url(
            '/local/frontpage/resources.php',
            [
            'courseid' => \local_frontpage\local\page_scope::course_id($page, page_access::context($page)),
            ]
        ),
        get_string('resources', 'local_frontpage'),
        'i/folder',
        false
    );
    $tools .= authoring::link(
        new moodle_url(
            '/local/frontpage/activity_create.php',
            $actions + [ 'segmentid' => 'segment_' . bin2hex(random_bytes(8)) ]
        ),
        get_string('createfrontpageresource', 'local_frontpage'),
        't/add',
        false
    );
}
$regions = $PAGE->blocks->get_regions();
$region = in_array('side-pre', $regions, true) ? 'side-pre' : (in_array('side-post', $regions, true) ? 'side-post' : null);
if ($region !== null) {
    $block = new block_contents();
    $block->add_class('local-frontpage-page-actions-block');
    $block->title = '';
    $block->content = html_writer::tag('nav', $tools . $primary, [
        'class' => 'local-frontpage-drawer-actions', 'data-frontpage-page-tools' => '1',
        'aria-label' => get_string('uipageactions', 'local_frontpage'),
    ]);
    $PAGE->blocks->add_fake_block($block, $region);
    $PAGE->requires->css(new moodle_url('/local/frontpage/styles/editor_sidebar.css'));
    $PAGE->requires->js_call_amd('local_frontpage/editor_sidebar', 'init', [[
        'title' => get_string('uipageactions', 'local_frontpage'),
        'open' => get_string('uiopentools', 'local_frontpage'),
        'close' => get_string('uiclosetools', 'local_frontpage'),
    ]]);
}
echo $OUTPUT->header();
echo html_writer::div(html_writer::div($primary, 'local-frontpage-editor-primary')
    . html_writer::div($tools, 'local-frontpage-page-actionbar'), 'local-frontpage-editor-header');
echo html_writer::div(
    authoring::icon('i/info') . s(get_string('draftnotice', 'local_frontpage')),
    'local-frontpage-draft-notice'
);
echo (
        new \local_frontpage\output\page_renderer(
            $OUTPUT,
            $page,
            (int)$revision->id,
            true,
            $actions
        )
    )->render(
        $revision->documentjson
    );
echo authoring::chooser($actions);
echo $OUTPUT->footer();

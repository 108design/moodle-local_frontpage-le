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
if ($page->ownercomponent === 'mod_frontpage') {
    $PAGE->add_body_class('local-frontpage-close-course-index');
    $PAGE->requires->js_call_amd('local_frontpage/editor_course_index', 'init');
}
use local_frontpage\output\authoring;
$primary = '';
$isactivity = $page->ownercomponent === 'mod_frontpage';
$context = page_access::context($page);
$canviewpublication = $isactivity
    ? $PAGE->cm->uservisible && has_capability('mod/frontpage:view', $context)
    : (int)get_config('local_frontpage', 'loggedinfrontpageid') === $id;
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
            $isactivity ? 'mod/frontpage:publishcontent' : 'local/frontpage:publishpages',
            $context
        )
) {
    $primary = authoring::post(
        get_string('publish', 'local_frontpage'),
        'i/publish',
        $actions + [ 'action' => 'publish' ],
        true
    );
    if ($canviewpublication && $page->enabled) {
        $primary .= authoring::post(
            get_string('publishandview', 'local_frontpage'),
            'fa/external-link-alt',
            $actions + ['action' => 'publish', 'showpublication' => 1],
            labelled: true
        );
    }
}
if (!$assigned) {
    $primary .= authoring::link(
        new moodle_url('/local/frontpage/preferences.php'),
        get_string('publishassignmentrequired', 'local_frontpage'),
        'i/settings'
    );
}
if ($canviewpublication && page_access::can_view_published($page)) {
    $primary .= authoring::link(
        \local_frontpage\local\url\page_url::get_public_url($page),
        get_string('currentpublishedversion', 'local_frontpage'),
        'fa/globe'
    );
}
$back = new moodle_url('/local/frontpage/pages.php');
if ($isactivity) {
    $back = new moodle_url('/course/view.php', ['id' => $PAGE->course->id], 'module-' . $PAGE->cm->id);
}
$tools = authoring::link($back, get_string($isactivity ? 'activitynavigationbacktocourse' : 'pages', 'local_frontpage'), 't/left');
$tools .= authoring::link(
    new moodle_url('/local/frontpage/segment.php', ['pageid' => $id]),
    get_string('addsegment', 'local_frontpage'),
    't/add'
);
$settings = new moodle_url('/local/frontpage/page_settings.php', ['id' => $id]);
$tools .= authoring::link($settings, get_string($isactivity ? 'pagesettings' : 'pageoptions', 'local_frontpage'),
    authoring::PAGE_SETTINGS_ICON);
if ($isactivity && has_capability('moodle/course:manageactivities', $context)) {
    $tools .= authoring::link(
        new moodle_url('/course/modedit.php', [ 'update' => $PAGE->cm->id, 'return' => 1 ]),
        get_string('activitysettings', 'local_frontpage'),
        'i/settings'
    );
}
$tools .= authoring::link(
    new moodle_url('/local/frontpage/page_files.php', ['id' => $id]),
    get_string('pagefiles', 'local_frontpage'),
    authoring::PAGE_FILES_ICON
);
if (page_access::can_edit_css($page)) {
    $tools .= authoring::link(
        new moodle_url('/local/frontpage/page_css.php', ['id' => $id]),
        get_string('pagecss', 'local_frontpage'),
        'e/source_code'
    );
}
if ($isactivity) {
    $tools .= authoring::link(
        new moodle_url($settings, [], 'id_activitynavigationheader'),
        get_string('activitynavigation', 'local_frontpage'),
        'fa/compass'
    );
}
$resourcecourse = \local_frontpage\local\page_scope::course_id($page, $context);
if (has_capability('moodle/course:manageactivities', context_course::instance($resourcecourse))) {
    $tools .= authoring::link(
        new moodle_url('/local/frontpage/resources.php', ['courseid' => $resourcecourse]),
        get_string('resources', 'local_frontpage'),
        'i/folder',
        false
    );
}
$draftstatus = html_writer::span(get_string('draftstatus', 'local_frontpage'), 'small text-muted local-frontpage-draft-status');
$editoractions = $tools . $draftstatus . $primary . authoring::link(
    new moodle_url('/local/frontpage/revisions.php', ['id' => $id]),
    get_string('revisions', 'local_frontpage'),
    authoring::VERSIONS_ICON
);
$regions = $PAGE->blocks->get_regions();
$region = in_array('side-pre', $regions, true) ? 'side-pre' : (in_array('side-post', $regions, true) ? 'side-post' : null);
if ($region !== null) {
    $block = new block_contents();
    $block->add_class('local-frontpage-page-actions-block');
    $block->title = '';
    $block->content = html_writer::tag('nav', $editoractions, [
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
echo html_writer::div(html_writer::div($editoractions, 'local-frontpage-page-actionbar'), 'local-frontpage-editor-header');
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

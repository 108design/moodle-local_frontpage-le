<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Native Moodle callbacks for Frontpage.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

defined('MOODLE_INTERNAL') || die();
/**
 * Return the component control for the native navigation bar.
 *
 * @param \renderer_base $renderer Native renderer used for output.
 * @return string
 */
function local_frontpage_render_navbar_output(\renderer_base $renderer): string {
    return \local_frontpage\output\context_help::navbar($renderer);
}
/**
 * Expose the supported Frontpage course navigation entry.
 *
 * @param navigation_node $navigation Navigation.
 * @param stdClass $course Native course record.
 * @param context_course $context Native Moodle context.
 * @return void
 */
function local_frontpage_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
): void {
    if (has_capability('moodle/course:manageactivities', $context)) {
        $navigation->add(get_string('resources', 'local_frontpage'), new moodle_url(
            '/local/frontpage/resources.php',
            ['courseid' => $course->id]
        ), navigation_node::TYPE_CUSTOM, null, 'frontpage_resources');
    }
}
/**
 * Serve a revision-owned file only after checking page and file access.
 *
 * @param mixed $course Native course record.
 * @param mixed $cm Native course-module record.
 * @param mixed $context Native Moodle context.
 * @param mixed $filearea Native Moodle file area.
 * @param mixed $args Arguments for the native callback.
 * @param mixed $forcedownload Whether to force a file download.
 * @param array $options Presentation or operation options.
 * @return bool
 */
function local_frontpage_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = [ ]
): bool {
    global $DB;
    if (
        !in_array($filearea, \local_frontpage\local\revision_files::AREAS, true)
            && !\local_frontpage\local\revision_files::is_hero_background_area($filearea)
    ) {
        return false;
    }
    $revisionid = (int)array_shift($args);
    $revision = $DB->get_record('local_frontpage_revision', ['id' => $revisionid]);
    if (!$revision) {
        return false;
    }
    $page = $DB->get_record('local_frontpage_page', ['id' => $revision->pageid, 'contextid' => $context->id]);
    if (!$page) {
        return false;
    }
    $preview = (int)$page->publishedrevisionid !== $revisionid;
    \local_frontpage\local\page_access::require_view($page, $preview);
    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file($context->id, 'local_frontpage', $filearea, $revisionid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    $forcedownload = $forcedownload || !\local_frontpage\local\media_files::is_media($file);
    send_stored_file($file, 0, 0, $forcedownload, $options);
    return true;
}

/**
 * Carry a per-form return ticket through Moodle's own form POST.
 *
 * @param mixed $wrapper Wrapper.
 * @param \MoodleQuickForm $form Native form receiving the controls.
 * @return void
 */
function local_frontpage_coursemodule_standard_elements($wrapper, \MoodleQuickForm $form): void {
    global $PAGE;
    $pending = \local_frontpage\local\activity_return::pending();
    if (
        !$pending || optional_param('add', '', PARAM_ALPHANUM) !== $pending['modname']
            || optional_param('course', 0, PARAM_INT) !== (int)$pending['courseid']
    ) {
        return;
    }
    $form->addElement('hidden', 'frontpagereturntoken', $pending['token']);
    $form->setType('frontpagereturntoken', PARAM_ALPHANUM);
    $PAGE->requires->js_call_amd('local_frontpage/activity_return', 'init', [
        \local_frontpage\local\activity_return::landing_url($pending)->out(false)]);
}

/**
 * Default a new Frontpage resource to link access inside the hidden native section.
 *
 * @param \moodleform_mod $wrapper Native activity form.
 * @param \MoodleQuickForm $form Native form receiving the controls.
 */
function local_frontpage_coursemodule_definition_after_data(\moodleform_mod $wrapper, \MoodleQuickForm $form): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET' || !$form->elementExists('frontpagereturntoken')
            || !$form->elementExists('visible') || $wrapper->get_coursemodule()) {
        return;
    }
    $pending = \local_frontpage\local\activity_return::pending();
    if (!$pending || $pending['mode'] !== 'create') {
        return;
    }
    $section = get_fast_modinfo($wrapper->get_course())->get_section_info((int)$wrapper->get_section());
    if ($section && (int)$section->id === (int)$pending['sectionid'] && !$section->visible) {
        $form->setDefault('visible', 1);
    }
}
/**
 * Let Core commit normally, then use its CM-specific redirect for an unambiguous return.
 *
 * @param mixed $moduleinfo Moduleinfo.
 * @param mixed $course Native course record.
 * @return mixed
 */
function local_frontpage_coursemodule_edit_post_actions($moduleinfo, $course) {
    $pending = \local_frontpage\local\activity_return::pending();
    if (
        $pending && $pending['modname'] === $moduleinfo->modulename && (int)$pending['courseid'] === (int)$course->id
            && in_array((int)$moduleinfo->coursemodule, $pending['createdcmids'], true)
    ) {
        $moduleinfo->submitbutton = 1;
    }
    return $moduleinfo;
}

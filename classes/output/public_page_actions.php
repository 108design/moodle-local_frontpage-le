<?php
/**
 * Render authoring shortcuts on the published page.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\output;

use action_menu;
use action_menu_link_secondary;
use core_renderer;
use html_writer;
use local_frontpage\local\page_access;
use local_frontpage\local\service\factory;
use local_frontpage\local\url\page_url;
use moodle_url;
use pix_icon;
use stdClass;

/** Compact authoring links shared by the site-home and activity views. */
final class public_page_actions {
    /**
     * Store the page record used for the shortcuts.
     *
     * @param stdClass $page Frontpage record.
     */
    private function __construct(private readonly stdClass $page) {
    }

    /**
     * Prepare author-only output before Moodle sends the page header.
     *
     * @param stdClass $page Frontpage record.
     * @return self|null
     */
    public static function prepare(stdClass $page): ?self {
        global $PAGE;

        if (!isloggedin() || isguestuser() || !page_access::can_manage($page)) {
            return null;
        }
        $PAGE->set_cacheable(false);
        context_help::prepare('editor');
        return new self($page);
    }

    /**
     * Link to segments that still exist in the current draft.
     *
     * @return array<string, moodle_url> Draft segment IDs and edit URLs.
     */
    public function segment_urls(): array {
        if (empty($this->page->draftrevisionid)) {
            return [];
        }
        $draft = factory::page_repository()->get_revision($this->page->id, (int)$this->page->draftrevisionid);
        $document = json_decode($draft->documentjson, true, 512, JSON_THROW_ON_ERROR);
        $urls = [];
        foreach ($document['segments'] as $segment) {
            if ($segment['type'] !== 'course_navigation') {
                $urls[$segment['id']] = new moodle_url('/local/frontpage/segment.php', [
                    'pageid' => $this->page->id,
                    'segmentid' => $segment['id'],
                ]);
            }
        }
        return $urls;
    }

    /**
     * Render a primary edit link and the native Moodle menu for other actions.
     *
     * @param core_renderer $output Native renderer used for output.
     * @return string
     */
    public function render(core_renderer $output): string {
        $params = ['id' => $this->page->id];
        $label = get_string('uipageactions', 'local_frontpage');
        $menu = new action_menu();
        $menu->set_kebab_trigger(get_string('moreactions'), $output);
        if ($this->page->ownercomponent === 'local_frontpage') {
            $menu->add(new action_menu_link_secondary(
                new moodle_url('/local/frontpage/pages.php'),
                new pix_icon('i/settings', ''),
                get_string('pages', 'local_frontpage')
            ));
            $menu->add(new action_menu_link_secondary(
                new moodle_url('/local/frontpage/page_settings.php', $params),
                new pix_icon('i/settings', ''),
                get_string('pageoptions', 'local_frontpage')
            ));
        } else {
            $context = page_access::context($this->page);
            if (has_capability('moodle/course:manageactivities', $context)) {
                $menu->add(new action_menu_link_secondary(
                    new moodle_url('/course/modedit.php', ['update' => $context->instanceid, 'return' => 1]),
                    new pix_icon('i/settings', ''),
                    get_string('activitysettings', 'local_frontpage')
                ));
            }
        }
        if (page_access::can_edit_css($this->page)) {
            $menu->add(new action_menu_link_secondary(
                new moodle_url('/local/frontpage/page_css.php', $params),
                new pix_icon('e/source_code', ''),
                get_string('pagecss', 'local_frontpage')
            ));
        }
        $menu->add(new action_menu_link_secondary(
            new moodle_url('/local/frontpage/page_files.php', $params),
            new pix_icon('i/folder', ''),
            get_string('pagefiles', 'local_frontpage')
        ));
        $menu->add(new action_menu_link_secondary(
            new moodle_url('/local/frontpage/revisions.php', $params),
            new pix_icon('i/backup', ''),
            get_string('revisions', 'local_frontpage')
        ));

        $edit = authoring::link(page_url::get_edit_url($this->page), get_string('editcontent', 'local_frontpage'), 't/edit', true);
        return html_writer::tag('nav',
            html_writer::span(s($label), 'badge local-frontpage-page-actions-label')
                . html_writer::div($edit . $output->render($menu), 'local-frontpage-page-actionbar'),
            ['class' => 'local-frontpage-page-actions-island d-print-none', 'aria-label' => $label]
        );
    }
}

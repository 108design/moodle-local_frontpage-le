<?php
/**
 * Revision-owned page stylesheet; separate CSS responses never enter HTML markup.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

use local_frontpage\local\service\factory;

defined('MOODLE_INTERNAL') || die();
/**
 * Revision-owned page stylesheet; separate CSS responses never enter HTML markup.
 */
final class page_css {
    /**
     * Register the assets needed by the current native page.
     *
     * @param \stdClass $page Page record or native page context.
     * @param \stdClass $revision Stored revision.
     * @return void
     */
    public static function prepare(\stdClass $page, \stdClass $revision): void {
        global $PAGE;
        $document = json_decode($revision->documentjson, true);
        if (trim((string)($document['css'] ?? '')) === '') {
            return;
        }
        $PAGE->requires->css(new \moodle_url('/local/frontpage/revision_css.php', [
            'pageid' => $page->id, 'revisionid' => $revision->id, 'v' => $revision->draftversion]));
    }
    /**
     * Return authorised revision-owned CSS with resolved image markers.
     *
     * @param \stdClass $page Page record or native page context.
     * @param int $revisionid Revision ID.
     * @return string
     */
    public static function content(\stdClass $page, int $revisionid): string {
        page_access::require_view($page, (int)$page->publishedrevisionid !== $revisionid);
        $revision = factory::page_repository()->get_revision((int)$page->id, $revisionid);
        $document = json_decode($revision->documentjson, true);
        $css = (new document\validator())->normalise_page_css($document['css'] ?? '');
        $context = page_access::context($page);
        return preg_replace_callback(
            '/\{\{image-url:([a-zA-Z0-9][a-zA-Z0-9._ -]{0,199})\}\}/',
            static function (
                $m
            ) use (
                $context,
                $revisionid
            ) {
                $file = get_file_storage()->get_file(
                    $context->id,
                    'local_frontpage',
                    'pageassets',
                    $revisionid,
                    '/',
                    $m[1]
                );
                return $file ? \moodle_url::make_pluginfile_url(
                    $context->id,
                    'local_frontpage',
                    'pageassets',
                    $revisionid,
                    '/',
                    $m[1]
                )->out(
                    false
                ) : '';
            },
            $css
        );
    }
}

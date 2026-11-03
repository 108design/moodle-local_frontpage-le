<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local\persistence;

use moodle_database;
use stdClass;

/**
 * Database access for Frontpage pages and revisions.
 */
final class page_repository {
    /**
     * Constructor.
     *
     * @param moodle_database $db Moodle database.
     */
    public function __construct(private readonly moodle_database $db) {
    }

    /**
     * Insert page metadata.
     *
     * @param stdClass $page Page record.
     * @return int
     */
    public function insert_page(stdClass $page): int {
        return $this->db->insert_record('local_frontpage_page', $page);
    }

    /**
     * Insert a draft workspace or immutable published version.
     *
     * @param stdClass $revision Revision record.
     * @return int
     */
    public function insert_revision(stdClass $revision): int {

        return $this->db->insert_record('local_frontpage_revision', $revision);
    }

    /**
     * Update the current draft workspace or publication metadata.
     *
     * @param stdClass $revision Partial record containing id.
     */
    public function update_revision(stdClass $revision): void {
        if ($this->db->get_field('local_frontpage_revision', 'ischeckpoint', ['id' => $revision->id])) {
            throw new \moodle_exception('historyreadonly', 'local_frontpage');
        }

        $this->db->update_record('local_frontpage_revision', $revision);
    }

    /**
     * Get a page or fail.
     *
     * @param int $pageid Page id.
     * @return stdClass
     */
    public function get_page(int $pageid): stdClass {
        return $this->db->get_record('local_frontpage_page', ['id' => $pageid], '*', MUST_EXIST);
    }

    /** Find a page by id without raising a database exception. */
    public function find_page(int $pageid): stdClass|false {
        return $this->db->get_record('local_frontpage_page', ['id' => $pageid]);
    }

    /**
     * Get a page by its public slug or fail.
     *
     * @param string $slug Page slug.
     * @return stdClass
     */
    public function get_page_by_slug(string $slug): stdClass {
        return $this->db->get_record('local_frontpage_page', ['slug' => $slug], '*', MUST_EXIST);
    }

    /** Find a page by slug without failing. */
    public function find_page_by_slug(string $slug): stdClass|false {
        return $this->db->get_record('local_frontpage_page', ['slug' => $slug]);
    }

    /** Find the single page bound to a Moodle target. */
    public function find_binding(string $bindingtype, int $targetid): stdClass|false {
        return $this->db->get_record('local_frontpage_page', [
            'bindingtype' => $bindingtype,
            'targetid' => $targetid,
        ]);
    }

    /**
     * List pages for the management interface.
     *
     * @return array Page records keyed by id.
     */
    public function get_pages(): array {
        return $this->db->get_records('local_frontpage_page', ['ownercomponent' => 'local_frontpage'],
            'timemodified DESC, id DESC');
    }

    /**
     * Get published pages which should appear in Moodle navigation.
     *
     * @param bool $authenticated Whether the viewer is authenticated.
     * @return array
     */
    

    /** Published system pages addressable by a canonical managed menu, regardless of legacy nav placement. */
    

    /** Published system pages addressed by portable slugs in Menu Builder documents. */
    

    /**
     * Get a revision belonging to a page or fail.
     *
     * @param int $pageid Page id.
     * @param int $revisionid Revision id.
     * @return stdClass
     */
    public function get_revision(int $pageid, int $revisionid): stdClass {
        return $this->db->get_record('local_frontpage_revision', [
            'id' => $revisionid,
            'pageid' => $pageid,
        ], '*', MUST_EXIST);
    }

    /**
     * List the current draft and published versions, newest first.
     *
     * @param int $pageid Page id.
     * @param int $limit Maximum number of revisions, or zero for all.
     * @return array Revision records keyed by id.
     */
    public function get_revisions(int $pageid, int $limit = 0, string $history = 'published'): array {
        $page = $this->get_page($pageid);
        return $this->db->get_records_select(
            'local_frontpage_revision',
            'pageid = :pageid AND (id = :draftid OR ' . ($history === 'drafts'
                ? 'ischeckpoint = 1 AND timepublished IS NULL' : 'timepublished IS NOT NULL') . ')',
            ['pageid' => $pageid, 'draftid' => $page->draftrevisionid],
            'CASE WHEN id = ' . (int)$page->draftrevisionid . ' THEN 0 ELSE 1 END, revisionnumber DESC',
            '*',
            0,
            $limit
        );
    }

    /**
     * Count a page's revisions.
     *
     * @param int $pageid Page id.
     * @return int
     */
    public function count_revisions(int $pageid, string $history = 'published'): int {
        $page = $this->get_page($pageid);
        return $this->db->count_records_select(
            'local_frontpage_revision',
            'pageid = :pageid AND (id = :draftid OR ' . ($history === 'drafts'
                ? 'ischeckpoint = 1 AND timepublished IS NULL' : 'timepublished IS NOT NULL') . ')',
            ['pageid' => $pageid, 'draftid' => $page->draftrevisionid]
        );
    }

    /**
     * Get the next revision number for a page.
     *
     * The caller must hold the page lock.
     *
     * @param int $pageid Page id.
     * @return int
     */
    public function get_next_revision_number(int $pageid): int {
        $maximum = $this->db->get_field_sql(
            'SELECT MAX(revisionnumber) FROM {local_frontpage_revision} WHERE pageid = :pageid',
            ['pageid' => $pageid]
        );

        return ((int) $maximum) + 1;
    }

    /**
     * Update page fields.
     *
     * @param stdClass $page Partial record containing id.
     */
    public function update_page(stdClass $page): void {
        $this->db->update_record('local_frontpage_page', $page);
    }

    /**
     * Determine whether a slug is already used.
     *
     * @param string $slug Slug.
     * @param int|null $ignorepageid Optional page to ignore.
     * @return bool
     */
    public function slug_exists(string $slug, ?int $ignorepageid = null): bool {
        if ($ignorepageid === null) {
            return $this->db->record_exists('local_frontpage_page', ['slug' => $slug]);
        }

        return $this->db->record_exists_select(
            'local_frontpage_page',
            'slug = :slug AND id <> :pageid',
            ['slug' => $slug, 'pageid' => $ignorepageid]
        );
    }

    /** Return explicit segment enabled states keyed by segment id. */
    

    /** Upsert an immediately effective segment enabled state. */
    

    
}

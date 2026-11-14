<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Manage page revisions, publication and deletion under the shared write locks.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local\service;

use local_frontpage\local\document\validator;
use local_frontpage\local\page_access;
use local_frontpage\local\page_scope;
use local_frontpage\local\revision_files;
use local_frontpage\local\course_resource_manager;

/**
 * Manage page revisions, publication and deletion under the shared write locks.
 */
final class page_service {
    /**
     * Render the page navigation appropriate to the current context.
     *
     * @return array
     */
    public static function navigation(): array {
        $segment = ['id' => 'course_navigation', 'type' => 'course_navigation', 'props' => [
            'definition' => '', 'style' => 'secondary', 'menupadding' => 'normal',
            'layout' => [
        'width' => 'inherit',
         'background' => 'transparent',
         'padding' => 'none',
         'radius' => 'none',
         'spacing' => 'medium',
            ]]];
        return json_decode((new validator())->validate_and_normalise(json_encode(['schemaVersion' => 2,
            'aboveHeaderSegment' => null, 'segments' => [$segment]], JSON_THROW_ON_ERROR)), true)['segments'][0];
    }
    /**
     * Run the page operation under the shared course write lock.
     *
     * @param int $courseid Native course ID.
     * @param callable $action Requested action.
     * @return mixed
     */
    public function locked(int $courseid, callable $action): mixed {
        $lock = \core\lock\lock_config::get_lock_factory('local_frontpage')->get_lock('resources_' . $courseid, 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout', 'local_frontpage');
        }
        try {
            return $action();
        } finally {
            $lock->release();
        }
    }
    /**
     * Create a private page bound to the site-home context.
     *
     * @param string $title Display title.
     * @return \stdClass
     */
    public function create_home(string $title): \stdClass {
        require_capability('local/frontpage:managepages', \context_system::instance());
        return $this->locked(SITEID, function () use ($title) {
            global $DB, $USER;
            if ($DB->count_records('local_frontpage_page', ['ownercomponent' => 'local_frontpage']) >= 5) {
                throw new \moodle_exception('homepagelimit', 'local_frontpage');
            }
            return $this->create($title, \context_system::instance(), 'local_frontpage', null, (int)$USER->id);
        });
    }
    /**
     * Switch audience assignments without leaving a public experimental page behind.
     *
     * @param int $guestid Guestid.
     * @param int $memberid Memberid.
     * @param bool $enabled Whether future delivery is enabled.
     * @return void
     */
    public function home_settings(int $guestid, int $memberid, bool $enabled): void {
        require_capability('local/frontpage:managepages', \context_system::instance());
        $this->locked(SITEID, function () use ($guestid, $memberid, $enabled) {
            global $DB;
            $tx = $DB->start_delegated_transaction();
            foreach (array_unique([$guestid, $memberid]) as $id) {
                if (
                    $id
                    && !$DB->record_exists(
                        'local_frontpage_page',
                        [ 'id' => $id, 'ownercomponent' => 'local_frontpage' ]
                    )
                ) {
                    throw new \invalid_parameter_exception('Invalid Site Home page.');
                }
            }
            foreach (factory::page_repository()->get_pages() as $page) {
                if (!in_array((int)$page->id, [$guestid, $memberid], true) && !empty($page->publishedrevisionid)) {
                    // Keep both the published revision and the working draft; remove only the public pointer.
                    $DB->update_record(
                        'local_frontpage_page',
                        (object)[
                        'id' => $page->id,
                        'publishedrevisionid' => null,

                        'status' => 'draft', 'visibility' => 'private', 'timemodified' => time(),
                        ]
                    );
                }
            }
            set_config('guestfrontpageid', $guestid, 'local_frontpage');
            set_config('loggedinfrontpageid', $memberid, 'local_frontpage');
            set_config('sitehomeenabled', (int)$enabled, 'local_frontpage');
            $tx->allow_commit();
        });
    }
    /**
     * Create the page owned by a native Frontpage activity.
     *
     * @param string $title Display title.
     * @param string $slug Stable URL slug.
     * @param \context $context Native Moodle context.
     * @param string $ownercomponent Ownercomponent.
     * @param int $instanceid Native instance ID.
     * @param int $userid User ID.
     * @return \stdClass
     */
    public function create_activity_page(
        string $title,
        string $slug,
        \context $context,
        string $ownercomponent,
        int $instanceid,
        int $userid
    ): \stdClass {
        if ($ownercomponent !== 'mod_frontpage' || $context->contextlevel !== CONTEXT_MODULE) {
            throw new \invalid_parameter_exception('Invalid activity owner.');
        }
        require_capability('mod/frontpage:addinstance', $context);
        return $this->create($title, $context, $ownercomponent, $instanceid, $userid, $slug);
    }
    /**
     * Create the bound page and its first draft revision.
     *
     * @param string $title Display title.
     * @param \context $context Native Moodle context.
     * @param string $owner Owning component.
     * @param int|null $instanceid Native instance ID.
     * @param int $userid User ID.
     * @param string $slug Stable URL slug.
     * @return \stdClass
     */
    private function create(
        string $title,
        \context $context,
        string $owner,
        ?int $instanceid,
        int $userid,
        string $slug = ''
    ): \stdClass {
        global $DB;
        $title = trim(clean_param($title, PARAM_TEXT));
        if ($title === '' || \core_text::strlen($title) > 255) {
            throw new \invalid_parameter_exception('Invalid page title.');
        }
        $tx = $DB->start_delegated_transaction();
        $page = (object)['ownercomponent' => $owner, 'ownerinstanceid' => $instanceid, 'contextid' => $context->id,
            'bindingtype' => 'system', 'targetid' => null, 'sitehomescope' => $owner === 'local_frontpage' ? 1 : 0,
            'slug' => $slug ?: 'home-' . bin2hex(random_bytes(8)),
         'title' => $title,
         'status' => 'draft',
         'enabled' => 1,

            'shell' => 'moodle', 'showtopnav' => 1, 'showdrawer' => 1, 'showpageindex' => 0, 'showfooter' => 1,
            'usecontentcontainer' => 1, 'contentwidth' => 'wide', 'visibility' => 'private', 'navplacement' => 'none',
            'createdby' => $userid, 'modifiedby' => $userid, 'timecreated' => time(), 'timemodified' => time()];
        $page->id = $DB->insert_record('local_frontpage_page', $page);
        $doc = [
        'schemaVersion' => 2,
         'aboveHeaderSegment' => null,
         'segments' => $owner === 'mod_frontpage' ? [ self::navigation() ] : [ ],
        ];
        $revision = $this->new_revision($page, json_encode($doc, JSON_THROW_ON_ERROR), $userid);
        $DB->set_field('local_frontpage_page', 'draftrevisionid', $revision->id, ['id' => $page->id]);
        $tx->allow_commit();
        return factory::page_repository()->get_page($page->id);
    }
    /**
     * Create a revision with the requested document and parent.
     *
     * @param \stdClass $page Page record or native page context.
     * @param string $json Encoded JSON document.
     * @param int $userid User ID.
     * @param \stdClass|null $source Configured source or original data.
     * @return \stdClass
     */
    private function new_revision(\stdClass $page, string $json, int $userid, ?\stdClass $source = null): \stdClass {
        global $DB;
        $revision = (object)[
        'pageid' => $page->id,
         'revisionnumber' => factory::page_repository()->get_next_revision_number($page->id),

            'schemaversion' => 2, 'documentjson' => $json, 'summary' => '', 'draftversion' => 1, 'createdby' => $userid,
            'timecreated' => time(),
         'publishedby' => null,
         'timepublished' => null,
         'ischeckpoint' => 0,
         'originjson' => null,
        ];
        $revision->id = $DB->insert_record('local_frontpage_revision', $revision);
        if ($source) {
            revision_files::copy_revision($source->id, $revision->id);
        }
        course_resource_manager::sync_revision($revision->id, $page->id, $json);
        return $revision;
    }
    /**
     * Validate the supported definition fields and reject unknown options.
     *
     * @param \stdClass $page Page record or native page context.
     * @param string $json Encoded JSON document.
     * @param string $previousjson Last validated draft, for references that may since have become unavailable.
     * @return string
     */
    private function validate(\stdClass $page, string $json, string $previousjson): string {
        $json = (new validator())->validate_and_normalise($json);
        $doc = json_decode($json, true);
        $navigation = array_values(array_filter($doc['segments'], fn($s)=>$s['type'] === 'course_navigation'));
        if ($page->ownercomponent === 'mod_frontpage') {
            if ($navigation !== [self::navigation()] || ($doc['segments'][0] ?? null) !== self::navigation()) {
                throw new \invalid_parameter_exception('Course navigation cannot be changed.');
            }
        } else if ($navigation) {
            throw new \invalid_parameter_exception('Course navigation requires an activity.');
        }
        $previous = array_column(json_decode($previousjson, true, 512, JSON_THROW_ON_ERROR)['segments'], null, 'id');
        foreach ($doc['segments'] as $segment) {
            $cmid = $segment['props']['cmid'] ?? 0;
            $existing = $previous[$segment['id']] ?? [];
            // Preserve the exact stored reference if its native activity has since been removed.
            if ($segment['type'] === 'course_module' && $cmid
                    && (($existing['type'] ?? '') !== 'course_module' || (int)($existing['props']['cmid'] ?? 0) !== $cmid)) {
                \local_frontpage\local\course_module_reference::get(
                    page_access::context($page),
                    $cmid,
                    $page
                );
            }
        }
        return $json;
    }
    /**
     * Save a validated draft after checking its expected revision state.
     *
     * @param int $pageid Page ID.
     * @param string $json Encoded JSON document.
     * @param int $userid User ID.
     * @param int $expectedrevision Expected revision for the concurrency check.
     * @param int $expectedversion Version expected by the editor.
     * @param callable|null $files Submitted file data.
     * @return \stdClass
     */
    public function save_draft(
        int $pageid,
        string $json,
        int $userid,
        int $expectedrevision,
        int $expectedversion,
        ?callable $files = null
    ): \stdClass {
        $initial = factory::page_repository()->get_page($pageid);
        page_access::require_manage($initial);
        return $this->locked(
            page_scope::course_id($initial, page_access::context($initial)),
            function () use ($pageid, $json, $userid, $expectedrevision, $expectedversion, $files) {
                global $DB;
                $tx = $DB->start_delegated_transaction();
                $page = factory::page_repository()->get_page($pageid);
                page_access::require_manage($page);
                $old = $this->expected($page, $expectedrevision, $expectedversion);
                $json = $this->validate($page, $json, $old->documentjson);
                $oldcss = json_decode($old->documentjson, true)['css'] ?? '';
                $newcss = json_decode($json, true)['css'] ?? '';
                if ($oldcss !== $newcss) {
                    page_access::require_edit_css($page);
                }
                $new = $this->new_revision($page, $json, $userid, $old);
                if ($files) {
                    $files($new);
                }
                $DB->set_field('local_frontpage_revision', 'ischeckpoint', 1, ['id' => $old->id]);
                $DB->update_record('local_frontpage_page', (object)['id' => $pageid, 'draftrevisionid' => $new->id,
                    'modifiedby' => $userid, 'timemodified' => time()]);
                course_resource_manager::refresh_orphan_state(
                    page_scope::course_id($page, page_access::context($page))
                );
                $tx->allow_commit();
                return $new;
            }
        );
    }
    /**
     * Check that the requested revision and version still match the page state.
     *
     * @param \stdClass $page Page record or native page context.
     * @param int $revisionid Revision ID.
     * @param int $version Stored version number.
     * @return \stdClass
     */
    private function expected(\stdClass $page, int $revisionid, int $version): \stdClass {
        $draft = factory::page_repository()->get_revision($page->id, (int)$page->draftrevisionid);
        if (
            $draft->id != $revisionid || $draft->draftversion != $version || $draft->timepublished
            || $draft->ischeckpoint
        ) {
            throw new \moodle_exception('draftconflict', 'local_frontpage');
        }
        return $draft;
    }
    /**
     * Publish the validated page revision and start a new draft.
     *
     * @param int $pageid Page ID.
     * @param int $userid User ID.
     * @param int $expectedrevision Expected revision for the concurrency check.
     * @param int $expectedversion Version expected by the editor.
     * @return \stdClass
     */
    public function publish(int $pageid, int $userid, int $expectedrevision, int $expectedversion): \stdClass {
        $page = factory::page_repository()->get_page($pageid);
        page_access::require_publish($page);
        return $this->locked(
            page_scope::course_id($page, page_access::context($page)),
            function () use ($pageid, $userid, $expectedrevision, $expectedversion) {
                global $DB;
                $tx = $DB->start_delegated_transaction();
                $page = factory::page_repository()->get_page($pageid);
                page_access::require_publish($page);
                if (
                    $page->ownercomponent === 'local_frontpage' && !in_array(
                        (int)$page->id,
                        [
                        (int)get_config('local_frontpage', 'guestfrontpageid'),
                        (int)get_config('local_frontpage', 'loggedinfrontpageid'),
                        ],
                        true
                    )
                ) {
                    throw new \moodle_exception('publishassignmentrequired', 'local_frontpage');
                }
                $draft = $this->expected($page, $expectedrevision, $expectedversion);
                $this->validate($page, $draft->documentjson, $draft->documentjson);
                $this->validate_files($page, $draft);
                $DB->update_record(
                    'local_frontpage_revision',
                    (object)[ 'id' => $draft->id, 'publishedby' => $userid, 'timepublished' => time() ]
                );
                $new = $this->new_revision($page, $draft->documentjson, $userid, $draft);
                $DB->update_record('local_frontpage_page', (object)['id' => $pageid, 'draftrevisionid' => $new->id,
                    'publishedrevisionid' => $draft->id, 'status' => 'published', 'visibility' => 'public',
                    'modifiedby' => $userid, 'timemodified' => time()]);
                $page = factory::page_repository()->get_page($pageid);
                $tx->allow_commit();
                if ($page->ownercomponent === 'mod_frontpage') {
                    global $CFG;
                    require_once($CFG->dirroot . '/mod/frontpage/lib.php');
                    frontpage_page_published($page);
                }
                return $page;
            }
        );
    }
    /**
     * Restore a historical page revision into an editable draft.
     *
     * @param int $pageid Page ID.
     * @param int $revisionid Revision ID.
     * @param int $userid User ID.
     * @param int $expectedrevision Expected revision for the concurrency check.
     * @param int $version Stored version number.
     * @return \stdClass
     */
    public function restore(int $pageid, int $revisionid, int $userid, int $expectedrevision, int $version): \stdClass {
        $page = factory::page_repository()->get_page($pageid);
        page_access::require_manage($page);
        $source = factory::page_repository()->get_revision($pageid, $revisionid);
        return $this->save_draft(
            $pageid,
            $source->documentjson,
            $userid,
            $expectedrevision,
            $version,
            fn($new)=>revision_files::copy_revision($source->id, $new->id)
        );
    }
    /**
     * Delete the selected site-home page and its owned data.
     *
     * @param int $pageid Page ID.
     * @return void
     */
    public function delete_page(int $pageid): void {
        $page = factory::page_repository()->get_page($pageid);
        page_access::require_manage($page);
        if ($page->ownercomponent !== 'local_frontpage') {
            throw new \invalid_parameter_exception('Delete the native activity instead.');
        }
        foreach (['guestfrontpageid', 'loggedinfrontpageid'] as $key) {
            if ((int)get_config('local_frontpage', $key) === $pageid) {
                throw new \moodle_exception('assignedpage', 'local_frontpage');
            }
        }
        $this->remove($page);
    }
    /**
     * Delete the page owned by the removed native activity.
     *
     * @param int $pageid Page ID.
     * @param string $owner Owning component.
     * @param int $instanceid Native instance ID.
     * @return void
     */
    public function delete_activity_page(int $pageid, string $owner, int $instanceid): void {
        $page = factory::page_repository()->get_page($pageid);
        if (
            $owner !== 'mod_frontpage' || $page->ownercomponent !== $owner
            || (int)$page->ownerinstanceid !== $instanceid
        ) {
            throw new \invalid_parameter_exception('Invalid page owner.');
        }
        // Called by Moodle's native module deletion after its own permission checks.
        $this->remove($page);
    }
    /**
     * Remove the requested item from the current selection.
     *
     * @param \stdClass $page Page record or native page context.
     * @return void
     */
    private function remove(\stdClass $page): void {
        $courseid = page_scope::course_id($page, page_access::context($page));
        $this->locked($courseid, function () use ($page, $courseid) {
            global $DB;
            $tx = $DB->start_delegated_transaction();
            if ($page->ownercomponent === 'local_frontpage') {
                foreach (['guestfrontpageid', 'loggedinfrontpageid'] as $key) {
                    if ((int)get_config('local_frontpage', $key) === (int)$page->id) {
                        throw new \moodle_exception('assignedpage', 'local_frontpage');
                    }
                }
            }
            foreach ($DB->get_records('local_frontpage_revision', ['pageid' => $page->id]) as $rev) {
                revision_files::delete_revision($rev->id);
            }
            $DB->delete_records('local_frontpage_cmref', ['pageid' => $page->id]);
            $DB->delete_records('local_frontpage_revision', ['pageid' => $page->id]);
            $DB->delete_records('local_frontpage_page', ['id' => $page->id]);
            course_resource_manager::refresh_orphan_state($courseid);
            $tx->allow_commit();
        });
    }

    /**
     * Remove an unprotected historical revision after checking the page state.
     *
     * @param int $pageid Page ID.
     * @param int $revisionid Revision ID.
     * @param int $expectedrevision Expected revision for the concurrency check.
     * @param int $version Stored version number.
     * @return void
     */
    public function prune(int $pageid, int $revisionid, int $expectedrevision, int $version): void {
        global $DB;
        $page = factory::page_repository()->get_page($pageid);
        page_access::require_manage($page);
        $courseid = page_scope::course_id($page, page_access::context($page));
        $this->locked($courseid, function () use ($pageid, $revisionid, $expectedrevision, $version, $courseid) {
            global $DB;
            $tx = $DB->start_delegated_transaction();
            $page = factory::page_repository()->get_page($pageid);
            page_access::require_manage($page);
            $this->expected($page, $expectedrevision, $version);
            if (in_array($revisionid, [(int)$page->draftrevisionid, (int)$page->publishedrevisionid], true)) {
                throw new \invalid_parameter_exception('The current draft and publication are protected.');
            }
            factory::page_repository()->get_revision($pageid, $revisionid);
            revision_files::delete_revision($revisionid);
            $DB->delete_records('local_frontpage_cmref', ['revisionid' => $revisionid]);
            $DB->delete_records('local_frontpage_revision', ['id' => $revisionid, 'pageid' => $pageid]);
            course_resource_manager::refresh_orphan_state($courseid);
            $tx->allow_commit();
        });
    }

    /**
     * Validate the revision-owned files referenced by the document.
     *
     * @param \stdClass $page Page record or native page context.
     * @param \stdClass $revision Stored revision.
     * @return void
     */
    private function validate_files(\stdClass $page, \stdClass $revision): void {
        $doc = json_decode($revision->documentjson, true);
        foreach ($doc['segments'] as $segment) {
            $props = $segment['props'];
            if ($segment['type'] === 'media' && ($props['asset'] ?? '') === \local_frontpage\local\media_files::ASSET) {
                $file = revision_files::get_hero_background($revision->id, $segment['id']);
                if (!$file || !\local_frontpage\local\media_files::is_media($file)) {
                    throw new \moodle_exception('missingimage', 'local_frontpage');
                }
            }
            foreach (['asset', 'mark', 'content'] as $field) {
                preg_match_all(
                    '/\{\{image-url:([a-zA-Z0-9][a-zA-Z0-9._ -]{0,199})\}\}/',
                    (string)($props[$field] ?? ''),
                    $matches
                );
                foreach ($matches[1] as $name) {
                    $file = get_file_storage()->get_file(
                        $page->contextid,
                        'local_frontpage',
                        'pageassets',
                        $revision->id,
                        '/',
                        $name
                    );
                    if (!$file || !\local_frontpage\local\media_files::is_media($file)) {
                        throw new \moodle_exception('missingimage', 'local_frontpage');
                    }
                }
            }
        }
    }
}

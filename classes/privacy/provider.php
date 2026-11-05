<?php
/**
 * Native provider support for Frontpage.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\privacy;

use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;

/**
 * Discover, export and erase the personal associations stored by Frontpage.
 */
final class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the personal data stored by this component.
     *
     * @param \core_privacy\local\metadata\collection $collection Privacy metadata collection.
     * @return \core_privacy\local\metadata\collection
     */
    public static function get_metadata(
        \core_privacy\local\metadata\collection $collection
    ): \core_privacy\local\metadata\collection {
        $collection->add_database_table(
            'local_frontpage_page',
            [ 'createdby' => 'privacy:author', 'modifiedby' => 'privacy:author' ],
            'privacy:pages'
        );
        $collection->add_database_table(
            'local_frontpage_revision',
            [
            'createdby' => 'privacy:author',
            'publishedby' => 'privacy:author',
            'documentjson' => 'privacy:content',
            ],
            'privacy:revisions'
        );
        $collection->add_database_table(
            'local_frontpage_resource',
            [ 'createdby' => 'privacy:author' ],
            'privacy:resources'
        );
        $collection->add_subsystem_link('core_files', [], 'privacy:files');
        return $collection;
    }
    /**
     * Find contexts containing records attributed to the user.
     *
     * @param int $userid User ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $list->add_from_sql(
            'SELECT DISTINCT p.contextid FROM {local_frontpage_page} p
            LEFT JOIN {local_frontpage_revision} r ON r.pageid=p.id
            WHERE p.createdby=:u1 OR p.modifiedby=:u2 OR r.createdby=:u3 OR r.publishedby=:u4',
            ['u1' => $userid, 'u2' => $userid, 'u3' => $userid, 'u4' => $userid]
        );
        $list->add_from_sql(
            'SELECT DISTINCT c.id FROM {context} c JOIN {local_frontpage_resource} r
            ON c.contextlevel=:level AND c.instanceid=r.courseid WHERE r.createdby=:userid',
            ['level' => CONTEXT_COURSE, 'userid' => $userid]
        );
        $list->add_from_sql(
            'SELECT DISTINCT contextid FROM {files} WHERE component=:component AND userid=:userid',
            ['component' => 'local_frontpage', 'userid' => $userid]
        );
        return $list;
    }
    /**
     * Export the records and files allowed by the approved user context list.
     *
     * @param approved_contextlist $contextlist Approved user and context selection.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $pages = $DB->get_records('local_frontpage_page', ['contextid' => $context->id]);
            $data = [];
            foreach ($pages as $p) {
                $revs = $DB->get_records_select(
                    'local_frontpage_revision',
                    'pageid=:pageid AND (createdby=:u1 OR publishedby=:u2)',
                    ['pageid' => $p->id, 'u1' => $userid, 'u2' => $userid]
                );
                if ($p->createdby == $userid || $p->modifiedby == $userid || $revs) {
                    $data[] = (object)['title' => $p->title, 'revisions' => array_values($revs)];
                }
            }
            $resources = $context->contextlevel === CONTEXT_COURSE
                ? $DB->get_records(
                    'local_frontpage_resource',
                    [ 'courseid' => $context->instanceid, 'createdby' => $userid ]
                ) : [ ];
            $ownedfiles = $DB->get_records(
                'files',
                ['contextid' => $context->id, 'component' => 'local_frontpage', 'userid' => $userid],
                'id',
                'id,filearea,itemid,filepath,filename,mimetype,filesize,author,license'
            );
            foreach ($ownedfiles as $metadata) {
                $file = get_file_storage()->get_file_by_id($metadata->id);
                if ($file && !$file->is_directory()) {
                    \core_privacy\local\request\writer::with_context($context)->export_file(
                        [ 'files', (string)$metadata->id ],
                        $file
                    );
                }
            }
            if ($data || $resources || $ownedfiles) {
                \core_privacy\local\request\writer::with_context($context)->export_data(
                    [ ],
                    (object)[
                    'pages' => $data,
                    'resources' => array_values($resources),
                    'files' => array_values($ownedfiles),
                    ]
                );
            }
        }
    }
    /**
     * Process erasure for all users in the requested context.
     *
     * @param \context $context Native Moodle context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        self::anonymise($context, null);
    }
    /**
     * Process the erasure request for the approved user and contexts.
     *
     * @param approved_contextlist $contextlist Approved user and context selection.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        foreach ($contextlist->get_contexts() as $context) {
            self::anonymise($context, (int)$contextlist->get_user()->id);
        }
    }
    /**
     * Add the users represented in the requested context to the privacy list.
     *
     * @param \core_privacy\local\request\userlist $userlist Users represented in the requested context.
     * @return void
     */
    public static function get_users_in_context(\core_privacy\local\request\userlist $userlist): void {
        $context = $userlist->get_context();
        foreach (['createdby', 'modifiedby'] as $field) {
            $userlist->add_from_sql(
                $field,
                "SELECT $field FROM {local_frontpage_page} WHERE contextid=:context",
                ['context' => $context->id]
            );
        }
        foreach (['createdby', 'publishedby'] as $field) {
            $userlist->add_from_sql(
                $field,
                "SELECT r.$field FROM {local_frontpage_revision} r "
                    . "JOIN {local_frontpage_page} p ON p.id=r.pageid WHERE p.contextid=:context",
                ['context' => $context->id]
            );
        }
        if ($context->contextlevel === CONTEXT_COURSE) {
            $userlist->add_from_sql(
                'createdby',
                'SELECT createdby FROM {local_frontpage_resource} WHERE courseid=:course',
                ['course' => $context->instanceid]
            );
        }
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {files} WHERE component=:component AND contextid=:context',
            ['component' => 'local_frontpage', 'context' => $context->id]
        );
    }
    /**
     * Process erasure for the approved users in the requested context.
     *
     * @param \core_privacy\local\request\approved_userlist $userlist Users represented in the requested context.
     * @return void
     */
    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $userlist): void {
        foreach ($userlist->get_userids() as $userid) {
            self::anonymise($userlist->get_context(), (int)$userid);
        }
    }
    /**
     * Clear the selected personal associations while retaining shared records.
     *
     * @param \context $context Native Moodle context.
     * @param int|null $userid User ID.
     * @return void
     */
    private static function anonymise(\context $context, ?int $userid): void {
        global $DB;
        foreach ($DB->get_records('local_frontpage_page', ['contextid' => $context->id]) as $page) {
            foreach (['createdby', 'modifiedby'] as $field) {
                if ($userid === null || $page->$field == $userid) {
                    $DB->set_field('local_frontpage_page', $field, 0, ['id' => $page->id]);
                }
            }
            foreach (['createdby', 'publishedby'] as $field) {
                $where = ['pageid' => $page->id];
                if ($userid !== null) {
                    $where[$field] = $userid;
                }
                $DB->set_field('local_frontpage_revision', $field, 0, $where);
            }
        }
        if ($context->contextlevel === CONTEXT_COURSE) {
            $where = ['courseid' => $context->instanceid];
            if ($userid !== null) {
                $where['createdby'] = $userid;
            }
            $DB->set_field('local_frontpage_resource', 'createdby', 0, $where);
        }
        $where = ['contextid' => $context->id, 'component' => 'local_frontpage'];
        if ($userid !== null) {
            $where['userid'] = $userid;
        }
        $DB->set_field('files', 'author', null, $where);
        $DB->set_field('files', 'source', null, $where);
        $DB->set_field('files', 'userid', 0, $where);
    }
}

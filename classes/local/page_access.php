<?php
/**
 * Resolve page ownership and enforce viewing, editing and publication rights.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

/**
 * Resolve page ownership and enforce viewing, editing and publication rights.
 */
final class page_access {
    /**
     * Resolve and validate the native context of the page owner.
     *
     * @param \stdClass $page Page record or native page context.
     * @return \context
     */
    public static function context(\stdClass $page): \context {
        $context = \context::instance_by_id($page->contextid);
        if (
            $page->ownercomponent === 'local_frontpage' && $context->contextlevel === CONTEXT_SYSTEM
                && $page->bindingtype === 'system'
        ) {
            return $context;
        }
        if ($page->ownercomponent === 'mod_frontpage' && $context->contextlevel === CONTEXT_MODULE) {
            $cm = get_coursemodule_from_id('frontpage', $context->instanceid, 0, false, MUST_EXIST);
            if ((int)$cm->instance === (int)$page->ownerinstanceid) {
                return $context;
            }
        }
        throw new \invalid_parameter_exception('Invalid page owner.');
    }
    /**
     * Check whether the current user can manage the selected page.
     *
     * @param \stdClass $page Page record or native page context.
     * @return bool
     */
    public static function can_manage(\stdClass $page): bool {
        $context = self::context($page);
        return has_capability($context->contextlevel === CONTEXT_MODULE
            ? 'mod/frontpage:managecontent' : 'local/frontpage:managepages', $context);
    }
    /**
     * Require native login and the page owner's editing capability.
     *
     * @param \stdClass $page Page record or native page context.
     * @return \context
     */
    public static function require_manage(\stdClass $page): \context {
        $context = self::context($page);
        if ($context->contextlevel === CONTEXT_MODULE) {
            $cm = get_coursemodule_from_id('frontpage', $context->instanceid, 0, false, MUST_EXIST);
            require_login(get_course($cm->course), false, $cm);
        } else {
            require_login();
        }
        require_capability($context->contextlevel === CONTEXT_MODULE
            ? 'mod/frontpage:managecontent' : 'local/frontpage:managepages', $context);
        return $context;
    }
    /**
     * Check the page editing right and its separate CSS capability.
     *
     * @param \stdClass $page Page record or native page context.
     * @return bool
     */
    public static function can_edit_css(\stdClass $page): bool {
        $context = self::context($page);
        return self::can_manage($page) && has_capability($context->contextlevel === CONTEXT_MODULE
            ? 'mod/frontpage:editcss' : 'local/frontpage:editcss', $context);
    }
    /**
     * Require the page editing right and its separate CSS capability.
     *
     * @param \stdClass $page Page record or native page context.
     * @return void
     */
    public static function require_edit_css(\stdClass $page): void {
        $context = self::require_manage($page);
        require_capability($context->contextlevel === CONTEXT_MODULE
            ? 'mod/frontpage:editcss' : 'local/frontpage:editcss', $context);
    }
    /**
     * Require the publication capability in the page owner's context.
     *
     * @param \stdClass $page Page record or native page context.
     * @return void
     */
    public static function require_publish(\stdClass $page): void {
        $context = self::require_manage($page);
        require_capability($context->contextlevel === CONTEXT_MODULE
            ? 'mod/frontpage:publishcontent' : 'local/frontpage:publishpages', $context);
    }
    /**
     * Check publication, native visibility and the assigned audience.
     *
     * @param \stdClass $page Page record or native page context.
     * @param bool $override Override.
     * @return bool
     */
    public static function can_view_published(\stdClass $page, bool $override = false): bool {
        if (empty($page->enabled) || empty($page->publishedrevisionid)) {
            return false;
        }
        $context = self::context($page);
        if ($context->contextlevel === CONTEXT_MODULE) {
            $cm = get_fast_modinfo(get_coursemodule_from_id('frontpage', $context->instanceid)->course)
                ->get_cm($context->instanceid);
            return $cm->uservisible && has_capability('mod/frontpage:view', $context);
        }
        $key = isloggedin() && !isguestuser() ? 'loggedinfrontpageid' : 'guestfrontpageid';
        return (int)get_config('local_frontpage', $key) === (int)$page->id;
    }
    /**
     * Require access to the published page or permission to preview its draft.
     *
     * @param \stdClass $page Page record or native page context.
     * @param bool $preview Whether draft preview is requested.
     * @return \context
     */
    public static function require_view(\stdClass $page, bool $preview = false): \context {
        if ($preview) {
            return self::require_manage($page);
        }
        $context = self::context($page);
        if ($context->contextlevel === CONTEXT_MODULE) {
            $cm = get_coursemodule_from_id('frontpage', $context->instanceid, 0, false, MUST_EXIST);
            require_login(get_course($cm->course), false, $cm);
        } else if (!empty(get_config('moodle', 'forcelogin'))) {
            require_login();
        }
        if (!self::can_view_published($page)) {
            throw new \required_capability_exception($context, 'local/frontpage:managepages', 'nopermissions', '');
        }
        return $context;
    }
}

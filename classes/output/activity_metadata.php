<?php
/**
 * Prepare the presentation of native Moodle activity metadata.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\output;

/** Applies to Frontpage activity views and settings in the same module context. */
final class activity_metadata {
    /**
     * Register the shared presentation before rendering the Moodle header.
     *
     * @param \core\hook\output\before_http_headers $hook Native Moodle output hook.
     */
    public static function prepare(\core\hook\output\before_http_headers $hook): void {
        global $PAGE, $SCRIPT;
        if (during_initial_install() || $PAGE->context->contextlevel !== CONTEXT_MODULE || $PAGE->cm?->modname !== 'frontpage') {
            return;
        }
        $PAGE->requires->js_call_amd('local_frontpage/activity_metadata', 'init', [[
            'authoring' => $SCRIPT !== '/mod/frontpage/view.php' || optional_param('edit', 0, PARAM_BOOL),
            'label' => get_string('activitymetadatacompletion', 'local_frontpage'),
            'single' => get_string('activitymetadatacompletionsingle', 'local_frontpage', '{count}'),
            'multiple' => get_string('activitymetadatacompletionmultiple', 'local_frontpage', '{count}'),
            'note' => get_string('activitymetadatacompletionnote', 'local_frontpage'),
        ]]);
    }
}

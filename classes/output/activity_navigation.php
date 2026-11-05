<?php
/**
 * Adapt the FP sequence to native 5.3 navigation output, with an older-version fallback.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>.
// Use and modification are permitted only under the Software License in LICENSE.md.
namespace local_frontpage\output;

/**
 * Adapt the FP sequence to native 5.3 navigation output, with an older-version fallback.
 */
final class activity_navigation {
    /**
     * Store the dependencies and context used by this helper.
     *
     * @param \cm_info $cm Native course-module record.
     * @param array $context Native Moodle context.
     */
    public function __construct(
        /** @var \cm_info cm. */ private \cm_info $cm,
        /** @var array context. */ private array $context
    ) {
    }

    /**
     * Render the activity navigation for the current published page.
     *
     * @param \renderer_base $output Native renderer used for output.
     * @return string
     */
    public function render(\renderer_base $output): string {
        $native = class_exists(\core_courseformat\output\local\linearnavigation\footer_content::class);
        $data = [
            'previousurl' => $this->context['previousurl']?->out(false),
            'nexturl' => $this->context['nexturl']?->out(false),
            // When Core keeps completion in the activity header (e.g. stealth activities), do not duplicate it.
            'completion' => $native && \core_courseformat\local\linearnavigationsettings::show_navigation_footer(
                $output->get_page()
            ) ? $this->completion($output) : '',
        ];
        $content = $output->render_from_template($native
            ? 'core_courseformat/local/linearnavigation/footer_content'
            : 'local_frontpage/activity_navigation_legacy', $data);
        // Native Core templates intentionally retain their own Previous/Next labels.
        // Explicit sequence exits are supplementary native actions with distinct labels.
        $boundary = static function (?array $action, string $direction) use ($output): string {
            return $action ? $output->render(new \core\output\action_link(
                $action['url'],
                $action['label'],
                null,
                ['class' => 'course-linear-navigation-link local-frontpage-activity-navigation-boundary '
                    . 'local-frontpage-activity-navigation-boundary-' . $direction
                . ' text-decoration-none d-inline-flex align-items-center flex-shrink-0']
            )) : '';
        };
        $content = $boundary($this->context['previousboundary'] ?? null, 'previous')
            . $content . $boundary($this->context['nextboundary'] ?? null, 'next');
        $returnlink = new \core\output\action_link(
            $this->context['returnurl'],
            get_string('activitynavigationreturn', 'local_frontpage', $this->context['origin']->get_formatted_name()),
            null,
            ['class' => ('btn btn-secondary course-linear-navigation-link '
                . 'local-frontpage-activity-navigation-return flex-shrink-0 ml-3 ms-3')]
        );
        $links = $output->render($returnlink);
        $page = $output->get_page();
        if (method_exists($page, 'get_supplementary_content') && $extra = $page->get_supplementary_content()) {
            $links .= $output->render($extra);
            // We have consumed this native action. The later Core hook must not create a second sticky footer.
            $page->set_supplementary_content(null);
        }
        return \html_writer::div(
            $content . $links,
            'd-flex w-100 align-items-center local-frontpage-activity-navigation-row'
        );
    }

    /**
     * Reuse Moodle's native completion models, templates and event handlers.
     *
     * @param \renderer_base $output Native renderer used for output.
     * @return string
     */
    private function completion(\renderer_base $output): string {
        global $USER;
        $details = \core_completion\cm_completion_details::get_instance($this->cm, (int) $USER->id);
        $data = (array) (new \core_course\output\activity_completion($this->cm, $details, smallbutton: true))
            ->export_for_template($output);
        if (empty($data['uservisible'])) {
            return '';
        }
        if (!empty($data['showmanualcompletion'])) {
            return $output->render_from_template('core_course/completion_manual', $data);
        }
        if (
            !empty($data['hascompletion']) && !empty($data['isautomatic'])
                && !empty($data['istrackeduser']) && !empty($data['overallcomplete'])
        ) {
            return $output->render_from_template('core_course/completion_status', $data);
        }
        return '';
    }
}

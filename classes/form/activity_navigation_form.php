<?php
/**
 * Navigation belongs to the page revision, not the native activity metadata.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\form;

defined('MOODLE_INTERNAL') || die();

/**
 * Navigation belongs to the page revision, not the native activity metadata.
 */
final class activity_navigation_form extends \moodleform {
    /**
     * Define the native form fields and their defaults.
     *
     * @return void
     */
    protected function definition(): void {
        $form = $this->_form;
        foreach (['pageid', 'baserevisionid', 'baseversion'] as $key) {
            $form->addElement('hidden', $key);
            $form->setType($key, PARAM_INT);
        }
        $form->addElement('header', 'activitynavigationheader', get_string('activitynavigation', 'local_frontpage'));
        $modes = [];
        if (\local_frontpage\local\activity_navigation_settings::supports_inheritance($this->_customdata['course'])) {
            $modes['inherit'] = get_string('activitynavigationinherit', 'local_frontpage');
        }
        $modes['on'] = get_string('yes');
        $modes['off'] = get_string('no');
        $form->addElement(
            'select',
            'activitynavigationmode',
            get_string('activitynavigationmode', 'local_frontpage'),
            $modes
        );
        $form->setType('activitynavigationmode', PARAM_ALPHA);
        $form->addHelpButton('activitynavigationmode', 'activitynavigationmode', 'local_frontpage');
        $form->addElement(
            'select',
            'activitynavigationboundary',
            get_string('activitynavigationboundary', 'local_frontpage'),
            [

            'frontpage' => get_string('activitynavigationboundaryfrontpage', 'local_frontpage'),
            'course' => get_string('activitynavigationboundarycourse', 'local_frontpage'),

            ]
        );
        $form->setType('activitynavigationboundary', PARAM_ALPHA);
        $form->hideIf('activitynavigationboundary', 'activitynavigationmode', 'eq', 'off');
        $form->addHelpButton('activitynavigationboundary', 'activitynavigationboundary', 'local_frontpage');
        \local_frontpage\output\context_help::field(
            $form,
            'activitynavigationmode',
            'activitynavigation',
            'activitynavigation'
        );
        \local_frontpage\output\context_help::field(
            $form,
            'activitynavigationboundary',
            'activitynavigation',
            'activitynavigation'
        );
        $this->add_action_buttons(true, get_string('savedraft', 'local_frontpage'));
    }
}

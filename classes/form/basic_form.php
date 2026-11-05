<?php
/**
 * Edit the basic properties of a Frontpage page.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\form;

/**
 * Edit the basic properties of a Frontpage page.
 */
final class basic_form extends \moodleform {
    /**
     * Define the native form fields and their defaults.
     *
     * @return void
     */
    protected function definition(): void {
        $f = $this->_form;
        $kind = $this->_customdata['kind'];
        if ($kind === 'title' || $kind === 'pageoptions') {
            $f->addElement('text', 'title', get_string('title', 'local_frontpage'));
            $f->setType('title', PARAM_TEXT);
            $f->addRule('title', null, 'required', null, 'client');
            if ($kind === 'pageoptions') {
                $options = [];
                foreach (['theme', 'content', 'wide', 'full', 'custom'] as $v) {
                    $options[$v] = get_string('option_' . $v, 'local_frontpage');
                }
                $f->addElement('select', 'contentwidth', get_string('pagecontentwidth', 'local_frontpage'), $options);
                $f->setType('contentwidth', PARAM_ALPHA);
                $f->addElement('text', 'contentwidthvalue', get_string('pagecontentwidthvalue', 'local_frontpage'));
                $f->setType('contentwidthvalue', PARAM_RAW_TRIMMED);
                $f->addHelpButton('contentwidthvalue', 'lengthvalue', 'local_frontpage');
                $f->hideIf('contentwidthvalue', 'contentwidth', 'neq', 'custom');
                $f->addElement('hidden', 'metadatahash');
                $f->setType('metadatahash', PARAM_ALPHANUM);
            }
        } else if ($kind === 'settings') {
            $f->addElement('advcheckbox', 'sitehomeenabled', get_string('sitehomeenabled', 'local_frontpage'));
            $f->setType('sitehomeenabled', PARAM_BOOL);
            \local_frontpage\output\context_help::field($f, 'sitehomeenabled', 'assignments', 'pluginsettings');
            $choices = [0 => get_string('nativesitehome', 'local_frontpage')];
            foreach (\local_frontpage\local\service\factory::page_repository()->get_pages() as $p) {
                $choices[$p->id] = format_string($p->title);
            }
            foreach (['guestfrontpageid', 'loggedinfrontpageid'] as $key) {
                $f->addElement('select', $key, get_string($key, 'local_frontpage'), $choices);
                $f->setType($key, PARAM_INT);
                $f->addHelpButton($key, $key, 'local_frontpage');
            }
        } else if ($kind === 'files') {
            $f->addElement(
                'filemanager',
                'assets',
                get_string('pagefiles', 'local_frontpage'),
                null,
                ['subdirs' => 0, 'maxfiles' => -1, 'accepted_types' => '*']
            );
            $f->addHelpButton('assets', 'pagefiles', 'local_frontpage');
            foreach (['id', 'baserevisionid', 'baseversion'] as $key) {
                $f->addElement('hidden', $key);
                $f->setType($key, PARAM_INT);
            }
        }
        $this->add_action_buttons();
    }
}

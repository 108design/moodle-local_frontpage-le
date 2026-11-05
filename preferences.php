<?php
/**
 * Native preferences entry point for Frontpage.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
use local_frontpage\local\screen;
require_login();
require_capability('local/frontpage:managepages', context_system::instance());
$url = new moodle_url('/local/frontpage/preferences.php');
screen::start($url, get_string('settings'), 'pluginsettings');
$form = new \local_frontpage\form\basic_form($url, ['kind' => 'settings']);
if ($data = $form->get_data()) {
    \local_frontpage\local\service\factory::page_service()->home_settings(
        (int)$data->guestfrontpageid,
        (int)$data->loggedinfrontpageid,
        (bool)$data->sitehomeenabled
    );
    redirect($url);
}
$form->set_data(get_config('local_frontpage'));
echo $OUTPUT->header();
echo screen::tabs('settings');
$form->display();
echo $OUTPUT->footer();

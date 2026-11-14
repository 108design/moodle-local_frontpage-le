<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Upgrade callbacks for Frontpage.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

defined('MOODLE_INTERNAL') || die();
/**
 * Check that this package can update the installed plugin.
 *
 * @param int $oldversion Previously installed numeric plugin build.
 * @return bool
 */
function xmldb_local_frontpage_upgrade(int $oldversion): bool {
    $supportedbuild = $oldversion >= 2026010100 && $oldversion <= 2026093099;
    $supportedsource = get_config('local_frontpage', 'distributionorigin') === 'marketplace';
    if (!$supportedbuild || !$supportedsource) {
        throw new moodle_exception('unsupportedupgrade', 'local_frontpage');
    }
    return true;
}

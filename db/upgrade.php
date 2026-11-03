<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
defined('MOODLE_INTERNAL') || die();
function xmldb_local_frontpage_upgrade(int $oldversion): bool {
    if ($oldversion < 2026010100 || $oldversion > 2026093099
            || get_config('local_frontpage', 'distributionorigin') !== 'marketplace') {
        throw new upgrade_exception('local_frontpage', $oldversion, 'Unsupported installation origin or build. Restore the matching installed source before retrying.');
    }
    return true;
}

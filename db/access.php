<?php
/**
 * Capability definitions for Frontpage.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

defined('MOODLE_INTERNAL') || die();
$capabilities = [
    'local/frontpage:editcss' => [
        'riskbitmask' => RISK_XSS,
         'captype' => 'write',
         'contextlevel' => CONTEXT_SYSTEM,
         'archetypes' => [ ],
    ],

    'local/frontpage:managepages' => ['riskbitmask' => RISK_XSS, 'captype' => 'write', 'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['manager' => CAP_ALLOW]],
    'local/frontpage:publishpages' => [
        'riskbitmask' => RISK_XSS,
         'captype' => 'write',
         'contextlevel' => CONTEXT_SYSTEM,

        'archetypes' => ['manager' => CAP_ALLOW],
    ],
];

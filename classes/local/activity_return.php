<?php
/**
 * Keep native activity-creation return requests separate for each open form.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

/**
 * Keep native activity-creation return requests separate for each open form.
 */
final class activity_return {
    /**
     * Lifetime.
     */
    private const LIFETIME = 7200;
    /**
     * Create a separate return request for a native activity-creation form.
     *
     * @param array $data Submitted or stored data.
     * @return string
     */
    public static function begin(array $data): string {
        global $SESSION, $USER;
        $token = bin2hex(random_bytes(24));
        $data['token'] = $token;
        $data['userid'] = (int)$USER->id;
        $data['expires'] = time() + self::LIFETIME;
        $data['createdcmid'] = 0;
        $data['createdcmids'] = [];
        $SESSION->local_frontpage_activity_returns[$token] = $data;
        return $token;
    }
    /**
     * Return the pending activity-creation request for its token.
     *
     * @param string|null $token Token.
     * @return array|null
     */
    public static function pending(?string $token = null): ?array {
        global $SESSION, $USER, $SCRIPT;
        foreach ($SESSION->local_frontpage_activity_returns ?? [] as $key => $data) {
            if (($data['expires'] ?? 0) < time() || ($data['userid'] ?? 0) !== (int)$USER->id) {
                unset($SESSION->local_frontpage_activity_returns[$key]);
            }
        }
        $token = $token ?? optional_param('frontpagereturntoken', '', PARAM_ALPHANUM);
        if ($token !== '') {
            return $SESSION->local_frontpage_activity_returns[$token] ?? null;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $cmid = optional_param('id', 0, PARAM_INT);
            foreach ($SESSION->local_frontpage_activity_returns ?? [] as $data) {
                if (
                    $SCRIPT === '/mod/' . $data['modname'] . '/view.php'
                    && in_array($cmid, $data['createdcmids'] ?? [ ], true)
                ) {
                    $data['createdcmid'] = $cmid;
                    return $data;
                }
            }
        }
        return null;
    }
    /**
     * Associate the created native module with its pending return request.
     *
     * @param int $cmid Course-module ID.
     * @param int $courseid Native course ID.
     * @param int $sectionid Native course section ID.
     * @param int $userid User ID.
     * @return bool
     */
    public static function record_created(int $cmid, int $courseid, int $sectionid, int $userid): bool {
        global $SESSION;
        $data = self::pending();
        if (
            !$data || $data['mode'] !== 'create' || (int)$data['courseid'] !== $courseid
                || (int)$data['sectionid'] !== $sectionid || (int)$data['userid'] !== $userid
        ) {
            return false;
        }
        $data['createdcmid'] = $cmid;
        $data['createdcmids'][] = $cmid;
        $SESSION->local_frontpage_activity_returns[$data['token']] = $data;
        return true;
    }
    /**
     * Consume the stored request and return its validated destination.
     *
     * @param string $token Token.
     * @return array|null
     */
    public static function consume(string $token): ?array {
        global $SESSION;
        $data = self::pending($token);
        if ($data) {
            unset($SESSION->local_frontpage_activity_returns[$token]);
        }
        return $data;
    }
    /**
     * Build the return URL for the activity-creation request.
     *
     * @param array $data Submitted or stored data.
     * @return \moodle_url
     */
    public static function landing_url(array $data): \moodle_url {
        return new \moodle_url(
            '/local/frontpage/activity_return.php',
            [ 'token' => $data['token'], 'cmid' => $data['createdcmid'] ?? 0 ]
        );
    }
    /**
     * Check that the native return matches the expected module path and course.
     *
     * @param string $path Local route or export path.
     * @param int $pagecourseid Pagecourseid.
     * @param array $data Submitted or stored data.
     * @return bool
     */
    public static function matches_moodle_return(string $path, int $pagecourseid, array $data): bool {
        return $path === '/mod/' . $data['modname'] . '/view.php' && $pagecourseid === (int)$data['courseid'];
    }
}

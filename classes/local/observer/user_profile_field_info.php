<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Observer to catch successful merge user operations and update
 * custom profile fields initialized also by this plugin.
 *
 * @package tool_mergeusers
 * @author Sam Møller <smo@moxis.dk>
 * @copyright 2019 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local\observer;

use tool_mergeusers\event\user_merged_success;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd


global $CFG;
require_once $CFG->dirroot . '/user/profile/lib.php';

/**
 * Observer class to update information on custom profile fields on both
 * related users on a given merge user operation.
 */
class user_profile_field_info {

    /**
     * Processes the successful merge user operation, updating custom profile fields
     * from both related users.
     *
     * @param user_merged_success $event
     * @return void
     */
    public static function add_merge_date_info(user_merged_success $event): void {
        global $DB;
        try {

            $olduserid = $event->get_old_user_id();
            $newuserid = $event->get_new_user_id();
            $logid = $event->get_log_id();
            $olduser = [
                'id' => $olduserid,
                'username' => $DB->get_field('user', 'username', ['id' => $olduserid]),
            ];
            $newuser = [
                'id' => $newuserid,
                'username' => $DB->get_field('user', 'username', ['id' => $newuserid]),
            ];

            self::add_merge_info(
                $olduserid,
                $logid,
                get_string(
                    'userfieldmergeto',
                    'tool_mergeusers',
                    $newuser,
                ),
                time(), // TODO: add time from event.
            );

            self::add_merge_info(
                $newuserid,
                get_string(
                    'userfieldmergefrom',
                    'tool_mergeusers',
                    $olduser,
                ),
                time(), // TODO: add time from event.
            );

        } catch (\Exception $e) {}
    }

    /**
     * Updates the profile fields from the given user, with the related detail.
     *
     * @param int $userid
     * @param string $detail
     * @param int|null $time
     * @return void
     */
    private static function add_merge_info(int $userid, int $logid, string $detail, ?int $time = null): void {
        $fields = [
            'mergeusers_detail' => $detail,
        ];

        if ($time !== null) {
            $fields['mergeusers_date'] = $time;
        }

        profile_save_custom_fields($userid, $fields);
    }
}

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
 * @package tool
 * @subpackage mergeusers
 * @author Sam Møller <smo@moxis.dk>
 * @copyright 2019 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local\observer;

use tool_mergeusers\event\user_merged_success;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd


global $CFG;
require_once $CFG->dirroot . '/user/profile/lib.php';

class user_profile_field_info {
    public static function add_merge_date_info(user_merged_success $event): void {
        try {
            $old_user_id = $event->get_old_user_id();
            $new_user_id = $event->get_new_user_id();

            $users = self::get_users_by_ids([$old_user_id, $new_user_id]);

            $info = [
                'newuserid' => $new_user_id,
                'newusername' => $users[$new_user_id]->username,
                'olduserid' => $old_user_id,
                'oldusername' => $users[$old_user_id]->username,
                'logurl' => self::get_log_url($event->get_log_id())->out(false)
            ];

            self::add_merge_info(
                $old_user_id,
                get_string(
                    'userfieldmergeto',
                    'tool_mergeusers',
                    $info
                ),
                time()
            );

            self::clear_merge_info($new_user_id);

        } catch (\Exception $e) {
            // Do nothing
        }
    }

    private static function get_users_by_ids(array $ids): array {
        global $DB;

        if (empty($ids)) {
            return [];
        }

        [$in_sql, $params] = $DB->get_in_or_equal($ids);

        return $DB->get_records_select(
            'user',
            "id $in_sql",
            $params,
            'id, username',
            'id, username'
        );
    }

    private static function add_merge_info(int $user_id, string $info, ?int $time = null): void {
        $fields = [
            'merge_info' => $info
        ];

        if ($time !== null) {
            $fields['merge_date'] = $time;
        }

        profile_save_custom_fields($user_id, $fields);
    }

    private static function clear_merge_info(int $user_id): void {
        $fields = [
            'merge_info' => '',
            'merge_date' => null
        ];
        profile_save_custom_fields($user_id, $fields);
    }

    private static function get_log_url(
        int $log_id
    ): \moodle_url {
        return new \moodle_url(
            '/admin/tool/mergeusers/log.php',
            ['id' => $log_id]
        );
    }
}

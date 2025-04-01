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

namespace tool_mergeusers\local;

use profile_define_base;

/**
 * Value for the "shotname"s of the custom profile fields used by this plugin.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 onwards to Universitat Rovira i Vrigili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_fields {
    /** @var string Default field for date where the merge was performed. */
    public const DEFAULT_MERGE_DATE_SHORTNAME = 'mergeusers_date';

    /** @var string Default field for log id of the merge. */
    public const DEFAULT_MERGE_LOG_ID_SHORTNAME = 'mergeusers_logid';

    /** @var string Default field for user id that has removed all her data. */
    public const DEFAULT_MERGE_OLD_USER_ID_SHORTNAME = 'mergeusers_olduserid';

    /** @var string Default field for user that keeps data from both merged users. */
    public const DEFAULT_MERGE_NEW_USER_ID_SHORTNAME = 'mergeusers_newuserid';

    /** @var string[] List of default custom profile shortnames */
    public const DEFAULT_MERGE_FIELD_SHORTNAMES = [
        self::DEFAULT_MERGE_DATE_SHORTNAME,
        self::DEFAULT_MERGE_LOG_ID_SHORTNAME,
        self::DEFAULT_MERGE_OLD_USER_ID_SHORTNAME,
        self::DEFAULT_MERGE_NEW_USER_ID_SHORTNAME,
    ];

    /**
     * Get date profile field shortname
     * @return string|null string if configured, else null.
     */
    public static function get_date_field_shortname(): ?string {
        return get_config('tool_mergeusers', 'date_field_shortname') ?: null;
    }

    /**
     * Get log id profile field shortname
     * @return string|null string if configured, else null.
     */
    public static function get_log_id_field_shortname(): ?string {
        return get_config('tool_mergeusers', 'log_id_field_shortname') ?: null;
    }

    /**
     * Get new user id profile field shortname
     * @return string|null string if configured, else null.
     */
    public static function get_new_userid_field_shortname(): ?string {
        return get_config('tool_mergeusers', 'new_userid_field_shortname') ?: null;
    }

    /**
     * Get old user id profile field shortname
     * @return string|null string if configured, else null.
     */
    public static function get_old_userid_field_shortname(): ?string {
        return get_config('tool_mergeusers', 'old_userid_field_shortname') ?: null;
    }

    /**
     * Updates the profile field values where they are set.
     * This means any with empty keys are removed/ignored.
     * @param int $userid user to update
     * @param array $values array of key value pairs
     */
    private static function update_where_field_set(int $userid, array $values): void {
        // Remove empty keys - this is where the profile field is not configured.
        $values = array_filter($values, fn($k) => !empty($k), ARRAY_FILTER_USE_KEY);
        profile_save_custom_fields($userid, $values);
    }

    /**
     * Updates old user with given data.
     * @param int $olduserid
     * @param int $newuserid
     * @param int $logid
     * @param int $timecreated
     */
    public static function update_old_user(int $olduserid, int $newuserid, int $logid, int $timecreated): void {
        self::update_where_field_set($olduserid, [
            self::get_date_field_shortname() => $timecreated,
            self::get_log_id_field_shortname() => $logid,
            self::get_new_userid_field_shortname() => $newuserid,
            self::get_old_userid_field_shortname() => null,
        ]);
    }

    /**
     * Updates new user with given data.
     * @param int $newuserid
     * @param int $olduserid
     * @param int $logid
     * @param int $timecreated
     */
    public static function update_new_user(int $newuserid, int $olduserid, int $logid, int $timecreated): void {
        self::update_where_field_set($newuserid, [
            self::get_date_field_shortname() => $timecreated,
            self::get_log_id_field_shortname() => $logid,
            self::get_new_userid_field_shortname() => null,
            self::get_old_userid_field_shortname() => $olduserid,
        ]);
    }

    /**
     * Creates the recommended fields, and configures them for use.
     * @param string $categoryname name of profile field category to create fields in.
     */
    public static function setup_recommended_fields(string $categoryname): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/profile/definelib.php');

        // First, ensure the category exists.
        $category = $DB->get_record('user_info_category', ['name' => $categoryname], '*', MUST_EXIST);

        // Definition of all 4 fields.
        $fields = [
            self::DEFAULT_MERGE_DATE_SHORTNAME => [
                'name' => 'Merge date',
                'shortname' => self::DEFAULT_MERGE_DATE_SHORTNAME,
                'datatype' => 'datetime',
                'description' => 'When was this merge completed?',
                'descriptionformat' => FORMAT_HTML,
                'categoryid' => $category->id,
                'required' => false,
                'locked' => true,
                'visible' => false,
                'forceunique' => false,
                'signup' => false,
                'defaultdata' => 0,
                'defaultdataformat' => '0',
                'param1' => '2025',
                'param2' => '2125',
            ],
            self::DEFAULT_MERGE_LOG_ID_SHORTNAME => [
                'name' => 'Merge log',
                'shortname' => self::DEFAULT_MERGE_LOG_ID_SHORTNAME,
                'datatype' => 'text',
                'description' => 'Log id for this merge.',
                'descriptionformat' => FORMAT_HTML,
                'categoryid' => $category->id,
                'required' => false,
                'locked' => true,
                'visible' => false,
                'forceunique' => false,
                'signup' => false,
                'defaultdata' => '',
                'defaultdataformat' => '0',
                'param1' => '30',
                'param2' => '2024',
                'param3' => '0',
                'param4' => $CFG->wwwroot . '/admin/tool/mergeusers/log.php?id=$$',
                'param5' => '_blank',
            ],
            self::DEFAULT_MERGE_OLD_USER_ID_SHORTNAME => [
                'name' => 'Merged old user',
                'shortname' => self::DEFAULT_MERGE_OLD_USER_ID_SHORTNAME,
                'datatype' => 'text',
                'description' => 'Old user id.',
                'descriptionformat' => FORMAT_HTML,
                'categoryid' => $category->id,
                'required' => false,
                'locked' => true,
                'visible' => false,
                'forceunique' => false,
                'signup' => false,
                'defaultdata' => '',
                'defaultdataformat' => '0',
                'param1' => '30',
                'param2' => '2024',
                'param3' => '0',
                'param4' => $CFG->wwwroot . '/user/profile.php?id=$$',
                'param5' => '_blank',
            ],
            self::DEFAULT_MERGE_NEW_USER_ID_SHORTNAME => [
                'name' => 'Merged new user',
                'shortname' => self::DEFAULT_MERGE_NEW_USER_ID_SHORTNAME,
                'datatype' => 'text',
                'description' => 'New user id.',
                'descriptionformat' => FORMAT_HTML,
                'categoryid' => $category->id,
                'required' => false,
                'locked' => true,
                'visible' => false,
                'forceunique' => false,
                'signup' => false,
                'defaultdata' => '',
                'defaultdataformat' => '0',
                'param1' => '30',
                'param2' => '2024',
                'param3' => '0',
                'param4' => $CFG->wwwroot . '/user/profile.php?id=$$',
                'param5' => '_blank',
            ],
        ];

        // Create custom fields if they do not exist.
        foreach ($fields as $field_info) {
            $record = (object)$field_info;

            // Check if it already exists to update it.
            $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => $record->shortname]);
            if (!empty($fieldid)) {
                $record->id = $fieldid;
            }

            $define_field = new profile_define_base();
            $define_field->define_save($record);
        }

        // Configure them as the ones used by mergeusers.
        set_config('date_field_shortname', self::DEFAULT_MERGE_DATE_SHORTNAME, 'tool_mergeusers');
        set_config('log_id_field_shortname', self::DEFAULT_MERGE_LOG_ID_SHORTNAME, 'tool_mergeusers');
        set_config('new_userid_field_shortname', self::DEFAULT_MERGE_NEW_USER_ID_SHORTNAME, 'tool_mergeusers');
        set_config('old_userid_field_shortname', self::DEFAULT_MERGE_OLD_USER_ID_SHORTNAME, 'tool_mergeusers');
    }
}

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

class user_profile_field_info_test extends advanced_testcase {
    protected function setUp(): void {
        global $CFG;

        require_once $CFG->dirroot . '/admin/tool/mergeusers/lib.php';

        $this->resetAfterTest();
    }

    /**
     * Find existing user profile category.
     * Remove it if it exists.
     * Create by calling the function.
     * Check if it exists.
     */
    public function test_create_user_profile_category(): void {
        global $DB;

        $category = $this->get_merge_users_profile_category();
        $old_id = $category->id;

        if (!empty($category->id)) {
            $DB->delete_records('user_info_category', ['id' => $category->id]);
        }

        tool_mergeusers_create_user_profile_fields();

        $category = $DB->get_record('user_info_category', ['name' => $category->name]);

        self::assertNotEmpty($category->id);
        self::assertNotEquals($old_id, $category->id);
    }

    /**
     * Get the merge user profile category.
     * Remove all fields in the category.
     * Call the function to create the fields.
     * Check if the fields exist.
     */
    public function test_create_user_profile_fields(): void {
        global $DB;

        $category = $this->get_merge_users_profile_category();

        // Remove all fields in the category.
        $DB->delete_records('user_info_field', ['categoryid' => $category->id]);

        tool_mergeusers_create_user_profile_fields();

        $records = $DB->get_recordset('user_info_field', ['categoryid' => $category->id]);
        $fields = [];

        foreach ($records as $record) {
            $fields[$record->shortname] = $record;
        }

        $records->close();

        self::assertArrayHasKey('merge_date', $fields);
        self::assertArrayHasKey('merge_info', $fields);
    }

    /**
     * Emit user_merged_success event.
     * Call the function to add merge date and info (add_merge_date_info).
     * Check if the fields have been updated on old user and new user.
     */
    public function test_event_observer_add_merge_date_and_info(): void {
        global $DB;

        $category = $this->get_merge_users_profile_category();
        [$in_sql, $field_params] = $DB->get_in_or_equal(['merge_date', 'merge_info']);
        $field_params[] = $category->id;

        $field_exists = $DB->record_exists_select(
            'user_info_field',
            "shortname $in_sql AND categoryid = ?",
            $field_params
        );

        self::assertTrue($field_exists);

        $generator = self::getDataGenerator();

        $old_user = $generator->create_user();
        $new_user = $generator->create_user();
        $log = (object)[
            'id' => 1,
            'touserid' => $new_user->id,
            'fromuserid' => $old_user->id,
            'mergedbyuserid' => 2,
            'timemodified' => time(),
            'log' => '',
        ];

        $event = $this->get_user_merged_success_event($old_user, $new_user, $log);

        \tool_mergeusers\local\observer\user_profile_field_info::add_merge_date_info($event);

        $old_user_fields = $this->get_profile_fields_with_shortnames(
            $category->id,
            $old_user->id
        );

        self::assertGreaterThan(
            0,
            $old_user_fields['merge_date']->data
        );
        self::assertStringContainsString(
            $new_user->username,
            $old_user_fields['merge_info']->data
        );

        $new_user_fields = $this->get_profile_fields_with_shortnames(
            $category->id,
            $new_user->id
        );

        self::assertEmpty(
            $new_user_fields['merge_date']->data
        );
        self::assertEmpty(
            $new_user_fields['merge_info']->data
        );
    }

    private function get_merge_users_profile_category(): object {
        global $DB;

        $record = ['name' => 'Merge User Info'];
        $category = $DB->get_record('user_info_category', $record);

        if (empty($category)) {
            $category = self::getDataGenerator()->create_custom_profile_field_category($record);
        }

        return $category;
    }

    /**
     * @param int $profile_category_id
     * @param int $user_id
     * @return profile_field_base[]
     */
    private function get_profile_fields_with_shortnames(int $profile_category_id, int $user_id): array {
        $category_fields = profile_get_user_fields_with_data_by_category($user_id);
        $items = [];

        if (!isset($category_fields[$profile_category_id])) {
            return $items;
        }

        foreach ($category_fields as $fields) {
            foreach ($fields as $field) {
                $items[$field->get_shortname()] = $field;
            }
        }

        return $items;
    }

    private function get_user_merged_success_event(
        object $old_user,
        object $new_user,
        object $log
    ): \tool_mergeusers\event\user_merged_success {
        return \tool_mergeusers\event\user_merged_success::create([
            'context' => \context_system::instance(),
            'other' => [
                'usersinvolved' => [
                    'toid' => $new_user->id,
                    'fromid' => $old_user->id,
                ],
                'logid' => $log->id,
                'log' => $log,
            ],
        ]);
    }
}

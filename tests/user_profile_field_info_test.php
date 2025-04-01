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

use tool_mergeusers\event\user_merged_success;
use tool_mergeusers\local\profile_fields;

/**
 * Tests related to user profile field setting post-merge.
 *
 * @package tool
 * @subpackage mergeusers
 * @author Sam Møller <smo@moxis.dk>
 * @author Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright 2019 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_profile_field_info_test extends advanced_testcase {

    /**
     * Sets up tests.
     */
    protected function setUp(): void {
        global $CFG;
        require_once $CFG->dirroot . '/admin/tool/mergeusers/lib.php';
        $this->resetAfterTest();
    }

    /**
     * Tests that setting up recommended fields does not work with a non-existent category.
     * @covers profile_fields::setup_recommended_fields
     */
    public function test_setup_recommended_fields_category_does_not_exist() {
        $this->expectException(dml_exception::class);
        profile_fields::setup_recommended_fields('does not exist');
    }

    /**
     * Tests that setting up recommended fields works with an existing category.
     * @covers profile_fields::setup_recommended_fields
     */
    public function test_setup_recommended_fields_category_does_exist() {
        self::getDataGenerator()->create_custom_profile_field_category(['name' => 'test']);
        profile_fields::setup_recommended_fields('test');

        // Check expected number are created.
        $fields = array_column(profile_get_custom_fields(), 'shortname');
        foreach (profile_fields::DEFAULT_MERGE_FIELD_SHORTNAMES as $expectedfield) {
            $this->assertContains($expectedfield, $fields);
        }
    }

    /**
     * Emulate two users are successfully merged and check that all profile fields are updated.
     *
     * @throws dml_exception
     * @throws coding_exception
     * @group tool_mergeusers
     * @group user_profile_fields
     */
    public function test_profile_fields_are_updated_on_merge_success(): void {
        // Use default fields for this test.
        self::getDataGenerator()->create_custom_profile_field_category(['name' => 'test']);
        profile_fields::setup_recommended_fields('test');

        $generator = self::getDataGenerator();

        $olduser = $generator->create_user();
        $newuser = $generator->create_user();
        $logid = 1;
        $mergedate = time();
        $log = (object)[
            'id' => $logid,
            'touserid' => $newuser->id,
            'fromuserid' => $olduser->id,
            'mergedbyuserid' => 2,
            'timemodified' => $mergedate,
            'log' => '',
        ];

        $this->trigger_user_merged_success_event($olduser, $newuser, $log);

        // Check old user.
        $this->assert_profile_field_value_is($olduser->id, profile_fields::get_old_userid_field_shortname(), '');
        $this->assert_profile_field_value_is($olduser->id, profile_fields::get_new_userid_field_shortname(), $newuser->id);
        $this->assert_profile_field_value_is($olduser->id, profile_fields::get_date_field_shortname(), $mergedate);
        $this->assert_profile_field_value_is($olduser->id, profile_fields::get_log_id_field_shortname(), $logid);

        // Check new user.
        $this->assert_profile_field_value_is($newuser->id, profile_fields::get_old_userid_field_shortname(), $olduser->id);
        $this->assert_profile_field_value_is($newuser->id, profile_fields::get_new_userid_field_shortname(), '');
        $this->assert_profile_field_value_is($newuser->id, profile_fields::get_date_field_shortname(), $mergedate);
        $this->assert_profile_field_value_is($newuser->id, profile_fields::get_log_id_field_shortname(), $logid);
    }

    /**
     * Asserts that a given user has a profile field with a certain value.
     * @param int $userid user whose profile to check.
     * @param string $profilefieldshortname short name of profile field to compare value to.
     * @param string $expectedvalue expected profile field value.
     */
    private function assert_profile_field_value_is(int $userid, string $profilefieldshortname, string $expectedvalue) {
        $data = profile_get_user_fields_with_data($userid);
        $field = current(array_filter($data, fn($v) => $v->field->shortname == $profilefieldshortname));

        if (!$field) {
            $this->fail('No profile field found for shortname '. $profilefieldshortname);
        }

        $this->assertEquals($expectedvalue, $field->data);
    }

    /**
     * Triggers a successful merge event.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    private function trigger_user_merged_success_event(
        object $old_user,
        object $new_user,
        object $log
    ): void {
        user_merged_success::create([
            'context' => \context_system::instance(),
            'other' => [
                'usersinvolved' => [
                    'toid' => $new_user->id,
                    'fromid' => $old_user->id,
                ],
                'logid' => $log->id,
                'log' => $log,
            ],
        ])->trigger();
    }
}

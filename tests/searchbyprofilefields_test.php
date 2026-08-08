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
 * Search and merge users by a custom user profile field.
 *
 * @package    tool_mergeusers
 * @author     Johnny Tsheke
 * @copyright  Johnny Tsheke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers;

use advanced_testcase;
use tool_mergeusers\local\user_merger;
use tool_mergeusers\local\user_searcher;

/**
 * Merge users searched by a custom profile field.
 *
 * Inspired by enrolments_test.php and user/tests/profilelib_test.php.
 *
 * @covers \tool_mergeusers\local\user_searcher
 */
final class searchbyprofilefields_test extends advanced_testcase {
    /**
     * Enrol two users on one unique course each and one shared course.
     * Search each user by a profile field value then merge them.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_search_and_merge_users_by_profile_field(): void {
        global $DB;
        $this->resetAfterTest(true);

        $userone = $this->getDataGenerator()->create_user();

        // Add a custom field of the "text" type.
        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        // Add userone profile data.
        $uidone = new \stdClass();
        $uidone->userid = $userone->id;
        $uidone->fieldid = $fieldid;
        $uidone->data = 'frogvalueone';
        $DB->insert_record('user_info_data', $uidone);

        $mus = new user_searcher();
        $searchusers = $mus->search_users('frogvalueone', (string) $fieldid);
        $this->assertCount(1, $searchusers);

        // Create another user with its own profile data.
        $usertwo = $this->getDataGenerator()->create_user();
        $uidtwo = new \stdClass();
        $uidtwo->userid = $usertwo->id;
        $uidtwo->fieldid = $fieldid;
        $uidtwo->data = 'frogvaluetwo';
        $DB->insert_record('user_info_data', $uidtwo);
        $searchusers = $mus->search_users('frogvaluetwo', (string) $fieldid);
        $this->assertCount(1, $searchusers);

        // A broader term matches both users.
        $searchusers = $mus->search_users('frogvalue', (string) $fieldid);
        $this->assertCount(2, $searchusers);

        $this->assertEquals(0, $userone->suspended);
        $this->assertEquals(0, $usertwo->suspended);

        // Create three courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $maninstance1 = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $course1->id], '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $course2->id], '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $course3->id], '*', MUST_EXIST);

        $manual = enrol_get_plugin('manual');
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        // Enrol $userone on courses 2+3 and $usertwo on courses 1+2.
        $manual->enrol_user($maninstance2, $userone->id, $studentrole->id);
        $manual->enrol_user($maninstance3, $userone->id, $studentrole->id);
        $manual->enrol_user($maninstance1, $usertwo->id, $studentrole->id);
        $manual->enrol_user($maninstance2, $usertwo->id, $studentrole->id);

        // Check the initial state of enrolments for both users.
        $courses = enrol_get_all_users_courses($usertwo->id);
        ksort($courses);
        $this->assertCount(2, $courses);
        $this->assertEquals([$course1->id, $course2->id], array_keys($courses));

        $courses = enrol_get_all_users_courses($userone->id);
        ksort($courses);
        $this->assertCount(2, $courses);
        $this->assertEquals([$course2->id, $course3->id], array_keys($courses));

        // Search users by profile field and merge usertwo into userone.
        $userkeep = $mus->search_users('frogvalueone', (string) $fieldid)[$userone->id];
        $userremove = $mus->search_users('frogvaluetwo', (string) $fieldid)[$usertwo->id];
        $mut = new user_merger();
        $mut->merge($userkeep->id, $userremove->id);

        // Userone stays active, usertwo becomes suspended.
        $userone = $DB->get_record('user', ['id' => $userone->id]);
        $this->assertEquals(0, $userone->suspended);

        $usertwo = $DB->get_record('user', ['id' => $usertwo->id]);
        $this->assertEquals(1, $usertwo->suspended);

        // Userone is now enrolled on all three courses.
        $courses = enrol_get_all_users_courses($userone->id);
        ksort($courses);
        $this->assertCount(3, $courses);
        $this->assertEquals([$course1->id, $course2->id, $course3->id], array_keys($courses));

        // Usertwo is no longer enrolled anywhere.
        $courses = enrol_get_all_users_courses($usertwo->id);
        $this->assertCount(0, $courses);
    }

    /**
     * Test that verify_user() resolves a user by an allow-listed profile field id,
     * the same way it already does for real user-table columns.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_verify_user_by_profile_field(): void {
        global $DB;
        $this->resetAfterTest(true);

        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        $user = $this->getDataGenerator()->create_user();
        $uid = new \stdClass();
        $uid->userid = $user->id;
        $uid->fieldid = $fieldid;
        $uid->data = 'frogvalueone';
        $DB->insert_record('user_info_data', $uid);

        $mus = new user_searcher();
        [$founduser, $message] = $mus->verify_user('frogvalueone', (string) $fieldid);

        $this->assertSame('', $message);
        $this->assertNotNull($founduser);
        $this->assertEquals($user->id, $founduser->id);
    }

    /**
     * Test that verify_user() rejects a numeric field id that is not allow-listed,
     * instead of silently falling back to the "search all fields" strategy.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_verify_user_rejects_non_allowlisted_profile_field(): void {
        global $DB;
        $this->resetAfterTest(true);

        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        // Deliberately not added to tool_mergeusers/searchbyprofilefields.

        $user = $this->getDataGenerator()->create_user();
        $uid = new \stdClass();
        $uid->userid = $user->id;
        $uid->fieldid = $fieldid;
        $uid->data = 'frogvalueone';
        $DB->insert_record('user_info_data', $uid);

        $mus = new user_searcher();
        [$founduser, $message] = $mus->verify_user('frogvalueone', (string) $fieldid);

        $this->assertNull($founduser);
        $this->assertNotSame('', $message);
    }

    /**
     * Test that verify_user() requires an exact match on the profile field value,
     * unlike search_users()'s partial (LIKE) matching - a value that is only a
     * substring of the stored data must not resolve to a user.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_verify_user_requires_exact_profile_field_match(): void {
        global $DB;
        $this->resetAfterTest(true);

        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        $user = $this->getDataGenerator()->create_user();
        $uid = new \stdClass();
        $uid->userid = $user->id;
        $uid->fieldid = $fieldid;
        $uid->data = 'frogvalueone';
        $DB->insert_record('user_info_data', $uid);

        $mus = new user_searcher();
        // Only a partial match of the stored "frogvalueone" value.
        [$founduser, $message] = $mus->verify_user('frogvalue', (string) $fieldid);

        $this->assertNull($founduser);
        $this->assertNotSame('', $message);
    }

    /**
     * Test that verify_user() refuses to silently pick one of several users who
     * happen to share the exact same profile field value, instead of returning an
     * arbitrary match.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_verify_user_rejects_ambiguous_profile_field_value(): void {
        global $DB;
        $this->resetAfterTest(true);

        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        foreach ([$this->getDataGenerator()->create_user(), $this->getDataGenerator()->create_user()] as $user) {
            $uid = new \stdClass();
            $uid->userid = $user->id;
            $uid->fieldid = $fieldid;
            $uid->data = 'sharedvalue';
            $DB->insert_record('user_info_data', $uid);
        }

        $mus = new user_searcher();
        [$founduser, $message] = $mus->verify_user('sharedvalue', (string) $fieldid);

        $this->assertNull($founduser);
        $this->assertNotSame('', $message);
    }
}

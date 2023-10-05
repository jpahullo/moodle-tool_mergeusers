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

namespace tool_mergeusers;

/**
 * Version information
 * Inspired by enrolments_test.php and user/tests/profilelib_test.php
 * @package    tool
 * @subpackage mergeusers
 * @covers \MergeUserSearch
 * @author     Johnny Tsheke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profilefields_test extends \advanced_testcase {
    /**
     * Setup the test.
     */
    public function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/user/profile/lib.php");
        require_once("$CFG->dirroot/admin/tool/mergeusers/lib/mergeusertool.php");
        require_once("$CFG->dirroot/admin/tool/mergeusers/lib/mergeusersearch.php");
        $this->resetAfterTest(true);
    }

    /**
     * Enrol two users on one unique course each and one shared course.
     * Search each user by a profile field value then merge them.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_profilefields
     */
    public function test_searchbyprofilefields() {
        global $DB;
        $userone = $this->getDataGenerator()->create_user();

         // Add custom field of normal text type.
         $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text', ])->id;
         // Check that profile field was created.
        $results = profile_get_custom_fields();
        $this->assertArrayHasKey($fieldid, $results);
        $this->assertEquals('frogname', $results[$fieldid]->shortname);
        // Add userone profile data.
        $uidone = new \stdClass();
        $uidone->userid = $userone->id;
        $uidone->fieldid = $fieldid;
        $uidone->data = 'frogvalueone';
        $DB->insert_record('user_info_data', $uidone);

        // Search tool for searching for users and verifying them.
        $mus = new \MergeUserSearch();
        $searchusers = $mus->search_users('frogvalueone', $fieldid);
        $this->assertCount(1, $searchusers);

        // Create the another user.
        $usertwo = $this->getDataGenerator()->create_user();

        // Add usertwo profile data.
        $uidtwo = new \stdClass();
        $uidtwo->userid = $usertwo->id;
        $uidtwo->fieldid = $fieldid;
        $uidtwo->data = 'frogvaluetwo';
        $DB->insert_record('user_info_data', $uidtwo);
        $searchusers = $mus->search_users('frogvaluetwo', $fieldid);
        $this->assertCount(1, $searchusers);

        // Check that search by profile field finds all users.
        $searchusers = $mus->search_users('frogvalue', $fieldid);
        $this->assertCount(2, $searchusers);

        // Check that userone and usertwo are not suspended.
        $this->assertEquals(0, $userone->suspended);
        $this->assertEquals(0, $usertwo->suspended);

        // Create three courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $maninstance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', ['courseid' => $course3->id, 'enrol' => 'manual'], '*', MUST_EXIST);

        $manual = enrol_get_plugin('manual');

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        // Enrol $user2 on course 1 + 2 and $user1 on course 2 + 3.
        $manual->enrol_user($maninstance1, $usertwo->id, $studentrole->id);
        $manual->enrol_user($maninstance2, $usertwo->id, $studentrole->id);
        $manual->enrol_user($maninstance2, $userone->id, $studentrole->id);
        $manual->enrol_user($maninstance3, $userone->id, $studentrole->id);

        // Check initial state of enrolments for $usertwo.
        $courses = enrol_get_all_users_courses($usertwo->id);
        ksort($courses);
        $this->assertCount(2, $courses);
        $this->assertEquals([$course1->id, $course2->id], array_keys($courses));

        // Check initial state of enrolments for $userone.
        $courses = enrol_get_all_users_courses($userone->id);
        ksort($courses);
        $this->assertCount(2, $courses);
        $this->assertEquals([$course2->id, $course3->id], array_keys($courses));

        // Search users by profile field and merge userwon into userone.
        $userkeep = $mus->search_users('frogvalueone', $fieldid)[$userone->id];
        $userremove = $mus->search_users('frogvaluetwo', $fieldid)[$usertwo->id];
        $mut = new \MergeUserTool();
        list($success, $log, $logid) = $mut->merge($userkeep->id, $userremove->id);

        // Check userone still not suspended but usertwo is now suspended.
        $userone = $DB->get_record('user', ['id' => $userone->id]);
        $this->assertEquals(0, $userone->suspended);

        $usertwo = $DB->get_record('user', ['id' => $usertwo->id]);
        $this->assertEquals(1, $usertwo->suspended);

        // Check userone is now enrolled on all three courses.
        $courses = enrol_get_all_users_courses($userone->id);
        ksort($courses);
        $this->assertCount(3, $courses);
        $this->assertEquals([$course1->id, $course2->id, $course3->id], array_keys($courses));

        // Check usertwo is no longer enrolled on any course.
        $courses = enrol_get_all_users_courses($usertwo->id);
        $this->assertCount(0, $courses);

    }

}

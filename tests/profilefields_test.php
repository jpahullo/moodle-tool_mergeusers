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
 * Version information
 *
 * @package    tool
 * @subpackage mergeusers
 * @author     Johnny Tsheke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profilefields_test extends advanced_testcase {
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
     * search user by profile field
     *
     * @return tool_mergeusers
     * @group tool_mergeusers_profilefields
     */
    public function test_searchbyprofilefields() {
        global $DB;
        $user_keep = $this->getDataGenerator()->create_user();

         // Add custom field of normal text type.
         $field1 = $this->getDataGenerator()->create_custom_profile_field(array(
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text'))->id;
         // Check both are returned using normal option.
        $result = profile_get_custom_fields();
        $this->assertArrayHasKey($field1, $result);
        $this->assertEquals('frogname', $result[$field1]->shortname);
        //add user1 profile data
        $profile1 = new \stdClass();
        $profile1->userid = $user_keep->id;
        $profile1->fieldid = $field1;
        $profile1->data = 'frogvalue1';
        $DB->insert_record('user_info_data',$profile1);
        $mut = new MergeUserTool();
        // Search tool for searching for users and verifying them.
        $mus = new MergeUserSearch();
        $searchusers = $mus->search_users('frogvalue1', $field1);
        $this->assertCount(1, $searchusers);
        // Create the second user
        $user_remove = $this->getDataGenerator()->create_user();
        //add user2 profile data
        $profile2 = new \stdClass();
        $profile2->userid = $user_remove->id;
        $profile2->fieldid = $field1;
        $profile2->data = 'frogvalue2';
        $DB->insert_record('user_info_data',$profile2);
        $searchusers = $mus->search_users('frogvalue2', $field1);
        $this->assertCount(1, $searchusers);
        
        // Found all user
        $searchusers = $mus->search_users('frogvalue', $field1);
        $this->assertCount(2, $searchusers);

        // Test user to remove is not suspended
        $this->assertEquals(0, $user_remove->suspended);

        // Merge test
      
        $mut = new MergeUserTool();
        list($success, $log, $logid) = $mut->merge($user_keep->id, $user_remove->id);

        // Check $user_remove is suspended.
        $user_remove = $DB->get_record('user', array('id' => $user_remove->id));
        $this->assertEquals(1, $user_remove->suspended);
    }
    /**
     * Enrol two users on one unique course each and one shared course
     * then merge them.
     * @group tool_mergeusers
     * @group tool_mergeusers_profilefields
     */
    public function test_mergeenrolments() {
        global $DB;

        // Setup two users to merge.
        $user_remove = $this->getDataGenerator()->create_user();
        $user_keep = $this->getDataGenerator()->create_user();

        // Create three courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid'=>$course2->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid'=>$course3->id, 'enrol'=>'manual'), '*', MUST_EXIST);

        $manual = enrol_get_plugin('manual');

        $studentrole = $DB->get_record('role', array('shortname'=>'student'));

        // Enrol $user_remove on course 1 + 2 and $user_keep on course 2 + 3.
        $manual->enrol_user($maninstance1, $user_remove->id, $studentrole->id);
        $manual->enrol_user($maninstance2, $user_remove->id, $studentrole->id);
        $manual->enrol_user($maninstance2, $user_keep->id, $studentrole->id);
        $manual->enrol_user($maninstance3, $user_keep->id, $studentrole->id);

        // Check initial state of enrolments for $user_remove.
        $courses = enrol_get_all_users_courses($user_remove->id);
        ksort($courses);
        $this->assertCount(2, $courses);
        $this->assertEquals(array($course1->id, $course2->id), array_keys($courses));

        // Check initial state of enrolments for $user_keep.
        $courses = enrol_get_all_users_courses($user_keep->id);
        ksort($courses);
        $this->assertCount(2, $courses);
        $this->assertEquals(array($course2->id, $course3->id), array_keys($courses));

        $mut = new MergeUserTool();
        list($success, $log, $logid) = $mut->merge($user_keep->id, $user_remove->id);

        // Check $user_remove is suspended.
        $user_remove = $DB->get_record('user', array('id' => $user_remove->id));
        $this->assertEquals(1, $user_remove->suspended);

        // Check $user_keep is now enrolled on all three courses.
        $courses = enrol_get_all_users_courses($user_keep->id);
        ksort($courses);
        $this->assertCount(3, $courses);
        $this->assertEquals(array($course1->id, $course2->id, $course3->id), array_keys($courses));
    }
}

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
 * @author     Andrew Hancox <andrewdchancox@googlemail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');

use mod_assign\base_test;

/**
 * Class assign_test
 */
class assign_test extends advanced_testcase {

    /** @var base_test assign test to be used during this testcase. */
    private base_test $assigntest;

    /**
     *
     */
    public function setUp(): void {
        global $CFG;
        require_once("$CFG->dirroot/admin/tool/mergeusers/lib/mergeusertool.php");
        parent::setUp();

        // Build testcase for the assign.
        $this->assigntest = new base_test();
        $this->assigntest->setup();
        $this->resetAfterTest(true);
    }

    /**
     * Test merging two users where one has submitted an assignment and the other
     * has no.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_assign
     */
    public function test_mergenonconflictingassigngrades() {
        global $DB;

        $editingteacher = $this->set_editingteacher();
        $assign = $this->create_instance();
        $teacher = $this->set_teacher();
        $students = $this->set_students();
        $course = $this->set_course();
        

        // Give a grade to student 1.
        $data = new stdClass();
        $data->grade = '75.0';
        $assign->testable_apply_grade_to_user($data, $students[1]->id, 0);

        // Check initial state - student 0 has no grade, student 1 has 75.00.
        $this->assertEquals(false, $assign->testable_is_graded($students[0]->id));
        $this->assertEquals(true, $assign->testable_is_graded($students[1]->id));
        $this->assertEquals('75.00', $this->get_user_assign_grade($students[1], $assign, $course));
        $this->assertEquals('-', $this->get_user_assign_grade($students[0], $assign, $course));

        // Merge student 1 into student 0.
        $mut = new MergeUserTool();
        $mut->merge($students[0]->id, $students[1]->id);

        // Student 0 should now have a grade of 75.00.
        $this->assertEquals(true, $assign->testable_is_graded($students[0]->id));
        $this->assertEquals('75.00', $this->get_user_assign_grade($students[0], $assign, $course));

        // Student 1 should now be suspended.
        $user_remove = $DB->get_record('user', array('id' => $students[1]->id));
        $this->assertEquals(1, $user_remove->suspended);
    }

    /**
     * Utility method to get the grade for a user.
     *
     * @param stdClass $user
     * @param testable_assign $assign
     * @param stdClass $course
     * @return string grade for the given assign.
     */
    private function get_user_assign_grade($user, $assign, $course): string {
        $gradebookgrades = \grade_get_grades($course->id, 'mod', 'assign', $assign->get_instance()->id, $user->id);
        $gradebookitem   = array_shift($gradebookgrades->items);
        $grade     = $gradebookitem->grades[$user->id];
        return $grade->str_grade;
    }

    /**
     * @throws ReflectionException
     */
    private function set_editingteacher(): stdClass {
        $editingteachers = new ReflectionProperty($this->assigntest, 'editingteachers');
        $editingteacher = ($editingteachers->getValue($this->assigntest))[0];
        $this->setUser($editingteacher);
        return $editingteacher;
    }

    /**
     * @throws ReflectionException
     */
    private function create_instance(): testable_assign {
        $createinstance = new ReflectionMethod($this->assigntest, 'create_instance');
        return $createinstance->invoke($this->assigntest);
    }

    /**
     * @throws ReflectionException
     */
    private function set_teacher(): stdClass {
        $teachers = new ReflectionProperty($this->assigntest, 'teachers');
        $teacher = ($teachers->getValue($this->assigntest))[0];
        $this->setUser($teacher);
        return $teacher;
    }

    /**
     * @throws ReflectionException
     */
    private function set_students(): array {
        $students = new ReflectionProperty($this->assigntest, 'students');
        return $students->getValue($this->assigntest);
    }

    /**
     * @throws ReflectionException
     */
    private function set_course(): stdClass {
        $course = new ReflectionProperty($this->assigntest, 'course');
        return $course->getValue($this->assigntest);
    }
}

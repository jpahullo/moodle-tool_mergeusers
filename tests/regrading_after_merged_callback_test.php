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
 * Tests for regrading callback after merging users.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers;

use advanced_testcase;
use grade_item;
use moodle_exception;
use tool_mergeusers\hook\after_merged_all_tables;
use tool_mergeusers\local\callbacks\regrading_after_merged_callback;

/**
 * Tests for regrading callback handling uninstalled plugins and edge cases.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \tool_mergeusers\local\callbacks\regrading_after_merged_callback
 */
final class regrading_after_merged_callback_test extends advanced_testcase {
    /** @var object Course object */
    private object $course;

    /** @var object First user (to keep) */
    private object $user1;

    /** @var object Second user (to remove) */
    private object $user2;

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Common setup: course and users.
        $this->course = $this->getDataGenerator()->create_course();
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course->id);
    }

    /**
     * Test that regrading skips grade items when plugin is uninstalled (not in {modules} table).
     *
     * This test validates the SQL INNER JOIN optimization that filters uninstalled plugins.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_regrade
     */
    public function test_regrade_skips_uninstalled_plugin(): void {
        global $DB;

        // Create an assignment with grades.
        $assign = $this->create_activity_with_grades('assign');

        // Remove the module from {modules} table to simulate uninstallation.
        $DB->delete_records('modules', ['name' => 'assign']);

        // Execute regrading callback.
        $logs = [];
        $errors = [];
        $hook = new after_merged_all_tables($this->user1->id, $this->user2->id, $logs, $errors);

        regrading_after_merged_callback::regrade($hook);

        // Verify: No logs should be generated because SQL filters out uninstalled modules.
        $this->assertEmpty($logs, 'No logs should be generated for uninstalled plugins');
        $this->assertEmpty($errors, 'No errors should be generated');
    }

    /**
     * Test that exception is thrown when plugin table is missing.
     *
     * This validates that when a plugin is installed (present in {modules}) but its
     * database table doesn't exist, an exception is thrown indicating critical database
     * corruption, consistent with the other two data integrity exceptions.
     *
     * Uses table renaming instead of dropping to avoid breaking test cleanup.
     *
     * NOTE: This test uses preventResetByRollback() to force full database reset,
     * which is required for DDL operations (rename_table). This makes the test slower
     * (~3-5 seconds) but allows safe table manipulation.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_regrade
     */
    public function test_regrade_throws_exception_when_plugin_table_missing(): void {
        global $DB;

        // Force full database reset (required for DDL operations).
        $this->preventResetByRollback();

        // Create an assignment with grades.
        $assign = $this->create_activity_with_grades('assign');

        // Rename table to simulate corruption (plugin installed but table missing).
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('assign');
        $backupname = 'assign_phpunit_backup_temp';

        try {
            // Hide the assign table by renaming it.
            $dbman->rename_table($table, $backupname);

            // Execute regrading callback and expect exception.
            $logs = [];
            $errors = [];
            $hook = new after_merged_all_tables($this->user1->id, $this->user2->id, $logs, $errors);

            $this->expectException(moodle_exception::class);
            $this->expectExceptionMessageMatches(
                '/Plugin "assign" is installed but its database table is missing/'
            );

            regrading_after_merged_callback::regrade($hook);

        } finally {
            // ALWAYS restore table name, even if test fails.
            if ($dbman->table_exists($backupname)) {
                $backuptable = new \xmldb_table($backupname);
                $dbman->rename_table($backuptable, 'assign');
            }
        }
    }

    /**
     * Test that exception is thrown when activity record is missing.
     *
     * This validates the first existing exception check.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_regrade
     */
    public function test_regrade_throws_exception_when_activity_record_missing(): void {
        global $DB;

        // Create an assignment with grades.
        $assign = $this->create_activity_with_grades('assign');

        // Delete the activity record (but keep course_modules and grade items).
        $DB->delete_records('assign', ['id' => $assign->id]);

        // Execute regrading callback and expect exception.
        $logs = [];
        $errors = [];
        $hook = new after_merged_all_tables($this->user1->id, $this->user2->id, $logs, $errors);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches('/Can not find "assign" activity with id "\d+"/');

        regrading_after_merged_callback::regrade($hook);
    }

    /**
     * Test that exception is thrown when course module is missing.
     *
     * This validates the second existing exception check.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_regrade
     */
    public function test_regrade_throws_exception_when_course_module_missing(): void {
        global $DB;

        // Create an assignment with grades.
        $assign = $this->create_activity_with_grades('assign');

        // Get the course module and delete it.
        $cm = get_coursemodule_from_instance('assign', $assign->id, $this->course->id);
        $DB->delete_records('course_modules', ['id' => $cm->id]);

        // Execute regrading callback and expect exception.
        $logs = [];
        $errors = [];
        $hook = new after_merged_all_tables($this->user1->id, $this->user2->id, $logs, $errors);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches(
            '/Can not find the course module for module "assign", activity\.id "\d+" and course\.id "\d+"/'
        );

        regrading_after_merged_callback::regrade($hook);
    }

    /**
     * Test that regrading succeeds with valid data.
     *
     * This validates the normal operation path.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_regrade
     */
    public function test_regrade_succeeds_with_valid_data(): void {
        // Create an assignment with grades.
        $assign = $this->create_activity_with_grades('assign');

        // Execute regrading callback.
        $logs = [];
        $errors = [];
        $hook = new after_merged_all_tables($this->user1->id, $this->user2->id, $logs, $errors);

        regrading_after_merged_callback::regrade($hook);

        // Verify: Logs should contain regrading success message.
        $this->assertNotEmpty($logs, 'Logs should be generated for successful regrade');
        $foundlog = false;
        foreach ($logs as $log) {
            if (strpos($log, 'Regraded grade item with id') !== false) {
                $foundlog = true;
                break;
            }
        }
        $this->assertTrue($foundlog, 'Log should contain regrading success message');
        $this->assertEmpty($errors, 'No errors should be generated');
    }

    /**
     * Test that regrading filters out uninstalled modules at SQL level.
     *
     * This validates that the SQL INNER JOIN with {modules} correctly excludes
     * grade items from uninstalled plugins, preventing any processing or logging
     * for those items.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_regrade
     */
    public function test_regrade_filters_uninstalled_module(): void {
        global $DB;

        // Create a valid assignment with grades.
        $assign = $this->create_activity_with_grades('assign', 'Assignment Test');

        // Create a data activity with grades.
        $data = $this->create_activity_with_grades('data', 'Database Test');

        // Uninstall the data module (simulate uninstalled plugin).
        $DB->delete_records('modules', ['name' => 'data']);

        // Execute regrading callback.
        $logs = [];
        $errors = [];
        $hook = new after_merged_all_tables($this->user1->id, $this->user2->id, $logs, $errors);

        regrading_after_merged_callback::regrade($hook);

        // Verify: Assignment was processed.
        $foundassign = false;
        foreach ($logs as $log) {
            if (strpos($log, 'Regraded grade item') !== false && strpos($log, 'assign') !== false) {
                $foundassign = true;
            }
            // Data module should NOT appear in logs (filtered by SQL).
            $this->assertStringNotContainsString('data', $log, 'Data module should be filtered by SQL');
        }

        $this->assertTrue($foundassign, 'Assignment should be processed and logged');
        $this->assertEmpty($errors, 'No errors should be generated');
    }

    /**
     * Test that regrading processes multiple valid modules correctly.
     *
     * This validates that when multiple activities from the same or different
     * modules exist, all valid ones are processed and logged correctly.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_regrade
     */
    public function test_regrade_processes_multiple_valid_modules(): void {
        // Create two assignments with grades.
        $assign1 = $this->create_activity_with_grades('assign', 'Assignment 1');
        $assign2 = $this->create_activity_with_grades('assign', 'Assignment 2');

        // Execute regrading callback.
        $logs = [];
        $errors = [];
        $hook = new after_merged_all_tables($this->user1->id, $this->user2->id, $logs, $errors);

        regrading_after_merged_callback::regrade($hook);

        // Count how many grade items were regraded.
        $regradecount = 0;
        foreach ($logs as $log) {
            if (strpos($log, 'Regraded grade item') !== false) {
                $regradecount++;
            }
        }

        // Both assignments should be processed.
        $this->assertEquals(2, $regradecount, 'Both assignments should be regraded');
        $this->assertEmpty($errors, 'No errors should be generated');
    }

    /**
     * Helper method to create an activity with grades for both test users.
     *
     * @param string $modulename Module name (e.g., 'assign', 'data')
     * @param string $activityname Optional activity name
     * @return object The created activity instance
     */
    private function create_activity_with_grades(string $modulename, string $activityname = ''): object {
        global $DB;

        if (empty($activityname)) {
            $activityname = ucfirst($modulename) . ' Test';
        }

        // Set admin user to have permissions for creating grades.
        $this->setAdminUser();

        // Create the activity using create_module (grade_item is auto-created).
        $activity = $this->getDataGenerator()->create_module($modulename, [
            'course' => $this->course->id,
            'name' => $activityname,
            'grade' => 100, // Enable grading.
        ]);

        // Fetch the auto-created grade item.
        $gradeitem = grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => $modulename,
            'iteminstance' => $activity->id,
            'courseid' => $this->course->id,
        ]);

        // Create grades for both users if grade item exists.
        if ($gradeitem) {
            // Use grade_update to create grades (more reliable than direct creation).
            require_once(__DIR__ . '/../../../../lib/gradelib.php');

            grade_update(
                'mod/' . $modulename,
                $this->course->id,
                'mod',
                $modulename,
                $activity->id,
                0,
                ['userid' => $this->user1->id, 'rawgrade' => 75.0]
            );

            grade_update(
                'mod/' . $modulename,
                $this->course->id,
                'mod',
                $modulename,
                $activity->id,
                0,
                ['userid' => $this->user2->id, 'rawgrade' => 85.0]
            );
        }

        return $activity;
    }
}

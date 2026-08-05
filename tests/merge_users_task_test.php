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

use advanced_testcase;
use core\task\manager;
use tool_mergeusers\task\merge_users_task;
use tool_mergeusers\local\logger;

/**
 * Tests for adhoc task queuing based on setting flag.
 *
 * @package     tool_mergeusers
 * @copyright   10/11/2025 LdesignMedia.nl - Luuk Verhoeven
 * @author      Nihaal Shaikh
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class merge_users_task_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that adhoc task is queued when enableadhocmerge setting is enabled.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_adhoc_task_queued_when_enabled(): void {
        global $DB, $USER;

        // Enable adhoc merge setting.
        set_config('enableadhocmerge', 1, 'tool_mergeusers');

        // Create test users.
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        // Create a pending log entry.
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);

        // Verify no tasks queued initially.
        $initialcount = $DB->count_records('task_adhoc', ['classname' => '\\tool_mergeusers\\task\\merge_users_task']);
        $this->assertEquals(0, $initialcount);

        // Queue the adhoc task (mimicking index.php behavior).
        $task = new merge_users_task();
        $task->set_custom_data([
            'toid' => $touser->id,
            'fromid' => $fromuser->id,
            'logid' => $logid,
        ]);
        $task->set_userid($USER->id);
        manager::queue_adhoc_task($task);

        // Assert that the task is now queued.
        $queuedcount = $DB->count_records('task_adhoc', ['classname' => '\\tool_mergeusers\\task\\merge_users_task']);
        $this->assertEquals(1, $queuedcount);
    }

    /**
     * Test that adhoc task is NOT queued when enableadhocmerge setting is disabled.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_adhoc_task_not_queued_when_disabled(): void {
        global $DB, $USER;

        // Disable adhoc merge setting.
        set_config('enableadhocmerge', 0, 'tool_mergeusers');

        // Create test users.
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        // Verify no tasks queued initially.
        $initialcount = $DB->count_records('task_adhoc', ['classname' => '\\tool_mergeusers\\task\\merge_users_task']);
        $this->assertEquals(0, $initialcount);

        // Check the setting (mimicking index.php behavior).
        $adhocenabled = (bool)(int)get_config('tool_mergeusers', 'enableadhocmerge');

        // Only queue task if enabled (this should NOT happen).
        if ($adhocenabled) {
            $logger = new logger();
            $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);

            $task = new merge_users_task();
            $task->set_custom_data([
                'toid' => $touser->id,
                'fromid' => $fromuser->id,
                'logid' => $logid,
            ]);
            $task->set_userid($USER->id);
            manager::queue_adhoc_task($task);
        }

        // Assert that no task was queued (setting was disabled).
        $queuedcount = $DB->count_records('task_adhoc', ['classname' => '\\tool_mergeusers\\task\\merge_users_task']);
        $this->assertEquals(0, $queuedcount);

        // Verify the setting is actually disabled.
        $this->assertFalse($adhocenabled);
    }

    /**
     * Test that the task's concurrency limit defaults to 1 with no configuration needed.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_get_concurrency_limit_defaults_to_one(): void {
        $task = new merge_users_task();

        $this->assertEquals(1, $task->get_concurrency_limit());
    }

    /**
     * Test that an administrator can override the concurrency limit via config.php.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_get_concurrency_limit_can_be_overridden_via_cfg(): void {
        global $CFG;

        $CFG->task_concurrency_limit = [
            merge_users_task::class => 5,
        ];

        $task = new merge_users_task();

        $this->assertEquals(5, $task->get_concurrency_limit());
    }

    /**
     * Test that a failed merge task is never retried by Moodle's task manager.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_retry_until_success_returns_false(): void {
        $task = new merge_users_task();

        $this->assertFalse($task->retry_until_success());
    }

    /**
     * Test that a queued task only has one attempt available, so Moodle's task
     * manager will never retry it after a failure.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_queued_task_has_only_one_attempt_available(): void {
        global $DB, $USER;

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);

        $task = new merge_users_task();
        $task->set_custom_data([
            'toid' => $touser->id,
            'fromid' => $fromuser->id,
            'logid' => $logid,
        ]);
        $task->set_userid($USER->id);
        manager::queue_adhoc_task($task);

        $record = $DB->get_record('task_adhoc', ['classname' => '\\tool_mergeusers\\task\\merge_users_task'], '*', MUST_EXIST);

        $this->assertEquals(1, $record->attemptsavailable);
    }
}

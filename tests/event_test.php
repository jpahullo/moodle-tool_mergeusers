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
use tool_mergeusers\event\user_merged_success;
use tool_mergeusers\event\user_merged_failure;
use tool_mergeusers\fixtures\after_merged_all_tables_callbacks;
use tool_mergeusers\task\merge_users_task;
use tool_mergeusers\local\logger;
use tool_mergeusers\local\user_merger;

/**
 * Tests for events triggered by adhoc task execution.
 *
 * @package     tool_mergeusers
 * @copyright   10/11/2025 LdesignMedia.nl - Luuk Verhoeven
 * @author      Nihaal Shaikh
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class event_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that user_merged_success event is triggered when adhoc task executes successfully.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\event\user_merged_success
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_user_merged_success_event_triggered_by_adhoc_task(): void {
        global $USER;

        // Enable adhoc merge setting.
        set_config('enableadhocmerge', 1, 'tool_mergeusers');

        // Create test users.
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        // Create a pending log entry.
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);

        // Create and queue the adhoc task.
        $task = new merge_users_task();
        $task->set_custom_data([
            'toid' => $touser->id,
            'fromid' => $fromuser->id,
            'logid' => $logid,
        ]);
        $task->set_userid($USER->id);

        // Start capturing events.
        $sink = $this->redirectEvents();

        // Execute the adhoc task. Buffer output to suppress mtrace.
        ob_start();
        $task->execute();
        ob_end_clean();

        // Get triggered events.
        $events = $sink->get_events();
        $sink->close();

        // Assert exactly one event was triggered.
        $this->assertCount(1, $events);

        // Assert it's the correct event type.
        $event = reset($events);
        $this->assertInstanceOf(user_merged_success::class, $event);

        // Assert event data is correct.
        $this->assertEquals($touser->id, $event->get_new_user_id());
        $this->assertEquals($fromuser->id, $event->get_old_user_id());
        $this->assertEquals($logid, $event->get_log_id());
    }

    /**
     * Test that user_merged_failure event is triggered when adhoc task execution fails.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\event\user_merged_failure
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_user_merged_failure_event_triggered_by_adhoc_task(): void {
        global $USER;

        // Enable adhoc merge setting.
        set_config('enableadhocmerge', 1, 'tool_mergeusers');

        // Create one valid user and one deleted user to force merge failure.
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();
        delete_user($fromuser);

        // Create a pending log entry.
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);

        // Create and queue the adhoc task.
        $task = new merge_users_task();
        $task->set_custom_data([
            'toid' => $touser->id,
            'fromid' => $fromuser->id,
            'logid' => $logid,
        ]);
        $task->set_userid($USER->id);

        // Start capturing events.
        $sink = $this->redirectEvents();

        // Execute the adhoc task (will fail due to deleted user). Buffer output to suppress mtrace.
        ob_start();
        $task->execute();
        ob_end_clean();

        // Get triggered events.
        $events = $sink->get_events();
        $sink->close();

        // Assert exactly one event was triggered.
        $this->assertCount(1, $events);

        // Assert it's the correct event type.
        $event = reset($events);
        $this->assertInstanceOf(user_merged_failure::class, $event);

        // Assert event data is correct.
        $this->assertEquals($touser->id, $event->get_new_user_id());
        $this->assertEquals($fromuser->id, $event->get_old_user_id());
        $this->assertEquals($logid, $event->get_log_id());
    }

    /**
     * Test that user_merged_failure fires exactly once when a PHP Error (not an Exception)
     * is thrown while merging, so it never escapes uncaught to cause a second failure event
     * elsewhere (e.g. from merge_users_task::execute()'s own catch block).
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\local\user_merger
     * @covers \tool_mergeusers\event\user_merged_failure
     */
    public function test_user_merged_failure_event_fires_exactly_once_when_merge_throws_error(): void {
        require_once(__DIR__ . '/fixtures/after_merged_all_tables_callbacks.php');
        \core\di::set(
            \core\hook\manager::class,
            \core\hook\manager::phpunit_get_instance([
                'tool_mergeusers' => __DIR__ . '/fixtures/after_merged_all_tables_hooks_with_throwable_error.php',
            ]),
        );

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $sink = $this->redirectEvents();

        $mut = new user_merger();
        [$success] = $mut->merge($touser->id, $fromuser->id);

        $events = $sink->get_events();
        $sink->close();

        $this->assertFalse($success);

        $failureevents = array_filter($events, fn ($event) => $event instanceof user_merged_failure);
        $this->assertCount(1, $failureevents);
    }
}

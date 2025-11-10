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
use tool_mergeusers\task\merge_users_task;
use tool_mergeusers\local\logger;

/**
 * Tests for events triggered by adhoc task execution.
 *
 * @package     tool_mergeusers
 * @copyright   10/11/2025 LdesignMedia.nl - Luuk Verhoeven
 * @author      Nihaal Shaikh
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

        // Execute the adhoc task.
        $task->execute();

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

        // Execute the adhoc task (will fail due to deleted user).
        $task->execute();

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
}

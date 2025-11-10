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
use tool_mergeusers\task\merge_users_task;
use tool_mergeusers\local\logger;

/**
 * Tests for notifications sent by adhoc task with different results.
 *
 * @package     tool_mergeusers
 * @copyright   10/11/2025 LdesignMedia.nl - Luuk Verhoeven
 * @author      Nihaal Shaikh
 */
final class notification_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that success notification is sent when adhoc task completes successfully.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_success_notification_sent_by_adhoc_task(): void {
        global $USER;

        // Create test users.
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        // Create a pending log entry.
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);

        // Create the adhoc task.
        $task = new merge_users_task();
        $task->set_custom_data([
            'toid' => $touser->id,
            'fromid' => $fromuser->id,
            'logid' => $logid,
        ]);
        $task->set_userid($USER->id);

        // Start capturing messages.
        $sink = $this->redirectMessages();

        // Execute the adhoc task (will succeed).
        $task->execute();

        // Get sent messages.
        $messages = $sink->get_messages();
        $sink->close();

        // Assert exactly one message was sent.
        $this->assertCount(1, $messages);

        // Assert message has correct subject and component.
        $message = reset($messages);
        $this->assertEquals('[Merge Users] Merge completed successfully', $message->subject);
        $this->assertEquals('tool_mergeusers', $message->component);
    }

    /**
     * Test that error notification is sent when adhoc task execution fails.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_error_notification_sent_by_adhoc_task(): void {
        global $USER;

        // Create one valid user and one deleted user to force failure.
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();
        delete_user($fromuser);

        // Create a pending log entry.
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);

        // Create the adhoc task.
        $task = new merge_users_task();
        $task->set_custom_data([
            'toid' => $touser->id,
            'fromid' => $fromuser->id,
            'logid' => $logid,
        ]);
        $task->set_userid($USER->id);

        // Start capturing messages.
        $sink = $this->redirectMessages();

        // Execute the adhoc task (will fail due to deleted user).
        $task->execute();

        // Get sent messages.
        $messages = $sink->get_messages();
        $sink->close();

        // Assert exactly one message was sent.
        $this->assertCount(1, $messages);

        // Assert message has correct subject and component.
        $message = reset($messages);
        $this->assertEquals('[Merge Users] Merge completed with errors', $message->subject);
        $this->assertEquals('tool_mergeusers', $message->component);
    }
}

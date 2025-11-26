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

        // Set up admin user - this ensures $USER is properly initialized.
        $this->setAdminUser();
        $adminuserid = $USER->id;

        // Create test users.
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        // Create a pending log entry.
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $adminuserid);

        // Create the adhoc task.
        $task = new merge_users_task();
        $task->set_custom_data([
            'toid' => $touser->id,
            'fromid' => $fromuser->id,
            'logid' => $logid,
        ]);
        $task->set_userid($adminuserid);

        // Start capturing messages.
        $sink = $this->redirectMessages();

        // Execute the adhoc task (will succeed). Buffer output to suppress mtrace.
        ob_start();
        $task->execute();
        ob_end_clean();

        // Get sent messages.
        $messages = $sink->get_messages();
        $sink->close();

        // Assert exactly one message was sent.
        $this->assertCount(1, $messages);

        // Assert message has correct subject and component.
        $message = reset($messages);
        $this->assertEquals('[Merge Users] Merge completed successfully', $message->subject);
        $this->assertEquals('tool_mergeusers', $message->component);

        // Validate notification content includes expected information.
        $this->assertStringContainsString('merge of user', $message->fullmessage);
        $this->assertStringContainsString('completed successfully', $message->fullmessage);
        $this->assertStringContainsString($fromuser->firstname, $message->fullmessage);
        $this->assertStringContainsString($fromuser->lastname, $message->fullmessage);
        $this->assertStringContainsString($touser->firstname, $message->fullmessage);
        $this->assertStringContainsString($touser->lastname, $message->fullmessage);
        $this->assertStringContainsString((string)$logid, $message->fullmessage);

        // Validate recipient is the user who initiated the merge.
        $this->assertEquals($adminuserid, $message->useridto);
    }

    /**
     * Test that error notification is sent when adhoc task execution fails.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\task\merge_users_task
     */
    public function test_error_notification_sent_by_adhoc_task(): void {
        global $USER;

        // Set up admin user - this ensures $USER is properly initialized.
        $this->setAdminUser();
        $adminuserid = $USER->id;

        // Create one valid user and one deleted user to force failure.
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();
        delete_user($fromuser);

        // Create a pending log entry.
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $adminuserid);

        // Create the adhoc task.
        $task = new merge_users_task();
        $task->set_custom_data([
            'toid' => $touser->id,
            'fromid' => $fromuser->id,
            'logid' => $logid,
        ]);
        $task->set_userid($adminuserid);

        // Start capturing messages.
        $sink = $this->redirectMessages();

        // Execute the adhoc task (will fail due to deleted user). Buffer output to suppress mtrace.
        ob_start();
        $task->execute();
        ob_end_clean();

        // Get sent messages.
        $messages = $sink->get_messages();
        $sink->close();

        // Assert exactly one message was sent.
        $this->assertCount(1, $messages);

        // Assert message has correct subject and component.
        $message = reset($messages);
        $this->assertEquals('[Merge Users] Merge completed with errors', $message->subject);
        $this->assertEquals('tool_mergeusers', $message->component);

        // Validate notification content includes expected information.
        $this->assertStringContainsString('merge of user', $message->fullmessage);
        $this->assertStringContainsString('completed with errors', $message->fullmessage);
        // The fromuser is deleted, so their name won't appear - just "Deleted".
        $this->assertStringContainsString('Deleted', $message->fullmessage);
        $this->assertStringContainsString($touser->firstname, $message->fullmessage);
        $this->assertStringContainsString($touser->lastname, $message->fullmessage);
        $this->assertStringContainsString((string)$logid, $message->fullmessage);

        // Validate recipient is the user who initiated the merge.
        $this->assertEquals($adminuserid, $message->useridto);
    }
}

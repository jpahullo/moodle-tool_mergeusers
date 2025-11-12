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

}

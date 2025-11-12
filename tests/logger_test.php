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
use tool_mergeusers\local\logger;

/**
 * Tests for logger user snapshot functionality.
 *
 * @package     tool_mergeusers
 * @copyright   2025 LdesignMedia.nl
 * @author      Nihaal Shaikh
 */
final class logger_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that user snapshots are captured when creating pending log.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\local\logger::create_pending_log
     */
    public function test_user_snapshots_captured_in_pending_log(): void {
        global $DB, $USER;

        // Create test users with specific attributes.
        $touser = $this->getDataGenerator()->create_user([
            'username' => 'touser123',
            'email' => 'touser@example.com',
            'firstname' => 'To',
            'lastname' => 'User',
            'idnumber' => 'ID001',
            'suspended' => 0,
        ]);

        $fromuser = $this->getDataGenerator()->create_user([
            'username' => 'fromuser456',
            'email' => 'fromuser@example.com',
            'firstname' => 'From',
            'lastname' => 'User',
            'idnumber' => 'ID002',
            'suspended' => 1,
        ]);

        // Create a pending log entry.
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);

        // Retrieve the log record.
        $logrec = $DB->get_record('tool_mergeusers', ['id' => $logid], '*', MUST_EXIST);

        // Decode the log JSON.
        $logdata = json_decode($logrec->log, true);

        // Assert user snapshots exist.
        $this->assertArrayHasKey('user_snapshots', $logdata);
        $this->assertArrayHasKey('to_user', $logdata['user_snapshots']);
        $this->assertArrayHasKey('from_user', $logdata['user_snapshots']);

        // Verify to_user snapshot.
        $tosnapshot = (object)$logdata['user_snapshots']['to_user'];
        $this->assertEquals($touser->id, $tosnapshot->id);
        $this->assertEquals('touser123', $tosnapshot->username);
        $this->assertEquals('touser@example.com', $tosnapshot->email);
        $this->assertEquals('To', $tosnapshot->firstname);
        $this->assertEquals('User', $tosnapshot->lastname);
        $this->assertEquals('ID001', $tosnapshot->idnumber);
        $this->assertEquals(0, $tosnapshot->suspended);

        // Verify from_user snapshot.
        $fromsnapshot = (object)$logdata['user_snapshots']['from_user'];
        $this->assertEquals($fromuser->id, $fromsnapshot->id);
        $this->assertEquals('fromuser456', $fromsnapshot->username);
        $this->assertEquals('fromuser@example.com', $fromsnapshot->email);
        $this->assertEquals('From', $fromsnapshot->firstname);
        $this->assertEquals('User', $fromsnapshot->lastname);
        $this->assertEquals('ID002', $fromsnapshot->idnumber);
        $this->assertEquals(1, $fromsnapshot->suspended);
    }

    /**
     * Test that timecreated is set when creating pending log.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\local\logger::create_pending_log
     */
    public function test_timecreated_set_in_pending_log(): void {
        global $DB, $USER;

        // Create test users.
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $timebefore = time();

        // Create a pending log entry.
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);

        $timeafter = time();

        // Retrieve the log record.
        $logrec = $DB->get_record('tool_mergeusers', ['id' => $logid], '*', MUST_EXIST);

        // Assert timecreated is set and within expected range.
        $this->assertNotEmpty($logrec->timecreated);
        $this->assertGreaterThanOrEqual($timebefore, $logrec->timecreated);
        $this->assertLessThanOrEqual($timeafter, $logrec->timecreated);

        // Assert timemodified is also set initially (same as timecreated for pending logs).
        $this->assertEquals($logrec->timecreated, $logrec->timemodified);
    }

    /**
     * Test that user snapshots are preserved when updating log status.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\local\logger::update_log_status
     */
    public function test_user_snapshots_preserved_on_update(): void {
        global $DB, $USER;

        // Create test users.
        $touser = $this->getDataGenerator()->create_user([
            'username' => 'touser123',
            'email' => 'touser@example.com',
        ]);
        $fromuser = $this->getDataGenerator()->create_user([
            'username' => 'fromuser456',
            'email' => 'fromuser@example.com',
        ]);

        // Create a pending log entry.
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);

        // Update the log status.
        $actions = ['Action 1', 'Action 2'];
        $logger->update_log_status($logid, 'success', $actions);

        // Retrieve the updated log record.
        $logrec = $DB->get_record('tool_mergeusers', ['id' => $logid], '*', MUST_EXIST);
        $logdata = json_decode($logrec->log, true);

        // Assert user snapshots are still present.
        $this->assertArrayHasKey('user_snapshots', $logdata);
        $this->assertArrayHasKey('to_user', $logdata['user_snapshots']);
        $this->assertArrayHasKey('from_user', $logdata['user_snapshots']);

        // Assert actions were added.
        $this->assertArrayHasKey('actions', $logdata);
        $this->assertEquals($actions, $logdata['actions']);

        // Verify snapshot data is unchanged.
        $tosnapshot = (object)$logdata['user_snapshots']['to_user'];
        $this->assertEquals('touser123', $tosnapshot->username);
        $this->assertEquals('touser@example.com', $tosnapshot->email);
    }

    /**
     * Test that user snapshots are captured in immediate merge logs.
     *
     * @group tool_mergeusers
     * @covers \tool_mergeusers\local\logger::log
     */
    public function test_user_snapshots_captured_in_immediate_log(): void {
        global $DB;

        // Create test users.
        $touser = $this->getDataGenerator()->create_user([
            'username' => 'touser789',
            'email' => 'touser789@example.com',
        ]);
        $fromuser = $this->getDataGenerator()->create_user([
            'username' => 'fromuser101',
            'email' => 'fromuser101@example.com',
        ]);

        // Create an immediate log entry.
        $logger = new logger();
        $actions = ['Merge action 1', 'Merge action 2'];
        $logid = $logger->log($touser->id, $fromuser->id, true, $actions);

        // Retrieve the log record.
        $logrec = $DB->get_record('tool_mergeusers', ['id' => $logid], '*', MUST_EXIST);
        $logdata = json_decode($logrec->log, true);

        // Assert user snapshots exist.
        $this->assertArrayHasKey('user_snapshots', $logdata);
        $this->assertArrayHasKey('to_user', $logdata['user_snapshots']);
        $this->assertArrayHasKey('from_user', $logdata['user_snapshots']);

        // Assert actions exist.
        $this->assertArrayHasKey('actions', $logdata);
        $this->assertEquals($actions, $logdata['actions']);

        // Verify snapshots contain correct data.
        $tosnapshot = (object)$logdata['user_snapshots']['to_user'];
        $this->assertEquals('touser789', $tosnapshot->username);
        $this->assertEquals('touser789@example.com', $tosnapshot->email);

        $fromsnapshot = (object)$logdata['user_snapshots']['from_user'];
        $this->assertEquals('fromuser101', $fromsnapshot->username);
        $this->assertEquals('fromuser101@example.com', $fromsnapshot->email);

        // Assert timecreated is set.
        $this->assertNotEmpty($logrec->timecreated);
    }
}

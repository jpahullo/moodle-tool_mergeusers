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
 * Tests for the logger class, especially the normalized user snapshot shape.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers;

use advanced_testcase;
use tool_mergeusers\local\logger;

/**
 * Tests for tool_mergeusers\local\logger.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_mergeusers\local\logger
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
     * Test that a non-positive id is captured as "notfound", never a bare null.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_capture_user_snapshot_marks_zero_or_negative_id_as_notfound(): void {
        foreach ([0, -1] as $userid) {
            $snapshot = logger::capture_user_snapshot($userid);

            $this->assertTrue($snapshot->notfound);
            $this->assertFalse($snapshot->recoverable);
            $this->assertNull($snapshot->id);
            $this->assertNull($snapshot->username);
        }
    }

    /**
     * Test that a real id with no matching {user} row is captured as unrecoverable,
     * distinct from notfound.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_capture_user_snapshot_marks_vanished_id_as_unrecoverable(): void {
        $snapshot = logger::capture_user_snapshot(999999);

        $this->assertFalse($snapshot->notfound);
        $this->assertFalse($snapshot->recoverable);
        $this->assertSame(999999, $snapshot->id);
        $this->assertNull($snapshot->username);
    }

    /**
     * Test that an existing user is captured with full, recoverable data.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_capture_user_snapshot_captures_existing_user(): void {
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'ID001']);

        $snapshot = logger::capture_user_snapshot($user->id);

        $this->assertFalse($snapshot->notfound);
        $this->assertTrue($snapshot->recoverable);
        $this->assertSame((int) $user->id, $snapshot->id);
        $this->assertSame($user->username, $snapshot->username);
        $this->assertSame($user->email, $snapshot->email);
        $this->assertSame('ID001', $snapshot->idnumber);
        $this->assertFalse($snapshot->suspended);
        $this->assertFalse($snapshot->deleted);
    }

    /**
     * Test that capturing both sides of a merge shares a single timemodified value.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_capture_user_snapshots_shares_a_single_timemodified(): void {
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $snapshots = logger::capture_user_snapshots($touser->id, $fromuser->id);

        $this->assertArrayHasKey('timemodified', $snapshots);
        $this->assertIsInt($snapshots['timemodified']);
        $this->assertSame((int) $touser->id, $snapshots['to_user']->id);
        $this->assertSame((int) $fromuser->id, $snapshots['from_user']->id);
    }

    /**
     * Test that log() stores the normalized shape, including the shared timemodified,
     * inside the log JSON column.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_log_stores_normalized_user_snapshots(): void {
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $mut = new logger();
        $logid = $mut->log($touser->id, $fromuser->id, true, ['Some action.']);

        $this->assertNotFalse($logid);

        $stored = $mut->detail_from($logid);
        $snapshots = $stored->log->user_snapshots;

        $this->assertIsInt($snapshots->timemodified);
        $this->assertTrue($snapshots->to_user->recoverable);
        $this->assertTrue($snapshots->from_user->recoverable);
        $this->assertSame($touser->username, $snapshots->to_user->username);
        $this->assertSame($fromuser->username, $snapshots->from_user->username);
    }
}

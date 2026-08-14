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
use tool_mergeusers\local\origin;

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

    /**
     * Test that notfound_snapshot() populates the matching existing field with the
     * searched value, without introducing any new key, when given an allowed field.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_notfound_snapshot_reuses_matching_field_for_allowed_searched_field(): void {
        $snapshot = logger::notfound_snapshot(logger::SEARCHED_FIELD_EMAIL, 'x@example.com');

        $this->assertTrue($snapshot->notfound);
        $this->assertFalse($snapshot->recoverable);
        $this->assertNull($snapshot->id);
        $this->assertSame('x@example.com', $snapshot->email);
        $this->assertNull($snapshot->username);
        $this->assertNull($snapshot->idnumber);
        $this->assertNull($snapshot->firstname);
        $this->assertNull($snapshot->lastname);
        $this->assertNull($snapshot->suspended);
        $this->assertNull($snapshot->deleted);
        $this->assertFalse($snapshot->erasedforgdpr);
        $this->assertNull($snapshot->timeerased);

        // Same object shape as the base notfound_snapshot(): no new keys anywhere.
        $this->assertSame(
            array_keys((array) logger::notfound_snapshot()),
            array_keys((array) $snapshot),
        );
    }

    /**
     * Test that a field name outside the allowed set (username/idnumber/email) is
     * silently ignored: the snapshot stays identical to the no-hint case.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_notfound_snapshot_ignores_disallowed_searched_field(): void {
        $withhint = logger::notfound_snapshot('firstname', 'Jane');
        $withouthint = logger::notfound_snapshot();

        $this->assertEquals($withouthint, $withhint);
    }

    /**
     * Test that capture_user_snapshot() propagates a hint into notfound_snapshot()
     * when the id is not positive, and that omitting the hint entirely (as every
     * call site predating this feature does) is unaffected.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_capture_user_snapshot_propagates_hint_when_not_found(): void {
        $snapshot = logger::capture_user_snapshot(0, ['field' => logger::SEARCHED_FIELD_USERNAME, 'value' => 'jsmith123']);
        $this->assertTrue($snapshot->notfound);
        $this->assertSame('jsmith123', $snapshot->username);

        $nohint = logger::capture_user_snapshot(0);
        $this->assertNull($nohint->username);
    }

    /**
     * Test that a hint for a side that DOES resolve to a real user prevails over the
     * live value for that one field only - the real-world case being a gathering that
     * renames a username in place on a single account (no real merge needed) but still
     * reports what the old username was, so the "from" side of the log can show it.
     * Every other field must keep reflecting the real, live user.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_capture_user_snapshot_hint_prevails_for_a_resolved_user(): void {
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'ID001']);

        $snapshot = logger::capture_user_snapshot(
            $user->id,
            ['field' => logger::SEARCHED_FIELD_USERNAME, 'value' => 'oldusername'],
        );

        $this->assertFalse($snapshot->notfound);
        $this->assertTrue($snapshot->recoverable);
        $this->assertSame('oldusername', $snapshot->username);
        $this->assertSame((int) $user->id, $snapshot->id);
        $this->assertSame($user->email, $snapshot->email);
        $this->assertSame('ID001', $snapshot->idnumber);
        $this->assertFalse($snapshot->suspended);
        $this->assertFalse($snapshot->deleted);
    }

    /**
     * Test that a disallowed field name on a resolved user's hint is silently
     * ignored, same as for a notfound side.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_capture_user_snapshot_ignores_disallowed_hint_field_for_a_resolved_user(): void {
        $user = $this->getDataGenerator()->create_user();

        $snapshot = logger::capture_user_snapshot($user->id, ['field' => 'firstname', 'value' => 'Jane']);

        $this->assertSame($user->username, $snapshot->username);
        $this->assertSame($user->firstname, $snapshot->firstname);
    }

    /**
     * Test that a hint also prevails for an unrecoverable side (a real id with no
     * live {user} row), for consistency with both the notfound and resolved cases.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_capture_user_snapshot_hint_prevails_for_unrecoverable_id(): void {
        $snapshot = logger::capture_user_snapshot(
            999999,
            ['field' => logger::SEARCHED_FIELD_EMAIL, 'value' => 'old@example.com'],
        );

        $this->assertFalse($snapshot->notfound);
        $this->assertFalse($snapshot->recoverable);
        $this->assertSame(999999, $snapshot->id);
        $this->assertSame('old@example.com', $snapshot->email);
    }

    /**
     * Test that log() forwards optional to/from hints through to the stored snapshot,
     * and that calling it exactly as every existing call site does (no hints) leaves
     * the stored snapshot unchanged from before this feature.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_log_forwards_optional_hints_and_stays_compatible_without_them(): void {
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();

        $mut = new logger();
        $logid = $mut->log(
            $touser->id,
            0,
            false,
            ['Could not resolve username.'],
            null,
            null,
            ['field' => logger::SEARCHED_FIELD_USERNAME, 'value' => 'jsmith123'],
        );
        $stored = $mut->detail_from($logid);
        $this->assertSame('jsmith123', $stored->log->user_snapshots->from_user->username);

        $legacylogid = $mut->log($touser->id, 0, false, ['Could not resolve username.']);
        $legacystored = $mut->detail_from($legacylogid);
        $this->assertNull($legacystored->log->user_snapshots->from_user->username);
    }

    /**
     * Test that create_pending_log() forwards optional hints the same way log() does,
     * and stays backward compatible when they are omitted - needed for a rename
     * request (issue #250), where there is no real "to" user to snapshot, only what
     * was searched for.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_create_pending_log_forwards_optional_hints_and_stays_compatible_without_them(): void {
        $fromuser = $this->getDataGenerator()->create_user();

        $mut = new logger();
        $logid = $mut->create_pending_log(
            0,
            $fromuser->id,
            0,
            ['field' => logger::SEARCHED_FIELD_USERNAME, 'value' => 'newusername'],
        );
        $stored = $mut->detail_from($logid);
        $this->assertSame('newusername', $stored->log->user_snapshots->to_user->username);
        $this->assertSame($fromuser->username, $stored->log->user_snapshots->from_user->username);

        $legacylogid = $mut->create_pending_log(0, $fromuser->id, 0);
        $legacystored = $mut->detail_from($legacylogid);
        $this->assertNull($legacystored->log->user_snapshots->to_user->username);
    }

    /**
     * Test that live_user_or_deleted_placeholder() returns the real {user} record
     * when it still exists.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_live_user_or_deleted_placeholder_returns_live_user_when_it_exists(): void {
        $user = $this->getDataGenerator()->create_user();

        $result = logger::live_user_or_deleted_placeholder($user->id);

        $this->assertSame((int) $user->id, (int) $result->id);
        $this->assertSame($user->username, $result->username);
        $this->assertObjectHasProperty('email', $result);
    }

    /**
     * Test that live_user_or_deleted_placeholder() returns a synthetic "deleted"
     * placeholder, carrying only id/username/deleted, when the id does not match
     * any {user} record.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_live_user_or_deleted_placeholder_returns_placeholder_when_missing(): void {
        $result = logger::live_user_or_deleted_placeholder(999999);

        $this->assertSame(999999, $result->id);
        $this->assertSame(get_string('deleted'), $result->username);
        $this->assertSame(1, $result->deleted);
    }

    /**
     * Test that log() defaults to a WEB origin when none is given, matching every
     * caller that predates the origin parameter.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_log_defaults_to_web_origin(): void {
        $this->setAdminUser();
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $logid = (new logger())->log($touser->id, $fromuser->id, true, ['ok']);

        global $DB;
        $this->assertSame(origin::WEB->value, $DB->get_field('tool_mergeusers', 'origin', ['id' => $logid]));
    }

    /**
     * Test that log() stores an explicitly given origin.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_log_stores_explicit_origin(): void {
        $this->setAdminUser();
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $logid = (new logger())->log($touser->id, $fromuser->id, true, ['ok'], origin: origin::CLI);

        global $DB;
        $this->assertSame(origin::CLI->value, $DB->get_field('tool_mergeusers', 'origin', ['id' => $logid]));
    }

    /**
     * Test that create_pending_log() defaults to a WEB origin when none is given.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_create_pending_log_defaults_to_web_origin(): void {
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $logid = (new logger())->create_pending_log($touser->id, $fromuser->id, 2);

        global $DB;
        $this->assertSame(origin::WEB->value, $DB->get_field('tool_mergeusers', 'origin', ['id' => $logid]));
    }

    /**
     * Test that create_pending_log() stores an explicitly given origin.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_create_pending_log_stores_explicit_origin(): void {
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $logid = (new logger())->create_pending_log($touser->id, $fromuser->id, 2, origin: origin::WS);

        global $DB;
        $this->assertSame(origin::WS->value, $DB->get_field('tool_mergeusers', 'origin', ['id' => $logid]));
    }

    /**
     * Test that update_log_status() never changes an already-recorded origin - it is
     * set once at creation and must stay that way for the life of the log entry.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_update_log_status_never_changes_origin(): void {
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $logid = $logger->create_pending_log($touser->id, $fromuser->id, 2, origin: origin::CLI);

        $logger->update_log_status($logid, 'success', ['done']);

        global $DB;
        $this->assertSame(origin::CLI->value, $DB->get_field('tool_mergeusers', 'origin', ['id' => $logid]));
    }

    /**
     * Test that retarget_pending_log() never changes an already-recorded origin either.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_retarget_pending_log_never_changes_origin(): void {
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $logid = $logger->create_pending_log(
            0,
            $fromuser->id,
            2,
            ['field' => 'id', 'value' => (string) $touser->id],
            origin: origin::WS,
        );

        $logger->retarget_pending_log($logid, $touser->id);

        global $DB;
        $this->assertSame(origin::WS->value, $DB->get_field('tool_mergeusers', 'origin', ['id' => $logid]));
    }
}

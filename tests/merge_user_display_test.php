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
 * Tests for merge_user_display.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers;

use advanced_testcase;
use tool_mergeusers\local\logger;
use tool_mergeusers\local\merge_user_display;

/**
 * Tests for tool_mergeusers\local\merge_user_display.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_mergeusers\local\merge_user_display
 */
final class merge_user_display_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test the notfound case (id <= 0 at merge time).
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_from_snapshot_notfound(): void {
        $display = merge_user_display::from_snapshot(logger::notfound_snapshot());

        $this->assertTrue($display->notfound);
        $this->assertFalse($display->recoverable);
        $this->assertNull($display->profileurl);
        $this->assertNull($display->displayname);
        $this->assertNull($display->username);
        $this->assertNull($display->email);
        $this->assertNull($display->idnumber);
    }

    /**
     * Test the notfound case when the snapshot carries a searched-field hint: only
     * the matching field is exposed, the rest stay null.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_from_snapshot_notfound_with_searched_field_hint(): void {
        $display = merge_user_display::from_snapshot(logger::notfound_snapshot('idnumber', 'ID999'));

        $this->assertTrue($display->notfound);
        $this->assertSame('ID999', $display->idnumber);
        $this->assertNull($display->username);
        $this->assertNull($display->email);
    }

    /**
     * Test the unrecoverable case (real id, but nothing left anywhere).
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_from_snapshot_unrecoverable(): void {
        $display = merge_user_display::from_snapshot(logger::unrecoverable_snapshot(999999));

        $this->assertFalse($display->notfound);
        $this->assertFalse($display->recoverable);
        $this->assertSame(999999, $display->id);
        $this->assertNull($display->profileurl);
        $this->assertFalse($display->erasedforgdpr);
        $this->assertNull($display->timeerased);
    }

    /**
     * Test a side erased by a privacy request: unrecoverable, but flagged and
     * dated distinctly from a plain "never had any data" side.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_from_snapshot_erased_for_gdpr(): void {
        $timeerased = time() - 3600;
        $snapshot = (object) [
            'notfound' => false,
            'recoverable' => false,
            'id' => 123,
            'username' => null,
            'email' => null,
            'firstname' => null,
            'lastname' => null,
            'idnumber' => null,
            'suspended' => null,
            'deleted' => null,
            'erasedforgdpr' => true,
            'timeerased' => $timeerased,
        ];

        $display = merge_user_display::from_snapshot($snapshot);

        $this->assertFalse($display->recoverable);
        $this->assertTrue($display->erasedforgdpr);
        $this->assertSame($timeerased, $display->timeerased);
    }

    /**
     * Test a recoverable snapshot for a live, non-deleted user: identity comes
     * from the snapshot (even if stale), but suspended/deleted and the profile
     * link reflect the CURRENT live state.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_from_snapshot_live_user_uses_current_suspended_and_link(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user(['suspended' => 0]);
        $snapshot = logger::capture_user_snapshot($user->id);

        // Change suspended live, after the snapshot was captured.
        $DB->set_field('user', 'suspended', 1, ['id' => $user->id]);

        $display = merge_user_display::from_snapshot($snapshot);

        $this->assertFalse($display->notfound);
        $this->assertTrue($display->recoverable);
        $this->assertSame($user->username, $display->username);
        $this->assertTrue($display->suspended, 'suspended must reflect the live value, not the stale snapshot');
        $this->assertFalse($display->deleted);
        $this->assertNotNull($display->profileurl);
    }

    /**
     * Test a recoverable snapshot for a user that is now deleted live: no profile
     * link, and deleted/suspended reflect the live (deleted) row.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_from_snapshot_live_user_deleted_has_no_link(): void {
        $user = $this->getDataGenerator()->create_user();
        $snapshot = logger::capture_user_snapshot($user->id);

        delete_user($user);

        $display = merge_user_display::from_snapshot($snapshot);

        $this->assertTrue($display->recoverable);
        $this->assertTrue($display->deleted);
        $this->assertNull($display->profileurl);
        // Identity fields still come from the pre-deletion snapshot.
        $this->assertSame($user->username, $display->username);
    }

    /**
     * Test a recoverable snapshot whose user no longer has any live row at all:
     * no link, and suspended/deleted fall back to the snapshot's last known values.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_from_snapshot_no_live_row_falls_back_to_snapshot_flags(): void {
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $snapshot = logger::capture_user_snapshot($user->id);

        global $DB;
        $DB->delete_records('user', ['id' => $user->id]);

        $display = merge_user_display::from_snapshot($snapshot);

        $this->assertTrue($display->recoverable);
        $this->assertNull($display->profileurl);
        $this->assertTrue($display->suspended);
        $this->assertSame($user->username, $display->username);
    }
}

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
 * Tests for the privacy provider's erasure of normalized user snapshots.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers;

use advanced_testcase;
use context_system;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use tool_mergeusers\local\logger;
use tool_mergeusers\privacy\provider;

/**
 * Tests for tool_mergeusers\privacy\provider erasure methods.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_mergeusers\privacy\provider
 */
final class privacy_provider_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Creates a merge log for the given pair, with a normal (non-adhoc) merge.
     *
     * @param int $touserid
     * @param int $fromuserid
     * @return int the created log id.
     */
    private function create_log(int $touserid, int $fromuserid): int {
        $this->setAdminUser();
        $mut = new logger();

        return $mut->log($touserid, $fromuserid, true, ['An action.']);
    }

    /**
     * Test that delete_data_for_user() erases only the matching side, marking
     * erasedforgdpr/timeerased, and leaves the other side untouched.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_privacy
     */
    public function test_delete_data_for_user_erases_only_the_matching_side(): void {
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $logid = $this->create_log($touser->id, $fromuser->id);

        $approvedlist = new approved_contextlist($fromuser, 'tool_mergeusers', [context_system::instance()->id]);
        provider::delete_data_for_user($approvedlist);

        $stored = (new logger())->detail_from($logid);
        $snapshots = $stored->log->user_snapshots;

        $this->assertFalse($snapshots->from_user->recoverable);
        $this->assertTrue($snapshots->from_user->erasedforgdpr);
        $this->assertIsInt($snapshots->from_user->timeerased);
        $this->assertNull($snapshots->from_user->username);
        $this->assertSame((int) $fromuser->id, $snapshots->from_user->id);

        // The other side is untouched.
        $this->assertTrue($snapshots->to_user->recoverable);
        $this->assertFalse($snapshots->to_user->erasedforgdpr);
        $this->assertSame($touser->username, $snapshots->to_user->username);
    }

    /**
     * Test that a notfound side (no real user id at merge time) is left alone,
     * since there is no personal data to erase.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_privacy
     */
    public function test_delete_data_for_user_leaves_notfound_side_untouched(): void {
        $touser = $this->getDataGenerator()->create_user();

        $logid = $this->create_log($touser->id, 0);

        $approvedlist = new approved_contextlist($touser, 'tool_mergeusers', [context_system::instance()->id]);
        provider::delete_data_for_user($approvedlist);

        $stored = (new logger())->detail_from($logid);
        $snapshots = $stored->log->user_snapshots;

        $this->assertTrue($snapshots->from_user->notfound);
        $this->assertFalse($snapshots->from_user->erasedforgdpr);
    }

    /**
     * Test that delete_data_for_users() erases the matching side for each user
     * in the approved list, across multiple logs.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_privacy
     */
    public function test_delete_data_for_users_erases_matching_sides(): void {
        $touser = $this->getDataGenerator()->create_user();
        $fromuser1 = $this->getDataGenerator()->create_user();
        $fromuser2 = $this->getDataGenerator()->create_user();

        $logid1 = $this->create_log($touser->id, $fromuser1->id);
        $logid2 = $this->create_log($touser->id, $fromuser2->id);

        $approvedlist = new approved_userlist(
            context_system::instance(),
            'tool_mergeusers',
            [$fromuser1->id, $fromuser2->id],
        );
        provider::delete_data_for_users($approvedlist);

        $stored1 = (new logger())->detail_from($logid1);
        $stored2 = (new logger())->detail_from($logid2);

        $this->assertTrue($stored1->log->user_snapshots->from_user->erasedforgdpr);
        $this->assertTrue($stored2->log->user_snapshots->from_user->erasedforgdpr);
        // Neither to_user side matches the erasure list.
        $this->assertTrue($stored1->log->user_snapshots->to_user->recoverable);
        $this->assertFalse($stored1->log->user_snapshots->to_user->erasedforgdpr);
    }

    /**
     * Test that delete_data_for_all_users_in_context() erases both sides of
     * every log.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_privacy
     */
    public function test_delete_data_for_all_users_in_context_erases_both_sides(): void {
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $logid = $this->create_log($touser->id, $fromuser->id);

        provider::delete_data_for_all_users_in_context(context_system::instance());

        $stored = (new logger())->detail_from($logid);
        $snapshots = $stored->log->user_snapshots;

        $this->assertTrue($snapshots->to_user->erasedforgdpr);
        $this->assertTrue($snapshots->from_user->erasedforgdpr);
        $this->assertIsInt($snapshots->timemodified, 'The shared capture timestamp itself is not personal data.');
    }
}

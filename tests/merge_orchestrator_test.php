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
 * Tests for the merge_orchestrator domain service.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers;

use advanced_testcase;
use tool_mergeusers\local\logger;
use tool_mergeusers\local\merge_orchestrator;
use tool_mergeusers\local\status;

/**
 * Tests for tool_mergeusers\local\merge_orchestrator.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \tool_mergeusers\local\merge_orchestrator
 */
final class merge_orchestrator_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test that request() with async=false merges synchronously: the log is
     * immediately final (success) and the removed user is already suspended.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_merges_synchronously_when_async_false(): void {
        global $DB, $USER;

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $result = (new merge_orchestrator())->request(
            'id',
            (string) $fromuser->id,
            'id',
            (string) $touser->id,
            $USER->id,
            false,
        );

        $this->assertTrue($result['ok']);
        $this->assertGreaterThan(0, $result['logid']);
        $this->assertSame(status::SUCCESS->value, $result['status']);
        $this->assertFalse($result['renamed']);
        $this->assertSame(1, (int) $DB->get_field('user', 'suspended', ['id' => $fromuser->id]));
    }

    /**
     * Test that request() with async=true queues the merge instead of running it:
     * the log stays pending and the removed user is untouched until cron runs.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_queues_merge_asynchronously_when_async_true(): void {
        global $DB, $USER;

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $result = (new merge_orchestrator())->request(
            'id',
            (string) $fromuser->id,
            'id',
            (string) $touser->id,
            $USER->id,
            true,
        );

        $this->assertTrue($result['ok']);
        $this->assertGreaterThan(0, $result['logid']);
        $this->assertSame(status::PENDING->value, $result['status']);
        $this->assertFalse($result['renamed']);
        $this->assertSame(0, (int) $DB->get_field('user', 'suspended', ['id' => $fromuser->id]));
    }

    /**
     * Test that omitting $async (null) follows the enableadhocmerge setting - on
     * means queued, matching the web UI's own default behaviour.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_follows_enableadhocmerge_setting_when_async_omitted(): void {
        global $USER;

        set_config('enableadhocmerge', 1, 'tool_mergeusers');
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $result = (new merge_orchestrator())->request('id', (string) $fromuser->id, 'id', (string) $touser->id, $USER->id);

        $this->assertSame(status::PENDING->value, $result['status']);
    }

    /**
     * Test that omitting $async (null) with enableadhocmerge off merges
     * synchronously, matching the web UI's own default behaviour.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_follows_disabled_enableadhocmerge_setting_when_async_omitted(): void {
        global $USER;

        set_config('enableadhocmerge', 0, 'tool_mergeusers');
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $result = (new merge_orchestrator())->request('id', (string) $fromuser->id, 'id', (string) $touser->id, $USER->id);

        $this->assertSame(status::SUCCESS->value, $result['status']);
    }

    /**
     * Test the #250 rename path: the "from" user's username is renamed in place,
     * logged with its own log id and a status of "renamed", and - critically - the
     * log's "from" snapshot preserves the ORIGINAL username, not the already-renamed
     * one, because the snapshot is captured before the rename happens.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_renames_when_target_missing_and_setting_enabled(): void {
        global $DB, $USER;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        $result = (new merge_orchestrator())->request('username', 'olduser', 'username', 'newuser', $USER->id);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['renamed']);
        $this->assertSame(status::RENAMED->value, $result['status']);
        $this->assertGreaterThan(0, $result['logid']);
        $this->assertSame('newuser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));

        $stored = (new logger())->detail_from($result['logid']);
        $this->assertSame(status::RENAMED->value, $stored->status);
        $this->assertSame('olduser', $stored->log->user_snapshots->from_user->username);
        $this->assertCount(1, $stored->log->actions);
        $this->assertStringContainsString('newuser', $stored->log->actions[0]);
    }

    /**
     * Test that a request whose "to" user does not exist yet at request time - and
     * would look like a #250 rename if evaluated right now - is still just queued like
     * any other request when $async is true, with nothing written or decided in place:
     * the username must stay untouched, and no rename/merge decision made, until the
     * task actually runs and evaluates it fresh (see resolve_and_act()). This is what
     * fixes a real ordering hazard: deciding and writing in place here could race an
     * earlier-queued task still acting on the very same "from" user.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_queues_deferred_instead_of_deciding_in_place_when_async_true(): void {
        global $DB, $USER;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        $result = (new merge_orchestrator())->request('username', 'olduser', 'username', 'newuser', $USER->id, true);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['renamed']);
        $this->assertSame(status::PENDING->value, $result['status']);
        $this->assertGreaterThan(0, $result['logid']);
        $this->assertSame('olduser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));
        $this->assertSame(
            1,
            $DB->count_records('task_adhoc', ['classname' => '\\' . \tool_mergeusers\task\merge_users_task::class]),
        );

        $stored = (new logger())->detail_from($result['logid']);
        $this->assertSame(status::PENDING->value, $stored->status);
        $this->assertSame('olduser', $stored->log->user_snapshots->from_user->username);
    }

    /**
     * Test that perform_rename() - the method the queued adhoc task calls - completes
     * the rename and finalizes the log the same way the synchronous path does.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_perform_rename_completes_a_previously_queued_rename(): void {
        global $DB, $USER;

        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);
        $logid = (new logger())->create_pending_log(
            0,
            $fromuser->id,
            $USER->id,
            ['field' => 'username', 'value' => 'newuser'],
        );

        $result = (new merge_orchestrator())->perform_rename($fromuser->id, 'username', 'newuser', $logid);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['renamed']);
        $this->assertSame(status::RENAMED->value, $result['status']);
        $this->assertSame('newuser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));

        $stored = (new logger())->detail_from($logid);
        $this->assertSame(status::RENAMED->value, $stored->status);
        $this->assertSame('olduser', $stored->log->user_snapshots->from_user->username);
    }

    /**
     * Test that a missing target user with the rename setting disabled is rejected,
     * and never leaves a log entry behind - only an actually-attempted rename does.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_does_not_rename_when_setting_disabled(): void {
        global $DB, $USER;

        set_config('renamewhenmissingtarget', 0, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        $result = (new merge_orchestrator())->request('username', 'olduser', 'username', 'newuser', $USER->id);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['logid']);
        $this->assertSame('olduser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));
        $this->assertEmpty((new logger())->get(['fromuserid' => $fromuser->id]));
    }

    /**
     * Test that an ambiguous "from" match is rejected, without attempting anything.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_rejects_ambiguous_fromuser(): void {
        global $USER;

        foreach ([1, 2] as $i) {
            $this->getDataGenerator()->create_user(['idnumber' => 'DUP']);
        }
        $touser = $this->getDataGenerator()->create_user();

        $result = (new merge_orchestrator())->request('idnumber', 'DUP', 'id', (string) $touser->id, $USER->id);

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Test that merging a user into itself is rejected.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_rejects_same_user(): void {
        global $USER;

        $user = $this->getDataGenerator()->create_user();

        $result = (new merge_orchestrator())->request('id', (string) $user->id, 'id', (string) $user->id, $USER->id);

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Test that a "from" user that does not exist is rejected.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_rejects_missing_fromuser(): void {
        global $USER;

        $touser = $this->getDataGenerator()->create_user();

        $result = (new merge_orchestrator())->request('username', 'doesnotexist', 'id', (string) $touser->id, $USER->id);

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Test that an ambiguous "to" match is rejected immediately, without ever
     * queuing anything - unlike existence, ambiguity is not expected to resolve
     * itself between now and task execution, so there is nothing to gain by deferring
     * it.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_rejects_ambiguous_touser_immediately(): void {
        global $USER;

        $fromuser = $this->getDataGenerator()->create_user();
        foreach ([1, 2] as $i) {
            $this->getDataGenerator()->create_user(['idnumber' => 'DUP']);
        }

        $result = (new merge_orchestrator())->request('id', (string) $fromuser->id, 'idnumber', 'DUP', $USER->id, true);

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['message']);
        $this->assertEmpty((new logger())->get(['fromuserid' => $fromuser->id]));
    }

    /**
     * Test that a missing "to" user is rejected immediately, without queuing, when the
     * field could never trigger a #250 rename regardless of the setting - it could not
     * possibly become anything but an error.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_rejects_missing_touser_immediately_for_ineligible_field(): void {
        global $USER;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['idnumber' => 'OLD1']);

        $result = (new merge_orchestrator())->request('idnumber', 'OLD1', 'idnumber', 'NEW1', $USER->id, true);

        $this->assertFalse($result['ok']);
        $this->assertEmpty((new logger())->get(['fromuserid' => $fromuser->id]));
    }

    /**
     * Test that a missing "to" user is rejected immediately, without queuing, when
     * renaming is currently disabled altogether - same reasoning as the ineligible
     * field case: nothing could make this succeed right now.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_rejects_missing_touser_immediately_when_setting_disabled(): void {
        global $USER;

        set_config('renamewhenmissingtarget', 0, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        $result = (new merge_orchestrator())->request('username', 'olduser', 'username', 'newuser', $USER->id, true);

        $this->assertFalse($result['ok']);
        $this->assertEmpty((new logger())->get(['fromuserid' => $fromuser->id]));
    }

    /**
     * Test that a successful queued request's result carries confirmation detail for
     * both users - the same information the web form's own review step shows - with
     * the "to" side describing that it does not exist yet and echoing the searched
     * value back.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_describes_both_users_when_queuing_a_missing_touser(): void {
        global $USER;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        $result = (new merge_orchestrator())->request('username', 'olduser', 'username', 'newuser', $USER->id, true);

        $this->assertSame((int) $fromuser->id, $result['fromuser']['id']);
        $this->assertSame('olduser', $result['fromuser']['username']);
        $this->assertSame($fromuser->email, $result['fromuser']['email']);
        $this->assertSame(0, $result['touser']['id']);
        $this->assertFalse($result['touser']['exists']);
        $this->assertSame('newuser', $result['touser']['username']);
        $this->assertNotEmpty($result['touser']['note']);
    }

    /**
     * Test that a successful queued request's result describes a "to" user that
     * already exists as fully resolved, with exists=true and an empty note.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_request_describes_both_users_when_queuing_a_real_merge(): void {
        global $USER;

        $fromuser = $this->getDataGenerator()->create_user();
        $touser = $this->getDataGenerator()->create_user();

        $result = (new merge_orchestrator())->request('id', (string) $fromuser->id, 'id', (string) $touser->id, $USER->id, true);

        $this->assertSame((int) $touser->id, $result['touser']['id']);
        $this->assertTrue($result['touser']['exists']);
        $this->assertSame('', $result['touser']['note']);
        $this->assertSame($touser->username, $result['touser']['username']);
    }

    /**
     * The central scenario resolve_and_act() exists for: a deferred request looked
     * like it would become a #250 rename when queued (the target username did not
     * exist yet), but a real user with that exact username shows up before the queued
     * task ever runs. Evaluated fresh, it must merge into that real user instead of
     * blindly renaming - deciding "rename" up front and only deferring the write would
     * get this wrong.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_resolve_and_act_merges_for_real_if_target_user_exists_by_execution_time(): void {
        global $DB, $USER;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        // At request time, "newuser" does not exist yet - this looks like a rename.
        $queued = (new merge_orchestrator())->request('username', 'olduser', 'username', 'newuser', $USER->id, true);
        $this->assertSame(status::PENDING->value, $queued['status']);

        // A real user with that exact username shows up before the task ever runs.
        $touser = $this->getDataGenerator()->create_user(['username' => 'newuser']);

        $final = (new merge_orchestrator())->resolve_and_act($fromuser->id, 'username', 'newuser', $queued['logid']);

        $this->assertTrue($final['ok']);
        $this->assertFalse($final['renamed']);
        $this->assertSame(status::SUCCESS->value, $final['status']);
        $this->assertSame((int) $touser->id, $final['touserid']);
        // No rename happened - the "from" user's own username is untouched.
        $this->assertSame('olduser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));
        // The merge itself did happen: the removed user ends up suspended.
        $this->assertSame(1, (int) $DB->get_field('user', 'suspended', ['id' => $fromuser->id]));

        $stored = (new logger())->detail_from($queued['logid']);
        $this->assertSame((int) $touser->id, (int) $stored->touserid);
        $this->assertTrue($stored->log->user_snapshots->to_user->recoverable);
    }

    /**
     * The other direction of the same principle: the tool_mergeusers/
     * renamewhenmissingtarget setting can be toggled off by an administrator between
     * queuing and execution. Evaluated fresh, the rename must fail then, not succeed
     * based on a setting value that no longer holds.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_resolve_and_act_fails_if_rename_setting_disabled_by_execution_time(): void {
        global $DB, $USER;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        $queued = (new merge_orchestrator())->request('username', 'olduser', 'username', 'newuser', $USER->id, true);

        // The administrator disables the setting before the task ever runs.
        set_config('renamewhenmissingtarget', 0, 'tool_mergeusers');

        $final = (new merge_orchestrator())->resolve_and_act($fromuser->id, 'username', 'newuser', $queued['logid']);

        $this->assertFalse($final['ok']);
        $this->assertSame('olduser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));

        $stored = (new logger())->detail_from($queued['logid']);
        $this->assertSame(status::ERROR->value, $stored->status);
    }

    /**
     * Test that resolve_and_act() performs the rename when the target is still
     * genuinely missing and renaming is still eligible by execution time - the common
     * case, nothing having changed since the request was queued.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_resolve_and_act_renames_when_target_still_missing_and_eligible(): void {
        global $DB, $USER;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);
        $queued = (new merge_orchestrator())->request('username', 'olduser', 'username', 'newuser', $USER->id, true);

        $final = (new merge_orchestrator())->resolve_and_act($fromuser->id, 'username', 'newuser', $queued['logid']);

        $this->assertTrue($final['ok']);
        $this->assertTrue($final['renamed']);
        $this->assertSame(status::RENAMED->value, $final['status']);
        $this->assertSame('newuser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));
    }

    /**
     * Test that resolve_and_act() rejects an ambiguous "to" match, marking the
     * already-existing log as an error.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_resolve_and_act_rejects_ambiguous_touser(): void {
        $fromuser = $this->getDataGenerator()->create_user();
        // Simulate a request queued before the ambiguity existed (request() itself
        // would reject this eagerly today) - resolve_and_act() must still guard
        // against it independently, evaluating fresh at execution time.
        $logid = (new logger())->create_pending_log(0, $fromuser->id, 2, ['field' => 'idnumber', 'value' => 'DUP']);
        foreach ([1, 2] as $i) {
            $this->getDataGenerator()->create_user(['idnumber' => 'DUP']);
        }

        $final = (new merge_orchestrator())->resolve_and_act($fromuser->id, 'idnumber', 'DUP', $logid);

        $this->assertFalse($final['ok']);
        $stored = (new logger())->detail_from($logid);
        $this->assertSame(status::ERROR->value, $stored->status);
    }

    /**
     * Test that resolve_and_act() rejects a "to" resolving to the same user as "from".
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_resolve_and_act_rejects_same_user(): void {
        $user = $this->getDataGenerator()->create_user();
        // Simulate a request queued before "to" and "from" happened to coincide -
        // request() itself would reject this eagerly today; resolve_and_act() must
        // still guard against it independently.
        $logid = (new logger())->create_pending_log(0, $user->id, 2, ['field' => 'id', 'value' => (string) $user->id]);

        $final = (new merge_orchestrator())->resolve_and_act($user->id, 'id', (string) $user->id, $logid);

        $this->assertFalse($final['ok']);
        $stored = (new logger())->detail_from($logid);
        $this->assertSame(status::ERROR->value, $stored->status);
    }

    /**
     * Test that resolve_and_act() fails gracefully, without throwing, when the "from"
     * user no longer exists by execution time (e.g. removed by an earlier merge).
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_orchestrator
     */
    public function test_resolve_and_act_fails_gracefully_if_fromuser_no_longer_exists(): void {
        $fromuser = $this->getDataGenerator()->create_user();
        $logid = (new logger())->create_pending_log(
            0,
            $fromuser->id,
            2,
            ['field' => 'username', 'value' => 'newuser'],
        );
        delete_user($fromuser);

        $final = (new merge_orchestrator())->resolve_and_act($fromuser->id, 'username', 'newuser', $logid);

        $this->assertFalse($final['ok']);
        $stored = (new logger())->detail_from($logid);
        $this->assertSame(status::ERROR->value, $stored->status);
    }
}

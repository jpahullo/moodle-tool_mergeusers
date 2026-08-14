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
}

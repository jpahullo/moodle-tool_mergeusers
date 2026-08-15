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

use tool_mergeusers\local\user_searcher;

/**
 * Tests for user_searcher's rename-on-missing-target support (#250).
 *
 * @package    tool_mergeusers
 * @author     Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright  2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_mergeusers\local\user_searcher
 */
final class user_searcher_rename_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * username is always a login identifier field; other fields never are, regardless of
     * authloginviaemail; email only counts when authloginviaemail is on.
     *
     * @param string $field
     * @param bool $authloginviaemail
     * @param bool $expected
     * @group tool_mergeusers
     * @group tool_mergeusers_rename
     * @dataProvider login_identifier_field_provider
     */
    public function test_is_login_identifier_field(string $field, bool $authloginviaemail, bool $expected): void {
        global $CFG;
        $CFG->authloginviaemail = $authloginviaemail ? 1 : 0;

        $this->assertSame($expected, (new user_searcher())->is_login_identifier_field($field));
    }

    /**
     * Data for test_is_login_identifier_field().
     *
     * @return array
     */
    public static function login_identifier_field_provider(): array {
        return [
            'username, authloginviaemail off' => ['username', false, true],
            'username, authloginviaemail on' => ['username', true, true],
            'email, authloginviaemail off' => ['email', false, false],
            'email, authloginviaemail on' => ['email', true, true],
            'idnumber, authloginviaemail on' => ['idnumber', true, false],
            'id, authloginviaemail on' => ['id', true, false],
        ];
    }

    /**
     * The setting must be off by default check: disabled setting never renames.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_rename
     */
    public function test_rename_if_eligible_does_nothing_when_setting_disabled(): void {
        set_config('renamewhenmissingtarget', 0, 'tool_mergeusers');
        $user = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        $renamed = (new user_searcher())->rename_if_eligible($user, 'username', 'newuser');

        $this->assertFalse($renamed);
        $this->assert_username($user->id, 'olduser');
    }

    /**
     * A field that is never a login identifier (idnumber) must never trigger a rename,
     * even with the setting on.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_rename
     */
    public function test_rename_if_eligible_does_nothing_for_ineligible_field(): void {
        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'OLD123']);

        $renamed = (new user_searcher())->rename_if_eligible($user, 'idnumber', 'NEW123');

        $this->assertFalse($renamed);
        $this->assert_idnumber($user->id, 'OLD123');
    }

    /**
     * The eligible, common case: username, setting enabled.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_rename
     */
    public function test_rename_if_eligible_renames_username(): void {
        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $user = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        $renamed = (new user_searcher())->rename_if_eligible($user, 'username', 'newuser');

        $this->assertTrue($renamed);
        $this->assert_username($user->id, 'newuser');
    }

    /**
     * email only renames when authloginviaemail is on.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_rename
     */
    public function test_rename_if_eligible_email_requires_authloginviaemail(): void {
        global $CFG, $DB;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $user = $this->getDataGenerator()->create_user(['email' => 'old@example.com']);

        $CFG->authloginviaemail = 0;
        $renamed = (new user_searcher())->rename_if_eligible($user, 'email', 'new@example.com');
        $this->assertFalse($renamed);
        $this->assertSame('old@example.com', $DB->get_field('user', 'email', ['id' => $user->id]));

        $CFG->authloginviaemail = 1;
        $renamed = (new user_searcher())->rename_if_eligible($user, 'email', 'new@example.com');
        $this->assertTrue($renamed);
        $this->assertSame('new@example.com', $DB->get_field('user', 'email', ['id' => $user->id]));
    }

    /**
     * Asserts $userid's stored username matches $expected.
     *
     * @param int $userid
     * @param string $expected
     */
    private function assert_username(int $userid, string $expected): void {
        global $DB;
        $this->assertSame($expected, $DB->get_field('user', 'username', ['id' => $userid]));
    }

    /**
     * Asserts $userid's stored idnumber matches $expected.
     *
     * @param int $userid
     * @param string $expected
     */
    private function assert_idnumber(int $userid, string $expected): void {
        global $DB;
        $this->assertSame($expected, $DB->get_field('user', 'idnumber', ['id' => $userid]));
    }
}

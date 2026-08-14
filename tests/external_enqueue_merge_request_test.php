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

use core_external\external_api;
use invalid_parameter_exception;
use required_capability_exception;
use tool_mergeusers\external\enqueue_merge_request;
use tool_mergeusers\local\logger;
use tool_mergeusers\local\merge_orchestrator;
use tool_mergeusers\local\origin;
use tool_mergeusers\local\profile_fields;
use tool_mergeusers\local\status;
use tool_mergeusers\task\merge_users_task;

/**
 * Tests for the tool_mergeusers_enqueue_merge_request web service.
 *
 * @package    tool_mergeusers
 * @author     Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright  2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_mergeusers\external\enqueue_merge_request
 */
final class external_enqueue_merge_request_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Calls the web service function through the same validation/return-cleaning path a
     * real web service request goes through.
     *
     * @param string $fromfield
     * @param string $fromvalue
     * @param string $tofield
     * @param string $tovalue
     * @return array
     */
    private function call(string $fromfield, string $fromvalue, string $tofield, string $tovalue): array {
        $result = enqueue_merge_request::execute($fromfield, $fromvalue, $tofield, $tovalue);
        return external_api::clean_returnvalue(enqueue_merge_request::execute_returns(), $result);
    }

    /**
     * The happy path: two existing users identified by username queue a pending merge.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_queues_merge_by_username(): void {
        $fromuser = $this->getDataGenerator()->create_user();
        $touser = $this->getDataGenerator()->create_user();

        $result = $this->call('username', $fromuser->username, 'username', $touser->username);

        $this->assertGreaterThan(0, $result['logid']);
        $this->assertSame(status::PENDING->value, $result['status']);
        $this->assertFalse($result['renamed']);
        $this->assertNotFalse((new logger())->get(['id' => $result['logid']]));
    }

    /**
     * Test that a request queued via this web service is recorded with a WS origin,
     * never the WEB default - this is the whole reason merge_orchestrator::request()
     * requires an explicit origin with no implicit default here.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_queued_request_records_ws_origin(): void {
        global $DB;

        $fromuser = $this->getDataGenerator()->create_user();
        $touser = $this->getDataGenerator()->create_user();

        $result = $this->call('username', $fromuser->username, 'username', $touser->username);

        $this->assertSame(origin::WS->value, $DB->get_field('tool_mergeusers', 'origin', ['id' => $result['logid']]));
    }

    /**
     * idnumber and id are also valid identifying fields.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_queues_merge_by_idnumber_and_id(): void {
        $this->getDataGenerator()->create_user(['idnumber' => 'F001']);
        $touser = $this->getDataGenerator()->create_user();

        $result = $this->call('idnumber', 'F001', 'id', (string) $touser->id);

        $this->assertGreaterThan(0, $result['logid']);
    }

    /**
     * An allow-listed custom profile field, identified by "profile_field_<shortname>"
     * rather than its internal database id, is a valid identifying field.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_queues_merge_by_allowed_profile_field(): void {
        global $DB;

        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'staffid', 'name' => 'Staff id', 'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        $fromuser = $this->getDataGenerator()->create_user();
        $DB->insert_record('user_info_data', (object) ['userid' => $fromuser->id, 'fieldid' => $fieldid, 'data' => 'S1']);
        $touser = $this->getDataGenerator()->create_user();

        $result = $this->call(profile_fields::FIELD_PREFIX . 'staffid', 'S1', 'id', (string) $touser->id);

        $this->assertGreaterThan(0, $result['logid']);
    }

    /**
     * The profile field's raw internal database id is never accepted, only
     * "profile_field_<shortname>" - the id is an environment-specific implementation
     * detail an external caller cannot be expected to know.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_raw_profile_field_id(): void {
        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'staffid', 'name' => 'Staff id', 'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');
        $touser = $this->getDataGenerator()->create_user();

        $this->expectException(invalid_parameter_exception::class);
        $this->call((string) $fieldid, 'S1', 'id', (string) $touser->id);
    }

    /**
     * A "profile_field_<shortname>" reference naming a shortname that does not exist
     * at all is rejected too, not just one that exists but is not allow-listed.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_unknown_profile_field_shortname(): void {
        $touser = $this->getDataGenerator()->create_user();

        $this->expectException(invalid_parameter_exception::class);
        $this->call(profile_fields::FIELD_PREFIX . 'doesnotexist', 'S1', 'id', (string) $touser->id);
    }

    /**
     * email is a valid field to identify a user in general, but not unique enough to be
     * offered by this web service - same restriction as merge_user_form.php.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_email_as_field(): void {
        $fromuser = $this->getDataGenerator()->create_user();
        $touser = $this->getDataGenerator()->create_user();

        $this->expectException(invalid_parameter_exception::class);
        $this->call('email', $fromuser->email, 'id', (string) $touser->id);
    }

    /**
     * A profile field not on the allow-list is rejected.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_non_allowlisted_profile_field(): void {
        $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'notallowed', 'name' => 'Not allowed', 'datatype' => 'text',
        ]);
        // Deliberately not added to tool_mergeusers/searchbyprofilefields.
        $this->getDataGenerator()->create_user();
        $touser = $this->getDataGenerator()->create_user();

        $this->expectException(invalid_parameter_exception::class);
        $this->call(profile_fields::FIELD_PREFIX . 'notallowed', 'whatever', 'id', (string) $touser->id);
    }

    /**
     * A "from" user that does not exist is rejected.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_missing_fromuser(): void {
        $touser = $this->getDataGenerator()->create_user();

        $this->expectException(invalid_parameter_exception::class);
        $this->call('username', 'doesnotexist', 'id', (string) $touser->id);
    }

    /**
     * A "from" field/value matching more than one user is rejected, distinctly from "not found".
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_ambiguous_fromuser(): void {
        foreach ([1, 2] as $i) {
            $this->getDataGenerator()->create_user(['idnumber' => 'DUP']);
        }
        $touser = $this->getDataGenerator()->create_user();

        $this->expectException(invalid_parameter_exception::class);
        $this->call('idnumber', 'DUP', 'id', (string) $touser->id);
    }

    /**
     * An ambiguous "to" user must never trigger the #250 rename path, even with the
     * setting enabled - only a genuinely missing "to" user is eligible. Ambiguity is
     * always rejected immediately, never queued: unlike existence, it is not the kind
     * of thing expected to resolve itself between now and task execution.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_ambiguous_touser_and_does_not_rename(): void {
        global $DB;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);
        foreach ([1, 2] as $i) {
            $this->getDataGenerator()->create_user(['idnumber' => 'DUP']);
        }

        try {
            $this->call('username', 'olduser', 'idnumber', 'DUP');
            $this->fail('Expected invalid_parameter_exception was not thrown.');
        } catch (invalid_parameter_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->assertSame('olduser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));
        $this->assertEmpty((new logger())->get(['fromuserid' => $fromuser->id]));
    }

    /**
     * Merging a user into itself is rejected immediately - both sides are always
     * resolved up front for this exact check, never queued for later.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_same_user(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->expectException(invalid_parameter_exception::class);
        $this->call('id', (string) $user->id, 'id', (string) $user->id);
    }

    /**
     * #250: a missing "to" user queues a rename of the "from" user's username instead
     * of failing - the web service always requests async processing, so the rename is
     * never written in place here: it is only queued, exactly like a real merge would
     * be, so a chained request affecting the same user is guaranteed to run after this
     * one, not race it. See merge_orchestrator::rename_or_error()'s docblock.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_queues_rename_when_target_missing_and_setting_enabled(): void {
        global $DB;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        $result = $this->call('username', 'olduser', 'username', 'newuser');

        $this->assertGreaterThan(0, $result['logid']);
        $this->assertSame('pending', $result['status']);
        $this->assertFalse($result['renamed']);
        $this->assertSame('olduser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));

        $stored = (new logger())->detail_from($result['logid']);
        $this->assertSame('pending', $stored->status);
        $this->assertSame('olduser', $stored->log->user_snapshots->from_user->username);
        $this->assertSame(1, $DB->count_records('task_adhoc', ['classname' => '\\' . merge_users_task::class]));
    }

    /**
     * With the setting disabled from the start, a missing "to" user is rejected
     * immediately, never queued - it could not possibly become a rename right now.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_missing_touser_immediately_when_setting_disabled(): void {
        global $DB;

        set_config('renamewhenmissingtarget', 0, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        try {
            $this->call('username', 'olduser', 'username', 'newuser');
            $this->fail('Expected invalid_parameter_exception was not thrown.');
        } catch (invalid_parameter_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->assertSame('olduser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));
        $this->assertEmpty((new logger())->get(['fromuserid' => $fromuser->id]));
    }

    /**
     * With the setting enabled when queued but disabled before the queued task ever
     * runs, the rename correctly fails then - the setting is only ever consulted for
     * real once the task actually executes, never trusted from queuing time.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_does_not_rename_when_setting_disabled_before_execution(): void {
        global $DB;

        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'olduser']);

        $result = $this->call('username', 'olduser', 'username', 'newuser');
        $this->assertSame('pending', $result['status']);

        // The administrator disables the setting before the queued task ever runs.
        set_config('renamewhenmissingtarget', 0, 'tool_mergeusers');

        $final = (new merge_orchestrator())->resolve_and_act($fromuser->id, 'username', 'newuser', $result['logid']);

        $this->assertFalse($final['ok']);
        $this->assertSame('olduser', $DB->get_field('user', 'username', ['id' => $fromuser->id]));
    }

    /**
     * idnumber is never a login identifier, so it must never trigger a rename, even with
     * the setting enabled - rejected immediately, since it could never become a rename.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_does_not_rename_via_idnumber_even_when_setting_enabled(): void {
        set_config('renamewhenmissingtarget', 1, 'tool_mergeusers');
        $this->getDataGenerator()->create_user(['idnumber' => 'OLD1']);

        $this->expectException(invalid_parameter_exception::class);
        $this->call('idnumber', 'OLD1', 'idnumber', 'NEW1');
    }

    /**
     * With duplicates allowed (the default), the same pair can be queued twice.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_allows_duplicate_pending_by_default(): void {
        set_config('wsallowduplicatepending', 1, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user();
        $touser = $this->getDataGenerator()->create_user();

        $result1 = $this->call('id', (string) $fromuser->id, 'id', (string) $touser->id);
        $result2 = $this->call('id', (string) $fromuser->id, 'id', (string) $touser->id);

        $this->assertNotSame($result1['logid'], $result2['logid']);
    }

    /**
     * With duplicates disallowed, a second request for the same "from" user returns the
     * existing pending logid instead of queuing a new one.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_returns_existing_pending_when_duplicates_disallowed(): void {
        set_config('wsallowduplicatepending', 0, 'tool_mergeusers');
        $fromuser = $this->getDataGenerator()->create_user();
        $touser = $this->getDataGenerator()->create_user();

        $result1 = $this->call('id', (string) $fromuser->id, 'id', (string) $touser->id);
        $result2 = $this->call('id', (string) $fromuser->id, 'id', (string) $touser->id);

        $this->assertSame($result1['logid'], $result2['logid']);
    }

    /**
     * A caller without tool/mergeusers:mergeusers is rejected.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_caller_without_capability(): void {
        $caller = $this->getDataGenerator()->create_user();
        $this->setUser($caller);

        $fromuser = $this->getDataGenerator()->create_user();
        $touser = $this->getDataGenerator()->create_user();

        $this->expectException(required_capability_exception::class);
        $this->call('id', (string) $fromuser->id, 'id', (string) $touser->id);
    }
}

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
 * Web service: queue a user merge request.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use tool_mergeusers\local\logger;
use tool_mergeusers\local\merge_orchestrator;
use tool_mergeusers\local\origin;
use tool_mergeusers\local\profile_fields;
use tool_mergeusers\local\status;
use tool_mergeusers\local\user_searcher;

/**
 * Queues a merge request the same way the web UI does (pending log + adhoc task), or -
 * when the user to keep does not exist yet - renames the user to remove instead, if
 * eligible. The actual merge/rename decision and logging is delegated to
 * merge_orchestrator; this class only handles web-service-specific concerns: parameter
 * validation, the capability check, and the tool_mergeusers/wsallowduplicatepending
 * idempotency setting (a web-service-only trust knob, not shared with web/CLI).
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enqueue_merge_request extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'fromuserfield' => new external_value(
                PARAM_ALPHANUMEXT,
                'Field identifying the user to remove: username, idnumber, id, or ' .
                'profile_field_<shortname> for an allow-listed custom profile field',
            ),
            'fromuservalue' => new external_value(PARAM_RAW, 'Value identifying the user to remove'),
            'touserfield' => new external_value(
                PARAM_ALPHANUMEXT,
                'Field identifying the user to keep: username, idnumber, id, or ' .
                'profile_field_<shortname> for an allow-listed custom profile field',
            ),
            'touservalue' => new external_value(PARAM_RAW, 'Value identifying the user to keep'),
        ]);
    }

    /**
     * Queues the merge request, or renames in place when eligible. The merge/rename
     * decision itself, and its logging, is delegated to merge_orchestrator; this
     * method only validates the request and applies the wsallowduplicatepending
     * idempotency check, both web-service-specific concerns.
     *
     * @param string $fromuserfield field identifying the user to remove.
     * @param string $fromuservalue value identifying the user to remove.
     * @param string $touserfield field identifying the user to keep.
     * @param string $touservalue value identifying the user to keep.
     * @return array{logid: int, status: string, renamed: bool, fromuser: array, touser: array}
     */
    public static function execute(
        string $fromuserfield,
        string $fromuservalue,
        string $touserfield,
        string $touservalue,
    ): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'fromuserfield' => $fromuserfield,
            'fromuservalue' => $fromuservalue,
            'touserfield' => $touserfield,
            'touservalue' => $touservalue,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('tool/mergeusers:mergeusers', $context);

        self::validate_field($params['fromuserfield']);
        self::validate_field($params['touserfield']);

        if (empty(get_config('tool_mergeusers', 'wsallowduplicatepending'))) {
            $existing = self::find_existing_pending_for(
                $params['fromuserfield'],
                $params['fromuservalue'],
                $params['touserfield'],
                $params['touservalue'],
            );
            if ($existing !== null) {
                return $existing;
            }
        }

        $result = (new merge_orchestrator())->request(
            $params['fromuserfield'],
            $params['fromuservalue'],
            $params['touserfield'],
            $params['touservalue'],
            (int) $USER->id,
            true, // A web service call must never block waiting on a synchronous merge.
            false, // Never notify: the caller is expected to poll for the result instead.
            origin::WS,
        );

        if (!$result['ok']) {
            throw new invalid_parameter_exception($result['message']);
        }

        return [
            'logid' => $result['logid'],
            'status' => $result['status'],
            'renamed' => $result['renamed'],
            'fromuser' => $result['fromuser'],
            'touser' => $result['touser'],
        ];
    }

    /**
     * Return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'logid' => new external_value(PARAM_INT, 'Id of the merge log entry'),
            'status' => new external_value(PARAM_ALPHA, 'pending, inprogress, or renamed'),
            'renamed' => new external_value(PARAM_BOOL, 'true when a rename was performed instead of queuing a merge'),
            'fromuser' => new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'user.id of the user to remove'),
                    'username' => new external_value(PARAM_RAW, 'Username of the user to remove'),
                    'fullname' => new external_value(PARAM_RAW, 'Full name of the user to remove'),
                    'email' => new external_value(PARAM_RAW, 'Email of the user to remove'),
                ],
                'The user identified to remove - confirmation the right user was found',
            ),
            'touser' => new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'user.id of the user to keep, or 0 if it does not exist yet'),
                    'username' => new external_value(PARAM_RAW, 'Username of the user to keep, if resolved'),
                    'fullname' => new external_value(PARAM_RAW, 'Full name of the user to keep, if resolved'),
                    'email' => new external_value(PARAM_RAW, 'Email of the user to keep, if resolved'),
                    'exists' => new external_value(PARAM_BOOL, 'Whether the user to keep currently exists'),
                    'note' => new external_value(PARAM_RAW, 'Explanation when the user to keep does not exist yet'),
                ],
                'The user identified to keep, or an explanation when it does not exist yet',
            ),
        ]);
    }

    /**
     * Restricts $field to the same set merge_user_form.php offers for identifying a user:
     * username/idnumber/id, or "profile_field_<shortname>" for an allow-listed custom
     * profile field - never the field's internal database id, which is an
     * environment-specific implementation detail external callers cannot be expected
     * to know (see profile_fields::FIELD_PREFIX).
     *
     * @param string $field the raw field value submitted by the caller, to validate.
     */
    private static function validate_field(string $field): void {
        $simplefields = ['username', 'idnumber', 'id'];
        if (in_array($field, $simplefields, true)) {
            return;
        }
        if (profile_fields::resolve_allowed($field) !== null) {
            return;
        }
        throw new invalid_parameter_exception(get_string('wsinvalidfield', 'tool_mergeusers', $field));
    }

    /**
     * Finds an existing pending/inprogress log for the user identified by
     * $fromfield/$fromvalue, if any, already shaped as an execute() return value. Used
     * only by the wsallowduplicatepending idempotency check; a $fromfield/$fromvalue
     * that does not resolve to a real user is not treated as an error here -
     * request()'s own resolution reports that.
     *
     * @param string $fromfield field identifying the user to remove.
     * @param string $fromvalue value identifying the user to remove.
     * @param string $tofield field identifying the user to keep, for the "not found
     * yet" detail only - the existing log's own stored target, if any, always wins.
     * @param string $tovalue value identifying the user to keep, same caveat as $tofield.
     * @return array{logid: int, status: string, renamed: bool, fromuser: array,
     * touser: array}|null the existing request, already formatted, or null when there
     * is none.
     */
    private static function find_existing_pending_for(
        string $fromfield,
        string $fromvalue,
        string $tofield,
        string $tovalue,
    ): ?array {
        [$fromuser] = (new user_searcher())->verify_user($fromvalue, $fromfield);
        if ($fromuser === null) {
            return null;
        }

        $logger = new logger();
        foreach ([status::PENDING->value, status::INPROGRESS->value] as $checkstatus) {
            $existing = $logger->get(['fromuserid' => $fromuser->id, 'status' => $checkstatus], 0, 1);
            if (!$existing) {
                continue;
            }

            $log = reset($existing);
            return [
                'logid' => (int) $log->id,
                'status' => $log->status,
                'renamed' => false,
                'fromuser' => merge_orchestrator::describe_user($fromuser),
                'touser' => $log->to
                    ? merge_orchestrator::describe_user($log->to) + ['exists' => true, 'note' => '']
                    : merge_orchestrator::describe_missing_user($tofield, $tovalue),
            ];
        }
        return null;
    }
}

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
use core\task\manager;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use moodle_exception;
use stdClass;
use tool_mergeusers\local\logger;
use tool_mergeusers\local\profile_fields;
use tool_mergeusers\local\status;
use tool_mergeusers\local\user_searcher;
use tool_mergeusers\task\merge_users_task;

/**
 * Queues a merge request the same way the web UI does (pending log + adhoc task), or -
 * when the user to keep does not exist yet - renames the user to remove instead, if
 * eligible (see user_searcher::rename_if_eligible()).
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
     * Queues the merge request, or renames in place when eligible.
     *
     * @param string $fromuserfield
     * @param string $fromuservalue
     * @param string $touserfield
     * @param string $touservalue
     * @return array{logid: int, status: string, renamed: bool}
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

        $searcher = new user_searcher();
        [$fromuser, $frommessage, $fromambiguous] = $searcher->verify_user($params['fromuservalue'], $params['fromuserfield']);
        [$touser, $tomessage, $toambiguous] = $searcher->verify_user($params['touservalue'], $params['touserfield']);

        if ($fromambiguous) {
            throw new invalid_parameter_exception($frommessage);
        }
        if ($toambiguous) {
            throw new invalid_parameter_exception($tomessage);
        }
        if ($fromuser === null) {
            throw new invalid_parameter_exception($frommessage);
        }

        if ($touser === null) {
            if ($searcher->rename_if_eligible($fromuser, $params['touserfield'], $params['touservalue'])) {
                return ['logid' => 0, 'status' => 'renamed', 'renamed' => true];
            }
            throw new invalid_parameter_exception($tomessage);
        }

        if ((int) $fromuser->id === (int) $touser->id) {
            throw new invalid_parameter_exception(get_string('errorsameuser', 'tool_mergeusers'));
        }

        $logger = new logger();

        if (empty(get_config('tool_mergeusers', 'wsallowduplicatepending'))) {
            $existing = self::find_existing_pending($logger, $fromuser->id);
            if ($existing !== null) {
                return ['logid' => (int) $existing->id, 'status' => $existing->status, 'renamed' => false];
            }
        }

        $logid = $logger->create_pending_log($touser->id, $fromuser->id, $USER->id);
        if (!$logid) {
            throw new moodle_exception('error_log_creation_failed', 'tool_mergeusers');
        }

        $task = new merge_users_task();
        $task->set_custom_data([
            'toid' => $touser->id,
            'fromid' => $fromuser->id,
            'logid' => $logid,
        ]);
        if (!empty($USER->id)) {
            $task->set_userid($USER->id);
        }
        manager::queue_adhoc_task($task);

        return ['logid' => $logid, 'status' => status::PENDING->value, 'renamed' => false];
    }

    /**
     * Return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'logid' => new external_value(PARAM_INT, 'Id of the merge log entry (0 when a rename was performed instead)'),
            'status' => new external_value(PARAM_ALPHA, 'pending, inprogress, or "renamed"'),
            'renamed' => new external_value(PARAM_BOOL, 'true when a rename was performed instead of queuing a merge'),
        ]);
    }

    /**
     * Restricts $field to the same set merge_user_form.php offers for identifying a user:
     * username/idnumber/id, or "profile_field_<shortname>" for an allow-listed custom
     * profile field - never the field's internal database id, which is an
     * environment-specific implementation detail external callers cannot be expected
     * to know (see profile_fields::FIELD_PREFIX).
     *
     * @param string $field
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
     * Finds an existing pending/inprogress log for $fromuserid, if any.
     *
     * @param logger $logger
     * @param int $fromuserid
     * @return stdClass|null
     */
    private static function find_existing_pending(logger $logger, int $fromuserid): ?stdClass {
        foreach ([status::PENDING->value, status::INPROGRESS->value] as $checkstatus) {
            $existing = $logger->get(['fromuserid' => $fromuserid, 'status' => $checkstatus], 0, 1);
            if ($existing) {
                return reset($existing);
            }
        }
        return null;
    }
}

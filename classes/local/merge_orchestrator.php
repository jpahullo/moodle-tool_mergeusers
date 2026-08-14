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
 * Domain service to request a merge, or a rename instead of merge (issue #250).
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local;

use core\task\manager;
use stdClass;
use Throwable;
use tool_mergeusers\task\merge_users_task;

/**
 * Resolves two identified users and either queues/runs a merge, or renames the "from"
 * user's identifying field in place when the "to" user does not exist yet. Shared
 * domain logic, independent of whether the caller is a web service, the web UI, or a
 * CLI script - none of them throw or catch web-service-specific exception types here;
 * each caller translates the returned result into its own error handling convention.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class merge_orchestrator {
    /** @var user_searcher */
    private user_searcher $searcher;

    /** @var logger */
    private logger $logger;

    /**
     * Constructor.
     *
     * @param user_searcher|null $searcher defaults to a new instance.
     * @param logger|null $logger defaults to a new instance.
     */
    public function __construct(?user_searcher $searcher = null, ?logger $logger = null) {
        $this->searcher = $searcher ?? new user_searcher();
        $this->logger = $logger ?? new logger();
    }

    /**
     * Resolves the two identified users and either queues/runs a merge, or - when the
     * "to" user genuinely does not exist (never on an ambiguous match) - renames the
     * "from" user's identifying field instead, per issue #250. Every outcome, a rename
     * included, is always persisted with its own log id - never a silent, unlogged
     * side effect.
     *
     * @param string $fromfield field identifying the user to remove.
     * @param string $fromvalue value identifying the user to remove.
     * @param string $tofield field identifying the user to keep.
     * @param string $tovalue value identifying the user to keep.
     * @param int $requestedbyuserid user.id of the user requesting the merge/rename.
     * @param bool|null $async when true, queues a merge_users_task instead of acting
     * synchronously (a merge or a rename alike); when false, always acts synchronously;
     * when null (the default), follows the tool_mergeusers/enableadhocmerge setting.
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     * ok is false only for a validation failure (not found/ambiguous/same user), never
     * for a merge that ran but failed - that outcome is conveyed by status=error instead.
     */
    public function request(
        string $fromfield,
        string $fromvalue,
        string $tofield,
        string $tovalue,
        int $requestedbyuserid,
        ?bool $async = null,
    ): array {
        [$fromuser, $frommessage, $fromambiguous] = $this->searcher->verify_user($fromvalue, $fromfield);
        [$touser, $tomessage, $toambiguous] = $this->searcher->verify_user($tovalue, $tofield);

        if ($fromambiguous) {
            return self::error($frommessage);
        }
        if ($toambiguous) {
            return self::error($tomessage);
        }
        if ($fromuser === null) {
            return self::error($frommessage);
        }

        if ($touser === null) {
            return $this->rename_or_error($fromuser, $tofield, $tovalue, $requestedbyuserid, $tomessage, $async);
        }

        if ((int) $fromuser->id === (int) $touser->id) {
            return self::error(get_string('errorsameuser', 'tool_mergeusers'));
        }

        return $this->queue_or_run($touser->id, $fromuser->id, $requestedbyuserid, $async);
    }

    /**
     * Renames $fromuser's $tofield to $tovalue instead of merging, when eligible (see
     * user_searcher::rename_if_eligible()); otherwise returns $notfoundmessage as the
     * error. Once eligibility is confirmed, persists a pending log entry capturing
     * $fromuser's identity BEFORE the rename - the same lifecycle a real merge uses -
     * then either performs the rename immediately, or - when $async resolves true -
     * queues it as a merge_users_task instead of writing it in place.
     *
     * Queuing matters for more than just "not blocking the request": $fromuser here
     * may be the SAME user another already-queued merge_users_task still has to finish
     * acting on (e.g. "merge A into B", then "merge B into C" where C does not exist
     * yet, becoming "rename B"). merge_users_task caps its own concurrency to 1 and
     * always picks the oldest queued task next, which is what guarantees the earlier
     * task finishes before this rename runs - a rename performed here in place, outside
     * that queue, would have no such ordering guarantee at all.
     *
     * @param stdClass $fromuser the user to rename (a full {user} record, at least id).
     * @param string $tofield field that was searched for the (non-existent) "to" user.
     * @param string $tovalue value that was searched for the (non-existent) "to" user.
     * @param int $requestedbyuserid user.id of the user requesting the rename.
     * @param string $notfoundmessage error to return when not eligible for renaming.
     * @param bool|null $async null follows the tool_mergeusers/enableadhocmerge setting.
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     */
    private function rename_or_error(
        stdClass $fromuser,
        string $tofield,
        string $tovalue,
        int $requestedbyuserid,
        string $notfoundmessage,
        ?bool $async,
    ): array {
        if (
            !$this->searcher->is_login_identifier_field($tofield)
            || empty(get_config('tool_mergeusers', 'renamewhenmissingtarget'))
        ) {
            return self::error($notfoundmessage);
        }

        $logid = $this->logger->create_pending_log(
            0,
            $fromuser->id,
            $requestedbyuserid,
            ['field' => $tofield, 'value' => $tovalue],
        );
        if (!$logid) {
            return self::error(get_string('error_log_creation_failed', 'tool_mergeusers'));
        }

        $async ??= (bool) get_config('tool_mergeusers', 'enableadhocmerge');

        if ($async) {
            $task = new merge_users_task();
            $task->set_custom_data([
                'fromid' => $fromuser->id,
                'renamefield' => $tofield,
                'renamevalue' => $tovalue,
                'logid' => $logid,
            ]);
            if (!empty($requestedbyuserid)) {
                $task->set_userid($requestedbyuserid);
            }
            manager::queue_adhoc_task($task);

            return ['ok' => true, 'message' => '', 'logid' => $logid, 'status' => status::PENDING->value, 'renamed' => false];
        }

        return $this->perform_rename($fromuser->id, $tofield, $tovalue, $logid);
    }

    /**
     * Performs the actual rename write and finalizes its (already pending) log entry -
     * shared by rename_or_error()'s synchronous path and merge_users_task::execute()
     * for the asynchronous one, so both go through the exact same logic.
     *
     * @param int $fromuserid the user to rename.
     * @param string $field the field to update (username or email).
     * @param string $value the new value for that field.
     * @param int $logid an existing pending log entry, as created by rename_or_error().
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     */
    public function perform_rename(int $fromuserid, string $field, string $value, int $logid): array {
        global $DB;

        try {
            $fromuser = $DB->get_record('user', ['id' => $fromuserid, 'deleted' => 0], '*', MUST_EXIST);
            $renamed = $this->searcher->rename_if_eligible($fromuser, $field, $value);
        } catch (Throwable $e) {
            $this->logger->update_log_status($logid, status::ERROR->value, ['Exception: ' . $e->getMessage()]);
            return self::error($e->getMessage());
        }

        if (!$renamed) {
            // Eligibility (or the user itself) can only have changed between queuing and
            // here if something changed concurrently - keep the log as evidence either way.
            $message = get_string('invaliduser', 'tool_mergeusers', ['field' => $field, 'value' => $value]);
            $this->logger->update_log_status($logid, status::ERROR->value, [$message]);
            return self::error($message);
        }

        $fieldlabel = $field === 'email' ? get_string('email') : get_string('username');
        $action = get_string(
            'renamelogaction',
            'tool_mergeusers',
            (object) ['fieldlabel' => $fieldlabel, 'value' => $value],
        );
        $this->logger->update_log_status($logid, status::RENAMED->value, [$action]);

        return ['ok' => true, 'message' => '', 'logid' => $logid, 'status' => status::RENAMED->value, 'renamed' => true];
    }

    /**
     * Queues a merge_users_task, or runs the merge synchronously, depending on $async.
     *
     * @param int $touserid the user kept.
     * @param int $fromuserid the user removed.
     * @param int $requestedbyuserid user.id of the user requesting the merge.
     * @param bool|null $async null follows the tool_mergeusers/enableadhocmerge setting.
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     */
    private function queue_or_run(int $touserid, int $fromuserid, int $requestedbyuserid, ?bool $async): array {
        $async ??= (bool) get_config('tool_mergeusers', 'enableadhocmerge');

        $logid = $this->logger->create_pending_log($touserid, $fromuserid, $requestedbyuserid);
        if (!$logid) {
            return self::error(get_string('error_log_creation_failed', 'tool_mergeusers'));
        }

        if ($async) {
            $task = new merge_users_task();
            $task->set_custom_data(['toid' => $touserid, 'fromid' => $fromuserid, 'logid' => $logid]);
            if (!empty($requestedbyuserid)) {
                $task->set_userid($requestedbyuserid);
            }
            manager::queue_adhoc_task($task);

            return ['ok' => true, 'message' => '', 'logid' => $logid, 'status' => status::PENDING->value, 'renamed' => false];
        }

        $merger = new user_merger();
        [$success, , $loggedid] = $merger->merge($touserid, $fromuserid, $logid);

        return [
            'ok' => true,
            'message' => '',
            'logid' => $loggedid,
            'status' => status::from_success($success)->value,
            'renamed' => false,
        ];
    }

    /**
     * Builds an error result.
     *
     * @param string $message the error message to report.
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     */
    private static function error(string $message): array {
        return ['ok' => false, 'message' => $message, 'logid' => 0, 'status' => '', 'renamed' => false];
    }
}

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
 * Resolves the "from" user and either acts on the "to" side immediately (synchronous
 * request), or - whenever the request is asynchronous - defers resolving it at all
 * until a merge_users_task actually executes. Shared domain logic, independent of
 * whether the caller is a web service, the web UI, or a CLI script - none of them
 * throw or catch web-service-specific exception types here; each caller translates the
 * returned result into its own error handling convention.
 *
 * Why defer the "to" side instead of deciding merge-vs-rename up front: whether the
 * "to" user exists, and whether renaming is currently allowed, can both change between
 * the moment a request is queued and the moment it actually runs - another user could
 * be created or renamed into existence by then, and an administrator could toggle
 * tool_mergeusers/renamewhenmissingtarget at any time. Deciding early and only
 * deferring the write (an earlier revision of this file) fixed the specific ordering
 * hazard below, but could still commit to the wrong outcome. Evaluating everything
 * fresh, exactly once, at the moment of actual execution is the only way to guarantee
 * the outcome always reflects live state - so an asynchronous caller must never expect
 * anything from this class but a "pending" result; the real outcome is only ever
 * discoverable afterwards, from the log.
 *
 * This also matters for more than just correctness of a single request: the "from"
 * user of one request can be the very same user another already-queued
 * merge_users_task still has to finish acting on (e.g. "merge A into B", then "merge B
 * into C" where C does not exist, becoming "rename B"). merge_users_task caps its own
 * concurrency to 1 and always picks the oldest queued task next, which is what
 * guarantees the earlier task finishes before a later one touching the same user
 * starts - a write performed here in place, outside that queue, would have no such
 * ordering guarantee at all.
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
     * Resolves the "from" user, then either resolves and acts on the "to" side right
     * away (synchronous request), or queues a merge_users_task that will do so later,
     * evaluated fresh at that point (asynchronous request) - see this class's own
     * docblock for why. Every outcome, a rename included, is always persisted with its
     * own log id - never a silent, unlogged side effect.
     *
     * @param string $fromfield field identifying the user to remove.
     * @param string $fromvalue value identifying the user to remove.
     * @param string $tofield field identifying the user to keep.
     * @param string $tovalue value identifying the user to keep.
     * @param int $requestedbyuserid user.id of the user requesting the merge/rename.
     * @param bool|null $async when true, always queues, deferring evaluation of the
     * "to" side to task execution time; when false, evaluates and acts immediately;
     * when null (the default), follows the tool_mergeusers/enableadhocmerge setting.
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     * ok is false only for a validation failure (not found/ambiguous/same user) that
     * could be determined synchronously - an asynchronous request only ever returns
     * ok=true/status=pending here, whatever the eventual outcome turns out to be.
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

        if ($fromambiguous || $fromuser === null) {
            return self::error($frommessage);
        }

        $async ??= (bool) get_config('tool_mergeusers', 'enableadhocmerge');

        if ($async) {
            return $this->queue_deferred($fromuser->id, $tofield, $tovalue, $requestedbyuserid);
        }

        [$touser, $tomessage, $toambiguous] = $this->searcher->verify_user($tovalue, $tofield);

        if ($toambiguous) {
            return self::error($tomessage);
        }

        if ($touser === null) {
            return $this->rename_or_error($fromuser, $tofield, $tovalue, $requestedbyuserid, $tomessage);
        }

        if ((int) $fromuser->id === (int) $touser->id) {
            return self::error(get_string('errorsameuser', 'tool_mergeusers'));
        }

        return $this->run_merge($touser->id, $fromuser->id, $requestedbyuserid);
    }

    /**
     * Queues a merge_users_task carrying the raw "to" field/value, deliberately
     * unresolved - resolve_and_act() evaluates them once the task actually runs.
     *
     * @param int $fromuserid the already-resolved user to remove.
     * @param string $tofield field identifying the user to keep.
     * @param string $tovalue value identifying the user to keep.
     * @param int $requestedbyuserid user.id of the user requesting the merge/rename.
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     */
    private function queue_deferred(int $fromuserid, string $tofield, string $tovalue, int $requestedbyuserid): array {
        $logid = $this->logger->create_pending_log(
            0,
            $fromuserid,
            $requestedbyuserid,
            ['field' => $tofield, 'value' => $tovalue],
        );
        if (!$logid) {
            return self::error(get_string('error_log_creation_failed', 'tool_mergeusers'));
        }

        $task = new merge_users_task();
        $task->set_custom_data([
            'fromid' => $fromuserid,
            'tofield' => $tofield,
            'tovalue' => $tovalue,
            'logid' => $logid,
        ]);
        if (!empty($requestedbyuserid)) {
            $task->set_userid($requestedbyuserid);
        }
        manager::queue_adhoc_task($task);

        return ['ok' => true, 'message' => '', 'logid' => $logid, 'status' => status::PENDING->value, 'renamed' => false];
    }

    /**
     * Evaluates a previously queued, deferred request - see queue_deferred() - against
     * live state, exactly once, at actual execution time: resolves the "to" side fresh
     * and either merges into it (retargeting the log first, since it was captured as
     * "not found" when queued), renames the "from" user in place, or marks the
     * already-existing log as an error. Called only by merge_users_task::execute().
     *
     * @param int $fromuserid the user to remove, already resolved when queued.
     * @param string $tofield field identifying the user to keep.
     * @param string $tovalue value identifying the user to keep.
     * @param int $logid an existing pending log entry, as created by queue_deferred().
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool,
     * touserid: int} touserid is the real user merged into, or 0 for a rename/error.
     */
    public function resolve_and_act(int $fromuserid, string $tofield, string $tovalue, int $logid): array {
        global $DB;

        $fromuser = $DB->get_record('user', ['id' => $fromuserid, 'deleted' => 0]);
        if (!$fromuser) {
            $message = get_string('invaliduser', 'tool_mergeusers', ['field' => 'id', 'value' => (string) $fromuserid]);
            $this->logger->update_log_status($logid, status::ERROR->value, [$message]);
            return self::error($message, $logid);
        }

        [$touser, $tomessage, $toambiguous] = $this->searcher->verify_user($tovalue, $tofield);

        if ($toambiguous) {
            $this->logger->update_log_status($logid, status::ERROR->value, [$tomessage]);
            return self::error($tomessage, $logid);
        }

        if ($touser === null) {
            $result = $this->perform_rename($fromuser->id, $tofield, $tovalue, $logid);
            return $result + ['touserid' => 0];
        }

        if ((int) $touser->id === (int) $fromuser->id) {
            $message = get_string('errorsameuser', 'tool_mergeusers');
            $this->logger->update_log_status($logid, status::ERROR->value, [$message]);
            return self::error($message, $logid);
        }

        // A real target now exists: refresh the log's snapshot (captured as "not
        // found" when this was queued) before merging into it.
        $this->logger->retarget_pending_log($logid, $touser->id);
        $this->logger->update_log_status($logid, status::INPROGRESS->value, []);

        $merger = new user_merger();
        [$success, , $loggedid] = $merger->merge($touser->id, $fromuser->id, $logid);

        return [
            'ok' => true,
            'message' => '',
            'logid' => $loggedid,
            'status' => status::from_success($success)->value,
            'renamed' => false,
            'touserid' => (int) $touser->id,
        ];
    }

    /**
     * Renames $fromuser's $tofield to $tovalue instead of merging, when eligible (see
     * user_searcher::rename_if_eligible()); otherwise returns $notfoundmessage as the
     * error, without creating any log entry - this is the synchronous path only, where
     * eligibility is already known before anything is persisted. Once eligible,
     * persists a pending log entry capturing $fromuser's identity BEFORE the rename -
     * the same lifecycle a real merge uses - then performs the rename immediately.
     *
     * @param stdClass $fromuser the user to rename (a full {user} record, at least id).
     * @param string $tofield field that was searched for the (non-existent) "to" user.
     * @param string $tovalue value that was searched for the (non-existent) "to" user.
     * @param int $requestedbyuserid user.id of the user requesting the rename.
     * @param string $notfoundmessage error to return when not eligible for renaming.
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     */
    private function rename_or_error(
        stdClass $fromuser,
        string $tofield,
        string $tovalue,
        int $requestedbyuserid,
        string $notfoundmessage,
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

        return $this->perform_rename($fromuser->id, $tofield, $tovalue, $logid);
    }

    /**
     * Performs the actual rename write and finalizes its (already pending) log entry -
     * shared by rename_or_error()'s synchronous path and resolve_and_act()'s
     * asynchronous one, so both go through the exact same logic.
     *
     * @param int $fromuserid the user to rename.
     * @param string $field the field to update (username or email).
     * @param string $value the new value for that field.
     * @param int $logid an existing pending log entry.
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     */
    public function perform_rename(int $fromuserid, string $field, string $value, int $logid): array {
        global $DB;

        try {
            $fromuser = $DB->get_record('user', ['id' => $fromuserid, 'deleted' => 0], '*', MUST_EXIST);
            $renamed = $this->searcher->rename_if_eligible($fromuser, $field, $value);
        } catch (Throwable $e) {
            $this->logger->update_log_status($logid, status::ERROR->value, ['Exception: ' . $e->getMessage()]);
            return self::error($e->getMessage(), $logid);
        }

        if (!$renamed) {
            // Not eligible right now (setting off, or the field never qualifies) - keep
            // the log as evidence either way.
            $message = get_string('invaliduser', 'tool_mergeusers', ['field' => $field, 'value' => $value]);
            $this->logger->update_log_status($logid, status::ERROR->value, [$message]);
            return self::error($message, $logid);
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
     * Creates a pending log and runs the merge synchronously - the request()'s
     * synchronous path only, both sides already resolved to real, distinct users.
     *
     * @param int $touserid the user kept.
     * @param int $fromuserid the user removed.
     * @param int $requestedbyuserid user.id of the user requesting the merge.
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     */
    private function run_merge(int $touserid, int $fromuserid, int $requestedbyuserid): array {
        $logid = $this->logger->create_pending_log($touserid, $fromuserid, $requestedbyuserid);
        if (!$logid) {
            return self::error(get_string('error_log_creation_failed', 'tool_mergeusers'));
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
     * @param int $logid an already-existing log entry this error was recorded against,
     * if any - 0 when the failure was a pure validation error with no log created.
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     */
    private static function error(string $message, int $logid = 0): array {
        return ['ok' => false, 'message' => $message, 'logid' => $logid, 'status' => '', 'renamed' => false];
    }
}

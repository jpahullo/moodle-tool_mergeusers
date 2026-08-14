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
     * @param bool|null $async when true, queues a merge_users_task instead of merging
     * synchronously; when false, always merges synchronously; when null (the default),
     * follows the tool_mergeusers/enableadhocmerge setting. A rename is always
     * synchronous - there is no meaningful asynchronous form of it.
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
            return $this->rename_or_error($fromuser, $tofield, $tovalue, $requestedbyuserid, $tomessage);
        }

        if ((int) $fromuser->id === (int) $touser->id) {
            return self::error(get_string('errorsameuser', 'tool_mergeusers'));
        }

        return $this->queue_or_run($touser->id, $fromuser->id, $requestedbyuserid, $async);
    }

    /**
     * Renames $fromuser's $tofield to $tovalue instead of merging, when eligible (see
     * user_searcher::rename_if_eligible()); otherwise returns $notfoundmessage as the
     * error. Persists a log entry once eligibility is confirmed, capturing $fromuser's
     * identity BEFORE the rename - via the same pending-log-then-update-status
     * lifecycle a real merge uses - so the log keeps evidence of what changed, rather
     * than a snapshot of the already-renamed value.
     *
     * @param stdClass $fromuser the user to rename (a full {user} record, at least id).
     * @param string $tofield
     * @param string $tovalue
     * @param int $requestedbyuserid
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

        try {
            $renamed = $this->searcher->rename_if_eligible($fromuser, $tofield, $tovalue);
        } catch (Throwable $e) {
            $this->logger->update_log_status($logid, status::ERROR->value, ['Exception: ' . $e->getMessage()]);
            return self::error($e->getMessage());
        }

        if (!$renamed) {
            // Eligibility can only have changed between the check above and here if the
            // setting was toggled concurrently - keep the already-created log as evidence.
            $this->logger->update_log_status($logid, status::ERROR->value, [$notfoundmessage]);
            return self::error($notfoundmessage);
        }

        $fieldlabel = $tofield === 'email' ? get_string('email') : get_string('username');
        $action = get_string(
            'renamelogaction',
            'tool_mergeusers',
            (object) ['fieldlabel' => $fieldlabel, 'value' => $tovalue],
        );
        $this->logger->update_log_status($logid, status::RENAMED->value, [$action]);

        return ['ok' => true, 'message' => '', 'logid' => $logid, 'status' => status::RENAMED->value, 'renamed' => true];
    }

    /**
     * Queues a merge_users_task, or runs the merge synchronously, depending on $async.
     *
     * @param int $touserid
     * @param int $fromuserid
     * @param int $requestedbyuserid
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
     * @param string $message
     * @return array{ok: bool, message: string, logid: int, status: string, renamed: bool}
     */
    private static function error(string $message): array {
        return ['ok' => false, 'message' => $message, 'logid' => 0, 'status' => '', 'renamed' => false];
    }
}

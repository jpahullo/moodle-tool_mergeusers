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
 * Provides a tool for managing merge logs.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2013 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local;

defined('MOODLE_INTERNAL') || die();

use dml_exception;
use Exception;
use moodle_exception;
use moodle_url;
use stdClass;

global $CFG;
require_once($CFG->libdir . '/clilib.php');

/**
 * Class to manage logging actions for this tool.
 * General log table cannot be used for log.info field length restrictions.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2013 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class logger {
    /**
     * Adds a merging action log into tool log.
     *
     * @param int $touserid user.id where all data from $fromuserid will be merged into.
     * @param int $fromuserid user.id moving all data into $touserid.
     * @param bool $success true if merging action was ok; false otherwise.
     * @param array $log list of actions performed for a successful merging;
     * or a problem description if merging failed.
     * @param string|null $status the status: pending, inprogress, success, error. When null, it will be derived from $success.
     * @return bool|int false when could not insert the record; the log id when success.
     * @throws moodle_exception when log record cannot be inserted.
     */
    public function log(int $touserid, int $fromuserid, bool $success, array $log, ?string $status = null): bool|int {
        global $DB, $USER;

        $currenttime = time();

        // Capture user snapshots.
        $tosnapshot = $this->capture_user_snapshot($touserid);
        $fromsnapshot = $this->capture_user_snapshot($fromuserid);

        // Add user snapshots to the log data.
        $logdata = [
            'user_snapshots' => [
                'to_user' => $tosnapshot,
                'from_user' => $fromsnapshot,
            ],
            'actions' => $log,
        ];

        $record = new stdClass();
        $record->touserid = $touserid;
        $record->fromuserid = $fromuserid;
        $record->timecreated = $currenttime;
        $record->timemodified = $currenttime;
        $record->mergedbyuserid = $USER->id;
        $record->log = json_encode($logdata);

        if ($status === null) {
            $record->status = status::from_success($success)->value;
        } else {
            $record->status = $status;
        }

        try {
            return $DB->insert_record('tool_mergeusers', $record, true);
        } catch (Exception $e) {
            $msg = __METHOD__ . ' : Cannot insert new record on log. Reason: "' . $DB->get_last_error() .
                '". Message: "' . $e->getMessage() . '". Trace' . $e->getTraceAsString();
            if (CLI_SCRIPT) {
                cli_error($msg);
            } else {
                throw new moodle_exception(
                    $msg,
                    'tool_mergeusers',
                    new moodle_url('/admin/tool/mergeusers/index.php'),
                );
            }
        }
        return false;
    }

    /**
     * Creates a pending log entry for a merge operation.
     *
     * @param int $touserid       user.id where all data from $fromuserid will be merged into.
     * @param int $fromuserid     user.id moving all data into $touserid.
     * @param int $mergedbyuserid user.id of the user initiating the merge.
     *
     * @return bool|int false when could not insert the record; the log id when success.
     */
    public function create_pending_log(int $touserid, int $fromuserid, int $mergedbyuserid): bool|int {
        global $DB;

        $currenttime = time();

        // Capture user snapshots at queue time.
        $tosnapshot = $this->capture_user_snapshot($touserid);
        $fromsnapshot = $this->capture_user_snapshot($fromuserid);

        // Store user snapshots in log data.
        $logdata = [
            'user_snapshots' => [
                'to_user' => $tosnapshot,
                'from_user' => $fromsnapshot,
            ],
            'actions' => [],
        ];

        $record = new stdClass();
        $record->touserid = $touserid;
        $record->fromuserid = $fromuserid;
        $record->timecreated = $currenttime;
        $record->timemodified = $currenttime;
        $record->mergedbyuserid = $mergedbyuserid;
        $record->log = json_encode($logdata);
        $record->status = status::PENDING->value;

        try {
            return $DB->insert_record('tool_mergeusers', $record, true);
        } catch (Exception $e) {
            $msg = __METHOD__ . ' : Cannot insert pending log record. Reason: "' . $DB->get_last_error() .
                '". Message: "' . $e->getMessage() . '". Trace' . $e->getTraceAsString();
            if (CLI_SCRIPT) {
                cli_error($msg);
            } else {
                throw new moodle_exception(
                    $msg,
                    'tool_mergeusers',
                    new moodle_url('/admin/tool/mergeusers/index.php'),
                );
            }
        }

        return false;
    }

    /**
     * Updates an existing log entry with status and log content.
     *
     * @param int $logid     the id of the log entry to update.
     * @param string $status the status: pending, inprogress, success, error.
     * @param array $log     list of actions performed for a successful merging; or errors on failure.
     *
     * @return bool true on success, false otherwise.
     */
    public function update_log_status(int $logid, string $status, array $log): bool {
        global $DB;

        try {
            // Get existing log to preserve user snapshots.
            // Use MUST_EXIST to ensure the record exists, or throw an exception.
            $existinglog = $DB->get_record('tool_mergeusers', ['id' => $logid], '*', MUST_EXIST);
        } catch (\dml_missing_record_exception $e) {
            debugging('Cannot update non-existent merge log: ' . $logid, DEBUG_DEVELOPER);
            return false;
        }

        $existinglogdata = json_decode($existinglog->log, true);

        // Preserve user snapshots from the original log.
        $logdata = [
            'user_snapshots' => $existinglogdata['user_snapshots'] ?? null,
            'actions' => $log,
        ];

        $record = new stdClass();
        $record->id = $logid;
        $record->status = $status;
        $record->log = json_encode($logdata);
        $record->timemodified = time();

        return $DB->update_record('tool_mergeusers', $record);
    }

    /**
     * Gets the merging logs and stores on to and from attributes the related user records.
     *
     * @param array|null $filter associative array with conditions to match for getting results.
     * If empty, this will return all logs.
     * @param int $limitfrom starting number of record to get. 0 to get all.
     * @param int $limitnum maximum number of records to get. 0 to get all.
     * @param string $sort SQL ordering, defaults to "timemodified DESC"
     * @return array|bool false when there are no records matching; list of merging logs otherwise.
     * @throws dml_exception
     */
    public function get(
        ?array $filter = null,
        int $limitfrom = 0,
        int $limitnum = 0,
        string $sort = "timemodified DESC",
    ): array|bool {
        global $DB;
        $logs = $DB->get_records(
            'tool_mergeusers',
            $filter,
            $sort,
            'id, touserid, fromuserid, mergedbyuserid, timecreated, timemodified, status',
            $limitfrom,
            $limitnum,
        );
        if (!$logs) {
            return $logs;
        }
        foreach ($logs as $id => &$log) {
            $log->to = $DB->get_record('user', ['id' => $log->touserid]);
            $log->from = $DB->get_record('user', ['id' => $log->fromuserid]);

            if (empty($log->mergedbyuserid)) {
                $log->mergedby = null;
            } else {
                $log->mergedby = $DB->get_record('user', ['id' => $log->mergedbyuserid]);
            }
        }
        return $logs;
    }

    /**
     * Get the whole detail of a log id.
     *
     * @param int $logid To user.id.
     * @return stdClass the whole record related to the $logid
     * @throws dml_exception when log does not exist.
     */
    public function detail_from(int $logid): stdClass {
        global $DB;
        $log = $DB->get_record('tool_mergeusers', ['id' => $logid], '*', MUST_EXIST);
        $log->log = json_decode($log->log);
        return $log;
    }

    /**
     * Captures a snapshot of user data at the current time.
     *
     * @param int $userid user.id to capture snapshot of.
     * @return stdClass|null user snapshot with relevant fields, or null if user not found.
     */
    private function capture_user_snapshot(int $userid): ?stdClass {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            return null;
        }

        $snapshot = new stdClass();
        $snapshot->id = $user->id;
        $snapshot->username = $user->username;
        $snapshot->email = $user->email;
        $snapshot->firstname = $user->firstname;
        $snapshot->lastname = $user->lastname;
        $snapshot->idnumber = $user->idnumber;
        $snapshot->suspended = $user->suspended;
        $snapshot->deleted = $user->deleted;

        return $snapshot;
    }
}

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
    /** @var string searched-field value for the username field. */
    public const SEARCHED_FIELD_USERNAME = 'username';
    /** @var string searched-field value for the idnumber field. */
    public const SEARCHED_FIELD_IDNUMBER = 'idnumber';
    /** @var string searched-field value for the email field. */
    public const SEARCHED_FIELD_EMAIL = 'email';

    /**
     * Fields a gathering is allowed to report as the one it searched by when it
     * could not resolve a user. Matches the fields the web UI itself lets an
     * admin search by; excludes 'id' because a not-found search by id is already
     * represented by the existing id + recoverable=false snapshot shape.
     */
    private const ALLOWED_SEARCH_FIELDS = [
        self::SEARCHED_FIELD_USERNAME,
        self::SEARCHED_FIELD_IDNUMBER,
        self::SEARCHED_FIELD_EMAIL,
    ];

    /**
     * Adds a merging action log into tool log.
     *
     * @param int $touserid user.id where all data from $fromuserid will be merged into.
     * @param int $fromuserid user.id moving all data into $touserid.
     * @param bool $success true if merging action was ok; false otherwise.
     * @param array $log list of actions performed for a successful merging;
     * or a problem description if merging failed.
     * @param string|null $status the status: pending, inprogress, success, error. When null, it will be derived from $success.
     * @param array|null $tohint optional ['field' => ..., 'value' => ...] describing what a gathering searched for
     * when $touserid could not be resolved (id <= 0). Ignored otherwise.
     * @param array|null $fromhint same as $tohint, for $fromuserid.
     * @return bool|int false when could not insert the record; the log id when success.
     * @throws moodle_exception when log record cannot be inserted.
     */
    public function log(
        int $touserid,
        int $fromuserid,
        bool $success,
        array $log,
        ?string $status = null,
        ?array $tohint = null,
        ?array $fromhint = null,
    ): bool|int {
        global $DB, $USER;

        $currenttime = time();

        // Add user snapshots to the log data.
        $logdata = [
            'user_snapshots' => self::capture_user_snapshots($touserid, $fromuserid, $tohint, $fromhint),
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

        // Store user snapshots in log data.
        $logdata = [
            'user_snapshots' => self::capture_user_snapshots($touserid, $fromuserid),
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
            'id, touserid, fromuserid, mergedbyuserid, timecreated, timemodified, status, log',
            $limitfrom,
            $limitnum,
        );
        if (!$logs) {
            return $logs;
        }
        $this->attach_related_users($logs);
        return $logs;
    }

    /**
     * Searches merge logs, matching $searchterm against the numeric id/touserid/
     * fromuserid/mergedbyuserid columns (exact match, when the term is numeric), the
     * merge status, the current username/firstname/lastname/email/fullname (firstname
     * and lastname combined, so a "firstname lastname"-shaped term matches even with
     * two-part surnames) of the users involved, and the beginning of the raw log
     * content (which keeps a username/email snapshot captured at merge time, still
     * searchable even after the real {user} row has since been deleted). A null/empty
     * $searchterm returns every log, in the same shape and order as get() without a
     * filter.
     *
     * @param string|null $searchterm free-text term to search for, or null/empty for no filter.
     * @param int $limitfrom starting number of record to get. 0 to get all.
     * @param int $limitnum maximum number of records to get. 0 to get all.
     * @param string $sort SQL ordering, defaults to "timemodified DESC".
     * @return array list of merging logs matching the search, in the same shape as get().
     * @throws dml_exception
     */
    public function search(
        ?string $searchterm,
        int $limitfrom = 0,
        int $limitnum = 0,
        string $sort = "timemodified DESC",
    ): array {
        global $DB;

        [$where, $params] = $this->build_search_where($searchterm);
        $sql = "SELECT tm.id, tm.touserid, tm.fromuserid, tm.mergedbyuserid, tm.timecreated, tm.timemodified,
                       tm.status, tm.log
                  FROM {tool_mergeusers} tm
             LEFT JOIN {user} tou ON tou.id = tm.touserid
             LEFT JOIN {user} fru ON fru.id = tm.fromuserid
             LEFT JOIN {user} mbu ON mbu.id = tm.mergedbyuserid
                 WHERE $where
              ORDER BY $sort";

        $logs = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        if (!$logs) {
            return [];
        }
        $this->attach_related_users($logs);
        return $logs;
    }

    /**
     * Counts merge logs matching $searchterm, using the same criteria as search().
     *
     * @param string|null $searchterm free-text term to search for, or null/empty for no filter.
     * @return int number of matching logs.
     * @throws dml_exception
     */
    public function count_search(?string $searchterm): int {
        global $DB;

        [$where, $params] = $this->build_search_where($searchterm);
        $sql = "SELECT COUNT(1)
                  FROM {tool_mergeusers} tm
             LEFT JOIN {user} tou ON tou.id = tm.touserid
             LEFT JOIN {user} fru ON fru.id = tm.fromuserid
             LEFT JOIN {user} mbu ON mbu.id = tm.mergedbyuserid
                 WHERE $where";

        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Builds the WHERE clause and bound parameters shared by search() and
     * count_search(). Assumes a query aliasing {tool_mergeusers} as "tm" and left
     * joining {user} as "tou"/"fru"/"mbu" for touserid/fromuserid/mergedbyuserid.
     *
     * @param string|null $searchterm free-text term to search for, or null/empty for no filter.
     * @return array{0: string, 1: array} [$where, $params].
     */
    private function build_search_where(?string $searchterm): array {
        global $DB;

        $term = trim((string) $searchterm);
        if ($term === '') {
            return ['1 = 1', []];
        }

        // Moodle's DML layer does not support reusing the same named placeholder more
        // than once in a single query (moodle_database::fix_sql_params() requires the
        // number of :name occurrences to match the number of distinct keys in
        // $params), so every LIKE/equality branch below gets its own uniquely-named
        // parameter, all bound to the same value.
        $like = '%' . $DB->sql_like_escape($term) . '%';
        $loglength = $this->get_log_search_maxlength();
        $logexpr = $DB->sql_substr('tm.log', 1, $loglength);

        $likecolumns = [
            'tm.status',
            $logexpr,
            'tou.username',
            'tou.firstname',
            'tou.lastname',
            'tou.email',
            $DB->sql_fullname('tou.firstname', 'tou.lastname'),
            'fru.username',
            'fru.firstname',
            'fru.lastname',
            'fru.email',
            $DB->sql_fullname('fru.firstname', 'fru.lastname'),
            'mbu.username',
            'mbu.firstname',
            'mbu.lastname',
            'mbu.email',
            $DB->sql_fullname('mbu.firstname', 'mbu.lastname'),
        ];

        $params = [];
        $likeparts = [];
        foreach ($likecolumns as $i => $column) {
            $paramname = 'term' . $i;
            $params[$paramname] = $like;
            $likeparts[] = $DB->sql_like($column, ":$paramname", false, false);
        }

        if (ctype_digit($term)) {
            $idcolumns = ['tm.id', 'tm.touserid', 'tm.fromuserid', 'tm.mergedbyuserid'];
            foreach ($idcolumns as $i => $column) {
                $paramname = 'idexact' . $i;
                $params[$paramname] = (int) $term;
                $likeparts[] = "$column = :$paramname";
            }
        }

        return ['(' . implode(' OR ', $likeparts) . ')', $params];
    }

    /**
     * Reads the tool_mergeusers/logsearchmaxlength setting, falling back to 1000
     * when unset or invalid, so search() never scans an unbounded amount of the
     * log column per row.
     *
     * @return int
     */
    private function get_log_search_maxlength(): int {
        $length = (int) get_config('tool_mergeusers', 'logsearchmaxlength');
        return ($length > 0) ? $length : 1000;
    }

    /**
     * Attaches the related {user} records (to/from/mergedby) onto each log row,
     * shared by get() and search().
     *
     * @param array $logs list of tool_mergeusers rows, modified in place.
     */
    private function attach_related_users(array &$logs): void {
        global $DB;

        foreach ($logs as &$log) {
            $log->to = $DB->get_record('user', ['id' => $log->touserid]);
            $log->from = $DB->get_record('user', ['id' => $log->fromuserid]);

            if (empty($log->mergedbyuserid)) {
                $log->mergedby = null;
            } else {
                $log->mergedby = $DB->get_record('user', ['id' => $log->mergedbyuserid]);
            }
        }
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
     * Captures normalized snapshots for both users involved in a merge, sharing a
     * single capture timestamp (when this pair was captured, not when the merge
     * itself happened - useful during db/upgrade.php normalization, where the
     * capture can happen long after the original merge).
     *
     * @param int $touserid user.id to keep.
     * @param int $fromuserid user.id to remove.
     * @param array|null $tohint optional ['field' => ..., 'value' => ...] a gathering searched for $touserid.
     * @param array|null $fromhint same as $tohint, for $fromuserid.
     * @return array{timemodified: int, to_user: stdClass, from_user: stdClass}
     */
    public static function capture_user_snapshots(
        int $touserid,
        int $fromuserid,
        ?array $tohint = null,
        ?array $fromhint = null,
    ): array {
        return [
            'timemodified' => time(),
            'to_user' => self::capture_user_snapshot($touserid, $tohint),
            'from_user' => self::capture_user_snapshot($fromuserid, $fromhint),
        ];
    }

    /**
     * Captures a normalized snapshot of a single user's data.
     *
     * Always returns a well-formed object (never a bare null), distinguishing:
     * - notfound: $userid is not a real user id (e.g. <= 0, produced by an
     *   external gathering that could not resolve a username to a user id).
     * - recoverable false: a real id, but no {user} row could be found for it.
     * - recoverable true: real, resolvable user data.
     *
     * @param int $userid user.id to capture snapshot of.
     * @param array|null $hint optional ['field' => ..., 'value' => ...] a gathering searched for, used only
     * when $userid <= 0.
     * @return stdClass normalized user snapshot.
     */
    public static function capture_user_snapshot(int $userid, ?array $hint = null): stdClass {
        global $DB;

        $searchedfield = $hint['field'] ?? null;
        $searchedvalue = $hint['value'] ?? null;

        if ($userid <= 0) {
            return self::notfound_snapshot($searchedfield, $searchedvalue);
        }

        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            return self::apply_search_hint(self::unrecoverable_snapshot($userid), $searchedfield, $searchedvalue);
        }

        return self::apply_search_hint(self::snapshot_from_user($user), $searchedfield, $searchedvalue);
    }

    /**
     * Builds a snapshot marking that no real user id was available to capture from.
     *
     * When a gathering reports the field and value it searched for, and that field
     * is one of the fields the web UI itself allows searching by (excluding id,
     * already covered by the unrecoverable_snapshot() shape), the matching existing
     * field is populated with the searched value instead of staying null - the rest
     * of the shape is untouched, so no new keys are ever added.
     *
     * @param string|null $searchedfield the field a gathering searched by, if any.
     * @param string|null $searchedvalue the value a gathering searched for, if any.
     * @return stdClass
     */
    public static function notfound_snapshot(?string $searchedfield = null, ?string $searchedvalue = null): stdClass {
        $snapshot = (object) [
            'notfound' => true,
            'recoverable' => false,
            'id' => null,
            'username' => null,
            'email' => null,
            'firstname' => null,
            'lastname' => null,
            'idnumber' => null,
            'suspended' => null,
            'deleted' => null,
            'erasedforgdpr' => false,
            'timeerased' => null,
        ];

        return self::apply_search_hint($snapshot, $searchedfield, $searchedvalue);
    }

    /**
     * Overwrites a snapshot's field with a searched value a gathering reported, when
     * that field is one of the fields the web UI itself allows searching by. Used both
     * for a notfound side (the only data it will ever have) and for a side that DID
     * resolve to a real user - e.g. a gathering that renames a username in place on a
     * single account (no real merge needed) can still report what the old username
     * was, so it prevails over the live value for that field alone; every other field
     * (email, idnumber, id, suspended, deleted...) keeps reflecting the real user.
     *
     * @param stdClass $snapshot the snapshot to (maybe) adjust.
     * @param string|null $searchedfield the field a gathering searched by, if any.
     * @param string|null $searchedvalue the value a gathering searched for, if any.
     * @return stdClass the same $snapshot instance, for chaining.
     */
    private static function apply_search_hint(stdClass $snapshot, ?string $searchedfield, ?string $searchedvalue): stdClass {
        if ($searchedvalue !== null && in_array($searchedfield, self::ALLOWED_SEARCH_FIELDS, true)) {
            $snapshot->$searchedfield = $searchedvalue;
        }

        return $snapshot;
    }

    /**
     * Builds a snapshot marking a real user id for which no data could be recovered
     * (neither a live {user} row nor a previously captured snapshot).
     *
     * @param int $userid
     * @return stdClass
     */
    public static function unrecoverable_snapshot(int $userid): stdClass {
        return (object) [
            'notfound' => false,
            'recoverable' => false,
            'id' => $userid,
            'username' => null,
            'email' => null,
            'firstname' => null,
            'lastname' => null,
            'idnumber' => null,
            'suspended' => null,
            'deleted' => null,
            'erasedforgdpr' => false,
            'timeerased' => null,
        ];
    }

    /**
     * Builds a fully recoverable snapshot from a {user} record.
     *
     * @param stdClass $user a {user} table record (at least id/username/email/
     * firstname/lastname/idnumber/suspended/deleted).
     * @return stdClass
     */
    public static function snapshot_from_user(stdClass $user): stdClass {
        return (object) [
            'notfound' => false,
            'recoverable' => true,
            'id' => (int) $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'idnumber' => $user->idnumber,
            'suspended' => (bool) $user->suspended,
            'deleted' => (bool) $user->deleted,
            'erasedforgdpr' => false,
            'timeerased' => null,
        ];
    }
}

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
 * Privacy Subsystem implementation for tool_mergeusers.
 *
 * @package   tool_mergeusers
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy provider for tool_mergeusers.
 *
 * This plugin stores merge logs containing user snapshots (username, email, idnumber)
 * for audit purposes, along with references to users involved in merge operations.
 *
 * @package   tool_mergeusers
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('tool_mergeusers', [
                'touserid' => 'privacy:metadata:tool_mergeusers:touserid',
                'fromuserid' => 'privacy:metadata:tool_mergeusers:fromuserid',
                'mergedbyuserid' => 'privacy:metadata:tool_mergeusers:mergedbyuserid',
                'log' => 'privacy:metadata:tool_mergeusers:log',
                'timecreated' => 'privacy:metadata:tool_mergeusers:timecreated',
                'timemodified' => 'privacy:metadata:tool_mergeusers:timemodified',
            ], 'privacy:metadata:tool_mergeusers');
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // All merge logs are stored in the system context.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :contextlevel
                   AND EXISTS (
                       SELECT 1
                         FROM {tool_mergeusers} tm
                        WHERE tm.touserid = :touserid
                           OR tm.fromuserid = :fromuserid
                           OR tm.mergedbyuserid = :mergedbyuserid
                   )";
        $params = [
            'contextlevel' => CONTEXT_SYSTEM,
            'touserid' => $userid,
            'fromuserid' => $userid,
            'mergedbyuserid' => $userid,
        ];

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_system) {
            return;
        }

        // Find all users referenced in merge logs.
        $sql = "SELECT DISTINCT touserid as userid
                  FROM {tool_mergeusers}
                 UNION
                SELECT DISTINCT fromuserid as userid
                  FROM {tool_mergeusers}
                 UNION
                SELECT DISTINCT mergedbyuserid as userid
                  FROM {tool_mergeusers}";

        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        // Only system context is relevant for merge logs.
        $context = \context_system::instance();
        if (!in_array($context->id, $contextlist->get_contextids())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        // Get all merge logs where user is involved.
        $sql = "SELECT tm.*
                  FROM {tool_mergeusers} tm
                 WHERE tm.touserid = :touserid
                    OR tm.fromuserid = :fromuserid
                    OR tm.mergedbyuserid = :mergedbyuserid
              ORDER BY tm.timecreated DESC";
        $params = [
            'touserid' => $userid,
            'fromuserid' => $userid,
            'mergedbyuserid' => $userid,
        ];

        $mergelogs = $DB->get_recordset_sql($sql, $params);
        $data = [];

        foreach ($mergelogs as $mergelog) {
            $logdata = json_decode($mergelog->log, true);

            $exportdata = (object) [
                'touserid' => $mergelog->touserid,
                'fromuserid' => $mergelog->fromuserid,
                'mergedbyuserid' => $mergelog->mergedbyuserid,
                'status' => $mergelog->status ?? get_string('unknown', 'core'),
                'timecreated' => transform::datetime($mergelog->timecreated),
                'timemodified' => transform::datetime($mergelog->timemodified),
            ];

            // Include user snapshots if present (contains username, email, idnumber).
            if (!empty($logdata['user_snapshots'])) {
                $exportdata->user_snapshots = $logdata['user_snapshots'];
            } else if (isset($logdata['user_snapshots']) && $logdata['user_snapshots'] === null) {
                // Indicate that snapshots have been anonymized.
                $exportdata->user_snapshots_anonymized = true;
            }

            // Include action log summary.
            if (!empty($logdata['actions'])) {
                $exportdata->actions_count = count($logdata['actions']);
            }

            $data[] = $exportdata;
        }
        $mergelogs->close();

        if (!empty($data)) {
            writer::with_context($context)->export_data(['tool_mergeusers'], (object) ['mergelogs' => $data]);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_system) {
            return;
        }

        // Anonymize user snapshots in all merge logs instead of deleting records.
        // This preserves audit trail with user IDs and timestamps while removing personal data.
        $logs = $DB->get_records('tool_mergeusers');
        foreach ($logs as $log) {
            $logdata = json_decode($log->log, true);
            if (!empty($logdata)) {
                // Set user_snapshots to null to anonymize personal data.
                $logdata['user_snapshots'] = null;
                $log->log = json_encode($logdata);
                $DB->update_record('tool_mergeusers', $log);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        // Only system context is relevant.
        $context = \context_system::instance();
        if (!in_array($context->id, $contextlist->get_contextids())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        // Anonymize user snapshots in logs where this user is involved.
        // This preserves audit trail while removing personal data from snapshots.
        $select = "touserid = :touserid OR fromuserid = :fromuserid OR mergedbyuserid = :mergedbyuserid";
        $params = [
            'touserid' => $userid,
            'fromuserid' => $userid,
            'mergedbyuserid' => $userid,
        ];

        $logs = $DB->get_records_select('tool_mergeusers', $select, $params);
        foreach ($logs as $log) {
            $logdata = json_decode($log->log, true);
            if (!empty($logdata) && !empty($logdata['user_snapshots'])) {
                // Selectively remove this user's snapshot only.
                if (
                    !empty($logdata['user_snapshots']['to_user']) &&
                    (int) $logdata['user_snapshots']['to_user']['id'] === $userid
                ) {
                    $logdata['user_snapshots']['to_user'] = null;
                }
                if (
                    !empty($logdata['user_snapshots']['from_user']) &&
                    (int) $logdata['user_snapshots']['from_user']['id'] === $userid
                ) {
                    $logdata['user_snapshots']['from_user'] = null;
                }

                // If both snapshots are now null, set the whole user_snapshots to null.
                if (empty($logdata['user_snapshots']['to_user']) && empty($logdata['user_snapshots']['from_user'])) {
                    $logdata['user_snapshots'] = null;
                }

                $log->log = json_encode($logdata);
                $DB->update_record('tool_mergeusers', $log);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_system) {
            return;
        }

        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        // Use SQL_PARAMS_QM so array_merge works correctly with positional parameters.
        // With question marks, array_merge properly reindexes numeric keys for multiple field conditions.
        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_QM);

        $select = "touserid $usersql OR fromuserid $usersql OR mergedbyuserid $usersql";
        $params = array_merge($userparams, $userparams, $userparams);

        $logs = $DB->get_records_select('tool_mergeusers', $select, $params);

        // Create a map of userids for quick lookup.
        $useridmap = array_flip($userids);

        foreach ($logs as $log) {
            $logdata = json_decode($log->log, true);
            if (!empty($logdata) && !empty($logdata['user_snapshots'])) {
                $modified = false;

                // Anonymize to_user snapshot if user is in deletion list.
                if (!empty($logdata['user_snapshots']['to_user'])) {
                    $touserid = (int) $logdata['user_snapshots']['to_user']['id'];
                    if (isset($useridmap[$touserid])) {
                        $logdata['user_snapshots']['to_user'] = null;
                        $modified = true;
                    }
                }

                // Anonymize from_user snapshot if user is in deletion list.
                if (!empty($logdata['user_snapshots']['from_user'])) {
                    $fromuserid = (int) $logdata['user_snapshots']['from_user']['id'];
                    if (isset($useridmap[$fromuserid])) {
                        $logdata['user_snapshots']['from_user'] = null;
                        $modified = true;
                    }
                }

                // If both snapshots are now null, set the whole user_snapshots to null.
                if (
                    $modified && empty($logdata['user_snapshots']['to_user']) &&
                    empty($logdata['user_snapshots']['from_user'])
                ) {
                    $logdata['user_snapshots'] = null;
                }

                if ($modified) {
                    $log->log = json_encode($logdata);
                    $DB->update_record('tool_mergeusers', $log);
                }
            }
        }
    }
}

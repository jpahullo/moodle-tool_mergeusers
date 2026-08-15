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
 * Web service: query merge request status/logs.
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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use tool_mergeusers\local\logger;
use tool_mergeusers\local\status;

/**
 * Either a single merge log by id, or a filtered/paginated list - same data
 * logger::get()/log.php already expose, reused as-is.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_merge_request_status extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'logid' => new external_value(PARAM_INT, 'Specific log id to fetch; 0 to list/filter instead', VALUE_DEFAULT, 0),
            'fromuserid' => new external_value(PARAM_INT, 'Filter: id of the user removed', VALUE_DEFAULT, 0),
            'touserid' => new external_value(PARAM_INT, 'Filter: id of the user kept', VALUE_DEFAULT, 0),
            'status' => new external_value(
                PARAM_ALPHA,
                'Filter: pending, inprogress, success, error, renamed',
                VALUE_DEFAULT,
                '',
            ),
            'limitfrom' => new external_value(PARAM_INT, 'Pagination offset', VALUE_DEFAULT, 0),
            'limitnum' => new external_value(
                PARAM_INT,
                'Maximum rows to return, capped by tool_mergeusers/logpagesize',
                VALUE_DEFAULT,
                0,
            ),
        ]);
    }

    /**
     * Fetches one log by id, or a filtered/paginated list.
     *
     * @param int $logid specific log id to fetch; 0 to list/filter instead.
     * @param int $fromuserid filter: id of the user removed.
     * @param int $touserid filter: id of the user kept.
     * @param string $status filter: pending, inprogress, success, error, renamed.
     * @param int $limitfrom pagination offset.
     * @param int $limitnum maximum rows to return, capped by tool_mergeusers/logpagesize.
     * @return array{logs: array}
     */
    public static function execute(
        int $logid = 0,
        int $fromuserid = 0,
        int $touserid = 0,
        string $status = '',
        int $limitfrom = 0,
        int $limitnum = 0,
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'logid' => $logid,
            'fromuserid' => $fromuserid,
            'touserid' => $touserid,
            'status' => $status,
            'limitfrom' => $limitfrom,
            'limitnum' => $limitnum,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('tool/mergeusers:viewlog', $context);

        $logger = new logger();

        if ($params['logid']) {
            $logs = $logger->get(['id' => $params['logid']]);
        } else {
            $filter = [];
            if ($params['fromuserid']) {
                $filter['fromuserid'] = $params['fromuserid'];
            }
            if ($params['touserid']) {
                $filter['touserid'] = $params['touserid'];
            }
            if ($params['status'] !== '') {
                $filter['status'] = $params['status'];
            }

            $logpagesize = (int) get_config('tool_mergeusers', 'logpagesize');
            $effectivelimitnum = $params['limitnum'] ? min($params['limitnum'], $logpagesize) : $logpagesize;

            $logs = $logger->get($filter ?: null, $params['limitfrom'], $effectivelimitnum);
        }

        if (!$logs) {
            return ['logs' => []];
        }

        $result = [];
        foreach ($logs as $log) {
            $result[] = [
                'id' => (int) $log->id,
                'touserid' => (int) $log->touserid,
                'fromuserid' => (int) $log->fromuserid,
                'mergedbyuserid' => (int) $log->mergedbyuserid,
                'status' => status::safe_from($log->status)->value,
                'origin' => $log->origin ?? '',
                'timecreated' => (int) $log->timecreated,
                'timemodified' => (int) $log->timemodified,
                'log' => $log->log,
            ];
        }

        return ['logs' => $result];
    }

    /**
     * Return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'logs' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Merge log id'),
                    'touserid' => new external_value(PARAM_INT, 'User kept'),
                    'fromuserid' => new external_value(PARAM_INT, 'User removed'),
                    'mergedbyuserid' => new external_value(PARAM_INT, 'User who initiated the merge'),
                    'status' => new external_value(PARAM_ALPHA, 'pending, inprogress, success, error, renamed'),
                    'origin' => new external_value(
                        PARAM_ALPHA,
                        'web, cli, or ws - empty for a legacy log recorded before this column existed',
                    ),
                    'timecreated' => new external_value(PARAM_INT, 'Unix timestamp'),
                    'timemodified' => new external_value(PARAM_INT, 'Unix timestamp'),
                    'log' => new external_value(PARAM_RAW, 'JSON-encoded log detail (user snapshots and actions performed)'),
                ]),
            ),
        ]);
    }
}

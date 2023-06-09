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
/*
* Merge users external api.
* @package     mergeuser
* @author      Nicola Vallinoto <n.vallinoto@liguriadigitale.it>
* @copyright   2023 Liguria Digitale (https://www.liguriadigitale.it)
* @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
namespace tool_mergeusers\external;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->libdir}/externallib.php");
defined('MOODLE_INTERNAL') || die();
use \tool_mergeusers\merge_request;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
class enqueue_merge_request extends \external_api {
    /**
     * Webservice enqueue_merge_request parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
            return new external_function_parameters(
            [
                'removeuserfield' => new external_value(PARAM_TEXT, 'Remove user field'),
                'removeuservalue' => new external_value(PARAM_RAW, 'Remove user value'),
                'keepuserfield' => new external_value(PARAM_TEXT, 'Keep user field'),
                'keepuservalue' => new external_value(PARAM_RAW, 'Keep user value'),
            ]
        );
    }
    /**
     * Return identifier of the enqueue merge request
     *
     * @param $removeuserid
     * @param $removeuservalue
     * @param $keepuserid
     * @param $keepuservalue
     */
    public static function execute(string $removeuserfield,
                                     string $removeuservalue,
                                   string $keepuserfield,
                                   string $keepuservalue): int {
        // Validate all of the parameters.
        $params = self::validate_parameters(self::execute_parameters(),
                                            ['removeuserfield' => $removeuserfield,
                                            'removeuservalue' => $removeuservalue,
                                            'keepuserfield' => $keepuserfield,
                                            'keepuservalue' => $keepuservalue
                                            ]);
        global $DB;
        // Insert of the merge request into mdl_merge_request_queue Moodle table.
        $usertoremove = $DB->get_records(merge_request::TABLE_USERS,
                                        [$removeuserfield => $removeuservalue]);
        if (count($usertoremove) == 0) {
            throw new Exception(get_string('cannotfindusertoremove', 'tool_mergeusers'));
        }
        if (count($usertoremove) > 1) {
            throw new Exception(get_string('toomanyuserstoremovefound', 'tool_mergeusers'));
        }
        foreach ($usertoremove as $item) {
            $removeuserid = $item->id;
        }
        // Verify user to keep.
        $usertokeep = $DB->get_records(merge_request::TABLE_USERS,
                                        [$keepuserfield => $keepuservalue]);
        if (count($usertokeep) == 0) {
            throw new Exception(get_string('cannotfindusertokeep', 'tool_mergeusers'));
        }
        if (count($usertokeep) > 1) {
            throw new Exception(get_string('toomanyuserstokeepfound', 'tool_mergeusers'));
        }
        foreach ($usertokeep as $item) {
            $keepuserid = $item->id;
        }
        $timeadded = time();
        $status = merge_request::QUEUED_NOT_PROCESSED;
        $retries = 0;
        $idrecord = $DB->insert_record(
            merge_request::TABLE_MERGE_REQUEST,
            [
                'removeuserfield' => $removeuserfield,
                'removeuservalue' => $removeuservalue,
                'removeuserid' => $removeuserid,
                'keepuserfield' => $keepuserfield,
                'keepuservalue' => $keepuservalue,
                'keepuserid' => $keepuserid,
                'timeadded' => $timeadded,
                'status' => $status,
                'retries' => $retries
            ],
            $returnid = true,
            $bulk = false
        );
        // Return a value as described in the returns function.
        return $idrecord;
    }
    public static function execute_returns() {
            return new external_value(PARAM_INT, 'Identifier of the merge request');
    }
}

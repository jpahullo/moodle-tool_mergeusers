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
namespace tool_mergeusers\external;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->libdir}/externallib.php");
use \tool_mergeusers\merge_request;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
/**
 * Version information
 *
 * @package     tool
 * @subpackage  mergeusers
 * @author      Nicola Vallinoto, Liguria Digitale
 * @author      Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_data_merge_requests extends \external_api {
    /**
     * Webservice tool_mergeusers_get_data_merge_request parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mergeusers' => new external_single_structure([
                'removeuserfield' => new external_value(
                    PARAM_TEXT, 'Remove user field', VALUE_OPTIONAL),
                'removeuservalue' => new external_value(
                    PARAM_RAW, 'Remove user value', VALUE_OPTIONAL),
                'removeuserid' => new external_value(
                    PARAM_INT, 'Identifier of the remove user id', VALUE_OPTIONAL),
                'keepuserfield' => new external_value(
                    PARAM_TEXT, 'Keep user field', VALUE_OPTIONAL),
                'keepuservalue' => new external_value(
                    PARAM_RAW, 'Keep user value', VALUE_OPTIONAL),
                'keepuserid' => new external_value(
                    PARAM_INT, 'Identifier of the keep user id', VALUE_OPTIONAL),
                'id' => new external_value(
                    PARAM_INT, 'Identifier of the merge request', VALUE_OPTIONAL),
                'status' => new external_value(
                    PARAM_INT, 'Status of the merge request', VALUE_OPTIONAL)
            ])
        ]);
    }
    /**
     * Return data of the custom queue of the merge request
     *
     * @param $mergeusers
     * @param $removeuserid
     * @param $removeuservalue
     * @param $keepuserid
     * @param $keepuservalue
     * @param $id
     * @param $status
     */
    public static function execute(array $mergeusers) {
        global $DB;
        // Validate all of the parameters.
        $params = array();
        /*$params = self::validate_parameters(self::execute_parameters(),
                                                array('id' => $mergeusers->id));*/
        $tablemr = merge_request::TABLE_MERGE_REQUEST;
        $sql = "SELECT
                    id, removeuserfield, removeuservalue, removeuserid,
                    keepuserfield, keepuservalue, keepuserid,
                    timeadded, timemodified, status, retries, log
                FROM
                   {".$tablemr."}
                WHERE
                     (1=1) ";
        if ($mergeusers['removeuserfield'] != "" || $mergeusers['removeuserfield'] != null) {
            $sql = $sql." AND removeuserfield = ?";
            array_push($params, $mergeusers['removeuserfield']);
        }
        if ($mergeusers['removeuservalue'] != "" || $mergeusers['removeuservalue'] != null) {
            $sql = $sql." AND removeuservalue = ?";
            array_push($params, $mergeusers['removeuservalue']);
        }
        if ($mergeusers['removeuserid'] != "" || $mergeusers['removeuserid'] != null) {
            $sql = $sql." AND removeuserid = ?";
            array_push($params, $mergeusers['removeuserid']);
        }
        if ($mergeusers['keepuserfield'] != '' || $mergeusers['keepuserfield'] != null) {
            $sql = $sql." AND keepuserfield = ?";
            array_push($params, $mergeusers['keepuserfield']);
        }
        if ($mergeusers['keepuservalue'] != "" || $mergeusers['keepuservalue'] != null) {
            $sql = $sql." AND keepuservalue = ?";
            array_push($params, $mergeusers['keepuservalue']);
        }
        if ($mergeusers['keepuserid'] != "" || $mergeusers['keepuserid'] != null) {
            $sql = $sql." AND keepuserid = ?";
            array_push($params, $mergeusers['keepuserid']);
        }
        if ($mergeusers['id'] != "" || $mergeusers['id'] != null) {
            $sql = $sql." AND id = ?";
            array_push($params, $mergeusers['id']);
        }
        if ($mergeusers['status'] != "" || $mergeusers['status'] != null) {
            $sql = $sql." AND status = ?";
            array_push($params, $mergeusers['status']);
        }
        $queuelist  = $DB->get_records_sql($sql, $params);
        return $queuelist;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'User id'),
                'removeuserfield' => new external_value(PARAM_TEXT, 'Remove user field'),
                'removeuservalue' => new external_value(PARAM_TEXT, 'Remove user value'),
                'removeuserid' => new external_value(PARAM_INT, 'Remove user id'),
                'keepuserfield' => new external_value(PARAM_TEXT, 'Keep user field'),
                'keepuservalue' => new external_value(PARAM_TEXT, 'Keep user value'),
                'keepuserid' => new external_value(PARAM_INT, 'Keep user id'),
                'timeadded' => new external_value(PARAM_RAW, 'Time creation'),
                'timemodified' => new external_value(PARAM_RAW, 'Time modified'),
                'status' => new external_value(PARAM_INT, 'Status'),
                'retries' => new external_value(PARAM_INT, 'Number of retries'),
                'log' => new external_value(PARAM_RAW, 'Log')
            ])
        );
    }
}

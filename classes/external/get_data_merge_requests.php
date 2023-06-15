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
                'removeuserfield' => new external_value(
                    PARAM_TEXT, 'Remove user field',  VALUE_DEFAULT, ''),
                'removeuservalue' => new external_value(
                    PARAM_RAW, 'Remove user value',  VALUE_DEFAULT, ''),
                'removeuserid' => new external_value(
                    PARAM_INT, 'Identifier of the remove user id',  VALUE_DEFAULT, 0),
                'keepuserfield' => new external_value(
                    PARAM_TEXT, 'Keep user field',  VALUE_DEFAULT, ''),
                'keepuservalue' => new external_value(
                    PARAM_RAW, 'Keep user value',  VALUE_DEFAULT, ''),
                'keepuserid' => new external_value(
                    PARAM_INT, 'Identifier of the keep user id',  VALUE_DEFAULT, 0),
                'id' => new external_value(
                    PARAM_INT, 'Identifier of the merge request',  VALUE_DEFAULT, 0),
                'status' => new external_value(
                    PARAM_INT, 'Status of the merge request',  VALUE_DEFAULT, 0)
        ]);
    }
    /**
     * Return data of the custom queue of the merge request
     *
     * @param $mergeusers id of the merge users request
     *
     * Return array with data of the custom queue of the merge request
     *
     */
    public static function execute(string $removeuserfield,
                                    string $removeuservalue,
                                    int $removeuserid,
                                    string $keepuserfield,
                                    string $keepuservalue,
                                    int $keepuserid,
                                    int $id,
                                    int $status): array {
        global $DB;
        // Validate all of the parameters.
        $params = array();
        $params = self::validate_parameters(self::execute_parameters(),
                                            ['removeuserfield' => $removeuserfield,
                                            'removeuservalue' => $removeuservalue,
                                            'removeuserid' => $removeuserid,
                                            'keepuserfield' => $keepuserfield,
                                            'keepuservalue' => $keepuservalue,
                                            'keepuserid' => $keepuserid,
                                            'id' => $id,
                                            'status' => $status]);
        $sql = "SELECT
                    id, removeuserfield, removeuservalue, removeuserid,
                    keepuserfield, keepuservalue, keepuserid,
                    timeadded, timemodified, status, retries, log
                FROM
                   {" .merge_request::TABLE_MERGE_REQUEST. "}
                ";
        $paramsquery = array();
        if (isset($removeuserfield) && !empty($removeuserfield)) {
            $whereclauses[] = 'removeuserfield = ?';
            array_push($paramsquery, $removeuserfield);
        }
        if (isset($removeuservalue) && !empty($removeuservalue)) {
            $whereclauses[] = 'removeuservalue = ?';
            array_push($paramsquery, $removeuservalue);
        }
        if (isset($removeuserid) && !empty($removeuserid)) {
            $whereclauses[] = 'removeuserid = ?';
            array_push($paramsquery, $removeuserid);
        }
        if (isset($keepuserfield) && !empty($keepuserfield)) {
            $whereclauses[] = 'keepuserfield = ?';
            array_push($paramsquery, $keepuserfield);
        }
        if (isset($keepuservalue) && !empty($keepuservalue)) {
            $whereclauses[] = 'keepuservalue = ?';
            array_push($paramsquery, $keepuservalue);
        }
        if (isset($keepuserid) && !empty($keepuserid)) {
            $whereclauses[] = 'keepuserid = ?';
            array_push($paramsquery, $keepuserid);
        }
        if (isset($id) && !empty($id)) {
            $whereclauses[] = 'id = ?';
            array_push($paramsquery, $id);
        }
        if (isset($status) && !empty($status)) {
            $whereclauses[] = 'status = ?';
            array_push($paramsquery, $status);
        }
        if (count($whereclauses) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $whereclauses);
        }
        return $DB->get_records_sql($sql, $paramsquery);
    }
    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure( [
                    'id' => new external_value(PARAM_INT, 'Merge users id'),
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
                ]
            )
        );
    }
}

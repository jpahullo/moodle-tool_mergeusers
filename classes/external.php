<?php
//namespace tool_mergeusers\external;
defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/externallib.php");
//require_once __DIR__ . '/merge_request.php';
use \tool_mergeusers\merge_request;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
/** 
 * Merge users external api.
 *
 * @package     mergeuser
 * @author      Nicola Vallinoto <n.vallinoto@liguriadigitale.it>
 * @copyright   2023 Liguria Digitale (https://www.liguriadigitale.it)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class tool_mergeusers_external extends external_api {
    /**
     * Webservice enqueue_merging_request parameters
     * 
     * @return external_function_parameters
     */
    public static function enqueue_merging_request_parameters(): external_function_parameters {
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
     * Return identifier of the enqueue merging request
     * 
     * @param $removeuserid
     * @param $removeuservalue
     * @param $keepuserid
     * @param $keepuservalue
     */
    public static function enqueue_merging_request(string $removeuserfield, 
                                   string $removeuservalue, 
                                   string $keepuserfield,
                                   string $keepuservalue): int {
        // Validate all of the parameters.
        $params = self::validate_parameters(self::enqueue_merging_request_parameters(),
                                            ['removeuserfield' => $removeuserfield, 
                                            'removeuservalue' => $removeuservalue,
                                            'keepuserfield' => $keepuserfield,
                                            'keepuservalue' => $keepuservalue
                                            ]);
                                                                        
        global $DB;
        // Insert of the merging request into mdl_merging_request_queue Moodle table.
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
        // Verify user to keep
        $usertokeep = $DB->get_records(merge_request::TABLE_USERS, 
                                        [$keepuserfield => $keepuservalue]);
        if (count($usertokeep) == 0) {
            throw new Exception(get_string('cannotfindusertokeep', 'tool_mergeusers'));
        } 
        if (count($usertokeep) > 1) {
            throw new Exception(get_string('toomanyuserstokeepfound', 'tool_mergeusers'));
        }
        //$keepuserid = (int)$usertokeep[0]->id;      
        foreach ($usertokeep as $item) {
            $keepuserid = $item->id; 
        }
        $timeadded = time();
        $status = 1; // request queued.
        $retries = 0;
        $idrecord = $DB->insert_record(
            merge_request::TABLE_MERGE_REQUEST ,
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
    public static function enqueue_merging_request_returns() {
        return new external_value(PARAM_INT, 'Identifier of the merging request');
    }
     /**
     * Webservice tool_mergeusers_get_data_merging_request parameters
     * 
     * @return external_function_parameters
     */
    public static function get_data_merging_requests_parameters(): external_function_parameters {
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
                    PARAM_INT, 'Identifier of the merging request', VALUE_OPTIONAL),
                'status' => new external_value(
                    PARAM_INT, 'Status of the merging request', VALUE_OPTIONAL)
            ])
        ]);
    }
        /**
     * Return data of the custom queue of the merging request
     * 
     * @param $mergeusers
     * @param $removeuserid
     * @param $removeuservalue
     * @param $keepuserid
     * @param $keepuservalue
     * @param $id
     * @param $status
     */
    public static function get_data_merging_requests(array $mergeusers) {
        global $DB;
            // Validate all of the parameters.
        $params = array();
        /*$params = self::validate_parameters(self::get_data_merging_requests_parameters(), 
                                         $mergeusers);*/
        
        $tablemr = merge_request::TABLE_MERGE_REQUEST;
        $sql = "SELECT
                    id, removeuserfield, removeuservalue, removeuserid,
                    keepuserfield, keepuservalue, keepuserid, 
                    timeadded, timemodified, status, retries, log
                FROM 
                   {".$tablemr."}
                WHERE 
                     (1=1) ";
            
        if ($mergeusers['removeuserfield']!="" or $mergeusers['removeuserfield']!=null) {
            $sql = $sql." AND removeuserfield = ?"; 
            array_push($params, $mergeusers['removeuserfield']);
        }
        if ($mergeusers['removeuservalue']!="" or $mergeusers['removeuservalue']!=null) {
            $sql = $sql." AND removeuservalue = ?"; 
            array_push($params, $mergeusers['removeuservalue']);
        }   
        if ($mergeusers['removeuserid']!="" or $mergeusers['removeuserid']!=null) {
            $sql = $sql." AND removeuserid = ?"; 
            array_push($params, $mergeusers['removeuserid']);
        }   
        if ($mergeusers['keepuserfield']!='' or $mergeusers['keepuserfield']!=null) {
            $sql = $sql." AND keepuserfield = ?"; 
            array_push($params, $mergeusers['keepuserfield']);
        }
        if ($mergeusers['keepuservalue']!="" or $mergeusers['keepuservalue']!=null) {
            $sql = $sql." AND keepuservalue = ?"; 
            array_push($params, $mergeusers['keepuservalue']);
        }   
        if ($mergeusers['keepuserid']!="" or $mergeusers['keepuserid']!=null) {
            $sql = $sql." AND keepuserid = ?"; 
            array_push($params, $mergeusers['keepuserid']);
        }   
        if ($mergeusers['id']!="" or $mergeusers['id']!=null) {
            $sql = $sql." AND id = ?"; 
            array_push($params, $mergeusers['id']);
        }  
        if ($mergeusers['status']!="" or $mergeusers['status']!=null) {
            $sql = $sql." AND status = ?"; 
            array_push($params, $mergeusers['status']);
        }  
        $queue_list  = $DB->get_records_sql($sql, $params);
        
        return $queue_list;
    }

   
   
    public static function get_data_merging_requests_returns() {
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

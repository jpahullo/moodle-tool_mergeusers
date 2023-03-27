<?php
//namespace tool_mergeusers\external;
defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/externallib.php");
/*use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;*/
/** 
 * Merge users external api.
 *
 * @package     mergeuser
 * @copyright   2023 Liguria Digitale
 * @author      Nicola Vallinoto <n.vallinoto@liguriadigitale.it>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class tool_mergeusers_external extends external_api {
    /**
     * Webservice queue_merging_request parameters
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
     * Return identifier of the queue merging request
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
        // Perform security checks, for example:
        //$context = \context_system::instance();
        //self::validate_context($context);
        //require_capability('moodle/co urse:creategroups', $coursecontext);
        // Create the group using existing Moodle APIs.
        // inserire la richiesta nella tabella mdl_merging_request_queue
        $merginguserstable = 'tool_mergeusers_queue';
        $userstable = 'user';
        $usertoremove = $DB->get_record($userstable, [$removeuserfield => $removeuservalue]);
        $removeuserid = $usertoremove->id;    
        $usertokeep = $DB->get_record($userstable, [$keepuserfield => $keepuservalue]);
        $keepuserid = $usertokeep->id;    
        $timeadded = time();
        $status = 1; // request queued.
        $retries = 0;
        $idrecord = $DB->insert_record(
            $merginguserstable ,
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
     * Webservice get_queue_list_merging_request parameters
     * 
     * @return external_function_parameters
     */
    public static function get_queue_list_merging_requests_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mergeusers' => new external_single_structure([
                'removeuserfield' => new external_value(
                    PARAM_TEXT, 'Remove user field', VALUE_OPTIONAL),
                'removeuservalue' => new external_value(
                    PARAM_RAW, 'Remove user value', VALUE_OPTIONAL),
                'removeuserid' => new external_value(
                    PARAM_INT, 'identifier of the remove user id', VALUE_OPTIONAL),
                'keepuserfield' => new external_value(
                    PARAM_TEXT, 'Keep user field', VALUE_OPTIONAL),
                'keepuservalue' => new external_value(
                    PARAM_RAW, 'Keep user value', VALUE_OPTIONAL),
                'keepuserid' => new external_value(
                    PARAM_INT, 'identifier of the keep user id', VALUE_OPTIONAL),               
                'id' => new external_value(
                    PARAM_INT, 'identifier of the merging request', VALUE_OPTIONAL),
                'status' => new external_value(
                    PARAM_TEXT, 'status of the merging request', VALUE_OPTIONAL)
            ])
        ]);
    }
        /**
     * Return the list of the current queue of the merging request
     * 
     * @param $mergeusers
     * @param $removeuserid
     * @param $removeuservalue
     * @param $keepuserid
     * @param $keepuservalue
     * @param $id
     * @param $status
     */
    public static function get_queue_list_merging_requests(array $mergeusers) {
        global $DB;
        $merginguserstable = 'tool_mergeusers_queue';
        //$id = (int)$mergeusers->id;
       // $id = (int) $mergeusers[id];
        // Validate all of the parameters.
        $params = self::validate_parameters(self::get_queue_list_merging_requests_parameters(), 
                                         $mergeusers);
        $params = array();
        $sql = "SELECT
                    id, removeuserfield, removeuservalue, removeuserid,
                    keepuserfield, keepuservalue, keepuserid, 
                    timeadded, timemodified, status, retries, log
                FROM 
                    {tool_mergeusers_queue}
                WHERE 
                     (1=1) ";
            
        if ($mergeusers['removeuserfield']!="") {
            $sql = $sql." AND removeuserfield = ?"; 
            array_push($params, $mergeusers['removeuserfield']);
        }
        if ($mergeusers['removeuservalue']!="") {
            $sql = $sql." AND removeuservalue = ?"; 
            array_push($params, $mergeusers['removeuservalue']);
        }   
        if ($mergeusers['removeuserid']!="") {
            $sql = $sql." AND removeuserid = ?"; 
            array_push($params, $mergeusers['removeuserid']);
        }   
        if ($mergeusers['keepuserfield']!='') {
            $sql = $sql." AND keepuserfield = ?"; 
            array_push($params, $mergeusers['keepuserfield']);
        }
        if ($mergeusers['keepuservalue']!="") {
            $sql = $sql." AND keepuservalue = ?"; 
            array_push($params, $mergeusers['keepuservalue']);
        }   
        if ($mergeusers['keepuserid']!="") {
            $sql = $sql." AND keepuserid = ?"; 
            array_push($params, $mergeusers['keepuserid']);
        }   
        if ($mergeusers['id']!="") {
            $sql = $sql." AND id = ?"; 
            array_push($params, $mergeusers['id']);
        }  
        if ($mergeusers['status']!="") {
            $sql = $sql." AND status = ?"; 
            array_push($params, $mergeusers['status']);
        }  
        $queue_list  = $DB->get_records_sql($sql, $params);
        // Return a value as described in the returns function.
        return $queue_list;
    }

   
   
    public static function get_queue_list_merging_requests_returns() {
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

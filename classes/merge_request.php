<?php
/**
 * Version information
 *
 * @package     tool
 * @subpackage  mergeusers
 * @author      Nicola Vallinoto, Liguria Digitale
 * @author      Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_mergeusers;
defined('MOODLE_INTERNAL') || die();
class merge_request {
      /**
     * Missing merge request id. This id really does not exist.
     * @var integer
     */
    const MISSING_MERGE_REQUEST_ID = 1;
    /**
     * Merge request queued but not yet processed.
     * @var integer
     */
    const QUEUED_NOT_PROCESSED = 2;
    /**
     * Merge request queued, to be processed (the adhoc task is created for it).
     * @var integer
     */
    const QUEUED_TO_BE_PROCESSED = 3;
    /**
     * Merge request is in progress but not concluded (its adhoc_task has started).
     * @var integer
     */
    const INPROGRESS_NOT_CONCLUDED = 4;
    /**
     * Tried with error; pending of retrial (the merge process has found some error
     * and the adhoc_task is queued to be retried in the future).
     * @var integer
     */
    const TRIED_WITH_ERROR = 5;
    /**
     * Completed with success.
     * @var integer
     */
    const COMPLETED_WITH_SUCCESS = 6;
    /**
     * Completed with errors (even with all the current retries the merge process
     * could not end with success).
     * @var integer
     */
    const COMPLETED_WITH_ERRORS = 7;
    /**
     * Reference table for merge requests.
     */
    const TABLE_MERGE_REQUEST = 'tool_mergeusers_queue';
     /**
     * Reference table for merge requests. Before web service integration.
     */
    const TABLE_MERGE_REQUEST_OLD = 'tool_mergeusers';
    /**
     * Reference table for users.
     */
    const TABLE_USERS = 'user';
    
    private $data;

    private function __construct(stdClass $data) {
        $this->data = $data;
    }

    public static function from(stdClass $data) {
        return new self($data);
    }

    public function __get($name)
    {
        if (isset($this->data->${name})) {
            $value = $this->data->${name};
            if ($name == 'log') {
                $value = json_decode($value, true);
            }
            return $value;
        }
        return null;
    }

    public function __set($name, $value)
    {
        if (isset($this->data->${name})) {
            if ($name == 'log') {
                $value = json_encode($value);
            }
            $this->data->${name} = $value;
        }
    }

    public function convert_log_to_new_format() {
        if (!isset($this->data->log) || empty($this->data->log)) {
            return;
        }
        $logs = [];
        $logs[1] = json_decode($this->data->log, true);
        $this->data->log = json_encode($logs);
    }

    public function get_record(): \stdClass {
        return $this->data;
    }

    public static function export_data_to_new_table() {
        global $DB;
        $filter = array('status' => self::QUEUED_NOT_PROCESSED);
        $limitfrom=0;
        $limitnum=0; 
        $sort = "id DESC";
        $records = $DB->get_records(merge_request::TABLE_MERGE_REQUEST_OLD, 
                                    $filter, $sort, 
                                    'id, touserid, fromuserid, success, timemodified, log', 
                                    $limitfrom, $limitnum);
        if (!$records) {
            return $records;
        }
        foreach ($records as $item) {
            // Insert item into new table.
            if ($item->status == 1) {
                $status = merge_request::COMPLETED_WITH_SUCCESS;
            } else if ($item->status == 0) {
                $status = merge_request::COMPLETED_WITH_ERRORS;
            }
        $logs = [];
        $logs[1] = json_decode($item->log, true);
       
            $idrecord = $DB->insert_record(
                merge_request::TABLE_MERGE_REQUEST ,
                [
                    'removeuserid' => $item->fromuserid,  
                    'keepuserid' => $item->touserid,
                    'timeadded' => time(),
                    'timemodified' => time(),
                    'status' => $status,
                    'log' =>  json_encode($logs)
                ],
            );
        }
    }
}

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
 * Version information
 *
 * @package     tool
 * @subpackage  mergeusers
 * @author      Nicola Vallinoto, Liguria Digitale
 * @author      Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_mergeusers;
use stdClass;
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
     * @var string
     */
    const TABLE_MERGE_REQUEST = 'tool_mergeusers_queue';
     /**
     * Reference table for merge requests. Before web service integration.
     * @var string
     */
    const TABLE_MERGE_REQUEST_OLD = 'tool_mergeusers';
    /**
     * Reference table for users.
     * @var string
     */
    const TABLE_USERS = 'user';
    private $data;
    /**
     * Private constructor for the singleton.
     */
    private function __construct(stdClass $data) {
        $this->data = $data;
    }

    /**
     * Singleton method.
     * @return stdClass singleton instance.
     */
    public static function from(stdClass $data) {
        return new self($data);
    }

    /**
     * Accessor to properties from the current config as attributes of a standard object.
     * @param string $name name of attribute;
     * @return
     */
    public function __get($name) {
        if (isset($this->data->{$name})) {
                $value = $this->data->{$name};
            if ($name == 'log') {
                $value = json_decode($value, true);
            }
            return $value;
        }
        return null;
    }

    public function __set($name, $value) {
        if (isset($this->data->{$name})) {
            if ($name == 'log') {
                $value = json_encode($value);
            }
            $this->data->{$name} = $value;
        }
    }

    /**
     * Convert log to the new format.
     * @param log field;
     * @return
     */
    public function convert_log_to_new_format() {
        if (!isset($this->data->log) || empty($this->data->log)) {
            return;
        }
        $logs = [];
        $logs[1] = json_decode($this->data->log, true);
        $this->data->log = json_encode($logs);
    }

    /**
     * Get funtcion.
     * @return data
     */
    public function get_record(): \stdClass {
        return $this->data;
    }

    /**
     * Export data to new merge users table.
     */
    public static function export_data_to_new_table(): void {
        global $DB;
        $sort = "id ASC";
        $records = $DB->get_recordset(self::TABLE_MERGE_REQUEST_OLD,
                                        null,
                                        $sort);
        if (!$records) {
            // There is no need to migrate. That's all!
            return;
        }
        $oldrequests = [];
        $orderedoldrequests = [];
        foreach ($records as $item) {
            if (!isset($oldrequests[$item->fromuserid])) {
                $oldrequests[$item->fromuserid] = [];
            }
            if (isset($oldrequests[$item->fromuserid][$item->touserid])) {
                 $baseitem = $oldrequests[$item->fromuserid][$item->touserid];
            } else {
                $baseitem = new \stdClass();
                $baseitem->removeuserfield = 'id';
                $baseitem->removeuservalue = $item->fromuserid;
                $baseitem->removeuserid = $item->fromuserid;
                $baseitem->keepuserfield = 'id';
                $baseitem->keepuservalue = $item->touserid;
                $baseitem->keepuserid = $item->touserid;
                if (isset( $item->mergedbyuserid)) {
                    $baseitem->mergedbyuserid = $item->mergedbyuserid;
                }
                $baseitem->timeadded = $item->timemodified;
                $baseitem->log = [];
                $oldrequests[$item->fromuserid][$item->touserid] = $baseitem;
                $orderedoldrequests[$baseitem->timeadded] = $baseitem;
            }
            // Old logs are obtained in ASC order, so we can safely update this values.
            $baseitem->timemodified = $item->timemodified;
            $baseitem->timecompleted = $item->timemodified;
            // Append logs to the list.
            $baseitem->log[$item->timemodified] = json_decode($item->log, false);
            // $baseitem->log[0] = "Migrated from old record with id = ".$item->id;.
            $baseitem->status = ($item->status == 1) ? self::COMPLETED_WITH_SUCCESS : self::COMPLETED_WITH_ERRORS;
            $baseitem->retries = 0;
        }
        // Insert ordered and simplified old records into new format.
        foreach ($orderedoldrequests as $newrecord) {
            $newrecord->log = json_encode($newrecord->log);
            $DB->insert_record(self::TABLE_MERGE_REQUEST, $newrecord);
        }
    }
    /**
     * Append log to merge users table.
     */
    public function append_log(array $newlog, int $logtime) {
        $log = json_decode($this->data->log, true);
        $log[$logtime] = $newlog;
        $this->data->log = json_encode($log);
    }
}

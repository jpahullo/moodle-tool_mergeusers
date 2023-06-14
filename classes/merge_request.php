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
    private function __construct(stdClass $data) {
        $this->data = $data;
    }

    public static function from(stdClass $data) {
        return new self($data);
    }

    public function __get($name) {
        if (isset($this->data->${name})) {
            $value = $this->data->${name};
            if ($name == 'log') {
                $value = json_decode($value, true);
            }
            return $value;
        }
        return null;
    }
    public function __set($name, $value) {
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

    public static function export_data_to_new_table(): void {
        global $DB;
        $filter = array('status' => self::QUEUED_NOT_PROCESSED);
        $sort = "id DESC";
        $fields = "id, touserid, fromuserid, success, timemodified, log";
        $records = $DB->get_recordset(self::TABLE_MERGE_REQUEST_OLD,
                                    $filter, $sort, $fields);
        if (!$records->valid()) {
            return;
        }
        foreach ($records as $item) {
            // Insert item into new table.
            if ($item->status == 1) {
                $status = self::COMPLETED_WITH_SUCCESS;
            } else {
                $status = self::COMPLETED_WITH_ERRORS;
            }
            $logs = [];
            // Take all data of $item->fromuserid and $item->touserid.
            $logs[1] = json_decode($item->log, true);
            $idrecord = $DB->insert_record(
                self::TABLE_MERGE_REQUEST ,
                [
                    'removeuserid' => $item->fromuserid,
                    'removeuserfield' => 'id',
                    'removeuservalue' => $item->fromuserid,
                    'keepuserid' => $item->touserid,
                    'keepuserfield' => 'id',
                    'keepuservalue' => $item->touserid,
                    'timeadded' => $item->timemodified,
                    'timemodified' => $item->timemodified,
                    'status' => $status,
                    'log' => json_encode($logs)
                ],
            );
        }
        $records->close();
    }
}

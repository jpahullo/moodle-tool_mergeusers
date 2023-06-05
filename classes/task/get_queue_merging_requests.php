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
 * Merge user accounts
 * @package   tool_mergeusers
 * @author    Nicola Vallinoto <n.vallinoto@liguriadigitale.it>
 * @copyright 2023 Liguria Digitale
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_mergeusers\task;
use \tool_mergeusers\merge_request;
/**
 * Get queue merging requests task class
 * @package   tool_mergeusers
 * @author    Nicola Vallinoto <n.vallinoto@liguriadigitale.it>
 * @copyright 2023 Liguria Digitale 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_queue_merging_requests extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('get_queue_merging_requests','tool_mergeusers');
    }
    /**
     * Run task for getting the merging requests and adding them to adhoc task.
     */
    public function execute() {
        mtrace("Task get_queue_merging_requests started.");
        global $DB;
        global $CFG;
        // Read from moodle table records with status = QUEUED_NOT_PROCESSED.
        // Add each record to adhoc task. 
        // Update STATUS of each record added to adhoc task.
        $notscheduled = merge_request::QUEUED_NOT_PROCESSED; 
        $tablerequests = merge_request::TABLE_MERGE_REQUEST;      
        $recordsnotyetscheduled = $DB->get_records($tablerequests, 
                                                    ['status' => $notscheduled]);
        foreach ($recordsnotyetscheduled as $record) {
            // Add to adhoc_task - Create the instance.   
            $mergerequestid = $record->id;
            $mytask = new \tool_mergeusers\task\merge_user_accounts();
            $mytask->set_custom_data(['mergerequestid' => $mergerequestid]);
            // Queue the task.
            \core\task\manager::queue_adhoc_task($mytask);
            // Update the status of the tasked request.
            $updated = merge_request::QUEUED_TO_BE_PROCESSED;
            $this->update_status_table($mergerequestid,
                                        $updated);
            mtrace("User to remove: ".$record->removeuservalue." - User to keep: ".$record->keepuservalue);
            mtrace("Adhoc task: merge request n. ".$mergerequestid." queued.");                            
        }
        mtrace("Task get_queue_merging_requests completed!");
    }
    /**
     * Function for updating the status of the record to be executed.
     */
    private function update_status_table(int $idrecord,
                                            int $status) {
        global $DB;
        $sql = $DB->update_record(
            merge_request::TABLE_MERGE_REQUEST,
            (object)[
                'id' => $idrecord,
                'status' => $status
            ],
            $bulk = false
        );
    }
}
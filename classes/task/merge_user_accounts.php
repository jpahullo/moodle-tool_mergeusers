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
namespace tool_mergeusers\task;
defined('MOODLE_INTERNAL') || die();
use MergeUserTool;
require_once(__DIR__ . '/../../lib/autoload.php');
use \tool_mergeusers\merge_request;
/**
 * Adhoc task to merge user accounts.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @author    Nicola Vallinoto <n.vallinoto@liguriadigitale.it>
 * @copyright 2013 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @copyright 2023 Liguria Digitale (http://www.liguriadigitale.it)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * */
class merge_user_accounts extends \core\task\adhoc_task {
    /**
     * Execute the task of merge users accounts.
     */
    public function execute() {
        // Merge the users.
        global $DB, $CFG;
        $data = $this->get_custom_data();
        $maxattempts = get_config('tool_mergeusers', 'maxattempts');
        $record = (object)$DB->get_record(merge_request::TABLE_MERGE_REQUEST, ['id' => $data->mergerequestid]);
        $lookatcriteriaforusers = $this->verify_users_to_keep_and_remove($record);
        $mergerequest = $this->merge($record, $maxattempts, merge_request::TRIED_WITH_ERROR);
        if ($mergerequest->status == merge_request::COMPLETED_WITH_SUCCESS) {
            /* We run the merge request AGAIN because the user may be interacting with Moodle
            * while merge request is being processed, so that NO ALL records are correctly migrated
            * into the user to keep. It will ensure ALL records are correctly migrated into the user to keep. */
            $mergerequest = $this->merge($mergerequest, $maxattempts, merge_request::COMPLETED_WITH_ERRORS);
        }
        if ($mergerequest->status != merge_request::COMPLETED_WITH_ERRORS &&
            $mergerequest->status != merge_request::COMPLETED_WITH_SUCCESS) {
            // Throwing exception will ensure this adhoc task is re-queued until $maxretries is reached.
            throw new moodle_exception(get_string('failedmergerequest', 'tool_mergeusers'));
        }
    }
    /**
     * Function for merging two users.
     */
    private function merge(object $record, int $maxattempts, int $statusforerror) {
        $retries = $record->retries + 1;
        // Update retries.
        $this->update_retries_in_table($record->id, $retries);
        $logs = $record->log;
        $mut = new MergeUserTool();
        list($success, $log, $logid) = $mut->merge($record->keepuserid, $record->removeuserid);
        if ($success) {
            $status = merge_request::COMPLETED_WITH_SUCCESS;
        } else {
            if ($retries >= $maxattempts) {
                $status = merge_request::COMPLETED_WITH_ERRORS;
                // Send a notification to administrator ?
            } else {
                $status = $statusforerror;
            }
        }
        $logs[$retries] = $log;
        $this->update_status_and_log_in_table($record->id,
                                            $status,
                                            $logs);
        $record->log = $logs;
        $record->status = $status;
        return $record;
    }
    /**
     * Function for updating the status of the merging request.
     */
    private function update_status_and_log_in_table(int $idrecord,
                                                    int $status,
                                                    array $log) {
        global $DB;
        $DB->update_record(
            merge_request::TABLE_MERGE_REQUEST,
            (object)[
                'id' => $idrecord,
                'status' => $status,
                'timecompleted' >= time(),
                'log' => json_encode($log),
            ]
        );
    }
    /**
     * Function for verifying the users to keep and remove.
     */
    private function verify_users_to_keep_and_remove(object $record) {
        global $DB;
        $returnvalue = 0;
        $keepuserfield = $record->keepuserfield;
        $keepuservalue = $record->keepuservalue;
        $removeuserfield = $record->removeuserfield;
        $removeuservalue = $record->removeuservalue;
        // Verify user to remove.
        $usertoremove = $DB->get_records(merge_request::TABLE_USERS, [$removeuserfield => $removeuservalue]);
        if (is_null($record->removeuserid)) {
            $this->removeuserid = $this->find_user_id_or_fail($record->removeuserfield, $record->removeuservalue);
        }
        // Verify user to keep.
        $usertokeep = $DB->get_records(merge_request::TABLE_USERS, [$keepuserfield => $keepuservalue]);
        if (is_null($record->keepuserid)) {
            $this->keepuserid = $this->find_user_id_or_fail($record->keepuserfield, $record->keepuservalue);
        }
        return $returnvalue = 1;
    }
    /**
     * Function for updating number of retries.
     */
    private function update_retries_in_table(int $idrecord,
                                             int $retries) {
        global $DB;
        $DB->update_record(
            merge_request::TABLE_MERGE_REQUEST,
            (object)[
                'id' => $idrecord,
                'retries' => $retries,
                'timecompleted' >= time(),
            ]
        );
    }
    /**
     * Function for updating removeuserid.
     */
    private function update_removeuserid_in_table(int $idrecord,
                                                        int $removeuserid) {
        global $DB;
        $DB->update_record(
            merge_request::TABLE_MERGE_REQUEST,
            (object)[
                'id' => $idrecord,
                'removeuserid' => $removeuserid
            ]
        );
    }
    /**
     * Function for updating keepuserid.
     */
    private function update_keepuserid_in_table(int $idrecord,
                                                int $keepuserid) {
        global $DB;
        $DB->update_record(
            merge_request::TABLE_MERGE_REQUEST,
            (object)[
                'id' => $idrecord,
                'keepuserid' => $keepuserid
            ]
        );
    }
    /**
     * Function to find user id or fail.
     */
    protected function find_user_id_or_fail(int $mergerequestid, string $userfield, string $uservalue): int {
        global $DB;
        $user = $DB->get_records(merge_request::TABLE_USERS, [$userfield => $uservalue]);
        if (count($usertoremove) == 0) {
            throw new moodle_exception(get_string('cannotfinduser', 'tool_mergeusers', (object)['userfield' => $userfield, 'uservalue' => $uservalue]));
        } 
        if (count($usertoremove) > 1) {
            throw new moodle_exception(get_string('toomanyusers', 'tool_mergeusers', (object)['userfield' => $userfield, 'uservalue' => $uservalue]));
        }
        $this->update_removeuserid_in_table($mergerequestid , $user->id);
    }
}

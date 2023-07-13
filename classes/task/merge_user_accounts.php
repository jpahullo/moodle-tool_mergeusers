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
require_once(__DIR__ . '/../../lib/autoload.php');
use MergeUserTool;
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
        global $DB;
        $data = $this->get_custom_data();
        $maxattempts = get_config('tool_mergeusers', 'maxattempts');
        $mergerequest = $DB->get_record(merge_request::TABLE_MERGE_REQUEST,
                                            ['id' => $data->mergerequestid]);
        if (!$mergerequest) {
            // Merge request was removed before running this task.
            return;
        }
        // From stdClass to merge_request class.
        $mergerequest = merge_request::from($mergerequest);
        if ($mergerequest->retries > $maxattempts) {
            $log_attempts = array("Reached the number of maximum attempts.");
            $mergerequest->append_log($log_attempts, time());
            $this->update_status_and_log($mergerequest->id,
                                        merge_request::COMPLETED_WITH_ERRORS,
                                        $mergerequest->log);
            return;
        }
        $this->verify_users_to_keep_and_remove($mergerequest);
        $mergerequestresult = $this->merge($mergerequest,
                                            $maxattempts,
                                            merge_request::TRIED_WITH_ERROR);
        if ($mergerequestresult->status == merge_request::COMPLETED_WITH_SUCCESS) {
            /* We run the merge request AGAIN because the user may be interacting with Moodle
            * while merge request is being processed, so that NO ALL records are correctly migrated
            * into the user to keep. It will ensure ALL records are correctly migrated into the user to keep. */
            $mergerequestresult = $this->merge($mergerequestresult,
                                                $maxattempts,
                                                merge_request::COMPLETED_WITH_ERRORS);
        }
        if ($mergerequestresult->status != merge_request::COMPLETED_WITH_ERRORS &&
            $mergerequestresult->status != merge_request::COMPLETED_WITH_SUCCESS) {
            // Throwing exception will ensure this adhoc task is re-queued until $maxretries is reached.
            throw new moodle_exception(get_string('failedmergerequest', 'tool_mergeusers'));
        }
    }
    /**
     * Function for merging two users.
     */
    private function merge(merge_request $mergerequest,
                            int $maxattempts,
                            int $statusforerror) {
        $retries = $mergerequest->retries + 1;
        $this->update_retries($mergerequest);
        $mut = new MergeUserTool();
        list($success, $log) = $mut->merge($mergerequest->keepuserid,
                                            $mergerequest->removeuserid);
        if (!$success) {
            if ($retries >= $maxattempts) {
                $status = merge_request::COMPLETED_WITH_ERRORS;
                // Send a notification to administrator ?
            } else {
                $status = $statusforerror;
            }
        } else {
            $status = merge_request::COMPLETED_WITH_SUCCESS;
        }
        $mergerequest->append_log($log, time());
        if ($mergerequest->retries > $maxattempts) {
            $log_attempts = "Reached the number of maximum attempts";
            $mergerequest->append_log($log_attempts, time());
            $status = merge_request::COMPLETED_WITH_ERRORS;
        } 
        $this->update_status_and_log($mergerequest->id,
                                        $status,
                                        $mergerequest->log);
        $mergerequest->status = $status;
        return $mergerequest;
    }
    /**
     * Function for updating the status of the merging request.
     */
    private function update_status_and_log(int $idrecord,
                                            int $status,
                                            array $log): void {
        global $DB;
        $update = (object)[
            'id' => $idrecord,
            'status' => $status,
            'log' => json_encode($log),
        ];
        if ($status == merge_request::COMPLETED_WITH_SUCCESS ||
            $status == merge_request::COMPLETED_WITH_ERRORS) {
                $update->timecompleted = time();
        }
        $DB->update_record(
            merge_request::TABLE_MERGE_REQUEST,
            $update
        );
    }
    /**
     * Function for verifying the users to keep and remove.
     */
    private function verify_users_to_keep_and_remove(merge_request $mergerequest): void {
        if (is_null($mergerequest->removeuserid)) {
            $this->removeuserid = $this->find_user_id_or_fail($mergerequest->removeuserfield,
                                                                $mergerequest->removeuservalue,
                                                                $mergerequest->retries
                                                                );
        }
        if (is_null($mergerequest->keepuserid)) {
            $this->keepuserid = $this->find_user_id_or_fail($mergerequest->keepuserfield,
                                                                $mergerequest->keepuservalue,
                                                                $mergerequest->retries
                                                            );
        }
    }
    /**
     * Function for updating number of retries.
     */
    private function update_retries(object $mergerequest): void {
        global $DB;
        $DB->update_record(
            merge_request::TABLE_MERGE_REQUEST,
            (object)[
                'id' => $mergerequest->id,
                'retries' => $mergerequest->retries + 1,
                'timecompleted' >= time(),
            ]
        );
    }
    /**
     * Function for updating removeuserid.
     */
    private function update_removeuserid_in_table(int $idrecord,
                                                int $removeuserid): void {
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
                                                int $keepuserid): void {
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
    protected function find_user_id_or_fail(string $userfield,
                                            string $uservalue,
                                            int $retries
                                            ): int {
        global $DB;
        $users = $DB->get_records(merge_request::TABLE_USERS, [$userfield => $uservalue]);
        $errorfound = '';
        if (count($users) == 0) {
            $errorfound = get_string(get_string('cannotfinduser', 'tool_mergeusers',
                                                (object)['userfield' => $userfield,
                                                        'uservalue' => $uservalue,
                                                        'retries' => $retries,
                                                        ]));
        } else if (count($users) > 1) {
            $errorfound = get_string(get_string('toomanyusers', 'tool_mergeusers',
            (object)['userfield' => $userfield,
                    'uservalue' => $uservalue,
                    'retries' => $retries,
                    ]));
        }
        if (!empty($errorfound)) {
            $mergerequest->appendlog([$errorfound], time());
            $this->update_log($mergerequest); // To store the whole set of logs into the database.
            throw new moodle_exception($errorfound);
        }
        $user = reset($users);
        return $user->id;
    }
}

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
 * TableMerger for the user_enrolments table.
 *
 * @package    tool
 * @subpackage mergeusers
 * @author     Jordi Pujol-Ahulló <jordi.pujol@urv.cat>,  SREd, Universitat Rovira i Virgili
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class UserEnrolmentsMerger extends GenericTableMerger
{

    protected $tablename;

    public function __construct()
    {
        parent::__construct();
        global $CFG;
        $this->tablename = $CFG->prefix . 'user_enrolments';
    }

    /**
     * Disables course enrolments for the old user.
     *
     * The user_enrolments table is similar to grade_grades in that it also
     * has a compound unique key. The approach here is not to replace the
     * user in the case of a duplicate, but to disable the old user for that
     * particular course.
     *
     * @param array $data array with the necessary data for merging records.
     * @param array $actionLog list of action performed.
     * @param array $errorMessages list of error messages.
     */
    public function merge($data, &$actionLog, &$errorMessages)
    {
        global $DB;

        $sql = 'SELECT id, enrolid, userid, status FROM ' . $this->tablename . ' WHERE userid IN ( ?, ?)';
        $result = $DB->get_records_sql($sql, array($data['fromid'], $data['toid']));

        if (empty($result)) {
            return;
        }

        $enrolArr = array();
        $idsToDisable = array();
        $enrolmentsToUpdate = array();
        $enrolmentsToReactivate = array();

        foreach ($result as $id => $resObj) {
            $enrolArr[$resObj->enrolid][$resObj->userid] = $id;
        }

        foreach ($enrolArr as $enrolId => $enrolInfo) {
            if (sizeof($enrolInfo) != 2) {
                //if we don't have 2 results, then these users did not both complete this activity.
                if (key($enrolInfo) == $data['fromid']) {
                    //if we have the old user, we have to assign this course to the new user.
                    $enrolmentsToUpdate[] = $enrolInfo[$data['fromid']]; //disable the old user
                    continue;
                } else {
                    //we don't have anything here for this course. We actually shouldn't get to this point ever.
                    continue;
                }
            }
            // check if it is actually enabled
            if ($result[$enrolInfo[$data['fromid']]]->status != 2) {
                $idsToDisable[] = $enrolInfo[$data['fromid']];
            }
            //check if it was already disabled
            if ($result[$enrolInfo[$data['toid']]]->status == 2) {
                $enrolmentsToReactivate[] = $enrolInfo[$data['toid']]; // reactivate new user.
            }
        }
        unset($enrolArr); //free memory
        unset($result); //free memory

        if (!empty($enrolmentsToUpdate)) { // it's possible we won't have any
            $this->updateAllUserEnrolmentsWithUser($data['toid'], $enrolmentsToUpdate, $actionLog, $errorMessages);
        }
        unset($enrolmentsToUpdate); //free memory

        // ok, now let's lock this user out from using the common courses.
        if (!empty($idsToDisable)) {
            $this->lockAllUserEnrolments($idsToDisable, $actionLog, $errorMessages);
        }
        unset($idsToDisable); //free memory

        // the enrolment was deactivated before by us.
        // reactivate it again.
        if (!empty($enrolmentsToReactivate)) {
            $this->unlockAllUserEnrolments($enrolmentsToReactivate, $actionLog, $errorMessages);
        }
        unset($enrolmentsToReactivate); //free memory
    }

    protected function updateAllUserEnrolmentsWithUser(int $userid, array $enrolmentsToUpdate, array &$actionLog, array &$errorMessages)
    {
        $chunks = array_chunk($enrolmentsToUpdate, static::CHUNK_SIZE);
        foreach ($chunks as $chunk) {
            $this->updateUserEnrolmentsWithUser($userid, $chunk, $actionLog, $errorMessages);
        }
        unset($chunks);
    }

    protected function updateUserEnrolmentsWithUser(int $userid, array $enrolmentsToUpdate, array &$actionLog, array &$errorMessages)
    {
        global $DB;
        $updateIds = implode(', ', $enrolmentsToUpdate);
        $sql = 'UPDATE ' . $this->tablename . ' SET userid = ' . $userid . ' WHERE id IN (' . $updateIds . ')';
        if ($DB->execute($sql)) {
            //all was ok: action done.
            $actionLog[] = $sql;
        } else {
            // a database error occurred.
            $errorMessages[] = get_string('tableko', 'tool_mergeusers', "user_enrolments (#1)") .
                ': ' . $DB->get_last_error();
        }
        unset($updateIds);
        unset($sql);
    }

    protected function lockAllUserEnrolments(array $idsToDisable, array &$actionLog, array &$errorMessages)
    {
        $chunks = array_chunk($idsToDisable, static::CHUNK_SIZE);
        foreach ($chunks as $chunk) {
            $this->lockUserEnrolments($chunk, $actionLog, $errorMessages);
        }
        unset($chunks);
    }

    protected function lockUserEnrolments(array $idsToDisable, array &$actionLog, array &$errorMessages)
    {
        global $DB;
        $idsToLock = implode(', ', $idsToDisable);
        $sql = 'UPDATE ' . $this->tablename . ' SET status = 2 WHERE id IN (' . $idsToLock . ')  AND status = 0';
        if ($DB->execute($sql)) {
            //all was ok: action done.
            $actionLog[] = $sql;
        } else {
            // a database error occurred.
            $errorMessages[] = get_string('tableko', 'tool_mergeusers', "user_enrolments (#2)") .
                ': ' . $DB->get_last_error();
        }
        unset($idsToLock);
        unset($sql);
    }

    protected function unlockAllUserEnrolments(array $enrolmentsToReactivate, array &$actionLog, array &$errorMessages)
    {
        $chunks = array_chunk($enrolmentsToReactivate, static::CHUNK_SIZE);
        foreach ($chunks as $chunk) {
            $this->unlockUserEnrolments($chunk, $actionLog, $errorMessages);
        }
        unset($chunks);
    }

    protected function unlockUserEnrolments(array $enrolmentsToReactivate, array &$actionLog, array &$errorMessages)
    {
        global $DB;
        $idsToUnlock = implode(', ', $enrolmentsToReactivate);
        $sql = 'UPDATE ' . $this->tablename . ' SET status = 0 WHERE id IN (' .  $idsToUnlock . ')  AND status = 2';
        if ($DB->execute($sql)) {
            //all was ok: action done.
            $actionLog[] = $sql;
        } else {
            // a database error occurred.
            $errorMessages[] = get_string('tableko', 'tool_mergeusers', "user_enrolments (#3)") .
                ': ' . $DB->get_last_error();
        }
        unset($idsToUnlock);
        unset($sql);
    }
}

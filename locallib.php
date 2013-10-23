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
 * @package    tool
 * @subpackage mergeusers
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @author     Mike Holzer
 * @author     Forrest Gaston
 * @author     Juan Pablo Torres Herrera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 
if ($CFG->dbtype == 'sqlsrv') {
    // MSSQL
    $dberror_func = 'mssql_get_last_message';
}
else if ($CFG->dbtype == 'mysqli') {
    // MySQL
    $dberror_func = 'mysqli_error';
}
else if ($CFG->dbtype == 'pgsql') {
    // PGSQL
    $dberror_func = 'pg_result_error';
}
 
 
/**
 * Merges the grade_grades table for two users.
 *
 * The intial problem in this table, grade_grades is that we have a
 * compound key on userid and itemid which means we can't just update
 * the userid because it's likely that the 2 users have completed at
 * least some of the same activities.
 *
 * What we're going to attempt to do is to look for the 2 users
 * completing the same activity. If we find a match, we'll delete the
 * record for the old user. That record can still be found in the
 * grade_grades_history table so we won't be losing any history. RELAX.
 *
 * Any activity completed by one but not the other will be updated in
 * the normal process.
 *
 * @param $newId                The user inheriting the data
 * @param $currentId            The user being replaced
 * @param $recordsToModify      The list of records to be updated to the new user. Passed by reference
 */
function mergeGrades($newId, $currentId, &$recordsToModify) {
    global $CFG, $DB, $mergeusers_errors, $mergeusers_queries, $dberror_func;

    $sql = 'SELECT id, itemid, userid from '.$CFG->prefix.'grade_grades WHERE userid in ('.$currentId.', '.$newId.')';
    $result = $DB->get_records_sql($sql);

    $itemArr = array();
    $idsToRemove = array();
    foreach($result as $id => $resObj) {
        $itemArr[$resObj->itemid][$resObj->userid] = $id;
    }

    foreach($itemArr as $itemId => $itemInfo) {
        if(sizeof($itemInfo) != 2){
            // if we don't have 2 results, then these users did not both complete this activity.
            continue;
        }
        $idsToRemove[] = $itemInfo[$currentId];
    }

    $idsGoByebye =  implode(', ', $idsToRemove);
    $sql = 'DELETE FROM '.$CFG->prefix.'grade_grades WHERE id IN ('.$idsGoByebye.')';
    if($idsGoByebye && $DB->execute($sql)) {
//      echo($sql);
        // ok, now remove those ids from the greater records list.
        for($i=0; $i < count($recordsToModify); $i++) {
            if(in_array($recordsToModify[$i], $idsToRemove)) {
                unset($recordsToModify[$i]);
            }
        }
//        echo '<p style="color:#0c0;">'.get_string('tableok', 'tool_mergeusers', 'grade_grades').'</p>';
    }
    else if($idsGoByebye) {
        // an error occured during DB query
        echo '<p style="color:#f00;">'.get_string('tableko', 'tool_mergeusers', 'grade_grades').': '.$dberror_func().'</p>';
        $mergeusers_errors++;
    }
    if ($idsGoByebye) {
        $mergeusers_queries[] = $sql;
    }
}


/**
 * Disables course enrollments for the old user.
 *
 * The user_enrolments table is similar to grade_grades in that it also
 * has a compound unique key. The approach here is not to replace the
 * user in the case of a duplicate, but to disable the old user for that
 * particular course.
 *
 * @param $newId        The user inheriting the data
 * @param $currentId    The user being replaced
 */
function disableOldUserEnrollments($newId, $currentId) {
    global $CFG, $DB, $mergeusers_errors, $mergeusers_queries, $dberror_func;

    $sql = 'SELECT id, enrolid, userid,status from '.$CFG->prefix.'user_enrolments WHERE userid in ('.$currentId.', '.$newId.') AND status !=2';
    $result = $DB->get_records_sql($sql);
    if(empty($result)){
//        echo "No enrollments found<br/>";
        return;
    }

    $enrolArr = array();
    $idsToDisable = array();
    $enrollmentsToUpdate = array();
    foreach($result as $id => $resObj) {
        $enrolArr[$resObj->enrolid][$resObj->userid] = $id;
    }
    foreach($enrolArr as $enrolId => $enrolInfo) {
        if(sizeof($enrolInfo) != 2) {
            //if we don't have 2 results, then these users did not both complete this activity.
            if(key($enrolInfo) == $currentId) {
                //if we have the old user, we have to assign this course to the new user.
                $enrollmentsToUpdate[] = $enrolInfo[$currentId];
                continue;
            }
            else {
                //we don't have anything here for this course. We actually shouldn't get to this point ever.
                continue;
            }
        }
        $idsToDisable[] = $enrolInfo[$currentId];
    }

    if(!empty($enrollmentsToUpdate)) { // it's possible we won't have any
        // First, let's move the courses belonging to the old user over to the new one.
        $updateIds = implode(', ', $enrollmentsToUpdate);
        $sql = 'UPDATE '.$CFG->prefix.'user_enrolments SET userid = "'.$newId.'" WHERE id IN ('.$updateIds.')';
       if($DB->execute($sql)) {
//            echo($sql);
//            echo '<p style="color:#0c0;">'.get_string('tableok', 'tool_mergeusers', "{$CFG->prefix}user_enrolments (#1)").'</p>';
        }
        else {
            echo '<p style="color:#f00;">'.get_string('tableko', 'tool_mergeusers', "{$CFG->prefix}user_enrolments (#1)").': '.$dberror_func().'</p>';
            $mergeusers_errors++;
        }
        $mergeusers_queries[] = $sql;
    }
    // ok, now let's lock this user out from using the common courses.
    if(!empty($idsToDisable)) {
        $idsGoByebye =  implode(', ',$idsToDisable);
        $sql = 'UPDATE '.$CFG->prefix.'user_enrolments SET status = 2 WHERE id IN ('.$idsGoByebye.')  AND status = 0';
        if($DB->execute($sql)) {
//            echo($sql);
//            echo '<p style="color:#0c0;">'.get_string('tableok', 'tool_mergeusers', "{$CFG->prefix}user_enrolments (#2)").'</p>';
        }
        else {
            echo '<p style="color:#f00;">'.get_string('tableko', 'tool_mergeusers', "{$CFG->prefix}user_enrolments (#2)").': '.$dberror_func().'</p>';
            $mergeusers_errors++;
        }
        $mergeusers_queries[] = $sql;
    }
}


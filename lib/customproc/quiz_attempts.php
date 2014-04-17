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
 * @package     tool
 * @subpackage  mergeusers
 * @author      John Hoopes <hoopes@wisc.edu>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This file is a custom code script that will be imported into custom processing of a complex index
 * It should not be imported into any other file and is just a code snippet that relies on other variables in a function
 *
 * It assumes that it has all functions in the mergeCompoundIndex function and should be required by that function
 *
 * Quiz attempts are a complex entity in that they also span multiple tables into the question engine
 * and so, if both users have attempted a quiz, the old user's attempts will be deleted, as well as the quiz_grades table
 * record will be deleted as well
 *
 * This file will also handle quiz_grades table, this is added to the table exceptions in the config
 *
 * There are 3 possible ways for quiz attempts to occur
 *
 * 1.  The old user only attempts the quiz
 *      - In this case the quiz attempt is transferred over through the $recordsToModify array
 *      - This file will then also modify the quiz_grades table to have the $toid be the userid in the quiz grades table
 * 2. The new user only attempts the quiz
 *      - In this case it won't matter, no processing is needed
 * 3. Both users attempt the quiz
 *      - In this case, the old user's attempt and quiz grade are left intact with the olduser id
 *          This is what this file ensures.
 *      - This makes sense as we are only suspending old user accounts, and even Moodle deletion of a user still keeps the
 *          user record around.
 *
 */


/**
 * recordsToModify will only contain records that will be changed from the old user to new (which is case 1 above),
 * so we'll move corresponding records in the quiz_grades table for the quiz over, as the table is excluded from normal processing
 */
$processedquizzes = array();  // store quizzes processed so we don't duplicate moving quiz grade records

foreach($recordsToModify as $r){


    try{ // catch db errors

        // get the full quiz attempt record
        $qa = $DB->get_record('quiz_attempts', array('id'=>$r), 'quiz', MUST_EXIST);
        $quizid = $qa->quiz;

        if(!in_array($quizid, $processedquizzes)){ // only do quiz_grade record once as there may be more than one attempt to move

            // Get quiz_grade record to update olduserid
            // We could just set_field, but getting record, then updating it is more strait forward
            $qg = $DB->get_record('quiz_grades', array('userid'=>$fromid, 'quiz'=>$quizid));
            $qg->userid = $toid;
            $DB->update_record('quiz_grades', $qg);
            $actionLog[] = 'Updated quiz grades table for quiz: ' . $quizid . 'and quiz attempt: ' . $r;
            array_push($processedquizzes, $quizid);
        }
    }catch(Exception $e){
        $errorMessages[] = 'Error processing modifying quiz grade for quiz attempt: ' . $r;
    }
}

/**
 * No code is actually needed for the 2nd or 3rd case.  For the 3rd case we're leaving all of the old information
 */



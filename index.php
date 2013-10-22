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


define('PRIMARY_KEY','id');

// Report all PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require('../../../config.php');
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/weblib.php');

require_once('./index_form.php');
require('./locallib.php');

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

admin_externalpage_setup('toolmergeusers');

// Define the form
$mergeuserform = new mergeuserform();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mergeusers', 'tool_mergeusers'));
echo $OUTPUT->box_start();

$data = $mergeuserform->get_data();

// Any submitted data?
if ($data) {

    $mergeusers_errors = 0;
    $mergeusers_queries = array();
    $transaction = $DB->start_delegated_transaction();

   // Get the userids
   $user1 = $DB->get_record('user', array('id' => $data->olduserid));
   if (!$user1) {
        print_error('errornouserid', 'tool_mergeusers');
   }
   $currentUser = $user1->id;
   $currentUserName = $user1->username;
   $user2 = $DB->get_record('user', array('id' => $data->newuserid));
   if (!$user2) {
       print_error('errornouserid', 'tool_mergeusers');
    }
    $newUser = $user2->id;
    $newUserName = $user2->username;

    echo('<h2>'.get_string('merging', 'tool_mergeusers').' &laquo;'.$currentUserName.'&raquo; (user ID = '.$currentUser.') '.get_string('into', 'tool_mergeusers').' &laquo;'.$newUserName.'&raquo; (user ID = '.$newUser.')</h2>');

    // these are tables we don't want to modify due to logging or security reasons.
    $tablesToSkip = array(
        $CFG->prefix.'user_lastaccess',
        $CFG->prefix.'user_preferences',
	$CFG->prefix.'user_private_key',
	$CFG->prefix.'user_info_data'
    );

    if ($CFG->dbtype == 'sqlsrv') {
        // MSSQL
        $tableNames = $DB->get_records_sql("SELECT name FROM sys.Tables WHERE name LIKE '".$CFG->prefix."%' AND type = 'U' ORDER BY name");
    }
    else if ($CFG->dbtype == 'mysqli') {
        // MySQL
        $tableNames = $DB->get_records_sql('SHOW TABLES like "'.$CFG->prefix.'%"');
    }
    else if ($CFG->dbtype == 'pgsql') {
        // PGSQL
        $tableNames = $DB->get_records_sql("SELECT table_name FROM information_schema.tables WHERE table_name LIKE '".$CFG->prefix."%' AND table_schema = 'public'");
    }
    else {
         print_error('errordatabase', 'report_mergeusers', '', $CFG->dbtype);
    }

//    $numtables = sizeof($tableNames);
//    echo "<h2>".$numtables. " tables found in database &quot;".$CFG->dbname."&quot;</h2>";

    foreach($tableNames as $table_name => $objWeCanIgnore){
        if(!trim($table_name)) { //This section should never be executed due to the way Moodle returns its resultsets
            // Skipping due to blank table name
            continue;
        } else if(in_array($table_name, $tablesToSkip)) {
            echo('<p style="color:#f80;">'.get_string('tableskipped', 'tool_mergeusers', $table_name).'</p>');
            continue;
        }

        if ($CFG->dbtype == 'sqlsrv') {
            // MSSQL
            $columnList = "SELECT * FROM INFORMATION_SCHEMA.Columns WHERE TABLE_NAME = '".$table_name."' AND COLUMN_NAME IN ('userid', 'user_id', 'id_user', 'user')";
        }
        else if ($CFG->dbtype == 'mysqli') {
            // MySQL
            $columnList = "SHOW COLUMNS FROM ".$table_name." where Field IN ('userid', 'user_id', 'id_user', 'user')";
        }
        else if ($CFG->dbtype == 'pgsql') {
            // PGSQL
            $columnList = "SELECT column_name FROM information_schema.columns WHERE table_name ='". $table_name ."' and column_name IN ('userid', 'user_id', 'id_user', 'user');";
        }
        else {
            print_error('errordatabase', 'report_mergeusers');
        }

        $columns = $DB->get_records_sql($columnList);

        if(count($columns) !== 1) {
            // no matching or multiple matching fields in this table, move onto the next table.
            continue;
        }

        // Now we have the appropriate fieldname and we know what to update!
        if ($CFG->dbtype == 'sqlsrv') {
            // MSSQL
            $field_name = array_shift($columns)->column_name; // get the fieldname
        }
        else if ($CFG->dbtype == 'mysqli') {
            // MySQL
            $field_name = array_shift($columns)->field; // get the fieldname
        }
        else if ($CFG->dbtype == 'pgsql') {
            // PGSQL
            $field_name = array_shift($columns)->column_name; // get the fieldname
        }
        else {
            print_error('errordatabase', 'report_mergeusers');
        }

        $recordsToUpdate = $DB->get_records_sql("SELECT ".PRIMARY_KEY." FROM ".$table_name." WHERE ".$field_name." = '".$currentUser."'");
        if(count($recordsToUpdate) == 0) {
            //this userid is not present in this table
            continue;
        }

        $recordsToModify = array_keys($recordsToUpdate); // get the 'id' field from the resultset

        if($table_name == $CFG->prefix.'grade_grades') {
            // Grades must be specially adjusted.
            /* pass $recordsToModify by reference so that the function can take care of some of our work for us */
            mergeGrades($newUser, $currentUser, $recordsToModify);
        }
        if($table_name == $CFG->prefix.'user_enrolments') {
            // User enrollments must be specially adjusted
            disableOldUserEnrollments($newUser, $currentUser);
            continue;
            // go onto next table
        }

        $idString = implode(', ', $recordsToModify);
        $updateRecords = "UPDATE ".$table_name." SET ".$field_name." = '".$newUser."' WHERE ".PRIMARY_KEY." IN (".$idString.")";
        if($DB->execute($updateRecords)) {
//          echo($updateRecords);
//            echo '<p style="color:#0c0;">'.get_string('tableok', 'tool_mergeusers', $table_name).'</p>';
        }
        else {
            echo '<p style="color:#f00;">'.get_string('tableko', 'tool_mergeusers', $table_name).': '.$dberror_func().'</p>';

            $mergeusers_errors++;
        }
        $mergeusers_queries[] = $updateRecords;
    }
    // TODO: An optional step at this point would be to disable or delete altogether the $currentUser.


    if (!$mergeusers_errors) {
        // we commit the DB transaction only if there are no errors
        $transaction->allow_commit();
        print_string('dbok', 'tool_mergeusers');
    }
    else {
        print_string('dbko', 'tool_mergeusers');
    }

    echo $OUTPUT->box_start();
    print_string('dbqueries', 'tool_mergeusers');
    echo '<pre>';
    foreach ($mergeusers_queries as $mergeusers_query) {
        echo $mergeusers_query . "\n";
    }
    echo '</pre>';
    echo $OUTPUT->box_end();

    echo $OUTPUT->single_button(new moodle_url('./index.php'), get_string('continue'), 'get');

}  else {

    // no form submitted data:
    $OUTPUT->box(get_string('description', 'tool_mergeusers'));
    print_string('description', 'tool_mergeusers');
    $mergeuserform->display();

}

// $OUTPUT->heading($strmergeusers);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();


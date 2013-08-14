<?php

/**
 * Main file for mergeusers report
 *
 * @package    report
 * @subpackage mergeusers
 * @author     Forrest Gaston & Juan Pablo Torres Herrera,
 *             based on the mergeusers_v2.php script written by Nicolas Dunand,
 *             updated for Moodle 2.0 by Mike Holzer.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    2012052300
 *
 *
 *  This script will merge two Moodle user accounts, "user A" and "user B".
 *  The intent of the script is to assign all activity & records from user A to
 *  user B. This will give the effect of user B seeming to have done everything
 *  both users have ever done in Moodle. The basic function of the script is to
 *  loop through the tables and update the userid of every record from user A to
 *  user B. This works well for most tables. We do however, have a few special
 *  cases:
 *
 *        Special Case #1: The grade_grades table has a compound unique key on userid
 *        and itemid. This prevents a simple update statement if the two users have
 *        done the same activity. What this script does is determine which activities
 *        have been completed by both users and delete the entry for the old user
 *        from this table. Data is not lost because a duplicate entry can be found in
 *        the grade_grades_history table, which is correctly updated by the regular
 *        processing of the script.
 *
 *        Special Case #2: The user_enrolments table controls which user is enrolled
 *        in which course. Rather than unenroll the old user from the course, this
 *        script simply updates their access to the course to "2" which makes them
 *        completely unable to access the course. To remove these records all
 *        together I recomend disabling or deleting the entire old user account once
 *        the migration has been successful.
 *
 *        Special Case #3: There are 3 logging/preference tables
 *        (user_lastaccess, user_preferences,user_private_key) which exist in
 *        Moodle 2.0. This script is simply skipping these tables since there's no
 *        legitimate purpose to updating the userid value here. This would lead to
 *        duplicate rows for the new user which is silly. Again, if you want to
 *        remove these records I would recommend deleting the old user after this
 *        script runs sucessfully.
 *
 *  BEFORE YOU RUN THIS SCRIPT, BACK UP YOUR DATABASE.
 *  There is no provision in this script for rollbacks, so if something
 *  were to fail midway through you will end up with a half-updated DB.
 *  This. Is. Bad. Practice safe script. Always backup first.
 *
 */


define('PRIMARY_KEY','id');

// Report all PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require('../../config.php');
require_once("$CFG->libdir/blocklib.php");
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/weblib.php');

require_once('./index_form.php');
require('./locallib.php');

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

admin_externalpage_setup('reportmergeusers', '', null, '', array('pagelayout' => 'report'));

// Define the form
$mergeuserform = new mergeuserform();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mergeusers', 'report_mergeusers'));
echo $OUTPUT->box_start();

$data = $mergeuserform->get_data();

// Any submitted data?
if ($data = $mergeuserform->get_data()) {

    $mergeusers_errors = 0;
    $mergeusers_queries = array();
    $transaction = $DB->start_delegated_transaction();

   // Get the userids
   $user1 = $DB->get_record('user', array('id' => $data->olduserid));
   if (!$user1) {
        print_error('errornouserid', 'report_mergeusers');
   }
   $currentUser = $user1->id;
   $currentUserName = $user1->username;
   $user2 = $DB->get_record('user', array('id' => $data->newuserid));
   if (!$user2) {
       print_error('errornouserid', 'report_mergeusers');
    }
    $newUser = $user2->id;
    $newUserName = $user2->username;

    echo('<h2>'.get_string('merging', 'report_mergeusers').' &laquo;'.$currentUserName.'&raquo; (user ID = '.$currentUser.') '.get_string('into', 'report_mergeusers').' &laquo;'.$newUserName.'&raquo; (user ID = '.$newUser.')</h2>');

    // these are tables we don't want to modify due to logging or security reasons.
    $tablesToSkip = array(
        $CFG->prefix.'user_lastaccess',
        $CFG->prefix.'user_preferences',
        $CFG->prefix.'user_private_key'
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
//            echo("Skipping due to blank table name.<br />");
            continue;
        } elseif(in_array($table_name, $tablesToSkip)) {
            echo('<p style="color:#f80;">'.get_string('tableskipped', 'report_mergeusers', $table_name).'</p>');
            continue;
        } else {
//            echo("Processing table: $table_name<br />");
        }

        if ($CFG->dbtype == 'sqlsrv') {
            // MSSQL
            $columnList = "SELECT * FROM INFORMATION_SCHEMA.Columns WHERE TABLE_NAME = '".$table_name."' AND COLUMN_NAME IN ('userid', 'user_id', 'id_user', 'user')";
        }
        else if ($CFG->dbtype == 'mysql') {
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
        else if ($CFG->dbtype == 'mysql') {
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
//            echo '<p style="color:#0c0;">'.get_string('tableok', 'report_mergeusers', $table_name).'</p>';
        }
        else {
            echo '<p style="color:#f00;">'.get_string('tableko', 'report_mergeusers', $table_name).': '.mysql_error().'</p>';

            $mergeusers_errors++;
        }
        $mergeusers_queries[] = $updateRecords;
    }
    // TODO: An optional step at this point would be to disable or delete altogether the $currentUser.


    if (!$mergeusers_errors) {
        // we commit the DB transaction only if there are no errors
        $transaction->allow_commit();
        print_string('dbok', 'report_mergeusers');
    }
    else {
        print_string('dbko', 'report_mergeusers');
    }

    echo $OUTPUT->box_start();
    print_string('dbqueries', 'report_mergeusers');
    echo '<pre>';
    foreach ($mergeusers_queries as $mergeusers_query) {
        echo $mergeusers_query . "\n";
    }
    echo '</pre>';
    echo $OUTPUT->box_end();

    echo $OUTPUT->single_button(new moodle_url('./index.php'), get_string('continue'), 'get');

}  else {

    // no form submitted data:
    $OUTPUT->box(get_string('description', 'report_mergeusers'));
    print_string('description', 'report_mergeusers');
    $mergeuserform->display();

}

// $OUTPUT->heading($strmergeusers);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();

?>
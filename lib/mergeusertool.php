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
 * Utility file.
 *
 * The effort of all given authors below gives you this current version of the file.
 *
 * @package    tool
 * @subpackage mergeusers
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @author     Mike Holzer
 * @author     Forrest Gaston
 * @author     Juan Pablo Torres Herrera
 * @author     Jordi Pujol-Ahulló <jordi.pujol@urv.cat>,  SREd, Universitat Rovira i Virgili
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/config.php';

global $CFG;

require_once $CFG->dirroot . '/lib/clilib.php';
require_once __DIR__ . '/autoload.php';

define('PRIMARY_KEY', 'id');

/**
 *
 *
 * Lifecycle:
 * <ol>
 *   <li>Once: <code>$mut = new MergeUserTool();</code></li>
 *   <li>N times: <code>$mut->merge($from, $to);</code> Passing two objects with at least
 *   two attributes ('id' and 'username') on each, this will merge the user $from into the
 *   user $to, so that the $from user will be empty of activity.</li>
 * </ol>
 *
 * @author Jordi Pujol-Ahulló
 */
class MergeUserTool
{

    /**
     * @var bool true if current database is supported; false otherwise.
     */
    protected $supportedDatabase;

    /**
     * @var array associative array showing the user-related fields per database table,
     * without the $CFG->prefix on each.
     */
    protected $userFieldsPerTable;

    /**
     * @var array string array with all known database table names to skip in analysis,
     * without the $CFG->prefix on each.
     */
    protected $tablesToSkip;

    /**
     * @var array string array with the current skipped tables with the $CFG->prefix on each.
     */
    protected $tablesSkipped;

    /**
     * @var array associative array with special cases for tables with compound indexes,
     * without the $CFG->prefix on each.
     */
    protected $tablesWithCompoundIndex;

    /**
     * @var string Database-specific SQL to get the list of database tables.
     */
    protected $sqlListTables;

    /**
     * @var array array with table names (without $CFG->prefix) and the list of field names
     * that are related to user.id. The key 'default' is the default for any non matching table name.
     */
    protected $userFieldNames;

    /**
     * @var Logger logger for merging users.
     */
    protected $logger;

    /**
     * Initializes
     * @global object $CFG
     * @param Config $config local configuration.
     * @param Logger $logger logger facility to save results of mergings.
     */
    public function __construct(Config $config = null, Logger $logger = null)
    {
        global $CFG;

        $this->logger = (is_null($logger))?new Logger():$logger;
        $config = (is_null($config))?Config::instance():$config;
        $this->supportedDatabase = true;

        if ($CFG->dbtype == 'sqlsrv') {
            // MSSQL
            $this->sqlListTables = "SELECT name FROM sys.Tables WHERE name LIKE '" .
                    $CFG->prefix . "%' AND type = 'U' ORDER BY name";
        } else if ($CFG->dbtype == 'mysqli') {
            // MySQL
            $this->sqlListTables = 'SHOW TABLES like "' . $CFG->prefix . '%"';
        } else if ($CFG->dbtype == 'pgsql') {
            // PGSQL
            $this->sqlListTables = "SELECT table_name FROM information_schema.tables WHERE table_name LIKE '" .
                    $CFG->prefix . "%' AND table_schema = 'public'";
        } else {
            $this->supportedDatabase = false;
            $this->sqlListTables = "";
        }

        // these are tables we don't want to modify due to logging or security reasons.
        // we flip key<-->value to accelerate lookups.
        $this->tablesToSkip = array_flip($config->exceptions);

        // these are special cases, corresponding to tables with compound indexes that
        // need a special treatment.
        $this->tablesWithCompoundIndex = $config->compoundindexes;

        // Initializes user-related field names.
        $userFieldNames = array();
        foreach ($config->userfieldnames as $tablename => $fields) {
            $userFieldNames[$tablename] = "'" . implode("','", $fields) . "'";
        }
        $this->userFieldNames = $userFieldNames;

        // this will abort execution if local database is not supported.
        $this->checkDatabaseSupport();

        // initializes the list of fields and tables to check in the current database,
        // given the local configuration.
        $this->init();
    }

    /**
     * Merges two users into one. User-related data records from user id $fromid are merged into the
     * user with id $toid.
     * @global object $CFG
     * @global moodle_database $DB
     * @param int $toid The user inheriting the data
     * @param int $fromid The user being replaced
     * @return array An array(bool, array, int) having the following cases: if array(true, log, id)
     * users' merging was successful and log contains all actions done; if array(false, errors, id)
     * means users' merging was aborted and errors contain the list of errors.
     * The last id is the log id of the merging action for later visual revision.
     */
    public function merge($toid, $fromid)
    {
        $result = $this->_merge($toid, $fromid);
        $result[] = $this->logger->log($toid, $fromid, $result[0], $result[1]);
        return $result;
    }

    /**
     * Real method that performs the merging action.
     * @global object $CFG
     * @global moodle_database $DB
     * @param int $toid The user inheriting the data
     * @param int $fromid The user being replaced
     * @return array An array(bool, array) having the following cases: if array(true, log)
     * users' merging was successful and log contains all actions done; if array(false, errors)
     * means users' merging was aborted and errors contain the list of errors.
     */
    private function _merge($toid, $fromid)
    {
        global $CFG, $DB;

        // initial checks.

        // database type is supported?
        if (!$this->supportedDatabase) {
            return array(false, array(get_string('errordatabase', 'tool_mergeusers', $CFG->dbtype)));
        }

        // are they the same?
        if ($fromid == $toid) {
            // yes. do nothing.
            return array(false, array(get_string('errorsameuser', 'tool_mergeusers')));
        }

        // ok, now we have to work;-)
        // first of all... initialization!
        $errorMessages = array();
        $actionLog = array();
        $transaction = $DB->start_delegated_transaction();

        try {
            // processing each table name
            foreach ($this->userFieldsPerTable as $tableName => $userFields) {

                foreach ($userFields as $fieldName) {
                    $recordsToUpdate = $DB->get_records_sql("SELECT " . PRIMARY_KEY . " FROM " .
                            $CFG->prefix . $tableName . " WHERE " . $fieldName . " = '" . $fromid . "'");
                    if (count($recordsToUpdate) == 0) {
                        //this userid is not present in these table and field names
                        continue;
                    }

                    $recordsToModify = array_keys($recordsToUpdate); // get the 'id' field from the resultset
                    // Special case of user_enrolments
                    if ($tableName == 'user_enrolments') {
                        // User enrolments must be specially adjusted
                        $this->disableOldUserEnrolments($toid, $fromid, $actionLog, $errorMessages);
                        continue;
                        // go onto next field or table
                    }
                    // Other special cases with user field as part of a compound index.
                    if (isset($this->tablesWithCompoundIndex[$tableName])) {
                        $this->mergeCompoundIndex($toid, $fromid, $tableName, $fieldName,
                                $this->getOtherFieldsOnCompoundIndex($tableName, $fieldName),
                                $recordsToModify, $actionLog, $errorMessages);
                        //ensure we have records to update
                        if (count($recordsToModify) == 0) {
                            //no records to update... go into the next field or table.
                            continue;
                        }
                    }

                    $idString = implode(', ', $recordsToModify);
                    $updateRecords = "UPDATE " . $CFG->prefix . $tableName .
                            " SET " . $fieldName . " = '" . $toid .
                            "' WHERE " . PRIMARY_KEY . " IN (" . $idString . ")";
                    if (!$DB->execute($updateRecords)) {
                        $errorMessages[] = get_string('tableko', 'tool_mergeusers', $tableName) .
                                ': ' . $DB->get_last_error();
                    }
                    $actionLog[] = $updateRecords;
                }
            }
        } catch (Exception $e) {
            $errorMessages[] = nl2br("Exception thrown when merging: '" . $e->getMessage() . '".' .
                    html_writer::empty_tag('br') . $DB->get_last_error() . html_writer::empty_tag('br') .
                    'Trace:' . html_writer::empty_tag('br') .
                    $e->getTraceAsString() . html_writer::empty_tag('br'));
        }

        // concludes with true if no error
        if (empty($errorMessages)) {
            $transaction->allow_commit();

            // add skipped tables as first action in log
            $skippedTables = array();
            if (!empty($this->tablesSkipped)) {
                $skippedTables[] = get_string('tableskipped', 'tool_mergeusers', implode(", ", $this->tablesSkipped));
            }

            return array(true, array_merge($skippedTables, $actionLog));
        } else {
            try {
                //thrown controlled exception.
                $transaction->rollback(new Exception(__METHOD__ . ':: Rolling back transcation.'));
            } catch (Exception $e) { /* do nothing, just for correctness */ }
        }

        // concludes with an array of error messages otherwise.
        return array(false, $errorMessages);
    }

    // ****************** INTERNAL UTILITY METHODS ***********************************************

    /**
     * Initializes the list of database table names and user-related fields for each table.
     * @global object $CFG
     * @global moodle_database $DB
     */
    private function init()
    {
        global $CFG, $DB;

        $userFieldsPerTable = array();

        $tableNames = $DB->get_records_sql($this->sqlListTables);
        $prefixLength = strlen($CFG->prefix);

        foreach ($tableNames as $fullTableName => $toIgnore) {

            if (!trim($fullTableName)) {
                //This section should never be executed due to the way Moodle returns its resultsets
                // Skipping due to blank table name
                continue;
            } else {
                $tableName = substr($fullTableName, $prefixLength);
                if (isset($this->tablesToSkip[$tableName])) {
                    $this->tablesSkipped[$tableName] = $fullTableName;
                    continue;
                }
            }

            // detect available user-related fields among database tables.
            $userFields = (isset($this->userFieldNames[$tableName]))?
                    $this->userFieldNames[$tableName]:
                    $this->userFieldNames['default'];

            $currentFields = $this->getCurrentUserFieldNames($fullTableName, $userFields);

            if ($currentFields !== false) {
                $userFieldsPerTable[$tableName] = array_values($currentFields);
            }
        }

        $this->userFieldsPerTable = $userFieldsPerTable;
    }

    /**
     * Check whether current Moodle's database type is supported.
     * If it is not supported, it aborts the execution with an error message, checking whether
     * it is on a CLI script or on web.
     */
    private function checkDatabaseSupport()
    {
        global $CFG;

        if (!$this->supportedDatabase) {
            if (CLI_SCRIPT) {
                cli_error('Error: ' . __METHOD__ . ':: ' . get_string('errordatabase', 'tool_mergeusers', $CFG->dbtype));
            } else {
                print_error('errordatabase', 'tool_mergeusers', '', $CFG->dbtype);
            }
        }
    }

    /**
     * Disables course enrolments for the old user.
     *
     * The user_enrolments table is similar to grade_grades in that it also
     * has a compound unique key. The approach here is not to replace the
     * user in the case of a duplicate, but to disable the old user for that
     * particular course.
     *
     * @param int $toid The user inheriting the data
     * @param int $fromid The user being replaced
     * @param array $actionLog Array where to append the list of actions done.
     * @param array $errorMessages Array where to append any error occurred.
     */
    private function disableOldUserEnrolments($toid, $fromid, &$actionLog, &$errorMessages)
    {
        global $CFG, $DB;

        $sql = 'SELECT id, enrolid, userid, status from ' . $CFG->prefix . 'user_enrolments WHERE userid in (' .
                $fromid . ', ' . $toid . ')';
        $result = $DB->get_records_sql($sql);

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
                if (key($enrolInfo) == $fromid) {
                    //if we have the old user, we have to assign this course to the new user.
                    $enrolmentsToUpdate[] = $enrolInfo[$fromid]; //disable the old user
                    continue;
                } else {
                    //we don't have anything here for this course. We actually shouldn't get to this point ever.
                    continue;
                }
            }
            // check if it is actually enabled
            if ($result[$enrolInfo[$fromid]]->status != 2) {
                $idsToDisable[] = $enrolInfo[$fromid];
            }
            //check if it was already disabled
            if ($result[$enrolInfo[$toid]]->status == 2) {
                $enrolmentsToReactivate[] = $enrolInfo[$toid]; // reactivate new user.
            }
        }
        unset($enrolArr); //free memory
        unset($result); //free memory

        if (!empty($enrolmentsToUpdate)) { // it's possible we won't have any
            // First, let's move the courses belonging to the old user over to the new one.
            $updateIds = implode(', ', $enrolmentsToUpdate);
            $sql = 'UPDATE ' . $CFG->prefix . 'user_enrolments SET userid = ' . $toid .
                    ' WHERE id IN (' . $updateIds . ')';
            if ($DB->execute($sql)) {
                //all was ok: action done.
                $actionLog[] = $sql;
            } else {
                // a database error occurred.
                $errorMessages[] = get_string('tableko', 'tool_mergeusers', "user_enrolments (#1)") .
                        ': ' . $DB->get_last_error();
            }
        }
        unset($enrolmentsToUpdate); //free memory
        unset($sql);

        // ok, now let's lock this user out from using the common courses.
        if (!empty($idsToDisable)) {
            $idsGoByebye = implode(', ', $idsToDisable);
            $sql = 'UPDATE ' . $CFG->prefix . 'user_enrolments SET status = 2 WHERE id IN (' .
                    $idsGoByebye . ')  AND status = 0';
            if ($DB->execute($sql)) {
                //all was ok: action done.
                $actionLog[] = $sql;
            } else {
                // a database error occurred.
                $errorMessages[] = get_string('tableko', 'tool_mergeusers', "user_enrolments (#2)") .
                        ': ' . $DB->get_last_error();
            }
        }
        unset($idsToDisable); //free memory
        unset($sql);

        // the enrolment was deactivated before by us.
        // reactivate it again.
        if (!empty($enrolmentsToReactivate)) {
            $idsReactivate = implode(', ', $enrolmentsToReactivate);
            $sql = 'UPDATE ' . $CFG->prefix . 'user_enrolments SET status = 0 WHERE id IN (' .
                    $idsReactivate . ')  AND status = 2';
            if ($DB->execute($sql)) {
                //all was ok: action done.
                $actionLog[] = $sql;
            } else {
                // a database error occurred.
                $errorMessages[] = get_string('tableko', 'tool_mergeusers', "user_enrolments (#3)") .
                        ': ' . $DB->get_last_error();
            }
        }
        unset($enrolmentsToReactivate); //free memory
        unset($sql);
    }

    /**
     * Both users may appear in the same table under the same database index or so,
     * making some kind of conflict on Moodle and the database model. For simplicity, we always
     * use "compound index" to refer to it below.
     *
     * The merging operation for these cases are treated as follows:
     *
     * Possible scenarios:
     *
     * <ul>
     *   <li>$currentId only appears in a given compound index: we have to update it.</li>
     *   <li>$newId only appears in a given compound index: do nothing, skip.</li>
     *   <li>$currentId and $newId appears in the given compount index: delete the record for the $currentId.</li>
     * </ul>
     *
     * This function extracts the records' ids that have to be updated to the $newId, appearing only the
     * $currentId, and deletes the records for the $currentId when both appear.
     *
     * @global object $CFG
     * @global moodle_database $DB
     * @param int $toid
     * @param int $fromid
     * @param string $table table to check
     * @param string $userfield table's field name that refers to the user id.
     * @param array $otherfields table's field names that refers to the other members of the coumpund index.
     * @param array $recordsToModify array with current $table's id to update.
     * @param array $actionLog Array where to append the list of actions done.
     * @param array $errorMessages Array where to append any error occurred.
     */
    private function mergeCompoundIndex($toid, $fromid, $table, $userfield, $otherfields,
            &$recordsToModify, &$actionLog, &$errorMessages)
    {
        global $CFG, $DB;

        $otherfieldsstr = implode(', ', $otherfields);
        $sql = 'SELECT id, ' . $userfield . ', ' . $otherfieldsstr . ' from ' . $CFG->prefix . $table .
                ' WHERE ' . $userfield . ' in (' . $fromid . ', ' . $toid . ')';
        $result = $DB->get_records_sql($sql);

        $itemArr = array();
        $idsToRemove = array();
        foreach ($result as $id => $resObj) {
            $keyfromother = array();
            foreach ($otherfields as $of) {
                $keyfromother[] = $resObj->$of;
            }
            $keyfromotherstr = implode('-', $keyfromother);
            $itemArr[$keyfromotherstr][$resObj->$userfield] = $id;
        }
        unset($result); //free memory

        foreach ($itemArr as $otherfieldsid => $otherInfo) {
            //iff we have only one result and it is from the current user => update record
            if (sizeof($otherInfo) == 1) {
                if (isset($otherInfo[$fromid])) {
                    $recordsToModify[$otherInfo[$fromid]] = $otherInfo[$fromid];
                }
            } else { // both users appears in the group
                //confirm both records exist, preventing problems from inconsistent data in database
                if (isset($otherInfo[$toid]) && isset($otherInfo[$fromid])) {
                    $idsToRemove[$otherInfo[$fromid]] = $otherInfo[$fromid];
                }
            }
        }
        unset($itemArr); //free memory
        // to ease serch for existing ids on array
        $toMod = array_flip($recordsToModify);

        // we know that idsToRemove have always to be removed and NOT to be updated.
        foreach ($idsToRemove as $id) {
            if (isset($toMod[$id])) {
                unset($recordsToModify[$toMod[$id]]);
            }
        }
        unset($toMod); //free memory

        $idsGoByebye = implode(', ', $idsToRemove);
        $sql = 'DELETE FROM ' . $CFG->prefix . $table . ' WHERE id IN (' . $idsGoByebye . ')';
        if ($idsGoByebye) {
            if ($DB->execute($sql)) {
                $actionLog[] = $sql;
            } else {
                // an error occured during DB query
                $errorMessages[] = get_string('tableko', 'tool_mergeusers', $table) . ': ' .
                        $DB->get_last_error();
            }
        }
        unset($idsGoByebye); //free memory
        unset($idsToRemove);
        unset($sql);
    }

    /**
     * Gets the fields name on a compound index case. If the compound index only has a
     * user-related field, always returns the 'otherfields' of the $this->tablesWithCompoundIndex.
     * If both fields are user-related, gets the opposite field name.
     * @param string $tableName current table name without $DB->prefix.
     * @param string $userField current user-related field being analyized.
     * @return array an array with the other field names of the compound index.
     */
    private function getOtherFieldsOnCompoundIndex($tableName, $userField)
    {
        // we can alternate column names when both fields are user-related.
        if (isset($this->tablesWithCompoundIndex[$tableName]['both']) &&
            $this->tablesWithCompoundIndex[$tableName]['both'] &&
            $userField != $this->tablesWithCompoundIndex[$tableName]['userfield']) {

            // get the list of other fields.
            $others = array_flip($this->tablesWithCompoundIndex[$tableName]['otherfields']);
            // remove the given $userField
            unset($others[$userField]);
            $others = array_flip($others);
            // append the 'userfield'
            $others[] = $this->tablesWithCompoundIndex[$tableName]['userfield'];
            // return all except $userField
            return $others;
        }
        // default behavior
        return $this->tablesWithCompoundIndex[$tableName]['otherfields'];
    }

    /**
     * Gets the matching fields on the given $tableName against the given $userFields.
     * @param string $tableName database table name to analyse, with $CFG->prefix.
     * @param string $userFields candidate user fields to check.
     * @return bool | array false if no matching field name;
     * string array with matching field names otherwise.
     */
    private function getCurrentUserFieldNames($tableName, $userFields)
    {
        global $CFG, $DB;

        if ($CFG->dbtype == 'sqlsrv') {
            // MSSQL
            $fieldList = "SELECT * FROM INFORMATION_SCHEMA.Columns WHERE TABLE_NAME = '" .
                    $tableName . "' AND COLUMN_NAME IN (" . $userFields . ")";
        } else if ($CFG->dbtype == 'mysqli') {
            // MySQL
            $fieldList = "SHOW COLUMNS FROM " . $tableName . " where Field IN (" . $userFields . ")";
        } else if ($CFG->dbtype == 'pgsql') {
            // PGSQL
            $fieldList = "SELECT column_name FROM information_schema.columns WHERE table_name ='" .
                    $tableName . "' and column_name IN (" . $userFields . ");";
        }

        $dbFields = $DB->get_records_sql($fieldList);

        if (!$dbFields) {
            return false;
        }

        $fieldNames = array();

        // Now we have the appropriate fieldname and we know what to update!
        // Make the $fieldNames associative, to make them unique.
        if ($CFG->dbtype == 'sqlsrv') {
            // MSSQL
            foreach ($dbFields as $record) {
                $fieldNames[$record->column_name] = $record->column_name; // get the fieldname
            }
        } else if ($CFG->dbtype == 'mysqli') {
            // MySQL
            foreach ($dbFields as $record) {
                $fieldNames[$record->field] = $record->field; // get the fieldname
            }
        } else if ($CFG->dbtype == 'pgsql') {
            // PGSQL
            foreach ($dbFields as $record) {
                $fieldNames[$record->column_name] = $record->column_name; // get the fieldname
            }
        }
        return $fieldNames;
    }
}

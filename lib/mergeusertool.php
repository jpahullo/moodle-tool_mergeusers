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
 * @author     John Hoopes <hoopes@wisc.edu>, University of Wisconsin - Madison
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mergeusers\logger;

defined('MOODLE_INTERNAL') || die();

require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/config.php';

global $CFG;

require_once $CFG->dirroot . '/lib/clilib.php';
require_once __DIR__ . '/autoload.php';
require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/mergeusers/lib.php');

/**
 * Main tool to merge users.
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
class MergeUserTool {
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
     * @var logger logger for merging users.
     */
    protected $logger;

    /**
     * @var array associative array (tablename => classname) with the
     * TableMerger tools to process all database tables.
     */
    protected $tableMergers;

    /**
     * @var array list of table names processed by TableMerger's.
     */
    protected $tablesProcessedByTableMergers;

    /**
     * @var bool if true then never commit the transaction, used for testing.
     */
    protected $alwaysRollback;

    /**
     * @var bool if true then write out all sql, used for testing.
     */
    protected $debugdb;

    /**
     * Initializes the tool to merge users.
     *
     * @param tool_mergeusers_config $config local configuration.
     * @param logger $logger logger facility to save results of mergings.
     * @throws moodle_exception when the merger for a given table is not an instance of TableMerger
     */
    public function __construct(?tool_mergeusers_config $config = null, ?logger $logger = null) {
        $this->logger = (is_null($logger)) ? new logger() : $logger;
        $config = (is_null($config)) ? tool_mergeusers_config::instance() : $config;

        $this->checkTransactionSupport();

        // These are tables we don't want to modify due to logging or security reasons.
        // We flip key<-->value to accelerate lookups.
        $this->tablesToSkip = array_flip($config->exceptions);
        $excluded = explode(',', get_config('tool_mergeusers', 'excluded_exceptions'));
        $excluded = array_flip($excluded);
        if (!isset($excluded['none'])) {
            foreach ($excluded as $exclude => $nonused) {
                unset($this->tablesToSkip[$exclude]);
            }
        }

        // These are special cases, corresponding to tables with compound indexes that need a special treatment.
        $this->tablesWithCompoundIndex = $config->compoundindexes;

        // Initializes user-related field names.
        $this->userFieldNames = $config->userfieldnames;

        // Load available TableMerger tools.
        $tableMergers = array();
        $tablesProcessedByTableMergers = array();
        foreach ($config->tablemergers as $tableName => $class) {
            $tm = new $class();
            // ensure any provided class is a class of TableMerger
            if (!$tm instanceof TableMerger) {
                // aborts execution by showing an error.
                if (CLI_SCRIPT) {
                    cli_error('Error: ' . __METHOD__ . ':: ' . get_string('notablemergerclass', 'tool_mergeusers',
                                    $class));
                } else {
                    throw new moodle_exception(
                        'notablemergerclass',
                        'tool_mergeusers',
                        new moodle_url('/admin/tool/mergeusers/index.php'),
                        $class,
                    );
                }
            }
            // Append any additional table to skip.
            $tablesProcessedByTableMergers = array_merge($tablesProcessedByTableMergers, $tm->getTablesToSkip());
            $tableMergers[$tableName] = $tm;
        }
        $this->tableMergers = $tableMergers;
        $this->tablesProcessedByTableMergers = array_flip($tablesProcessedByTableMergers);

        $this->alwaysRollback = !empty($config->alwaysRollback);
        $this->debugdb = !empty($config->debugdb);

        // Initializes the list of fields and tables to check in the current database, given the local configuration.
        $this->init();
    }

    /**
     * Merges two users into one. User-related data records from user id $fromid are merged into the
     * user with id $toid.
     *
     * @param int $toid The user inheriting the data
     * @param int $fromid The user being replaced
     * @return array An array(bool, array, int) having the following cases: if array(true, log, id)
     * users' merging was successful and log contains all actions done; if array(false, errors, id)
     * means users' merging was aborted and errors contain the list of errors.
     * The last id is the log id of the merging action for later visual revision.
     * @throws dml_exception
     */
    public function merge(int $toid, int $fromid): array {
        list($success, $log) = $this->_merge($toid, $fromid);

        $eventpath = "\\tool_mergeusers\\event\\";
        $eventpath .= ($success) ? "user_merged_success" : "user_merged_failure";

        $logid = $this->logger->log($toid, $fromid, $success, $log);

        $event = $eventpath::create(array(
            'context' => \context_system::instance(),
            'other' => array(
                'usersinvolved' => array(
                    'toid' => $toid,
                    'fromid' => $fromid,
                ),
                'logid' => $logid,
                'log' => $log,
            ),
        ));
        $event->trigger();

        return array($success, $log, $logid);
    }

    /**
     * Real method that performs the merging action.
     *
     * @param int $toid The user inheriting the data
     * @param int $fromid The user being replaced
     * @return array An array(bool, array) having the following cases: if array(true, log)
     * users' merging was successful and log contains all actions done; if array(false, errors)
     * means users' merging was aborted and errors contain the list of errors.
     * @throws coding_exception
     * @throws dml_transaction_exception
     */
    private function _merge(int $toid, int $fromid): array {
        global $DB;

        // Initial checks.
        // Are they the same?
        if ($fromid == $toid) {
            // Do nothing.
            return [false, [get_string('errorsameuser', 'tool_mergeusers')]];
        }


        $someuserdoesnotexists = array_filter(
            array_map(
                function ($userid) use ($DB) {
                    if ($DB->record_exists('user', ['id' => $userid, 'deleted' => 0])) {
                        return null;
                    }
                    return get_string('invaliduser', 'tool_mergeusers', ['field' => 'id', 'value' => $userid]);
                },
                [$toid, $fromid],
            ),
        );

        // Abort merging users when at least one of them is already deleted.
        // We need to enforce this condition here.
        if (count($someuserdoesnotexists) > 0) {
            return [false, $someuserdoesnotexists];
        }

        // Ok, now we have to work ;-)
        // First of all... initialization!
        $errorMessages = [];
        $actionLog = [];

        if ($this->debugdb) {
            $DB->set_debug(true);
        }

        $startTime = time();
        $startTimeString = get_string('starttime', 'tool_mergeusers', userdate($startTime));
        $actionLog[] = $startTimeString;

        $transaction = $DB->start_delegated_transaction();

        try {
            // Processing each table name.
            $data = [
                'toid' => $toid,
                'fromid' => $fromid,
            ];
            foreach ($this->userFieldsPerTable as $tableName => $userFields) {
                $data['tableName'] = $tableName;
                $data['userFields'] = $userFields;
                if (isset($this->tablesWithCompoundIndex[$tableName])) {
                    $data['compoundIndex'] = $this->tablesWithCompoundIndex[$tableName];
                } else {
                    unset($data['compoundIndex']);
                }

                $tableMerger = (isset($this->tableMergers[$tableName])) ?
                        $this->tableMergers[$tableName] :
                        $this->tableMergers['default'];

                // process the given $tableName.
                $tableMerger->merge($data, $actionLog, $errorMessages);
            }

            $this->updateGrades($toid, $fromid);
            $this->reaggregateCompletions($toid);
        } catch (Exception $e) {
            $errorMessages[] = nl2br("Exception thrown when merging: '" . $e->getMessage() . '".' .
                    html_writer::empty_tag('br') . $DB->get_last_error() . html_writer::empty_tag('br') .
                    'Trace:' . html_writer::empty_tag('br') .
                    $e->getTraceAsString() . html_writer::empty_tag('br'));
        }

        if ($this->debugdb) {
            $DB->set_debug(false);
        }

        if ($this->alwaysRollback) {
            $transaction->rollback(new Exception('alwaysRollback option is set so rolling back transaction'));
        }

        // concludes with true if no error
        if (empty($errorMessages)) {
            $transaction->allow_commit();

            // add skipped tables as first action in log
            $skippedTables = [];
            if (!empty($this->tablesSkipped)) {
                $skippedTables[] = get_string('tableskipped', 'tool_mergeusers', implode(", ", $this->tablesSkipped));
            }

            $finishTime = time();
            $actionLog[] = get_string('finishtime', 'tool_mergeusers', userdate($finishTime));
            $actionLog[] = get_string('timetaken', 'tool_mergeusers', $finishTime - $startTime);

            return [true, array_merge($skippedTables, $actionLog)];
        } else {
            try {
                // Thrown controlled exception.
                $transaction->rollback(new Exception(__METHOD__ . ':: Rolling back transcation.'));
            } catch (Exception $e) { /* Do nothing, just for correctness */
            }
        }

        $finishTime = time();
        $errorMessages[] = $startTimeString;
        $errorMessages[] = get_string('timetaken', 'tool_mergeusers', $finishTime - $startTime);

        // Concludes with an array of error messages otherwise.
        return [false, $errorMessages];
    }

    // ****************** INTERNAL UTILITY METHODS ***********************************************

    /**
     * Initializes the list of database table names and user-related fields for each table.
     * @global object $CFG
     * @global moodle_database $DB
     */
    private function init(): void {
        global $DB;

        $userFieldsPerTable = array();

        // Name of tables comes without db prefix.
        $tableNames = $DB->get_tables(false);

        foreach ($tableNames as $tableName) {

            if (!trim($tableName)) {
                // This section should never be executed due to the way Moodle returns its resultsets.
                // Skipping due to blank table name.
                continue;
            } else {
                // Table specified to be excluded.
                if (isset($this->tablesToSkip[$tableName])) {
                    $this->tablesSkipped[$tableName] = $tableName;
                    continue;
                }
                // Table specified to be processed additionally by a TableMerger.
                if (isset($this->tablesProcessedByTableMergers[$tableName])) {
                    continue;
                }
            }

            // detect available user-related fields among database tables.
            $userFields = (isset($this->userFieldNames[$tableName])) ?
                    $this->userFieldNames[$tableName] :
                    $this->userFieldNames['default'];

            $arrayUserFields = array_flip($userFields);
            $currentFields = $this->getCurrentUserFieldNames($tableName, $arrayUserFields);

            if ($currentFields !== false) {
                $userFieldsPerTable[$tableName] = $currentFields;
            }
        }

        $this->userFieldsPerTable = $userFieldsPerTable;

        $existingCompoundIndexes = $this->tablesWithCompoundIndex;
        foreach ($this->tablesWithCompoundIndex as $tableName => $columns) {
            $chosenColumns = array_merge($columns['userfield'], $columns['otherfields']);

            $columnNames = array();
            foreach ($chosenColumns as $columnName) {
                $columnNames[$columnName] = 0;
            }

            $tableColumns = $DB->get_columns($tableName, false);

            foreach ($tableColumns as $column) {
                if (isset($columnNames[$column->name])) {
                    $columnNames[$column->name] = 1;
                }
            }

            // Remove compound index when loaded configuration does not correspond to current database scheme.
            $found = array_sum($columnNames);
            if (sizeof($columnNames) !== $found) {
                unset($existingCompoundIndexes[$tableName]);
            }
        }

        // update the attribute with the current existing compound indexes per table.
        $this->tablesWithCompoundIndex = $existingCompoundIndexes;
    }

    /**
     * Checks whether the current database supports transactions.
     * If settings of this plugin are set up to allow only transactions,
     * this method aborts the execution. Otherwise, this method will return
     * true or false whether the current database supports transactions or not,
     * respectively.
     *
     * @return bool true if database transactions are supported. false otherwise.
     * @throws moodle_exception when the current db instance does not support transactions
     * and the plugin settings prevents merging users under this case.
     */
    public function checkTransactionSupport(): bool {
        global $CFG;

        $transactionsSupported = tool_mergeusers_transactionssupported();
        $forceOnlyTransactions = get_config('tool_mergeusers', 'transactions_only');

        if (!$transactionsSupported && $forceOnlyTransactions) {
            if (CLI_SCRIPT) {
                cli_error('Error: ' . __METHOD__ . ':: ' . get_string('errortransactionsonly', 'tool_mergeusers',
                                $CFG->dbtype));
            } else {
                throw new moodle_exception(
                    'errortransactionsonly',
                    'tool_mergeusers',
                    new moodle_url('/admin/tool/mergeusers/index.php'),
                    $CFG->dbtype,
                );
            }
        }

        return $transactionsSupported;
    }

    /**
     * Gets the matching fields on the given $tableName against the given $userFields.
     * @param string $tableName database table name to analyse, with $CFG->prefix.
     * @param array $userFields candidate user fields to check.
     * @return array table columns that correspond to user.id field.
     */
    private function getCurrentUserFieldNames(string $tableName, array $userFields): array {
        global $DB;
        $columns = $DB->get_columns($tableName,false);
        $usercolumns = [];
        foreach($columns as $column) {
            if (isset($userFields[$column->name])) {
                $usercolumns[$column->name] = $column->name;
            }
        }
        return $usercolumns;
    }

    /**
     * Update all the target user's grades.
     *
     * @param int $toid To user.id
     * @param int $fromid From user.id
     * @throws dml_exception
     * @throws coding_exception
     * @throws Exception
     */
    private function updateGrades(int $toid, int $fromid): void {
        global $DB, $CFG;
        require_once($CFG->libdir.'/gradelib.php');

        $sql = "SELECT DISTINCT gi.id, gi.iteminstance, gi.itemmodule, gi.courseid
                FROM {grade_grades} gg
                INNER JOIN {grade_items} gi on gg.itemid = gi.id
                WHERE itemtype = 'mod' AND (gg.userid = :toid OR gg.userid = :fromid)";

        $iteminstances = $DB->get_records_sql($sql, array('toid' => $toid, 'fromid' => $fromid));

        foreach ($iteminstances as $iteminstance) {
            if (!$activity = $DB->get_record($iteminstance->itemmodule, array('id' => $iteminstance->iteminstance))) {
                throw new moodle_exception(
                    'exception:nomoduleinstance',
                    'tool_mergeusers',
                    '',
                    [
                        'module' => $iteminstance->itemmodule,
                        'activityid' => $iteminstance->iteminstance,
                    ]
                );
            }
            if (!$cm = get_coursemodule_from_instance($iteminstance->itemmodule, $activity->id, $iteminstance->courseid)) {
                throw new moodle_exception(
                    'exception:nocoursemodule',
                    'tool_mergeusers',
                    '',
                    [
                        'module' => $iteminstance->itemmodule,
                        'activityid' => $activity->id,
                        'courseid' => $iteminstance->courseid,
                    ],
                );
            }

            $activity->modname    = $iteminstance->itemmodule;
            $activity->cmidnumber = $cm->idnumber;

            grade_update_mod_grades($activity, $toid);
        }
    }

    /**
     * Forces Moodle to repeat aggregation of completion conditions.
     *
     * @param int $toid To user.id
     * @return void
     * @throws dml_exception
     */
    private function reaggregateCompletions(int $toid): void {
        global $DB;

        $now = time();
        $DB->execute(
                'UPDATE {course_completions}
                        SET reaggregate = :now
                      WHERE userid = :toid 
                        AND (timecompleted IS NULL OR timecompleted = 0)',
                ['now' => $now, 'toid' => $toid]
        );
    }
}

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
 * @package tool
 * @subpackage mergeusers
 * @author Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2013 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Wrapper class for the configuration settings of the merge user utility.
 */
class tool_mergeusers_config
{

    /**
     * @var tool_mergeusers_config singleton instance.
     */
    private static $instance = null;

    /**
     * @var array settings
     */
    private $config;

    /**
     * @var array on-demand calculated settings.
     */
    private $calculated;

    /**
     * Private constructor for the singleton.
     */
    private function __construct()
    {
        $this->config = (array) get_config('tool_mergeusers');
        $this->config['tablemergers'] = array(
            'default' => 'GenericTableMerger',
            'user_enrolments' => 'UserEnrolmentsMerger',
            'quiz_attempts' => 'QuizAttemptsMerger',
        );
        $this->calculated = array();
    }

    /**
     * Singleton method.
     * @return tool_mergeusers_config singleton instance.
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new tool_mergeusers_config();
        }
        return self::$instance;
    }

    /**
     * Accessor to properties from the current config as attributes of an standard object.
     * @param string $name name of attribute; by now only:
     * 'gathering', 'exceptions', 'compoundindexes', 'userfieldnames'.
     * @return mixed null if $name is not a valid property name of the current configuration;
     * string or array having the value of the $name property.
     */
    public function __get($name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

    /**
     * Loads the whole set column names from all tables altogether.
     * @global \moodle_database $DB
     */
    public function getAllColumns()
    {
        if (!isset($this->calculated['all_columns'])) {
            global $DB;
            $useCache = false;
            $allTables = $DB->get_tables($useCache);

            $allColumns = array();
            foreach ($allTables as $table) {
                $columns = $DB->get_columns($table, $useCache);
                foreach ($columns as $columnName => $column_unused) {
                    $allColumns[$columnName] = $columnName;
                }
            }
            asort($allColumns);
            $this->calculated['all_columns'] = $allColumns;
        }

        return $this->calculated['all_columns'];
    }

    /**
     * Loads all compound indexes with user related columns.
     * @global \moodle_database $DB
     */
    public function getCompoundIndexes()
    {
        if (!isset($this->calculated['all_indexes_by_table'])) {
            global $DB;
            $useCache = false;
            $allTables = $DB->get_tables($useCache);

            $indexes = array();
            $indexesByTable = array();
            $indexesByColumns = array();
            foreach ($allTables as $table) {
                $indexes = $DB->get_indexes($table);
                foreach ($indexes as $indexName => $index) {
                    if (1 == count($index['columns'])) {
                        continue;
                    }
                    $names = $this->getUserColumnnames($table);
                    $group = $this->groupFieldnames($table, $index['columns'], $names);
                    if ($group) {
                        $unique = (int) $index['unique'];
                        $indexes[$unique][$table][$indexName] = $index['columns'];
                        $indexesByTable[$table][$indexName][$unique] = $group;
                        $prototype = new stdClass();
                        $prototype->type = (int) $unique;
                        $prototype->name = $indexName;
                        $indexesByColumns[$table . '-' . $group['stringordered']] = $prototype;
                    }
                }
            }
            $this->calculated['all_indexes'] = $indexes;
            $this->calculated['all_indexes_by_table'] = $indexesByTable;
            $this->calculated['all_indexes_by_columns'] = $indexesByColumns;
        }

        return $this->calculated['all_indexes_by_table'];
    }

    /**
     * Get the user-related column names for any specified table. The structure
     * is as follows: <code>array('default' => array('columnname1', ...),
     * 'tablename1' => array('columnnamet1', ...), ...)</code>. If a table name
     * is present, it will return the list of column names if the given
     * table has specific user-related columns, or the default ones otherwise.
     *
     * @param bool|string $table false to get all tables and their user-related
     * column names. Otherwise, ask for columns for a given table specifying
     * its name here.
     * @return array array of columns per table if asking all of them (when
     * $table is false), or just an array with the list of column names
     * if asking for a given table.
     */
    protected function getUserColumnnames($table = false)
    {
        if (!isset($this->calculated['userfieldnames_indexed'])) {
            $userFieldnames = array();
            $prefix = 'user_related_columns_for_';
            $prefixLen = strlen($prefix);
            foreach (array_keys($this->config) as $key) {
                if (strstr($key, $prefix)) {
                    $tableName = substr($key, $prefixLen);
                    $values = explode(',', $this->config[$key]);
                    $userFieldnames[$tableName] = array_flip($values);
                }
            }

            $this->calculated['userfieldnames_indexed'] = $userFieldnames;
        }

        // have we return the whole set of user related fieldnames?
        if ($table === false) {
            return $this->calculated['userfieldnames_indexed'];
        }

        // check if the asked table is present; otherwise, use default.
        return (isset($this->calculated['userfieldnames_indexed'][$table]))
            ? $this->calculated['userfieldnames_indexed'][$table]
            : $this->calculated['userfieldnames_indexed']['default'];
    }

    /**
     * Group the fieldnames for those related to user.id and the others.
     * @param string $table table name, without prefix
     * @param array $columns list of columns from the table
     * @param array $userColumns the list of columns from the table that are related to user.id.
     * If not present, it is loaded from current settings.
     * @return bool|array false if there is no user.id related column; an array
     * of the form <code>array('userfields' => array(colunm1, ...),
     * 'otherfields' => array(...), 'ordered' => array(...),
     * 'stringordered' => "column1,column2,...")</code>.
     */
    protected function groupFieldnames($table, array $columns, array $userColumns = null)
    {
        if (null === $userColumns) {
            $userColumns = $this->getUserColumnnames($table);
        }

        $users = array();
        $others = array();
        foreach ($columns as $column) {
            if (isset($userColumns[$column])) {
                $users[] = $column;
            } else {
                $others[] = $column;
            }
        }
        $ordered = $columns;
        sort($ordered);

        return (empty($users))
            ? false
            : array(
                'userfields' => $users,
                'otherfields' => $others,
                'ordered' => $columns,
                'stringordered' => implode(',', $ordered),
            );
    }

    public function getIndexType($columnlist)
    {
        $tableName = substr($columnlist, 0, strpos($columnlist, '-'));
        return (isset($this->config['indexesbycolumns'][$columnlist]))
            ? $this->config['indexesbycolumns'][$columnlist] // unique or non unique indexes
            : (object) array('type' => 2, 'name' => $tableName); //ad-hoc
    }

    public function getIndexesToCheckOptions(array $nonuniqueIndexes)
    {
        if (!isset($this->calculated['all_indexes_by_table'])) {
            $this->getCompoundIndexes();
        }

        if (!isset($this->calculated['indexes_to_check'])) {
            if (isset($this->config['tables_with_adhoc_indexes'])) {
                $adhocindexes = explode(',', $this->config['tables_with_adhoc_indexes']);
                foreach ($adhocindexes as $table) {
                    $settingname = 'columns_for_adhoc_index_for_' . $table;
                    if (!isset($this->config[$settingname])) {
                        continue;
                    }
                    $columns = explode(',', $this->config[$settingname]);
                    $group = $this->groupFieldnames($table, $columns);
                    if ($group) {
                        $this->calculated['all_indexes_by_table'][$table][$settingname][2] = $group;
                    }
                }
            }

            $tablenames = array_keys($this->calculated['all_indexes_by_table']);
            array_multisort($tablenames, SORT_ASC, $this->calculated['all_indexes_by_table']);

            $options = $this->calculated['all_indexes_by_table']; // produces a copy.
            $nonuniques = array_flip($nonuniqueIndexes);
            foreach ($options as $table => $indexes) {
                foreach ($indexes as $index => $uniqueness) {
                    if (isset($uniqueness[0]) && !isset($nonuniques[$table])) {
                        unset($options[$table][$index]);
                    }
                }
            }
            $this->calculated['indexes_to_check'] = $options;
        }

        return $this->calculated['indexes_to_check'];
    }

    public function tableHasUniqueIndex($table)
    {
        if (!isset($this->calculated['all_indexes'])) {
            $this->getCompoundIndexes();
        }
        return isset($this->calculated['all_indexes'][1][$table]);
    }

    public function getNonuniqueIndexOptions($renderer)
    {
        if (!isset($this->calculated['nonunique_index_options'])) {
            $nonuniqueoptions = array();
            foreach ($this->calculated['all_indexes_by_table'] as $tablename => $indexes) {
                foreach ($indexes as $indexname => $uniquevalues) {
                    // We assume that a unique index is preferent to a non unique.
                    // If both types of indexes exist, only unique index is presented,
                    // since we only allow a merger per table, not per index.
                    if (isset($uniquevalues[0]) && !$this->tableHasUniqueIndex($tablename)) {
                        $columns = &$uniquevalues[0];
                        $orderedcolumns = $renderer->format_columns($columns['ordered'], $columns['userfields']);
                        $key = $tablename . ':' . implode('.', $columns['ordered']);
                        $nonuniqueoptions[$key] = $tablename . ' - ' . $indexname . ' : ' . $orderedcolumns;
                    }
                }
            }
            $this->config['nonunique_index_options'] = $nonuniqueoptions;
        }

        return $this->config['nonunique_index_options'];
    }

}

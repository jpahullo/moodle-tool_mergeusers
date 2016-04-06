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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Take actions on upgrading mergeusers tool.
 * @package tool_mergeusers
 * @global moodle_database $DB
 * @param int $oldversion old plugin version.
 * @return boolean true when success, false on error.
 */
function xmldb_tool_mergeusers_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013112912) {

        // Define table tool_mergeusers to be created
        $table = new xmldb_table('tool_mergeusers');

        // Adding fields to table tool_mergeusers
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('touserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fromuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('success', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('log', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_mergeusers
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table tool_mergeusers
        $table->add_index('mdl_toolmerg_tou_ix', XMLDB_INDEX_NOTUNIQUE, array('touserid'));
        $table->add_index('mdl_toolmerg_fru_ix', XMLDB_INDEX_NOTUNIQUE, array('fromuserid'));
        $table->add_index('mdl_toolmerg_suc_ix', XMLDB_INDEX_NOTUNIQUE, array('success'));
        $table->add_index('mdl_toolmerg_tfs_ix', XMLDB_INDEX_NOTUNIQUE, array('touserid', 'fromuserid', 'success'));

        // Conditionally launch create table for tool_mergeusersr
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // mergeusers savepoint reached
        upgrade_plugin_savepoint(true, 2013112912, 'tool', 'mergeusers');
    }

    if ($oldversion < 2015060222) {
        tool_mergeusers_upgrade_settings_2015060222();
        // mergeusers savepoint reached
        upgrade_plugin_savepoint(true, 2015060222, 'tool', 'mergeusers');
    }

    return true;
}

function tool_mergeusers_upgrade_settings_2015060222()
{
    require_once(__DIR__ . '/../lib/autoload.php');
    // 1. loading old config.php settings here.
    $config = array(
        // gathering tool
        'gathering' => 'CLIGathering',
        // Database tables to be excluded from normal processing.
        // You normally will add tables. Be very cautious if you delete any of them.
        'exceptions' => array(
            'user_preferences',
            'user_private_key',
            'user_info_data',
            'my_pages',
        ),
        // List of compound indexes.
        // This list may vary from Moodle instance to another, given that the Moodle version,
        // local changes and non-core plugins may add new special cases to be processed.
        // Put in 'userfields' all column names related to a user (i.e., user.id), and all the rest column names
        // into 'otherfields'.
        // See README.txt for details on special cases.
        // Table names must be without $CFG->prefix.
        'compoundindexes' => array(
            'grade_grades' => array(// automatic index
                'userfields' => array('userid'),
                'otherfields' => array('itemid'),
            ),
            'groups_members' => array(// MANUAL INDEX (non existing compound index actually)
                'userfields' => array('userid'),
                'otherfields' => array('groupid'),
            ),
            'journal_entries' => array(// manual index (non existing compound index actually)
                'userfields' => array('userid'),
                'otherfields' => array('journal'),
            ),
            'course_completions' => array(// automatic index
                'userfields' => array('userid'),
                'otherfields' => array('course'),
            ),
            'message_contacts' => array(// automatic index //both fields are user.id values
                'userfields' => array('userid', 'contactid'),
                'otherfields' => array(),
            ),
            'role_assignments' => array(//automatic index, but not unique and so not matched
                'userfields' => array('userid'),
                'otherfields' => array('contextid', 'roleid'), // mdl_roleassi_useconrol_ix (not unique)
            ),
            'user_lastaccess' => array(// automatic index
                'userfields' => array('userid'),
                'otherfields' => array('courseid'), // mdl_userlast_usecou_ui (unique)
            ),
            'quiz_attempts' => array(// automatic index
                'userfields' => array('userid'),
                'otherfields' => array('quiz', 'attempt'), // mdl_quizatte_quiuseatt_uix (unique)
            ),
            'cohort_members' => array(// automatic index
                'userfields' => array('userid'),
                'otherfields' => array('cohortid'),
            ),
            'certif_completion' => array(// automatic index // mdl_certcomp_ceruse_uix (unique)
                'userfields' => array('userid'),
                'otherfields' => array('certifid'),
            ),
            'course_modules_completion' => array(// automatic index // mdl_courmoducomp_usecou_uix (unique)
                'userfields' => array('userid'),
                'otherfields' => array('coursemoduleid'),
            ),
            'scorm_scoes_track' => array(// automatic index // mdl_scorscoetrac_usescosco_uix (unique)
                'userfields' => array('userid'),
                'otherfields' => array('scormid', 'scoid', 'attempt', 'element'),
            ),
            'assign_grades' => array(// automatic index // UNIQUE KEY mdl_assigrad_assuseatt_uix
                'userfields' => array('userid'),
                'otherfields' => array('assignment', 'attemptnumber'),
            ),
            'badge_issued' => array(// unique key mdl_badgissu_baduse_uix
                'userfields' => array('userid'),
                'otherfields' => array('badgeid'),
            ),
        ),
        // List of column names per table, where their content is a user.id.
        // These are necessary for matching passed by userids in these column names.
        // In other words, only column names given below will be search for matching user ids.
        // The key 'default' will be applied for any non matching table name.
        'userfieldnames' => array(
            'message_contacts' => array('userid', 'contactid'), //compound index
            'message' => array('useridfrom', 'useridto'),
            'message_read' => array('useridfrom', 'useridto'),
            'question' => array('createdby', 'modifiedby'),
            'default' => array('authorid', 'reviewerid', 'userid', 'user_id', 'id_user', 'user'), //may appear compound index
        ),
        // TableMergers to process each database table.
        // 'default' is applied when no specific TableMerger is specified.
        'tablemergers' => array(
            'default' => 'GenericTableMerger',
            'user_enrolments' => 'UserEnrolmentsMerger',
            'quiz_attempts' => 'QuizAttemptsMerger',
        ),
    );

    // 2. load customized settings and merge them.
    if (file_exists(dirname(__DIR__) . '/config/config.local.php')) {
        $localconfig = include dirname(__DIR__) . '/config/config.local.php';
        $config = array_replace_recursive($config, $localconfig);
    }
    $configTool = tool_mergeusers_config::instance();
    $configTool->loadCompoundIndexes();

    // 3. translate to new settings format (add/delete/update settings)
    $settings = tool_mergeusers_translate_settings($config, $configTool);

    // 4. store them as plugin settings in database.
    $excluded = get_config('tool_mergeusers', 'excluded_exceptions');
    if ($excluded !== false) {
        set_config('excluded_exceptions_', $excluded, 'tool_mergeusers');
    }
    foreach ($settings as $key => $value) {
        set_config($key, $value, 'tool_mergeusers');
        set_config('default_' . $key, $value, 'tool_mergeusers');
    }
}

function tool_mergeusers_translate_settings(array $config, $indexes)
{
    $settings = array();

    // on settings.
    $settings['gathering'] = $config['gathering'];

    $excludedExceptions = get_config('tool_mergeusers', 'excluded_exceptions_');

    echo '<pre>';var_dump($config['exceptions']);echo '</pre>';
    if ($excludedExceptions !== false) {
        $exceptions = array_flip($config['exceptions']);
        $excludedExceptions = explode(',', $excludedExceptions);
        foreach ($excludedExceptions as $option) {
            switch ($option) {
                case 'none':
                case 'my_pages':
                    break;
                default:
                    unset($exceptions[$option]);
            }
        }
        echo '<pre>';var_dump($excludedExceptions, $exceptions);echo '</pre>';
        $config['exceptions'] = array_flip($exceptions);
    }

    $settings['excluded_exceptions'] = null; //clean up unused old setting
    $settings['excluded_tables'] = implode(',',$config['exceptions']);

    $tables = array();
    foreach ($config['userfieldnames'] as $table => $columns) {
        $settings['user_related_columns_for_' . $table] = implode(',', $columns);
        if ($table !== 'default') {
            $tables[] = $table;
        }
    }
    $settings['tables_with_custom_user_related_columns'] = implode(',', $tables);

    $settings['nonunique_indexes'] = array();
    $settings['adhoc_indexes'] = array();
    echo '<pre>';var_dump($indexes);echo '</pre>';
    foreach ($config['compoundindexes'] as $table => $columns) {
        $names = array_merge($columns['userfields'], $columns['otherfields']);
        sort($names);
        $names = implode(',', $names);
        $key = $table.'-'.$names;

        $index = $indexes->getIndexType($key);
        echo '<pre>';var_dump($key, $index);echo '</pre>';
        switch ($index->type) {
            case 0:
                // non unique
                $settings['nonunique_indexes'][] = $index->name;
                break;
            case 1:
                //unique => do nothing, it will be processed always.
                break;
            case 2:
                // non existing => ad hoc index.
                $settings['adhoc_indexes'][] = $index->name;
                $settings['adhoc_indexes_columns_for_' . $table] = $names;
                break;
            default:
                // it should not get here.
        }
    }
    $settings['nonunique_indexes'] = implode(',', $settings['nonunique_indexes']);
    $settings['adhoc_indexes'] = implode(',', $settings['adhoc_indexes']);

    $settings['table_mergers'] = implode(',', $config['tablemergers']);

    return $settings;
}
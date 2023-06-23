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
require_once __DIR__ . '/../classes/merge_request.php';
use \tool_mergeusers\merge_request;
/**
 * @package tool
 * @subpackage mergeusers
 * @author Jordi Pujol-Ahulló <jordi.pujol@urv.cat>, Nicola Vallinoto <n.vallinoto@liguriadigitale.it>
 * @copyright 2013 Servei de Recursos Educatius (http://www.sre.urv.cat) - 2023 Liguria Digitale (https://www.liguriadigitale.it)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Take actions on upgrading mergeusers tool. Any Moodle instance that is having this plugin already installed,
 * must produce new table schema, and all existing data in old table (tool_mergeusers) should be migrated to the new table
 * @package tool_mergeusers
 * @global moodle_database $DB
 * @param int $oldversion old plugin version.
 * @return boolean true when success, false on error.
 */
function xmldb_tool_mergeusers_upgrade ($oldversion) {
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

    if ($oldversion < 2023040401) {

        // Define field mergedbyuserid to be added to tool_mergeusers.
        $table = new xmldb_table('tool_mergeusers');
        $field = new xmldb_field('mergedbyuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'success');

        // Conditionally launch add field mergedbyuserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mergeusers savepoint reached.
        upgrade_plugin_savepoint(true, 2023040401, 'tool', 'mergeusers');
    }
    
    if ($oldversion < 2023062310) {
        // Define table tool_mergeusers_queue to be created.
        $table = new xmldb_table('tool_mergeusers_queue');
        // Adding fields to table tool_mergeusers_queue.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('removeuserfield', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('removeuservalue', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('removeuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('keepuserfield', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('keepuservalue', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('keepuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('mergedbyuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timeadded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('retries', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('log', XMLDB_TYPE_TEXT, null, null, null, null, null);
        // Adding keys to table tool_mergeusers_queue.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('removeuserid', XMLDB_KEY_FOREIGN, ['removeuserid'], 'user', ['id']);
        $table->add_key('keepuserid', XMLDB_KEY_FOREIGN, ['keepuserid'], 'user', ['id']);
        // Adding indexes to table tool_mergeusers_queue.
        $table->add_index('mdl_toolmergqueu_rem_ix', XMLDB_INDEX_NOTUNIQUE, ['removeuservalue']);
        $table->add_index('mdl_toolmergqueu_kee_ix', XMLDB_INDEX_NOTUNIQUE, ['keepuservalue']);
        $table->add_index('mdl_toolmergqueu_timsta_ix', XMLDB_INDEX_NOTUNIQUE, ['timeadded', 'status']);
        $table->add_index('mdl_toolmergqueu_timsta2_ix', XMLDB_INDEX_NOTUNIQUE, ['timemodified', 'status']);
        // Conditionally launch create table for tool_mergeusers_queue and populate it importing data from old table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
            merge_request::export_data_to_new_table();
            upgrade_plugin_savepoint(true, 2023062310, 'tool', 'mergeusers');
        }
    }
   
    return true;
}

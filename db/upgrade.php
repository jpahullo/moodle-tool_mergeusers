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
 * Upgrade steps for the plugin.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2013 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Take actions on upgrading mergeusers tool.
 *
 * @package tool_mergeusers
 * @param int $oldversion old plugin version.
 * @return boolean true when success, false on error.
 */
function xmldb_tool_mergeusers_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013112912) {
        // Define table tool_mergeusers to be created.
        $table = new xmldb_table('tool_mergeusers');

        // Adding fields to table tool_mergeusers.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('touserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fromuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('success', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('log', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_mergeusers.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table tool_mergeusers.
        $table->add_index('mdl_toolmerg_tou_ix', XMLDB_INDEX_NOTUNIQUE, ['touserid']);
        $table->add_index('mdl_toolmerg_fru_ix', XMLDB_INDEX_NOTUNIQUE, ['fromuserid']);
        $table->add_index('mdl_toolmerg_suc_ix', XMLDB_INDEX_NOTUNIQUE, ['success']);
        $table->add_index('mdl_toolmerg_tfs_ix', XMLDB_INDEX_NOTUNIQUE, ['touserid', 'fromuserid', 'success']);

        // Conditionally launch create table for tool_mergeusers.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Savepoint reached.
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

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2023040401, 'tool', 'mergeusers');
    }

    if ($oldversion < 2026060100) {
        // Define field status to be added to tool_mergeusers.
        $table = new xmldb_table('tool_mergeusers');
        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'log');

        // Conditionally launch add field status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('mdl_toolmerg_sta_ix', XMLDB_INDEX_NOTUNIQUE, ['status']);

        // Conditionally launch add index mdl_toolmerg_sta_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Mergeusers savepoint reached.
        upgrade_plugin_savepoint(true, 2026060100, 'tool', 'mergeusers');
    }

    if ($oldversion < 2026060101) {
        // Migrate success field into status field.
        $table = new xmldb_table('tool_mergeusers');
        $field = new xmldb_field('success');

        // Only migrate if the success field exists (for upgrades from older versions).
        if ($dbman->field_exists($table, $field)) {
            $DB->execute("UPDATE {tool_mergeusers} SET status =
                CASE
                    WHEN success = 1 THEN 'success'
                    WHEN success = 0 THEN 'error'
                END
                WHERE status IS NULL OR status = ''");
        }

        // Mergeusers savepoint reached.
        upgrade_plugin_savepoint(true, 2026060101, 'tool', 'mergeusers');
    }

    if ($oldversion < 2026060102) {
        $table = new xmldb_table('tool_mergeusers');

        // Drop indexes related to success field.
        $index = new xmldb_index('mdl_toolmerg_tfs_ix', XMLDB_INDEX_NOTUNIQUE, ['touserid', 'fromuserid', 'success']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $index = new xmldb_index('mdl_toolmerg_suc_ix', XMLDB_INDEX_NOTUNIQUE, ['success']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Drop success field.
        $field = new xmldb_field('success');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Mergeusers savepoint reached.
        upgrade_plugin_savepoint(true, 2026060102, 'tool', 'mergeusers');
    }

    if ($oldversion < 2026061000) {
        // Define field timecreated to be added to tool_mergeusers.
        $table = new xmldb_table('tool_mergeusers');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'mergedbyuserid');

        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // For existing records, set timecreated to timemodified if not already set.
        $DB->execute("UPDATE {tool_mergeusers} SET timecreated = timemodified WHERE timecreated IS NULL OR timecreated = 0");

        // Mergeusers savepoint reached.
        upgrade_plugin_savepoint(true, 2026061000, 'tool', 'mergeusers');
    }

    if ($oldversion < 2026080100) {
        // Normalize user_snapshots on every row to a consistent shape: this backfills
        // it for logs that never captured one (pre-dating the feature entirely), and
        // fixes the ambiguous bare null some already-captured rows can have for a
        // side whose id was either never a real user id or no longer resolvable.
        tool_mergeusers_normalize_user_snapshots();

        // Mergeusers savepoint reached.
        upgrade_plugin_savepoint(true, 2026080100, 'tool', 'mergeusers');
    }

    return true;
}

/**
 * Normalizes the user_snapshots value stored in every {tool_mergeusers} row's log
 * column, so that both sides (to_user/from_user) always have explicit notfound/
 * recoverable flags and the whole structure carries a shared capture timemodified.
 * Rows already in the normalized shape are left untouched.
 *
 * @return void
 */
function tool_mergeusers_normalize_user_snapshots(): void {
    global $DB;

    // Process-local cache of {user} lookups, keyed by user id, so the same id
    // referenced by many merge logs is only ever queried once (single pass over
    // the recordset, O(N) queries at most on distinct user ids, not on rows).
    $usercache = [];
    $userfields = 'id, username, email, firstname, lastname, idnumber, suspended, deleted';

    $rows = $DB->get_recordset('tool_mergeusers');
    foreach ($rows as $row) {
        $logdata = json_decode($row->log, true);
        if (!is_array($logdata)) {
            continue;
        }

        $snapshots = $logdata['user_snapshots'] ?? null;
        if (tool_mergeusers_snapshot_is_normalized($snapshots)) {
            continue;
        }

        $existingto = is_array($snapshots) ? ($snapshots['to_user'] ?? null) : null;
        $existingfrom = is_array($snapshots) ? ($snapshots['from_user'] ?? null) : null;

        $logdata['user_snapshots'] = [
            'timemodified' => time(),
            'to_user' => tool_mergeusers_normalize_user_snapshot_side((int) $row->touserid, $existingto, $usercache, $userfields),
            'from_user' => tool_mergeusers_normalize_user_snapshot_side(
                (int) $row->fromuserid,
                $existingfrom,
                $usercache,
                $userfields,
            ),
        ];

        $record = new stdClass();
        $record->id = $row->id;
        $record->log = json_encode($logdata);
        $DB->update_record('tool_mergeusers', $record);
    }
    $rows->close();
}

/**
 * Checks whether a decoded user_snapshots value already has the normalized shape.
 *
 * @param mixed $snapshots
 * @return bool
 */
function tool_mergeusers_snapshot_is_normalized($snapshots): bool {
    if (!is_array($snapshots) || !isset($snapshots['timemodified'])) {
        return false;
    }
    foreach (['to_user', 'from_user'] as $side) {
        if (
            !is_array($snapshots[$side] ?? null)
            || !array_key_exists('notfound', $snapshots[$side])
            || !array_key_exists('recoverable', $snapshots[$side])
            || !array_key_exists('erasedforgdpr', $snapshots[$side])
            || !array_key_exists('timeerased', $snapshots[$side])
        ) {
            return false;
        }
    }
    return true;
}

/**
 * Normalizes a single side (to_user/from_user) of a user_snapshots value.
 *
 * @param int $userid the raw touserid/fromuserid column value.
 * @param mixed $existing the previously captured side data, if any (old shape).
 * @param array $usercache process-local cache of {user} lookups, keyed by user id.
 * @param string $userfields fields to select when a live lookup is needed.
 * @return array
 */
function tool_mergeusers_normalize_user_snapshot_side(int $userid, $existing, array &$usercache, string $userfields): array {
    global $DB;

    // A previously captured snapshot with real data always wins (decision: the
    // snapshot has audit value as of merge time, even if the live row has since
    // changed or disappeared). Older-shape data never had erasedforgdpr/timeerased,
    // so default those in without overriding them if already present.
    if (is_array($existing) && !empty($existing['id'])) {
        $normalized = array_merge(
            ['notfound' => false, 'recoverable' => true, 'erasedforgdpr' => false, 'timeerased' => null],
            $existing,
        );
        // Older-shape data may have stored id as a numeric string, depending on
        // the DB driver; force it to int so strict comparisons elsewhere never
        // silently fail to match it.
        $normalized['id'] = (int) $normalized['id'];

        return $normalized;
    }

    if ($userid <= 0) {
        return (array) \tool_mergeusers\local\logger::notfound_snapshot();
    }

    if (!array_key_exists($userid, $usercache)) {
        $usercache[$userid] = $DB->get_record('user', ['id' => $userid], $userfields);
    }

    $user = $usercache[$userid];
    if (!$user) {
        return (array) \tool_mergeusers\local\logger::unrecoverable_snapshot($userid);
    }

    return (array) \tool_mergeusers\local\logger::snapshot_from_user($user);
}

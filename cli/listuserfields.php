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
 * List potential user-related fields from Moodle database.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 Universitat Rovira i Virgili
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define("CLI_SCRIPT", true);

require_once(__DIR__ . '/../../../../config.php');

ini_set('display_errors', true);
ini_set('error_reporting', E_ALL | E_STRICT);

global $CFG, $DB;
require_once($CFG->libdir . '/clilib.php');


cli_heading('List of Moodle database tables and potential %user%-related columns');
$tables = $DB->get_tables(false);

cli_writeln(sprintf('Processing %d tables and loading their XML schema...', count($tables)));
$schema = $DB->get_manager()->get_install_xml_schema();
$matching = [];
$matchingcount = [];
$matchingbykeys = [];
$nonmatching = [];
$alluserrelatedcolumns = [];
$alluserrelatedcolumnswithtable = [];
$omittedtables = [];
foreach ($tables as $table) {
    $columns = $DB->get_columns($table, false);
    $schematable = $schema->getTable($table);
    if (!$schematable) {
        $omittedtables[$table] = $table;
        continue;
    }
    $tablekeys = $schema->getTable($table)->getKeys();
    $userrelatedbykeys = array_filter(
        array_map(
            /** @param xmldb_key $key */
            function ($key) {
                $keyfields = implode(',', $key->getRefFields());
                if ($key->getRefTable() == 'user' && $keyfields == 'id') {
                    return implode(',', $key->getFields());
                }
                return null;
            },
            $tablekeys,
        ),
    );
    $userrelatedbykeys = array_flip($userrelatedbykeys);
    $userrelatedcolumns = array_filter(
        $columns,
        function ($column) use ($table, $userrelatedbykeys) {
            return (strstr($column->name, 'user') && $column->meta_type == 'I') ||
                isset($userrelatedbykeys[$column->name]);
        }
    );
    if (count($userrelatedcolumns) <= 0) {
        $nonmatching[$table] = $table;
        continue;
    }
    $userrelatedcolumns = array_map(
        function ($column) {
            return $column->name;
        },
        $userrelatedcolumns,
    );
    sort($userrelatedcolumns);
    $matching[$table] = $userrelatedcolumns;
    $userrelatedbykeys = array_keys($userrelatedbykeys);
    sort($userrelatedbykeys);
    $matchingbykeys[$table] = $userrelatedbykeys;
    $matchingcount[$table] = count($userrelatedcolumns);
    foreach ($userrelatedcolumns as $column) {
        if (!isset($alluserrelatedcolumns[$column])) {
            $alluserrelatedcolumns[$column] = 0;
        }
        $alluserrelatedcolumns[$column]++;
        $alluserrelatedcolumnswithtable[$column][$table] = $table;
    }
}
ksort($matchingcount);
sort($nonmatching);
ksort($alluserrelatedcolumns);
cli_writeln('... done!');
$log = new text_progress_trace();
$log->output('Tables without potential %user%-related fields:', 1);
foreach ($nonmatching as $table) {
    $log->output($table, 2);
}
$log->output('Tables with potential %user%-related fields:', 1);
$log->output(
    'NOTE: All tables with non-default user-related field names must appear into ' .
    '"userfieldnames" config.php setting.',
    2,
);
$log->output(
    'FORMAT: {number of user-related fields}: \'{table name}\' => [{list of fields}] ' .
    '// {list of fields that appear as foreign key to user.id on the XML definition.}',
    2,
);
arsort($matchingcount);
foreach ($matchingcount as $table => $numberofcolumns) {
    $log->output(
        sprintf(
            "%d: '%s' => ['%s'], // %s",
            $numberofcolumns,
            $table,
            implode("', '", $matching[$table]),
            implode(", ", $matchingbykeys[$table]),
        ),
        2,
    );
}
$log->output('List of user-related column names and number of appearances:', 1);
arsort($alluserrelatedcolumns);
foreach ($alluserrelatedcolumns as $column => $appearances) {
    $log->output(
        sprintf(
            '%d: %s: %s',
            $appearances,
            $column,
            implode(',', $alluserrelatedcolumnswithtable[$column]),
        ),
        2,
    );
}
if (count($omittedtables) <= 0) {
    $log->output('INFO: There were no omitted tables: your database tables and XML definition are consistent.', 1);
} else {
    $log->output(sprintf('INFO: Omitted tables (no XML definition found for %d tables):', count($omittedtables)), 1);
    foreach ($omittedtables as $table) {
        $log->output($table, 2);
    }
    $log->output('');
    $log->output('Omitted tables can come for several reasons and they talk about your Moodle and database consistency:', 2);
    $log->output('1. Uninstalled plugins, with kept database tables.', 3);
    $log->output('2. Plugins without a proper install.xml definition.', 3);
    $log->output('3. Manually created database tables, or other reasons.', 3);
    $log->output('Recommendation: Double check these omitted tables to be sure they should exist and take the action ' .
        'that fits better.', 1);
}
$log->finished();
cli_writeln('End!');

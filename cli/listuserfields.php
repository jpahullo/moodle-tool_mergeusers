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

require_once $CFG->dirroot . '/lib/clilib.php';

cli_heading('List of Moodle database tables and potential %user%-related columns');
$tables = $DB->get_tables(false);
cli_writeln(sprintf('Processing %d tables...', count($tables)));
$matching = [];
$matchingcount = [];
$nonmatching = [];
$alluserrelatedcolumns = [];
$alluserrelatedcolumnswithtable = [];
foreach ($tables as $table) {
    $columns = $DB->get_columns($table, false);
    $userrelatedcolumns = array_filter(
        $columns,
        function ($column) use ($table) {
            return (strstr($column->name, 'user') && $column->meta_type == 'I')||
                ($table == 'user' && $column == 'id');
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
    $matching[$table] = $userrelatedcolumns;
    $matchingcount[$table] = count($userrelatedcolumns);
    foreach ($userrelatedcolumns as $column) {
        if (!isset($alluserrelatedcolumns[$column])) {
            $alluserrelatedcolumns[$column] = 0;
        }
        $alluserrelatedcolumns[$column]++;
        $alluserrelatedcolumnswithtable[$column][$table] = $table;
    }
}
cli_writeln('... done!');
$log = new text_progress_trace();
$log->output('Tables without potential %user%-related fields:', 1);
foreach ($matching as $table => $columns) {
    $log->output($table, 2);
}
$log->output('Tables with potential %user%-related fields:', 1);
$log->output('NOTE: All tables that has more than one user-related column should appear on "userfieldnames" config setting.', 2);
arsort($matchingcount);
foreach ($matchingcount as $table => $numberofcolumns) {
    $log->output(
        sprintf(
            "%d: '%s' => ['%s'],",
            $numberofcolumns,
            $table,
            implode("', '", $matching[$table]),
        ),
        2,
    );
}
$log->output('List of user-related column names and number of appearances:', 1);
arsort($alluserrelatedcolumns);
foreach ($alluserrelatedcolumns as $column => $appearances) {
    $log->output(sprintf('%s: %d: %s', $column, $appearances, implode(',', $alluserrelatedcolumnswithtable[$column])), 2);
}
$log->finished();
cli_writeln('End!');

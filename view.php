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
 * View merging logs.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mergeusers\local\logger;
use tool_mergeusers\output\renderer;

require('../../../config.php');

global $CFG, $PAGE;

// Report all PHP errors.
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('tool/mergeusers:mergeusers', context_system::instance());

admin_externalpage_setup('tool_mergeusers_viewlog');

$logger = new logger();

$export = optional_param('export', 0, PARAM_BOOL);
$search = optional_param('search', '', PARAM_RAW_TRIMMED);
$page = max(0, optional_param('page', 0, PARAM_INT));
$searchterm = ($search !== '') ? $search : null;

if ($export) {
    require_once($CFG->dirroot . '/lib/csvlib.class.php');
    $csv = new csv_export_writer();
    $logs = $logger->search($searchterm, 0, 0);
    $headings = [
        'id',
        'touserid',
        'to',
        'fromuserid',
        'from',
        'mergedbyuserid',
        'mergedby',
        'status',
        'timecreated',
        'timemodified',
    ];
    $csv->add_data($headings);
    foreach ($logs as $log) {
        $exportlog = [
            $log->id,
            $log->touserid,
            fullname($log->to),
            $log->fromuserid,
            fullname($log->from),
            $log->mergedbyuserid,
            ($log->mergedby) ? fullname($log->mergedby) : null,
            $log->status,
            ($log->timecreated) ? userdate($log->timecreated) : '',
            userdate($log->timemodified),
        ];
        $csv->add_data($exportlog);
    }

    $csv->download_file();
}

$requestedperpage = optional_param('perpage', 0, PARAM_INT);
if ($requestedperpage > 0) {
    // Also covers the "show all" toggle link, capped so a manually-crafted URL
    // can't force rendering an unbounded number of rows at once.
    $perpage = min($requestedperpage, renderer::SHOW_ALL_PAGE_SIZE);
} else {
    $perpage = (int) get_config('tool_mergeusers', 'logpagesize');
    if ($perpage < 1) {
        $perpage = 100; // Defensive fallback: the setting has not been saved yet on this site.
    }
}

$totalcount = $logger->count_search($searchterm);
$limitfrom = $page * $perpage;
if ($totalcount > 0 && $limitfrom >= $totalcount) {
    // Stale bookmark, or a search that shrank the result set below the requested
    // page: fall back to the actual last page instead of showing an empty table.
    $page = (int) floor(($totalcount - 1) / $perpage);
    $limitfrom = $page * $perpage;
}
$logs = $logger->search($searchterm, $limitfrom, $perpage);

$baseurl = new moodle_url('/admin/tool/mergeusers/view.php', ($searchterm !== null) ? ['search' => $searchterm] : []);

// phpcs:disable
/**
 * @var renderer $renderer
 */
$renderer = $PAGE->get_renderer('tool_mergeusers');
// phpcs:enable

echo $renderer->logs_page($logs, $totalcount, $page, $perpage, $baseurl, $searchterm);

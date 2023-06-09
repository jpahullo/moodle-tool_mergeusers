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
/*
* This file contains a merge request for a queue and.
* A function returning the list of the current queue of the merge requests.
*
* @package    merge_users
* @copyright  2023 Liguria Digitale www.liguriadigitale.it
* @author     Nicola Vallinoto <n.vallinoto@liguriadigitale.it>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$functions = [
    // The name of your web service function, as discussed above.
    'tool_mergeusers_enqueue_merge_request' => [
        'classname'   => 'tool_mergeusers\external\enqueue_merge_request',
        'methodname' => 'execute',
        'description' => 'Enqueue merge request',
        'type'        => 'write',
        'ajax'        => true,
        ],
    'tool_mergeusers_get_data_merge_requests' => [
        'classname' => 'tool_mergeusers\external\get_data_merge_requests',
        'methodname' => 'execute',
        'description' => 'Get data of merge requests.',
        'type'        => 'read',
        'ajax'        => true,
    ]
];

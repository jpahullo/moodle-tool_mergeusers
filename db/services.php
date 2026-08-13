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
 * List of Web Services for the tool_mergeusers plugin.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_mergeusers_enqueue_merge_request' => [
        'classname'    => 'tool_mergeusers\external\enqueue_merge_request',
        'methodname'   => 'execute',
        'description'  => 'Queue a user merge request, or rename the user to remove when the user to keep does not exist yet.',
        'type'         => 'write',
        'capabilities' => 'tool/mergeusers:mergeusers',
        'ajax'         => false,
    ],
    'tool_mergeusers_get_merge_request_status' => [
        'classname'    => 'tool_mergeusers\external\get_merge_request_status',
        'methodname'   => 'execute',
        'description'  => 'Get one merge request by log id, or a filtered/paginated list of them.',
        'type'         => 'read',
        'capabilities' => 'tool/mergeusers:viewlog',
        'ajax'         => false,
    ],
];

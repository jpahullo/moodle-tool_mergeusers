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
* This file contains a merging request for a queue and. 
* A function returning the list of the current queue of the merging requests.
*
* @package    merge_users
* @copyright  2023 Liguria Digitale www.liguriadigitale.it
* @author     Nicola Vallinoto <n.vallinoto@liguriadigitale.it>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$functions = [
    // The name of your web service function, as discussed above.
    'tool_mergeusers_enqueue_merging_request' => [
        // The name of the namespaced class that the function is located in.
        //'classname'   => 'tool_mergeusers\external\queue_merging_request',
        'classname' => 'tool_mergeusers_external', 
	    // A brief, human-readable, description of the web service function.
        //'methodname' => 'execute',
	    'methodname' => 'enqueue_merging_request',
        'description' => 'Enqueue merging request',
        // Options include read, and write.
        'type'        => 'write',
        // Whether the service is available for use in AJAX calls from the web.
        'ajax'        => true,
        // An optional list of services where the function will be included.
        //'services' => [
            // A standard Moodle install includes one default service:
            // - MOODLE_OFFICIAL_MOBILE_SERVICE.
            // Specifying this service means that your function will be available for.
            // Use in the Moodle Mobile App.
        //    MOODLE_OFFICIAL_MOBILE_SERVICE,
        //]
        ],
    'tool_mergeusers_get_data_merging_requests' => [
        // The name of the namespaced class that the function is located in.
        'classname' => 'tool_mergeusers_external',
        'methodname' => 'get_data_merging_requests',
        // A brief, human-readable, description of the web service function.
        'description' => 'Get data of merging requests.',
        // Options include read, and write.
        'type'        => 'read',
        // Whether the service is available for use in AJAX calls from the web.
        'ajax'        => true,
    ]
];

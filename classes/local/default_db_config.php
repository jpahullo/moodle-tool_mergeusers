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
 * Default database-related configuration.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local;

use tool_mergeusers\local\cli\cli_gathering;
use tool_mergeusers\local\merger\assign_submission_table_merger;
use tool_mergeusers\local\merger\generic_table_merger;
use tool_mergeusers\local\merger\quiz_attempts_table_merger;

/**
 * Default database-related configuration.
 *
 * It contains what config/config.php provided before.
 * These settings are, beforehand, sufficient for a normal operation of the merge users tool.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_db_config {
    /** @var string[] Default database-related settings from this plugin. */
    public static array $config = [
        // The gathering tool.
        'gathering' => cli_gathering::class,

        // Database tables to be excluded from normal processing.
        // You normally will add tables. Be very cautious if you delete any of them.
        'exceptions' => [
            'user_preferences',
            'user_private_key',
            'user_info_data',
            'my_pages',
        ],

        // List of compound indexes.
        //
        // This list may vary from Moodle instance to another, given that the Moodle version,
        // local changes and non-core plugins may add new special cases to be processed.
        // Place in 'userfield' all column names related to a user (i.e., user.id).
        // Place all the rest column names into 'otherfields'. It may be empty.
        // Table names must be without $CFG->prefix.
        'compoundindexes' => [
            'grade_grades' => [
                'userfield' => ['userid'],
                'otherfields' => ['itemid'],
            ],
            'groups_members' => [
                'userfield' => ['userid'],
                'otherfields' => ['groupid'],
            ],
            'journal_entries' => [
                'userfield' => ['userid'],
                'otherfields' => ['journal'],
            ],
            'course_completions' => [
                'userfield' => ['userid'],
                'otherfields' => ['course'],
            ],
            'message_contacts' => [ // Both fields are user.id values.
                'userfield' => ['userid', 'contactid'],
                'otherfields' => [],
            ],
            'role_assignments' => [
                'userfield' => ['userid'],
                'otherfields' => ['contextid', 'roleid'], // From mdl_roleassi_useconrol_ix (not unique).
            ],
            'user_lastaccess' => [
                'userfield' => ['userid'],
                'otherfields' => ['courseid'], // From mdl_userlast_usecou_ui (unique).
            ],
            'quiz_attempts' => [
                'userfield' => ['userid'],
                'otherfields' => ['quiz', 'attempt'], // From mdl_quizatte_quiuseatt_uix (unique).
            ],
            'cohort_members' => [
                'userfield' => ['userid'],
                'otherfields' => ['cohortid'],
            ],
            'certif_completion' => [  // From mdl_certcomp_ceruse_uix (unique).
                'userfield' => ['userid'],
                'otherfields' => ['certifid'],
            ],
            'course_modules_completion' => [ // From mdl_courmoducomp_usecou_uix (unique).
                'userfield' => ['userid'],
                'otherfields' => ['coursemoduleid'],
            ],
            'scorm_scoes_track' => [ // From mdl_scorscoetrac_usescosco_uix (unique).
                'userfield' => ['userid'],
                'otherfields' => ['scormid', 'scoid', 'attempt', 'element'],
            ],
            'assign_grades' => [ // From mdl_assigrad_assuseatt_uix (unique).
                'userfield' => ['userid'],
                'otherfields' => ['assignment', 'attemptnumber'],
            ],
            'badge_issued' => [ // From mdl_badgissu_baduse_uix (unique).
                'userfield' => ['userid'],
                'otherfields' => ['badgeid'],
            ],
            'assign_submission' => [ // From mdl_assisubm_assusegroatt_uix (unique).
                'userfield' => ['userid'],
                'otherfields' => ['assignment', 'groupid', 'attemptnumber'],
            ],
            'wiki_pages' => [ // From mdl_wikipage_subtituse_uix (unique).
                'userfield' => ['userid'],
                'otherfields' => ['subwikiid', 'title'],
            ],
            'wiki_subwikis' => [ // From mdl_wikisubw_wikgrouse_uix (unique).
                'userfield' => ['userid'],
                'otherfields' => ['wikiid', 'groupid'],
            ],
            'user_enrolments' => [
                'userfield' => ['userid'],
                'otherfields' => ['enrolid'],
            ],
            'assign_user_flags' => [ // They are actually a unique key, but not in DDL.
                'userfield' => ['userid'],
                'otherfields' => ['assignment'],
            ],
            'assign_user_mapping' => [ // They are actually a unique key, but not in DDL.
                'userfield' => ['userid'],
                'otherfields' => ['assignment'],
            ],
            'customcert_issues' => [
                'userfield' => ['userid'],
                'otherfields' => ['customcertid'],
            ],
        ],

        // List of column names per table, where columns' content is related to user.id.
        // These are necessary for matching passed by userids in these column names.
        // In other words, only column names given below will be searching for matching user ids.
        // The key 'default' will be applied for any non-matching table name.
        // You can use the cli/listuserfields.php CLI script to help detect other cases for your Moodle instance.
        'userfieldnames' => [
            'badge_manual_award' => ['issuerid', 'recipientid'],
            'competency_evidence' => ['actionuserid', 'usermodified'],
            'external_tokens' => ['creatorid', 'userid'],
            'grade_import_values' => ['importer', 'userid'],
            'grade_import_newitem' => ['importer'],
            'grading_instances' => ['raterid'],
            'logstore_standard_log' => ['userid', 'relateduserid', 'realuserid'],
            'message_contacts' => ['contactid', 'userid'],
            'message_contact_requests' => ['userid', 'requesteduserid'],
            'message_users_blocked' => ['blockeduserid', 'userid'],
            'question' => ['createdby', 'modifiedby'],
            'reportbuilder_schedule' => ['usercreated', 'usermodified', 'userviewas'],
            'role_capabilities' => ['modifierid'],
            'search_simpledb_index' => ['owneruserid', 'userid'],
            'sms_messages' => ['recipientuserid'],
            'tool_mergeusers' => ['mergedbyuserid'], // Only this column. Others must be kept as is.
            'tool_dataprivacy_request' => ['dp4o', 'requestedby', 'userid', 'usermodified'],
            'user_enrolments' => ['modifierid', 'userid'],
            'workshop_assessments' => ['gradinggradeoverby', 'reviewerid'],
            'workshop_submissions' => ['authorid', 'gradeoverby'],
            'default' => [
                'authorid',
                'id_user',
                'loggeduser',
                'reviewerid',
                'user',
                'user_id',
                'usercreated',
                'userid',
                'useridfrom',
                'useridto',
                'usermodified',
            ],
        ],

        // The table_mergers to process each database table.
        // The 'default' is applied when no specific table_merger is specified.
        'tablemergers' => [
            'default' => generic_table_merger::class,
            'quiz_attempts' => quiz_attempts_table_merger::class,
            'assign_submission' => assign_submission_table_merger::class,
        ],

        'alwaysrollback' => false,
        'debugdb' => false,
    ];
}

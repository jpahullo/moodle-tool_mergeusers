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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die();

/**
 * This is the default settings for the correct behaviour of the plugin, given the knowledge base
 * of our experience.
 *
 * Your local Moodle instance may need additional adjusts. Please, do not modify this file.
 * Instead, create or edit in the same directory than this "config.php" a file named
 * "config.local.php" to add/replace elements of the default configuration.
 */
return array(

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
    // Put in 'userfield' all column names related to a user (i.e., user.id), and all the rest column names
    // into 'otherfields'.
    // See README.txt for details on special cases.
    // Table names must be without $CFG->prefix.
    'compoundindexes' => array(
        'grade_grades' => array(
            'userfield' => array('userid'),
            'otherfields' => array('itemid'),
        ),
        'groups_members' => array(
            'userfield' => array('userid'),
            'otherfields' => array('groupid'),
        ),
        'journal_entries' => array(
            'userfield' => array('userid'),
            'otherfields' => array('journal'),
        ),
        'course_completions' => array(
            'userfield' => array('userid'),
            'otherfields' => array('course'),
        ),
        'message_contacts' => array(//both fields are user.id values
            'userfield' => array('userid', 'contactid'),
            'otherfields' => array(),
        ),
        'role_assignments' => array(
            'userfield' => array('userid'),
            'otherfields' => array('contextid', 'roleid'), // mdl_roleassi_useconrol_ix (not unique)
        ),
        'user_lastaccess' => array(
            'userfield' => array('userid'),
            'otherfields' => array('courseid'), // mdl_userlast_usecou_ui (unique)
        ),
        'quiz_attempts' => array(
            'userfield' => array('userid'),
            'otherfields' => array('quiz', 'attempt'), // mdl_quizatte_quiuseatt_uix (unique)
        ),
        'cohort_members' => array(
            'userfield' => array('userid'),
            'otherfields' => array('cohortid'),
        ),
        'certif_completion' => array(  // mdl_certcomp_ceruse_uix (unique)
            'userfield' => array('userid'),
            'otherfields' => array('certifid'),
        ),
        'course_modules_completion' => array( // mdl_courmoducomp_usecou_uix (unique)
            'userfield' => array('userid'),
            'otherfields' => array('coursemoduleid'),
        ),
        'scorm_scoes_track' => array( //mdl_scorscoetrac_usescosco_uix (unique)
            'userfield' => array('userid'),
            'otherfields' => array('scormid', 'scoid', 'attempt', 'element'),
        ),
        'assign_grades' => array( //UNIQUE KEY mdl_assigrad_assuseatt_uix
            'userfield' => array('userid'),
            'otherfields' => array('assignment', 'attemptnumber'),
        ),
        'badge_issued' => array( // unique key mdl_badgissu_baduse_uix
            'userfield' => array('userid'),
            'otherfields' => array('badgeid'),
        ),
       'assign_submission' => array( // unique key mdl_assisubm_assusegroatt_uix
            'userfield' => array('userid'),
            'otherfields' => array('assignment', 'groupid', 'attemptnumber'),
        ),
        'wiki_pages' => array( //unique key mdl_wikipage_subtituse_uix
            'userfield' => array('userid'),
            'otherfields' => array('subwikiid', 'title'),
        ),
        'wiki_subwikis' => array( //unique key mdl_wikisubw_wikgrouse_uix
            'userfield' => array('userid'),
            'otherfields' => array('wikiid', 'groupid'),
        ),
        'user_enrolments' => array(
            'userfield' => array('userid'),
            'otherfields' => array('enrolid'),
        ),
        'assign_user_flags' => array( // They are actually a unique key, but not in DDL.
            'userfield' => array('userid'),
            'otherfields' => array('assignment'),
        ),
        'assign_user_mapping' => array( // They are actually a unique key, but not in DDL.
            'userfield' => array('userid'),
            'otherfields' => array('assignment'),
        ),
        'customcert_issues' => array(
            'userfield' => array('userid'),
            'otherfields' => array('customcertid'),
        ),
    ),

    // List of column names per table, where their content is a user.id.
    // These are necessary for matching passed by userids in these column names.
    // In other words, only column names given below will be searching for matching user ids.
    // The key 'default' will be applied for any non-matching table name.
    'userfieldnames' => [
        'badge_manual_award' => ['issuerid', 'recipientid'],
        'competency_evidence' => ['actionuserid', 'usermodified'],
        'external_tokens' => ['creatorid', 'userid'],
        'grade_import_values' => ['importer', 'userid'],
        'grade_import_newitem' => ['importer'],
        'grading_instances' => ['raterid'],
        'logstore_standard_log' => ['userid','relateduserid','realuserid'],
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

    // TableMergers to process each database table.
    // 'default' is applied when no specific TableMerger is specified.
    'tablemergers' => array(
        'default' => 'GenericTableMerger',
        'quiz_attempts' => 'QuizAttemptsMerger',
        'assign_submission' => 'AssignSubmissionTableMerger',
    ),

    'alwaysRollback' => false,
    'debugdb' => false,
);

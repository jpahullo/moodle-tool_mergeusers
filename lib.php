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
 * mergeusers functions.
 *
 * @package    tool_mergeusers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Gets whether database transactions are allowed.
 * @global moodle_database $DB
 * @return bool true if transactions are allowed. false otherwise.
 */
function tool_mergeusers_transactionssupported() {
    global $DB;

    // Tricky way of getting real transactions support, without re-programming it.
    // May be in the future, as phpdoc shows, this method will be publicly accessible.
    $method = new ReflectionMethod($DB, 'transactions_supported');
    $method->setAccessible(true); //method is protected; make it accessible.
    return $method->invoke($DB);
}

function tool_mergeusers_build_exceptions_options() {
    require_once(__DIR__ . '/classes/tool_mergeusers_config.php');

    $config = tool_mergeusers_config::instance();
    $none = get_string('none');
    $options = array('none' => $none);
    foreach ($config->exceptions as $exception) {
        $options[$exception] = $exception;
    }
    unset($options['my_pages']); //duplicated records make MyMoodle does not work.

    $result = new stdClass();
    $result->defaultkey = 'none';
    $result->defaultvalue = $none;
    $result->options = $options;

    return $result;
}

function tool_mergeusers_build_quiz_options() {
    require_once(__DIR__ . '/lib/table/quizattemptsmerger.php');

    // quiz attempts
    $quizStrings = new stdClass();
    $quizStrings->{QuizAttemptsMerger::ACTION_RENUMBER} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_RENUMBER, 'tool_mergeusers');
    $quizStrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE, 'tool_mergeusers');
    $quizStrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET, 'tool_mergeusers');
    $quizStrings->{QuizAttemptsMerger::ACTION_REMAIN} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_REMAIN, 'tool_mergeusers');

    $quizOptions = array(
        QuizAttemptsMerger::ACTION_RENUMBER => $quizStrings->{QuizAttemptsMerger::ACTION_RENUMBER},
        QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE => $quizStrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE},
        QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET => $quizStrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET},
        QuizAttemptsMerger::ACTION_REMAIN => $quizStrings->{QuizAttemptsMerger::ACTION_REMAIN},
    );

    $result = new stdClass();
    $result->allstrings = $quizStrings;
    $result->defaultkey = QuizAttemptsMerger::ACTION_REMAIN;
    $result->options = $quizOptions;

    return $result;
}

/**
 * Create user profile fields for user metadata once users have been merged.
 * @return void
 */
function tool_mergeusers_create_user_profile_fields(): void {
    global $CFG, $DB;

    require_once $CFG->dirroot . '/user/profile/lib.php';
    require_once $CFG->dirroot . '/user/profile/definelib.php';

    // Create user profile field category
    $category_name = 'Merge User Info';
    $category = $DB->get_record('user_info_category', ['name' => $category_name]);

    if (empty($category)) {
        $category = (object)[
            'name' => $category_name,
        ];
        profile_save_category($category);
    }


    // Skip if category is not found
    if (!isset($category->id)) {
        return;
    }

    $fields = [
        'merge_date' => [
            'name' => 'Merge Date',
            'shortname' => 'merge_date',
            'datatype' => 'datetime',
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'categoryid' => $category->id,
            'required' => false,
            'locked' => false,
            'visible' => false,
            'forceunique' => false,
            'signup' => false,
            'defaultdata' => 0,
            'defaultdataformat' => '0',
            'param1' => '2024',
            'param2' => '2024',
        ],
        'merge_info' => [
            'name' => 'Merge Info',
            'shortname' => 'merge_info',
            'datatype' => 'text',
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'categoryid' => $category->id,
            'required' => false,
            'locked' => false,
            'visible' => false,
            'forceunique' => false,
            'signup' => false,
            'defaultdata' => '',
            'defaultdataformat' => '0',
            'param1' => '30',
            'param2' => '2048',
            'param3' => '0',
        ],
    ];

    // Create custom fields if they do not exist
    foreach ($fields as $field_info) {
        $record = (object)$field_info;

        $define_field = new profile_define_base();
        $define_field->define_save($record);
    }
}

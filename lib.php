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
    $method->setAccessible(true); // method is protected; make it accessible.
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
    unset($options['my_pages']); // Duplicated records make MyMoodle does not work.

    $result = new stdClass();
    $result->defaultkey = 'none';
    $result->defaultvalue = $none;
    $result->options = $options;

    return $result;
}

function tool_mergeusers_build_quiz_options() {
    require_once(__DIR__ . '/lib/table/quizattemptsmerger.php');

    // quiz attempts.
    $quizstrings = new stdClass();
    $quizstrings->{QuizAttemptsMerger::ACTION_RENUMBER} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_RENUMBER, 'tool_mergeusers');
    $quizstrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE, 'tool_mergeusers');
    $quizstrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET, 'tool_mergeusers');
    $quizstrings->{QuizAttemptsMerger::ACTION_REMAIN} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_REMAIN, 'tool_mergeusers');

    $quizoptions = array(
        QuizAttemptsMerger::ACTION_RENUMBER => $quizstrings->{QuizAttemptsMerger::ACTION_RENUMBER},
        QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE => $quizstrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE},
        QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET => $quizstrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET},
        QuizAttemptsMerger::ACTION_REMAIN => $quizstrings->{QuizAttemptsMerger::ACTION_REMAIN},
    );

    $result = new stdClass();
    $result->allstrings = $quizstrings;
    $result->defaultkey = QuizAttemptsMerger::ACTION_REMAIN;
    $result->options = $quizoptions;

    return $result;
}

function tool_mergeusers_build_profilefields_options() {
    global $CFG;
    require_once($CFG->dirroot.'/user/filters/profilefield.php');
    $userprofile = new user_filter_profilefield('profile', get_string('profilefields', 'admin'), false);
    $profiefields = $userprofile->get_profile_fields();

    $none = get_string('none');
    $options = array(-1 => $none);
    if (array_diff_key($profiefields, array(0 => 'any field'))) {

        foreach ($profiefields as $fieldid => $fieldname) {
            $options[$fieldid] = $fieldname;
        }
    }
    $result = new stdClass();
    $result->defaultkey = -1;
    $result->defaultvalue = $none;
    $result->options = $options;

    return $result;
}

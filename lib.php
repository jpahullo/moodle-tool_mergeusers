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

use tool_mergeusers\logger;

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

/**
 * Builds the quiz attempts options for the plugin settings.
 *
 * @return stdClass instance with the options and defaultkey to be used.
 * @throws coding_exception
 */
function tool_mergeusers_build_quiz_options() {
    require_once(__DIR__ . '/lib/table/quizattemptsmerger.php');

    // Quiz attempts.
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
    $result->defaultkey = QuizAttemptsMerger::ACTION_RENUMBER;
    $result->options = $quizOptions;

    return $result;
}

/**
 * Profile callback to add merging data to a users profile.
 *
 * @param tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param null|stdClass $course Course object
 */
function tool_mergeusers_myprofile_navigation($tree, $user, $iscurrentuser, $course) {
    global $PAGE;

    if (has_capability('tool/mergeusers:mergeusers', context_system::instance())) {
        $renderer = $PAGE->get_renderer('tool_mergeusers');
        $logger = new logger();

        // Find last merge to/from this profile.
        $lastmergetome = current($logger->get(['touserid' => $user->id], 0, 1)) ?: null;
        $lastmergefromme = current($logger->get(['fromuserid' => $user->id], 0, 1)) ?: null;

        // Display last merge.
        $category = new core_user\output\myprofile\category('tool_mergeusers_info', get_string('pluginname', 'tool_mergeusers'));
        $tree->add_category($category);
        $node = new core_user\output\myprofile\node('tool_mergeusers_info', 'olduser',
            get_string('lastmerge', 'tool_mergeusers'), null, null,
            $renderer->get_merge_detail($lastmergetome, $lastmergefromme));
        $category->add_node($node);
    }
}

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

use tool_mergeusers\local\config;

/**
 * Gets whether database transactions are allowed.
 *
 * @return bool true if transactions are allowed. false otherwise.
 * @throws ReflectionException
 */
function tool_mergeusers_transactionssupported(): bool {
    global $DB;

    // Tricky way of getting real transactions support, without re-programming it.
    // May be in the future, as phpdoc shows, this method will be publicly accessible.
    $method = new ReflectionMethod($DB, 'transactions_supported');
    $method->setAccessible(true); //method is protected; make it accessible.
    return $method->invoke($DB);
}

/**
 * Builds the form options for table exception from processing.
 *
 * @return stdClass instance with attributes for defining exception options.
 * @throws coding_exception
 */
function tool_mergeusers_build_exceptions_options(): stdClass {
    $config = config::instance();
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
function tool_mergeusers_build_quiz_options(): stdClass {
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
 * Informs whether there exist yet prior user profile fields from this plugin.
 *
 * In prior versions we added custom user profile fields to inform about
 * last merges related to the user on its profile.
 *
 * With this function we inform whether there are yet some of the custom
 * user profile fields and informs the administrator that they are no longer used,
 * and they can be securely deleted.
 *
 * We do not delete them on an upgrade to let administrators adapt to the new
 * way of proceeding.
 *
 * @throws dml_exception
 */
function tool_mergeusers_inform_about_pending_user_profile_fields(): stdClass {
    global $DB;

    // Upgrade and install code related to user profile fields was removed.
    // Using literals here for convenience.
    $shortnames = [
        'mergeusers_date',
        'mergeusers_logid',
        'mergeusers_olduserid',
        'mergeusers_newuserid',
    ];
    $results = [];
    $categories = [];
    foreach ($shortnames as $shortname) {
        $categoryid = $DB->get_field('user_info_field', 'categoryid', ['shortname' => $shortname]);
        if (!$categoryid) {
            continue;
        }
        $results[$shortname] = $shortname;
        $categories[$categoryid] = $DB->get_field('user_info_category', 'name', ['id' => $categoryid]);
    }

    $stillexists = (count($results) > 0);
    return (object)[
        'exists' => $stillexists,
        'shortnames' => implode(', ', $results),
        'categories' => implode(', ', $categories),
        'url' => (new moodle_url('/user/profile/index.php'))->out(false),
    ];
}

/**
 * Profile callback to add merging data to a users profile.
 *
 * @param core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param null|stdClass $course Course object
 * @throws coding_exception
 * @throws dml_exception
 */
function tool_mergeusers_myprofile_navigation(
    core_user\output\myprofile\tree $tree,
    stdClass $user,
    bool $iscurrentuser,
    null|stdClass $course,
) {
    global $PAGE;

    if (!has_capability('tool/mergeusers:viewlog', context_system::instance())) {
        return;
    }

    /** @var tool_mergeusers_renderer $renderer */
    $renderer = $PAGE->get_renderer('tool_mergeusers');
    $lastmerge = tool_mergeusers\local\last_merge::from($user->id);

    // Display last merge.
    $category = new core_user\output\myprofile\category('tool_mergeusers_info', get_string('pluginname', 'tool_mergeusers'));
    $tree->add_category($category);
    $node = new core_user\output\myprofile\node('tool_mergeusers_info', 'olduser',
        get_string('lastmerge', 'tool_mergeusers'), null, null,
        $renderer->get_merge_detail($user, $lastmerge));
    $category->add_node($node);
}

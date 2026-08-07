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
 * Define default English language strings for report
 *
 * @package   tool_mergeusers
 * @copyright Forrest Gaston
 * @copyright Juan Pablo Torres Herrera
 * @copyright Shane Elliott, Pukunui Technology
 * @copyright Jordi Pujol-Ahulló <jordi.pujol@urv.cat>, Universitat Rovira i Virgili (https://www.urv.cat)
 * @copyright John Hoopes <hoopes@wisc.edu>, University of Wisconsin - Madison
 * @link      http://moodle.org/mod/forum/discuss.php?d=103425
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['adhocconcurrencywarning'] = 'Adhoc merge concurrency limit overridden';
$string['adhocconcurrencywarning_desc'] = 'By default this plugin only allows one "{$a}" task to run at a time, which guarantees merges are always applied in the order they were requested (important for chained merges, e.g. merging A into B, then B into C). Your config.php overrides this via $CFG->task_concurrency_limit for this task to a different value, which can let merges run out of order. Only keep this override if you specifically understand and accept that risk.';
$string['choose_users'] = 'Choose users to merge';
$string['clear_selection'] = 'Clear current user selection';
$string['cligathering:description'] = "Introduce pairs of user's id to merge the first one into the\nsecond one. The first user id (fromid) will 'lose' all its data to be 'migrated'\ninto the second one (toid). The user 'toid' will include data from both users.";
$string['cligathering:fromid'] = 'Source user id (fromid):';
$string['cligathering:stopping'] = 'To stop the merge, Ctrl+C or type -1 either on fromid or toid fields.';
$string['cligathering:toid'] = 'Target user id   (toid):';
$string['dbinprogress'] = '<strong>Merge in progress.</strong><br/>Moodle cron is currently processing this merge.';
$string['dbko_no_transactions'] = '<strong>Merge failed!</strong><br/>Your database engine does not support transactions. Therefore, your database <strong>has been updated</strong>. Your database status may be inconsistent. <br/>But, take a look at the merge log and, please, inform about the error to plugin developers. You will get a solution in short time. After updating the plugin to its last version, which will include the solution to that problem, repeat the merge action to complete it with success.';
$string['dbko_transactions'] = '<strong>Merge failed!</strong><br/>Your database engine supports transactions. Therefore, the whole current transaction has been rolled back and <strong>no modification has been made to your database</strong>.';
$string['dbok'] = 'Merge successful';
$string['dbpending'] = '<strong>Merge queued.</strong><br/>Sooner than later Moodle cron will start processing this merge.';
$string['deletableuser'] = 'This user account is suspended and last merge was to another account with success: this account can be deleted. <strong>Check it twice before proceeding</strong>, though.';
$string['deleted'] = 'User with ID {$a} was deleted';
$string['enableadhocmerge'] = 'Enable adhoc merge';
$string['enableadhocmerge_desc'] = 'If enabled, merges started from the web interface are queued as a background adhoc task and processed the next time Moodle cron runs, instead of running immediately within the web request - avoiding page timeouts on large merges. This only changes how web-triggered merges behave: CLI/CRON-driven merges (e.g. via cli/climerger.php or a custom gathering) are never affected by this setting and always run synchronously.';
$string['error_log_creation_failed'] = 'Failed to create log entry for merge operation';
$string['error_return'] = 'Return to search form';
$string['errorsameuser'] = 'Trying to merge the same user';
$string['errortransactionsonly'] = 'Error: transactions are required, but your database type {$a} does not support them. If needed, you can allow merge users without transactions. Please, review plugin settings to set up them accordingly.';
$string['eventusermergedfailure'] = 'Merge failed';
$string['eventusermergedsuccess'] = 'Merge success';
$string['exception:nocoursemodule'] = 'Can not find the course module for module "{$a->module}", activity.id "{$a->activityid}" and course.id "{$a->courseid}".';
$string['exception:nomoduleinstance'] = 'Can not find "{$a->module}" activity with id "{$a->activityid}".';
$string['exception:plugintablemissing'] = 'Plugin "{$a->module}" is installed but its database table is missing. Grade item id "{$a->itemid}" in course id "{$a->courseid}" cannot be processed due to database corruption.';
$string['excluded_exceptions'] = 'Exclude exceptions';
$string['excluded_exceptions_desc'] = 'Experience on this subject suggests that all these database tables should be excluded from the merge. See README for more details. <br> Therefore, for applying default plugin behaviour, you need to choose \'{$a}\' to exclude all those tables from the merge process (recommended).<br> If you prefer, you can exclude any of those tables and include them in the merge process (not recommended).';
$string['exportlogs'] = 'Export logs as CSV';
$string['exportlogs_help'] = 'Downloads all merge log entries as CSV, or all entries matching the current search if one is active. This always includes every matching row, never just the ones currently shown on this page.';
$string['finishtime'] = 'Merge finished at {$a}';
$string['form_description'] = '<p>You may search for users here if you don\'t know the user\'s username/id number. Otherwise you may expand the form to enter that information directly.  Please see help on fields for more information</p>';
$string['form_header'] = 'Find users to merge';
$string['frommedetail'] = 'From this account into {$a->profilelink} with {$a->success} on {$a->time}. {$a->loglink}';
$string['header'] = 'Merge two users into a single account';
$string['header_help'] = '<p>Given a user to be deleted and a user to keep, this will merge the user data associated with the former user into the latter user. Note that both users must already exist and no accounts will actually be deleted. That process is left to the administrator to do manually.</p><p><strong>Only do this if you know what you are doing as it is not reversable!</strong></p>';
$string['invalid_option'] = 'Invalid form option';
$string['invaliduser'] = 'Does not exist a user with field "{$a->field}" = "{$a->value}"';
$string['lastmerge'] = 'Last merges involving this user:';
$string['logidurl'] = '<a href="{$a->url}">log id {$a->id}</a>';
$string['logko'] = 'Some error occurred:';
$string['logline'] = 'For further reference, these results are recorded in the {$a}.';
$string['loglist'] = 'All these records are merge actions done, showing if they went ok ({$a} in total, most recent first):';
$string['loglistforsearch'] = '{$a->count} merge log entries match "{$a->search}" (most recent first):';
$string['logok'] = 'Here are the queries that have been sent to the DB:';
$string['logpagesize_setting'] = 'Merge log page size';
$string['logpagesize_setting_desc'] = 'Number of merge log rows shown per page on the merge log listing.';
$string['logpagesize_setting_invalid'] = 'Page size must be a whole number of at least {$a}.';
$string['logsearchmaxlength_setting'] = 'Merge log search length';
$string['logsearchmaxlength_setting_desc'] = 'When searching the merge log listing, only the first this many characters of a log entry\'s stored content are inspected. The useful data for search (the merged users\' username/email/name) is always stored at the beginning of that content, so this keeps the search fast even for merges with a very long list of actions.';
$string['logsearchmaxlength_setting_invalid'] = 'Search length must be a whole number of at least {$a}.';
$string['maxsearchresults_setting'] = 'Maximum search results';
$string['maxsearchresults_setting_desc'] = 'Maximum number of users shown at once when searching for a user to merge. If a search matches more users than this, only the first ones (sorted by last name, first name) are shown, along with a message asking to narrow the search.';
$string['mergedbyuseridonlog'] = 'Merged by';
$string['mergeusers'] = 'Merge user accounts';
$string['mergeusers:mergeusers'] = 'Merge user accounts';
$string['mergeusers:view'] = 'Merge User Accounts';
$string['mergeusers:viewlog'] = 'View merge logs';
$string['mergeusers_confirm'] = 'After confirming, the merge process will start.<br/><strong>This merge will not be reversible!</strong><br/>Are you sure you want to continue?';
$string['mergeusers_confirm_adhoc'] = 'After confirming, an adhoc task will be queued for this merge.<br/>You will receive a Moodle notification when the merge concludes.<br/><strong>This merge will not be reversible!</strong><br/>Are you sure you want to continue?';
$string['mergeusersadvanced'] = '<strong>Direct user input</strong>';
$string['mergeusersadvanced_help'] = 'Here you can enter the below fields if you know exactly what users that you want to merge.<br /><br /> Click the "search" button in order to verify/confirm that the input entered are in fact users.';
$string['mergeusersqueued'] = 'Merge queued!<br/>Between {$a->fromuser}<br/>and {$a->touser}.<br/>It will run during the next cron execution. You will see the results on {$a->logid}.';
$string['message:mergeusers_error_body'] = 'The merge of user {$a->fromuser} (ID: {$a->fromuserid}) into {$a->touser} (ID: {$a->touserid}) has been completed with errors. Please review the merge logs for details. {$a->logid}';
$string['message:mergeusers_error_subject'] = '[Merge Users] Merge completed with errors';
$string['message:mergeusers_success_body'] = 'The merge of user {$a->fromuser} (ID: {$a->fromuserid}) into {$a->touser} (ID: {$a->touserid}) has been completed successfully. {$a->logid}';
$string['message:mergeusers_success_subject'] = '[Merge Users] Merge completed successfully';
$string['messageprovider:mergeusers_completion'] = 'Notification when user merge process is completed';
$string['newuser'] = 'User to keep';
$string['newuserid'] = 'User ID to be kept';
$string['newuseridonlog'] = 'User kept';
$string['no_saveselection'] = 'You did not select either an old or new user.';
$string['nologs'] = 'There is no merge logs yet. Good for you!';
$string['nologsforsearch'] = 'No merge logs match "{$a}".';
$string['nomergedby'] = 'Not recorded';
$string['nondeletableuser'] = 'This user account is not suspended or last merge was to this account with success: <strong>this account is active and must be kept</strong>.';
$string['nouserdescription'] = 'No description';
$string['olduser'] = 'User to remove';
$string['olduserid'] = 'User ID to be removed';
$string['olduseridonlog'] = 'User removed';
$string['openlog'] = 'View log';
$string['pluginname'] = 'Merge user accounts';
$string['privacy:metadata'] = 'The Merge User Accounts plugin stores merge logs. Personal data in user snapshots is anonymized upon user deletion request while preserving audit records.';
$string['privacy:metadata:tool_mergeusers'] = 'Merge logs containing information about user merge operations and user snapshots.';
$string['privacy:metadata:tool_mergeusers:fromuserid'] = 'The ID of the user being merged (removed).';
$string['privacy:metadata:tool_mergeusers:log'] = 'JSON log data including user snapshots (username, email, idnumber) and merge actions.';
$string['privacy:metadata:tool_mergeusers:mergedbyuserid'] = 'The ID of the user who performed the merge operation.';
$string['privacy:metadata:tool_mergeusers:timecreated'] = 'The time when the merge log was created.';
$string['privacy:metadata:tool_mergeusers:timemodified'] = 'The time when the merge log was last modified.';
$string['privacy:metadata:tool_mergeusers:touserid'] = 'The ID of the user receiving the merged data (kept).';
$string['profilefields'] = 'User profie fields';
$string['profilefieldsdesc'] = 'This plugin now shows merge status under a specific box on the user profile page. Prior custom user profile fields related to this plugin <strong>are no longer used, and they show outdated information</strong>.<p>We inform you that we found the fields with shortname "{$a->shortnames}" still present under field categories "{$a->categories}". Please, visit <a href="{$a->url}">user profile fields management page</a> to delete them completely by hand. We did not delete them on an upgrade process to let you adapt to the new situation.';
$string['qa_action_delete_fromid'] = 'Keep attempts from the new user';
$string['qa_action_delete_toid'] = 'Keep attempts from the old user';
$string['qa_action_remain'] = 'Do nothing: do not merge nor delete';
$string['qa_action_remain_log'] = 'User data from table <strong>{$a}</strong> are not updated.';
$string['qa_action_renumber'] = 'Merge attempts from both users and renumber';
$string['qa_chosen_action'] = 'Active option for quiz attempts: {$a}.';
$string['qa_grades'] = 'Grades recalculated for quizzes: {$a}.';
$string['quizattemptsaction'] = 'How to resolve quiz attempts';
$string['quizattemptsaction_desc'] = 'When merging quiz attempts there may exist three cases: <ol><li>Only the old user has quiz attempts. All attempts will appear as if they were made by the new user.</li><li>Only the new user has quiz attempts. All is correct and nothing is done.</li><li>Both users have attempts for the same quiz. <strong>You have to choose what to do in this case of conflict.</strong>. You are required to choose one of the following actions: <ul> <li><strong>{$a->renumber}</strong>. Attempts from the old user are merged with the ones of the new user and renumbered by the time they were started.</li><li><strong>{$a->delete_fromid}</strong>. Attempts from the old user are removed. Attempts from the new user are kept, since this option considers them as the most important.</li><li><strong>{$a->delete_toid}</strong>. Attempts from the new user are removed. Attempts from the old user are kept, since this option considers them as the most important.</li><li><strong>{$a->remain}</strong>. Attempts are not merged nor deleted, remaining related to the user who made them. This is the most secure action, but merging users from user A to user B or B to A may produce different quiz grades.</li></ul> </li></ol>';
$string['results'] = 'Merge results and log';
$string['review_users'] = 'Confirm users to merge';
$string['saveselection_submit'] = 'Save selection';
$string['searchlogs'] = 'Search merge logs';
$string['searchuser'] = 'Search for User';
$string['searchuser_help'] = 'Enter the expression you want to match for a specific user field. Only Id makes exact match. The rest of user fields provides partial matching. You can also search for all supported user fields at once.';
$string['setting:invalidjson'] = 'Invalid JSON content.';
$string['settings:calculateddbsettings'] = 'Calculated database settings';
$string['settings:calculateddbsettingsdesc'] = 'The following settings are the calculated database settings and the default ones, to help you understand and compare both settings. Calculated settings include the default and custom settings properly merged, and they will be used while merging users.<p><table><tr><th>{$a->calculatedname}</th><th>{$a->defaultname}</th></tr><tr><td><blockquote><code><pre>{$a->calculated}</pre></code></blockquote></td><td><blockquote><code><pre>{$a->default}</pre></code></blockquote></td></tr></table>';
$string['settings:customdbsettings'] = 'Custom database settings';
$string['settings:customdbsettingsdesc'] = 'Specify the custom database settings for this Moodle instance in <strong>JSON</strong> format. They will complement and override existing settings from de default database settings. The current content of the file <code>config/config.local.php</code> (if it exists) will be shown as the default value to help you on the migration process. The content is automatically formatted while storing the setting\'s value.</p>When facing <code>Syntax error</code> problems when storing this setting, please, consider escaping characters as it must be a valid <strong>JSON string</strong>. For instance, the backslash (<code>\\</code>) should be present as a double backslash (<code>\\\\\\</code>).';
$string['settings:databasesettings'] = 'Database settings';
$string['settings:defaultdbsettings'] = 'Default database settings from <code>default_db_config.php</code>';
$string['settings:generalsettings'] = 'General settings';
$string['showfulldescription'] = 'Show full description';
$string['snapshot_capturedat'] = 'Snapshot data captured on {$a}:';
$string['snapshot_created'] = 'Created at:';
$string['snapshot_deleted'] = 'Deleted (current):';
$string['snapshot_email'] = 'Email:';
$string['snapshot_executed'] = 'Executed at:';
$string['snapshot_header'] = 'User information:';
$string['snapshot_id'] = 'ID:';
$string['snapshot_idnumber'] = 'ID Number:';
$string['snapshot_name'] = 'Name:';
$string['snapshot_queued'] = 'Queued at:';
$string['snapshot_suspended'] = 'Suspended (current):';
$string['snapshot_username'] = 'Username:';
$string['starttime'] = 'Merge started at {$a}';
$string['status'] = 'Status';
$string['status:error'] = 'Error';
$string['status:inprogress'] = 'In Progress';
$string['status:pending'] = 'Pending';
$string['status:success'] = 'Success';
$string['status:unknown'] = 'Unknown';
$string['suspenduser_setting'] = 'Suspend old user';
$string['suspenduser_setting_desc'] = 'If enabled, it suspends the old user automatically upon a successful merge process, preventing the user from logging in Moodle (recommended). If disabled, the old user remains active. In both cases, old user will not have his/her related data.';
$string['tableko'] = 'Table {$a} : update NOT OK!';
$string['tableok'] = 'Table {$a} : update OK';
$string['tableskipped'] = 'For logging or security reasons we are skipping <strong>{$a}</strong>. <br />To remove these entries, delete the old user once this script has run successfully.';
$string['timetaken'] = 'Merge took {$a} seconds';
$string['tomedetail'] = 'From {$a->profilelink} into this account with {$a->success} on {$a->time}. {$a->loglink}';
$string['toomanyusersmatchsearch'] = 'Too many users ({$a->count}) match "{$a->search}". Showing the first {$a->shown}. Please be more specific.';
$string['transactions_not_supported'] = 'For your information, your database <strong>does not supports transactions</strong>.';
$string['transactions_setting'] = 'Only transactions allowed';
$string['transactions_setting_desc'] = 'If enabled, merge users will not work at all on databases that do NOT support transactions (recommended). Enabling it is necessary to ensure that your database remains consistent in case of merge errors. <br />If disabled, you will always run merge actions. In case of errors, the merge log will show you what was the problem. Reporting it to the plugin supporters will give you a solution in short.<br />Above all, core Moodle tables and some third party plugins are already considered by this plugin. If you do not have any third party plugins in your Moodle installation, you can be quiet on running this plugin enabling or disabling this option.';
$string['transactions_supported'] = 'For your information, your database <strong>supports transactions</strong>.';
$string['uniquekeynewidtomaintain'] = 'Keep new user\'s data';
$string['uniquekeynewidtomaintain_desc'] = 'In case of conflict, like when the user.id related column is a unique key, this plugin will keep data from new user (by default). This also means that data from old user is deleted to keep the consistence. Otherwise, if you uncheck this option, data from old user will be kept.';
$string['unknownprofile'] = 'Unknown userid {$a}';
$string['userdescription'] = 'Description: {$a}';
$string['userinfo_erasedforgdpr'] = 'Data erased following a privacy request on {$a}.';
$string['userinfo_notavailable'] = 'No information available for user ID {$a}.';
$string['usernotfoundatmerge'] = 'User not found at merge time';
$string['usernotfoundatmergewithhint'] = 'User not found at merge time ({$a->field}: {$a->value})';
$string['userreviewtable_legend'] = '<b>Review users to merge</b>';
$string['userselecttable_legend'] = '<b>Select users to merge</b>';
$string['viewlog'] = 'Merge users logs';
$string['wronglogid'] = 'The log you are asking for does not exist.';

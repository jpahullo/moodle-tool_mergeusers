<?php

/**
 * Define default English language strings for report
 * @author Forrest Gaston
 * @author Juan Pablo Torres Herrera
 * @author Shane Elliott, Pukunui Technology
 * @author Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @package tool_mergeusers
 * @link http://moodle.org/mod/forum/discuss.php?d=103425
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Merge user accounts';
$string['header'] = 'Merge two users into a single account';
$string['header_help'] =
'<p>Given a user to be deleted and a user to keep, this will merge the user data
 associated with the former user into the latter user. Note that both users must
 already exist and no accounts will actually be deleted. That process is left to the
 administrator to do manually.</p>
 <p>This process involves some database dependant functions and may not have been fully tested
 on your particular choice of database.</p>
 <p><strong>Only do this if you know what you are doing as it is not reversable!</strong></p>';
$string['usermergingheader'] = '&laquo;{$a->username}&raquo; (user ID = {$a->id})';
$string['errorsameuser'] = 'Trying to merge the same user';
$string['mergeusers'] = 'Merge user accounts';
$string['merging'] = 'Merged';
$string['into'] = 'into';
$string['newuserid'] = 'User ID to be kept';
$string['olduserid'] = 'User ID to be removed';
$string['mergeusers:view'] = 'Merge User Accounts';
$string['tableok'] = 'Table {$a} : update OK';
$string['tableko'] = 'Table {$a} : update NOT OK!';
$string['logok'] = 'Here are the queries that have been sent to the DB:';
$string['logko'] = 'Some error occurred:';
$string['logid'] = 'For further reference, these results are recorded in the log id {$a}.';
$string['dbok'] = 'Merge successful';
$string['dbko'] = 'Merge FAILED! <br/>If your database engine supports
 transactions, the whole current transaction has been rolled back and no modification has been
 made to your database records.';
$string['tableskipped'] = 'For logging or security reasons we are skipping <strong>{$a}</strong>.
 <br />To remove these entries, delete the old user once this script has run successfully.';
$string['errordatabase'] = 'Database type not supported: {$a}';
$string['invaliduser'] = 'Invalid user';
$string['cligathering:description'] = "Introduce pairs of user's id to merge the first one into the\n
second one. The first user id (fromid) will 'lose' all its data to be 'migrated'\n
into the second one (toid). The user 'toid' will include data from both users.";
$string['cligathering:stopping'] = 'To stop merging, Ctrl+C or type -1 either on fromid or toid fields.';
$string['cligathering:fromid'] = 'Source user id (fromid):';
$string['cligathering:toid'] =   'Target user id   (toid):';
$string['viewlog'] = 'See merging logs';
$string['loglist'] = 'All these records are merging actions done, showing if they went ok:';
$string['newuseridonlog'] = 'User ID kept';
$string['olduseridonlog'] = 'User ID removed';
$string['nologs'] = 'There is no merging logs yet. Good for you!';
$string['wronglogid'] = 'The log you are asking for does not exist.';
$string['deleted'] = 'User with ID {$a} was deleted';

//New strings

// Form Strings
$string['form_header'] = 'Find users to merge';
$string['form_description'] = '<p>You may search for users here if you don\'t
    know the user\'s username/id number. Otherwise you may expand the form to
    enter that information directly.  Please see help on fields for more
    information</p>';
$string['searchuser'] = 'Search for User';
$string['searchuser_help'] = 'Enter a username, first/last name, email address
    or user id to search for potential users. You may also specify if you only
    want to search through a particular field.';
$string['mergeusersadvanced'] = '<strong>Direct user input</strong>';
$string['mergeusersadvanced_help'] = 'Here you can enter the below fields if
    you know exactly what users that you want to merge.<br /><br />
    Click the "search" button in order to verify/confirm that the input entered
    are in fact users.';
$string['mergeusers_confirm'] = 'After confirming the merge process will start.
    <br /><strong>This will not be reversible!</strong>
    Are you sure you want to continue?';
$string['clear_selection'] = 'Clear current user selection';

// Merge users select table
$string['olduser'] = 'User to remove';
$string['newuser'] = 'User to keep';
$string['saveselection_submit'] = 'Save selection';
$string['userselecttable_legend'] = '<b>Select users to merge</b>';

// Merge users review table
$string['userreviewtable_legend'] = '<b>Review users to merge</b>';

// Error string
$string['error_return'] = 'Return to search form';
$string['no_saveselection'] = 'You did not select either an old or new user.';
$string['invalid_option'] = 'Invalid form option';

// Settings page
$string['suspenduser_setting'] = 'Suspend old user';
$string['suspenduser_setting_desc'] = 'If enabled, it suspends the old user
    automatically upon a succesful merging process, preventing the user
    from logging in Moodle (recommended). If disabled, the old user remains active.
    In both cases, old user will not have his/her related data.';
$string['transactions_setting'] = 'Only transactions allowed';
$string['transactions_setting_desc'] = 'If enabled, merge users will not work
    at all on databases that do NOT support transactions (recommended).
    Enabling it is necessary to ensure that your database remains consistent
    in case of merging errors. <br />If disabled, you will always run merging actions.
    In case of errors, the merging log will show you what was the problem.
    Reporting it to the plugin supporters will give you a solution in short.
    <br />Above all, core Moodle tables and some third party plugins are already
    considered by this plugin. If you do not have any third party plugins
    in your Moodle installation, you can be quiet on running this plugin
    enabling or disabling this option.';

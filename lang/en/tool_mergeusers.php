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
$string['description'] =
'<p>Given a user ID to be deleted and a user ID to keep, this will merge the user data
 associated with the former user ID into the latter user ID. Note that both user IDs must
 already exist and no accounts will actually be deleted. That process is left to the
 administrator to do manually.</p>
 <p>This process involves some database dependant functions and may not have been fully tested
 on your particular choice of database. <strong>Only do this if you know what you are doing
 as it is not reversable!</strong></p>';
$string['usermergingheader'] = '&laquo;{$a->username}&raquo; (user ID = {$a->id})';
$string['errorsameuser'] = 'Trying to merge the same user';
$string['mergeusers'] = 'Merge user accounts';
$string['merging'] = 'Merging';
$string['into'] = 'into';
$string['newuserid'] = 'User ID to be kept';
$string['olduserid'] = 'User ID to be removed';
$string['mergeusers:view'] = 'Merge User Accounts';
$string['tableok'] = 'Table {$a} : update OK';
$string['tableko'] = 'Table {$a} : update NOT OK!';
$string['logok'] = '<p><strong>Here are the queries that have been sent to the DB</strong><br/>
 Please save this page for further reference.</p>';
$string['logko'] = 'Some error occurred:';
$string['dbok'] = 'Merge successful';
$string['dbko'] = 'Merge FAILED! <br/>If your database engine supports
 transactions, the whole current transaction has been rolled back and no modification has been
 made to your database records.';
$string['tableskipped'] = 'For logging or security reasons we are skipping <strong>{$a}</strong>.
 <br />To remove these entries, delete the old user once this script has run successfully.';
$string['errordatabase'] = 'Database type not supported: {$a}';
$string['invaliduser'] = 'Invalid user';

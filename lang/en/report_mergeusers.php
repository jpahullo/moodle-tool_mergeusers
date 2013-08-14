<?php

/**
 * Define default English language strings for report
 * @author Forrest Gaston & Juan Pablo Torres Herrera
 * @author Shane Elliott, Pukunui Technology
 * @package    report
 * @subpackage mergeusers
 * @link http://moodle.org/mod/forum/discuss.php?d=103425
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package admin-report-mergeusers
 * @version 2012052500
 */

$string['pluginname'] = 'Merge user accounts';
$string['description'] = '
    <h1>Merge two users into a single account.</h1>
    <p>Given a user ID to be deleted and a user ID to keep, this will merge the user data associated with the former user ID into the latter user ID. Note that both user IDs must already exist and no accounts will actually be deleted. That process is left to the administrator to do manually.</p>
    <p>This process involves some database dependant functions and may not have been fully tested on your particular choice of database. <strong>Only do this if you know what you are doing as it is not reversable!</strong></p>';
$string['errornouserid'] = 'Cannot retrieve user ID';
$string['errordatabase'] = 'Unsupported database type: {$a}';
$string['mergeusers'] = 'Merge user accounts';
$string['merging'] = 'Merging';
$string['into'] = 'into';
$string['newuserid'] = 'User ID to be kept';
$string['olduserid'] = 'User ID to be removed';
$string['useridnotexist'] = 'User ID does not exist';
$string['mergeusers:view'] = 'Merge User Accounts';
$string['tableok'] = 'Table {$a} : update OK';
$string['tableko'] = 'Table {$a} : update NOT OK!';
$string['dbqueries'] = '<h2>Here are the queries that have been sent to the DB</h2><p style="color: #f00;">Please save this page for further reference.</p>';
$string['dbok'] = '<h1 style="color:#0c0;">Merge succesful</h1>';
$string['dbko'] = '<h1 style="color:#f00;">Merge FAILED!</h1><p>If your database engine supports transactions, the whole current transaction has been rolled back and no modification has been made to your database records.</p>';
$string['tableskipped'] = 'For logging or security reasons we are skipping <strong>{$a}</strong>.<br />To remove these entries, delete the old user once this script has run successfully.';

<?php

/**
 * Define default English language strings for report
 * @author Forrest Gaston
 * @author Juan Pablo Torres Herrera
 * @author Shane Elliott, Pukunui Technology
 * @author Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @author John Hoopes <hoopes@wisc.edu>, University of Wisconsin - Madison
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
 <p><strong>Only do this if you know what you are doing as it is not reversable!</strong></p>';
$string['usermergingheader'] = '&laquo;{$a->username}&raquo; (user ID = {$a->id})';
$string['errorsameuser'] = 'Trying to merge the same user';
$string['mergeusers'] = 'Merge user accounts';
$string['mergeusers:mergeusers'] = 'Merge user accounts';
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
$string['dbko_transactions'] = '<strong>Merge failed!</strong> <br/>Your database engine
    supports transactions. Therefore, the whole current transaction has been rolled back
    and <strong>no modification has been made to your database</strong>.';
$string['dbko_no_transactions'] = '<strong>Merge failed!</strong> <br/>Your database engine
    does not support transactions. Therefore, your database <strong>has been updated</strong>.
    Your database status may be inconsistent. <br/>But, take a look at the merging log
    and, please, inform about the error to plugin developers. You will get a solution
    in short time. After updating the plugin to its last version, which will include the solution
    to that problem, repeat the merging action to complete it with success.';
$string['tableskipped'] = 'For logging or security reasons we are skipping <strong>{$a}</strong>.
 <br />To remove these entries, delete the old user once this script has run successfully.';
$string['errordatabase'] = 'Error: Database type {$a} not supported.';
$string['invaliduser'] = 'Invalid user';
$string['cligathering:description'] = "Introduce pairs of user's id to merge the first one into the\n
second one. The first user id (fromid) will 'lose' all its data to be 'migrated'\n
into the second one (toid). The user 'toid' will include data from both users.";
$string['cligathering:stopping'] = 'To stop merging, Ctrl+C or type -1 either on fromid or toid fields.';
$string['cligathering:fromid'] = 'Source user id (fromid):';
$string['cligathering:toid'] =   'Target user id   (toid):';
$string['viewlog'] = 'See merging logs';
$string['loglist'] = 'All these records are merging actions done, showing if they went ok:';
$string['newuseridonlog'] = 'User kept';
$string['olduseridonlog'] = 'User removed';
$string['nologs'] = 'There is no merging logs yet. Good for you!';
$string['wronglogid'] = 'The log you are asking for does not exist.';
$string['deleted'] = 'User with ID {$a} was deleted';
$string['errortransactionsonly'] = 'Error: transactions are required, but your database type {$a}
    does not support them. If needed, you can allow merging users without transactions.
    Please, review plugin settings to set up them accordingly.';

//New strings

// Progress bar
$string['choose_users'] = 'Choose users to merge';
$string['review_users'] = 'Confirm users to merge';
$string['results'] = 'Merging results and log';

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

// quiz attempts strings
$string['quizattemptsaction'] = 'How to resolve quiz attempts';
$string['quizattemptsaction_desc'] = 'When merging quiz attempts there may exist three cases:
    <ol>
    <li>Only the old user has quiz attempts. All attemps will appear as if they were made by the new user.</li>
    <li>Only the new user has quiz attempts. All is correct and nothing is done.</li>
    <li>Both users have attempts for the same quiz. <strong>You have to choose what to do in this case of conflict.
    </strong>. You are required to choose one of the following actions:
        <ul>
        <li><strong>{$a->renumber}</strong>. Attempts from the old user are merged with the ones of the new user
        and renumbered by the time they were started.</li>
        <li><strong>{$a->delete_fromid}</strong>. Attempts from the old user are removed. Attempts from the new user
        are kept, since this option considers them as the most important.</li>
        <li><strong>{$a->delete_toid}</strong>. Attempts from the new user are removed. Attempts from
        the old user are kept, since this option considers them as the most important.</li>
        <li><strong>{$a->remain}</strong> (by default). Attempts are not merged nor deleted, remaining related to
        the user who made them. This is the most secure action, but merging users from user A to user B or B to A may
        produce different quiz grades.</li>
        </ul>
    </li>
    </ol>';
$string['qa_action_renumber'] = 'Merge attempts from both users and renumber';
$string['qa_action_delete_fromid'] = 'Keep attempts from the new user';
$string['qa_action_delete_toid'] = 'Keep attempts from the old user';
$string['qa_action_remain'] = 'Do nothing: do not merge nor delete';
$string['qa_action_remain_log'] = 'User data from table <strong>{$a}</strong> are not updated.';
$string['qa_chosen_action'] = 'Active option for quiz attempts: {$a}.';

$string['qa_grades'] = 'Grades recalculated for quizzes: {$a}.';

// Settings page
$string['suspenduser'] = 'Suspend old user';
$string['suspenduser_desc'] = 'If enabled, it suspends the old user
    automatically upon a succesful merging process, preventing the user
    from logging in Moodle (recommended). If disabled, the old user remains active.
    In both cases, old user will not have his/her related data.';
$string['transactions'] = 'Only transactions allowed';
$string['transactions_desc'] = 'If enabled, merge users will not work
    at all on databases that do NOT support transactions (recommended).
    Enabling it is necessary to ensure that your database remains consistent
    in case of merging errors. <br />If disabled, you will always run merging actions.
    In case of errors, the merging log will show you what was the problem.
    With that information, you will be able to set up settings below to make
    it proceed successfully. In case of doubt, report it to the plugin supporters
    and they will give you a solution in short.
    <br>Above all, core Moodle tables and some third party plugins are already
    considered by this plugin. If you do not have any third party plugins
    in your Moodle installation, you can be quiet on running this plugin
    enabling or disabling this option.';
$string['transactions_supported'] = 'For your information, your database
    <strong>supports transactions</strong>.';
$string['transactions_not_supported'] = 'For your information, your database
    <strong>does not supports transactions</strong>.';
$string['tablemerger_settings'] = 'Specific settings from table mergers';
$string['tablemerger_settings_desc'] = 'Table mergers are the tools that this plugin implements
    to merge users in a per database table basis. These table mergers may have specific
    settings to work properly. They appear below.';
$string['cronsettings'] = 'Cron settings';
$string['cronsettings_help'] = 'This setting specifies which process to use for gathering users
    to merge. By default it is an interative CLIGathering, provided by this plugin.<br>
    However, you can implement your own class implementing the Gathering interface.
    At every invocation of this interface it must return an object with \'fromid\'
    and \'toid\' attributes, which identify the users to merge by means of their \'user.id\' values.
    Then, place the CLI script in server cron to process all user mergings automatically.';
$string['cronsettings_desc'] = 'If you set up the CLI script at your cron and provide a non
    interactive gathering tool, you can make this process automatic. By default,
    CLIGathering is an interactive gathering tool which is not suitable for that.
    See the help for more information.';
$string['gathering'] = 'Gathering tool';
$string['gathering_desc'] = 'Gathering tool which basically is an iterator. At every iteration,
    this provides an object with \'fromid\' and \'toid\' attributes for users to merge.';
$string['exclude_tables_settings'] = 'Exclude tables from merging';
$string['exclude_tables_settings_help'] = 'When you exclude a table from
    the merging process you are preventing this plugin from processing the given
    table. Therefore, selected tables remain unaltered after the merging process.
    This is necessary, even though it may seem strange.<br>
    Our experience on this subject suggests
    that all the following database tables should be excluded from merging and
    to make the plugin have the default behavior:
    my_pages, user_info_data, user_preferences, user_private_key. See
    README for more technical details. <br>
    Actually, my_pages should always be excluded, since having multiple records
    on this table for the same user makes My Moodle not work.';
$string['exclude_tables_settings_desc'] = 'Select the database tables that must be excluded from
    merging users.';
$string['excluded_tables'] = 'Excluded tables';
$string['excluded_tables_desc'] = 'Excluded tables from merging.';
$string['tablesettings'] = 'Tables and columns related to user.id';
$string['tablesettings_help'] = 'This section is very important and you, admin,
    have to be cautious with it.<br>
    Below you have the possibility to set up all column names related to the
    user.id column throughout your database scheme.
    You have two ways of setting up column names related to user.id.
    The first one is to just set up a list of common, <strong>generic column
    names</strong> that, if appear, they <strong>will be related to user.id
    always</strong>, regardless the database table the column name is found in.
    In the second way, you have the ability to set up <strong>specific column
    names for selected tables</strong>.
    <br><br>Having this configured out, this plugin will check througout the
    whole Moodle database scheme and merge two users considering only:
    <ul>
    <li>These selected tables with only their specified column names.</li>
    <li>The rest of the tables with the generic column names.</li>
    </ul>Therefore, it is very important that these settings reflect and include
    all existing column name related to the user.id column.
    Finally, even though you can set up all the column names in the default
    list, this plugin works this way for efficiency and clarity.';
$string['specifiedtablesettingsoperation'] = 'In order to set up column names
    for specific table names, <strong>you have to visit this settings page
    twice</strong> to do as follows:
    <ol>
    <li>Select all tables to customize the list of column names related to user.id,
        and then save settings.</li>
    <li>Revisit this settings page and fill in the list of column names related
        to user.id for each table you selected before. Finally save settings again.</li>
    </ol>These settings have more priority than the generic column names, so that
    these specified column names will be the only column names that will be checked for.';
$string['user_related_columns_for_default_setting'] = 'Generic user.id related columns';
$string['user_related_columns_for_default_setting_desc'] = 'All column names
    from your database appear in this list. Choose those column names that, <strong>if appear
    in any table</strong>, they will be <strong>always related to a user.id value</strong>.';
$string['tables_with_custom_user_related_columns'] = 'Tables with specific user.id related column names';
$string['tables_with_custom_user_related_columns_desc'] = 'All tables appear in this list.
    Select the tables that must be processed searching for particular column names
    related to user.id, which should be different from those ones listed in the
    generic list of user.id related columns.';
$string['user_related_columns_for_table_setting_desc'] = 'Choose all column
    names from this table that are related to a user.id value.';
$string['unique_indexes_settings'] = 'Unique compound indexes';
$string['unique_indexes_settings_desc'] = 'This is the list of <strong>unique
    compound indexes</strong> from your current database schema.
    All the listed unique indexes from below are processed by this tool when
    merging users. This is important since tables with unique compound indexes
    do not allow multiple records with the same values for the given index.
    Therefore, this tool have to address this multiplicity of records before
    updating your database.<br><br>
    This is the list of table names, index names and columns for the given
    index. Columns in bold are related to the column user.id.';
$string['table'] = 'Database table';
$string['index'] = 'Table index';
$string['columns'] = 'Ordered list of index columns';
$string['nonunique_index_settings'] = 'Non unique compound indexes';
$string['nonunique_index_settings_help'] = 'All the compound indexes listed
    here are non unique. This means that by default, your database schema
    allows multiple records with the same values for the given compound index.<br><br>
    However, there are cases that these multiple records have no sense, when
    they are referred to the same person and merging two Moodle users.
    We provide this section to choose those compound indexes to be processed as
    if they were unique indexes, <strong>without modifying your database schema</strong>.';
$string['nonunique_index_operation'] = 'You can decide this tool to process non
    unique compound indexes as if they were unique <strong>without modifying
    your database schema</strong>. To do so, you have to follow the following steps:<ol>
    <li>Choose the non unique compound index names from the first list.</li>
    <li>Save settings.</li>
    </ol>Default values to <strong>yes</strong> means default behavior for this plugin.<br><br>
    Note that each line provides the whole index information, by stating
    <strong>{table name} - {index name} : {column1}, {column2}[, ...]</strong>.
    Columns in bold are related to user.id values.';
$string['tables_with_adhoc_indexes_settings'] = 'Define ad-hoc compound indexes';
$string['tables_with_adhoc_indexes_settings_help'] = 'The database schema may
    not contain all compound indexes necessary to help make a sound and proper
    users\' merge.<br><br>
    To address this issue, <strong>without modifying your database schema</strong>,
    you may define here easily ad-hoc table indexes.
    Indexes are used by this tool to identify duplicated user data records.<br><br>
    Define ad-hoc indexes is easy: you only need to select tables from the given
    list, save settings, then define the column names for the selected tables
    to be part of the index, and save again the current settings.
    From the selected columns, at least one column must be a user related field.';
$string['tables_with_adhoc_indexes_settings_desc'] = 'You can define ad-hoc
    compound indexes <strong>without modifying your database schema</strong>
    following these steps:
    <ol>
    <li>Choose the names from the table list to have ad-hoc indexes.</li>
    <li>Save settings.</li>
    <li>For each selected table, choose the column names from the given list.
    This will define your table index.</li>
    <li>Save settings again.</li>
    </ol>';
$string['tables_with_adhoc_indexes'] = 'Choose tables to have ad-hoc indexes';
$string['tables_with_adhoc_indexes_desc'] = 'Select the tables to have one
    ad-hoc index per selected table.';
$string['columns_for_adhoc_index_for_table_setting_desc'] = 'Choose the columns
    to define the ad-hoc index for this table.';
$string['check_indexes_settings'] = 'Checkpoint for the list of indexes';
$string['check_indexes_settings_desc'] = 'This is the list indexes from your
    database schema and manually defined with some column related to user.id.
    This is the list of table names, index names, index type and columns for
    every index. Columns in bold are related to the column user.id.
    <strong>These indexes are automatically checked when merging two
    users</strong>.<br><br>
    If you think there is some missing index in this list or you get an error
    when merging two users, please check the above settings for <strong>user
    related columns</strong> in either of the two depicted ways. Afterwards, you
    have to check the unique, non unique compound indexes or define ad-hoc
    compound indexes.  In the end, you have to see the missing index in the
    list or you have to be able to merge those users. <br><br>
    Be very careful when updating all these settings.';
$string['noindexes'] = 'No compound indexes with user related columns were
    detected. It is very strange. <strong>You should verify your database
    scheme, seriously</strong>.';
$string['uniqueness'] = 'Uniqueness';
$string['uniqueness0'] = 'Non unique';
$string['uniqueness1'] = 'Unique';
$string['uniqueness2'] = 'Ad hoc';

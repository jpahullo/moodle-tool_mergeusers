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

$string['pluginname'] = 'Unire due profili utente';
$string['header'] = 'Unire due utenti in un unico profilo';
$string['header_help'] =
'<p>Dati un utente da rimuovere e uno da tenere, questa funzione unirà i dati associati al primo utente con quelli dell\'ultimo utente. 
Si noti che entrambi gli utenti devono già esistere e che nessun profilo verrà cancellato. Questa funzione è lasciata all\'attività manuale dell\'amministratore.</p>
 <p><strong>Eseguire questa operazione solo se si sa cosa si sta facendo dal momento che non è reversibile!</strong></p>';
$string['usermergingheader'] = '&laquo;{$a->username}&raquo; (user ID = {$a->id})';
$string['errorsameuser'] = 'Cercando di unire lo stesso utente';
$string['mergeusers'] = 'Unire due profili utente';
$string['mergeusers:mergeusers'] = 'Unire due profili utente';
$string['merging'] = 'Uniti';
$string['into'] = 'in';
$string['newuserid'] = 'ID utente da tenere';
$string['olduserid'] = 'ID utente di rimuovere';
$string['mergeusers:view'] = 'Unire due profili utente';
$string['tableok'] = 'Tabella {$a} : aggiornamento OK';
$string['tableko'] = 'Tabella {$a} : aggiornamento NOT OK!';
$string['logok'] = 'Queste sono le interrogazioni che sono state inviate al DB:';
$string['logko'] = 'Sono stati riscontrati alcuni errori:';
$string['logid'] = 'Per successivi riferimenti, sono stati registrati questi risultati nel log con id {$a}.';
$string['dbok'] = 'Unione terminata con successo';
$string['dbko_transactions'] = '<strong>Unione fallita!</strong> <br/>Il tuo motore di database supporta le transazioni. 
    Dunque, l\'attuale transazione è stata ripristinata interamente e nessuna modifica è stata apportata al database</strong>.';
$string['dbko_no_transactions'] = '<strong>Unione fallita!</strong> <br/>Il tuo motore di database non supporta le transazioni.
    Dunque, il database <strong>è stato aggiornato</strong>.
    Lo stato del database potrebbe essere inconsistente. <br/>Comunque, dai un\'occhiata al registro dell\'unione
    e informa, per favore, gli sviluppatori del plugin di questo errore. Riceverai una soluzione in breve tempo. 
    Dopo avr aggiornato il plugin alla sua ultima versone, che includerà la soluzione a questo problema, ripeti
    l\'azione di unione per completarla con successo.';
$string['tableskipped'] = 'For logging or security reasosns we are skipping <strong>{$a}</strong>.
 <br />To remove these entries, delete the old user once this script has run successfully.';
$string['invaliduser'] = 'Utente non valido';
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
$string['eventusermergedsuccess'] = 'Merging success';
$string['eventusermergedfailure'] = 'Merge failed';

// Settings page
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
$string['transactions_supported'] = 'For your information, your database
    <strong>supports transactions</strong>.';
$string['transactions_not_supported'] = 'For your information, your database
    <strong>does not supports transactions</strong>.';
$string['excluded_exceptions'] = 'Exclude exceptions';
$string['excluded_exceptions_desc'] = 'Experience on this subject suggests
    that all these database tables should be excluded from merging. See
    README for more details. <br>
    Therefore, for applying default plugin behaviour, you need to choose \'{$a}\'
    to exclude all those tables from the merging process (recommended).<br>
    If you prefer, you can exclude any of those tables and include them in the
    merging process (not recommended).';

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

$string['uniquekeynewidtomaintain'] = 'Mantenere i dati del nuovo utente';
$string['uniquekeynewidtomaintain_desc'] = 'In case of conflict, 
    like when the user.id related column is a unique key, this plugin will keep 
    data from new user (by default). This also means that data from old user is 
    deleted to keep the consistence. Otherwise, if you uncheck this option, 
    data from old user will be kept.';

$string['starttime'] = 'Processo di unione iniziato alle {$a}';
$string['finishtime'] = 'Processo di unione terminato alle {$a}';
$string['timetaken'] = 'La unione ci ha impiegato {$a} secondi';
$string['privacy:metadata'] = 'Il plugin Merge User Accounts non memorizza dati personali.';
$string['get_queue_merging_requests'] = 'Richiama la coda delle richieste di unione di due profili utente e lancia elaborazioni adhoc per ogni singola richiesta';
$string['cannotfindusertoremove'] = 'Utente da rimuovere non trovato. Modificare i parametri di input.';
$string['toomanyuserstoremovefound'] = 'Troppi utenti da rimuovere. Modificare i parametri di input.';
$string['cannotfindusertokeep'] = 'Utente da tenere non trovato. Modificare i parametri di input.';
$string['toomanyuserstokeepfound'] = 'Troppi utenti da tenere. Modificare i parametri di input.';

$string['failedmergerequest'] = 'Richiesta di unione fallita.';
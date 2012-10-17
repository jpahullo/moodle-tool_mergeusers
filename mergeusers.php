<?php

    /*
    USE AT YOUR OWN RISK ! THIS HAS NOT BEEN TESTED EXTENSIVELY !
    YOU HAVE BEEN WARNED ! NO WARRANTY WHATSOEVER !
    
    This script intends to merge two Moodle user accounts. We had some cases with users having two accounts created for some reason, and needed some means of merging these accounts into one. Using this scheme, the "good" accounts inherits all roles associations (course subscriptions, forum postings, exercises done, etc.) from the "bad" one, but the account details themselves (i.e. name, e-mail addresss, etc.) aren't moved from one count to the other (this is up to you).
    
    MINIMUM REQUIREMENTS :
    
      - MySQL version 4.1
      - Moodle version 1.8
    
    
    How to proceed :

     - be careful not to let anyone except you execute that script on your Moodle site;
     
     - fill the "BLANK"s (on lines 43-44) : replace "BLANK" with faulty user ID and good user ID;
     
     - if you wish to do things right (i.e. be safe if anything turns out badly) :
         - stop your Moodle server (at least MySQL);
         - backup the complete Moodle database;
         - restart your Moodle server, to make it accessible again;
         - if anything goes wrong in the next steps, you may always restart your MySQL server from the last backup.
         
     - upload this script to your Moodle site (say, http://moodle.example.com/mergeusers.php);
     
     - visit the script (in my example : http://moodle.example.com/mergeusers.php) with your web browser (you'll have to be logged as an admin in your Moodle);
     
     - let this script do his thing (it should finish saying "DB update OK" at the bottom);
     
     - save the whole Web page displayed for further reference : it also contains information necessary if you would go back without completely restoring from your backup;
     
     - check the two user accounts, and verify that :
         - the "bad one" is no more associated with any course;
         - the "good one" is associated with the courses that were associated with the "bad one" before;
         
     - disable or delete the "bad" account, so that the user cannot log into this one any more;
     
     - check the login details of the "good" account (now the only one), and provide the user with his/her new login details (if needed).
     
    */
    $user_bad_ID = null; // bad user ID : the one to dismiss, characteristics (forum subscriptions, course registrations, etc. will be shifted to $user_good_ID
    $user_good_ID = null; // good user ID : the one to keep, will inherit all characteristics from the "bad user ID"
    /*************************************************************************************************************************/
    
    if (!$user_bad_ID || !$user_good_ID) {
    	die();
    }
    
    
    // Report all PHP errors
    error_reporting(E_ALL);
    
    // Same as error_reporting(E_ALL);
    ini_set('display_errors', 'On');
    ini_set('error_reporting', E_ALL);

    require('../config.php');
    require_once("$CFG->libdir/blocklib.php");
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/accesslib.php');
    require_once($CFG->libdir.'/weblib.php');

    require_login();
    require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));
    if (!isadmin()) die('You must be admin to use this feature');

    print_header_simple();

    echo("<h1>Be careful!</h1>");
    
    $query1 = mysql_query("SHOW TABLES");
    echo "<h2>".mysql_num_rows($query1). " tables found in database &quot;".$CFG->dbname."&quot;</h2>";
    
    $backqueries = array();
    $modifications_made = 0;
    
    while ($table = mysql_fetch_assoc($query1)) {
        $table_name = $table['Tables_in_'.$CFG->dbname];

        if(!trim($table_name)) {
            echo("Skipping due to blank table name.  $table=".print_r($table, true)."<br />");
            continue;
        } else {
            echo("Starting on table = $table_name<br />");
        }

        $sql_a = "SHOW COLUMNS FROM `".$table_name."` LIKE 'userid'";
        $sql_b = "SHOW COLUMNS FROM `".$table_name."` LIKE 'user_id'";
        $sql_c = "SHOW COLUMNS FROM `".$table_name."` LIKE 'id_user'";
        $query2_a = mysql_query($sql_a);
        $query2_b = mysql_query($sql_b);
        $query2_c = mysql_query($sql_c);
        
        if ((mysql_num_rows($query2_a) + mysql_num_rows($query2_b) + mysql_num_rows($query2_c)) !== 0) {
        
            if ((mysql_num_rows($query2_a) + mysql_num_rows($query2_b) + mysql_num_rows($query2_c)) == 1) {
                if (mysql_num_rows($query2_a) == 1) {
                    $assoc = mysql_fetch_assoc($query2_a);
                }
                elseif (mysql_num_rows($query2_b) == 1) {
                    $assoc = mysql_fetch_assoc($query2_b);
                }
                elseif (mysql_num_rows($query2_c) == 1) {
                    $assoc = mysql_fetch_assoc($query2_c);
                }
                else {
                    echo("ERROR : an error occured!");
                    continue;
                }
                $field_name = $assoc['Field'];
            }
            
            else {
                echo("<br />$sql_a<br />$sql_b<br />$sql_c<br />");
                echo("ERROR : several rows here!!");
                continue;
            }

            $primary_key = 'id';
            
            $query3 = mysql_query("SELECT `".$primary_key."` FROM `".$table_name."` WHERE `".$field_name."` = '".$user_bad_ID."'");
            
            if (mysql_num_rows($query3) !== 0) {
                $records_to_modify = array();
                
                while ($record_to_modify = mysql_fetch_assoc($query3)) {
                    $records_to_modify[] = $record_to_modify[$primary_key];
                }
                
                foreach ($records_to_modify as $record_to_modify) {
                    $modifier_query = "UPDATE `".$table_name."` SET `".$field_name."` = '".$user_good_ID."' WHERE `".$primary_key."` = '".$record_to_modify."'";
                    echo "::::: ".$modifier_query."<br />";
                    if (mysql_query($modifier_query)) {
                        $backqueries[] = "UPDATE `".$table_name."` SET `".$field_name."` = '".$user_bad_ID."' WHERE `".$primary_key."` = '".$record_to_modify."'";
                        $modifications_made++;
                        echo "<p style=\"color:#0C0;\">Table update OK</p>";
                    }
                    else {
                        echo "<p style=\"color:#F00;\">ERROR : Table update NOT OK!!! : ".mysql_error()."</p>";
                    }
                }
            }
        }
    }
    
    mysql_close();
    
    echo "<h1>DB update OK (modifications made: $modifications_made)</h1>";
    if ($modifications_made > 0) {
        echo "<h1>to display the SQL statements needed to undo these modifications, click <a href=\"#\" onclick=\"document.getElementById('mergeusers_modifications').style.display='block'; return false;\">here</a></h1>";
        echo '<div id="mergeusers_modifications" style="display:none; background-color:#CCC; padding:20px; margin:20px;">';
        echo '<h2>To go back, run these statements on your MySQL server :</h2>';
        echo '<p style="padding:20px; font-size:1em; line-height:1em;">';
        $backqueries = implode ('<br />', $backqueries);
        echo $backqueries;
        echo '</p></div>';
    }
    
    print_footer();
  
?>

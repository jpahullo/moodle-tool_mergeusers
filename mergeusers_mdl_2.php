<?php
/**
 *  This script will merge two Moodle user accounts, "user A" and "user B". 
 *  The intent of the script is to assign all activity & records from user A to 
 *  user B. This will give the effect of user B seeming to have done everything 
 *  both users have ever done in Moodle. The basic function of the script is to 
 *  loop through the tables and update the userid of every record from user A to  
 *  user B. This works well for most tables. We do however, have a few special 
 *	cases:
 *   
 *		Special Case #1: The grade_grades table has a compound unique key on userid 
 *	    and itemid. This prevents a simple update statement if the two users have 
 *	    done the same activity. What this script does is determine which activities 
 *	    have been completed by both users and delete the entry for the old user 
 *	    from this table. Data is not lost because a duplicate entry can be found in 
 *	    the grade_grades_history table, which is correctly updated by the regular 
 *	    processing of the script.   
 *     
 *		Special Case #2: The user_enrolments table controls which user is enrolled 
 *	    in which course. Rather than unenroll the old user from the course, this 
 *	    script simply updates their access to the course to "2" which makes them 
 *	    completely unable to access the course. To remove these records all 
 *	    together I recomend disabling or deleting the entire old user account once 
 *	    the migration has been successful.
 *    
 *	    Special Case #3: There are 3 logging/preference tables 
 *	    (user_lastaccess, user_preferences,user_private_key) which exist in 
 *	    Moodle 2.0. This script is simply skipping these tables since there's no 
 *	    legitimate purpose to updating the userid value here. This would lead to 
 *	    duplicate rows for the new user which is silly. Again, if you want to 
 *	    remove these records I would recommend deleting the old user after this 
 *	    script runs sucessfully.  
 *    
 *	BEFORE YOU RUN THIS SCRIPT, BACK UP YOUR DATABASE.
 *  There is no provision in this script for rollbacks, so if something 
 *  were to fail midway through you will end up with a half-updated DB.
 *  This. Is. Bad. Practice safe script. Always backup first.
 *   
 *   
 *  MINIMUM REQUIREMENTS:
 *	- MySQL v5.0
 *  - Moodle v2.0
 *   
 *  How to proceed:
 *	- be careful not to let anyone except you execute this script on your Moodle site;
 *  - fill the "BLANK"s (on lines 79-80): replace "BLANK" with faulty user ID and good user ID;
 *  - if you wish to do things right (ie be safe if anything turns out badly):
 *  	- stop your Moodle server (at least MySQL);
 *      - backup the complete Moodle database;
 *      - restart your Moodle server, to make it accessible again;
 *      - if anything goes wrong in the next steps, you may always restart your 
 *        MySQL server from the last backup.
 *      - verify your backup script contains SQL. Again, if this script fails 
 *        and you have no backup, you're sadly SOL. 
 *	- upload this script to your Moodle site 
 *    (say, http://moodle.example.com/mergeusers.php);
 *  - login as an admin user
 *  - visit the script (ie: http://moodle.example.com/mergeusers.php) 
 *    with your web browser;
 *  - let this script do its thing (it should finish saying "DB update OK" at the bottom);
 *  - check the two user accounts, and verify that :
 *      - the "bad one" is no more associated with any course;
 *      - the "good one" is associated with the courses that were associated with the "bad one" before;
 *  - disable or delete the "bad" account, so that the user cannot log into this one any more;
 *  - check the login details of the "good" account (now the only one), and provide the user with his/her new login details (if needed).
 *	
 *	Based on the mergeusers_v2.php script written by Nicolas Dunand.
 *	Updated for Moodle 2.0 by Mike Holzer [mike.holzer AT psu DOT edu]
 * 
 *	Mike Holzer
 *	Web Technology Manager	
 *  The Center for Sustainability at Penn State
 *  The Pennsylvania State University
 *  University Park, PA 16801  
 *  
 *	@author Mike Holzer
 */

    $currentUser = BLANK; // current user ID: the one to dismiss, characteristics (forum subscriptions, course registrations, etc. will be shifted to $newUser
    $newUser = BLANK; // new user ID: the one to keep, will inherit all characteristics from the "current user ID"
    define('PRIMARY_KEY','id');
    
    // Report all PHP errors
    error_reporting(E_ALL);
    
    // Same as error_reporting(E_ALL);
    ini_set('display_errors', 'On');
    ini_set('error_reporting', E_ALL);

    require('config.php');
    require_once("$CFG->libdir/blocklib.php");
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/accesslib.php');
    require_once($CFG->libdir.'/weblib.php');
    
    /**
     * Merges the grade_grades table for two users.
     * 
     * The intial problem in this table, grade_grades is that we have a 
     * compound key on userid and itemid which means we can't just update 
     * the userid because it's likely that the 2 users have completed at 
     * least some of the same activities.
     * 
     * What we're going to attempt to do is to look for the 2 users 
     * completing the same activity. If we find a match, we'll delete the 
     * record for the old user. That record can still be found in the 
     * grade_grades_history table so we won't be losing any history. RELAX.
     * 
     * Any activity completed by one but not the other will be updated in 
     * the normal process.
     *     	 
     * @param $newId		The user inheriting the data 
     * @param $currentId	The user being replaced
     * @param $recordsToModify	The list of records to be updated to the new user. Passed by reference
     */
    function mergeGrades($newId,$currentId,$recordsToModify)
    {
    	global $CFG,$DB;
    	
    	$sql = 'SELECT id, itemid, userid from '.$CFG->prefix.'grade_grades WHERE userid in ('.$currentId.','.$newId.')';
    	$result = $DB->get_records_sql($sql);

    	$itemArr = array();
    	$idsToRemove = array();
    	foreach($result as $id=>$resObj)
    	{
    		$itemArr[$resObj->itemid][$resObj->userid] = $id;
    	}
    	
    	foreach($itemArr as $itemId=>$itemInfo)
    	{
    		if(sizeof($itemInfo)!=2){continue;}//if we don't have 2 results, then these users did not both complete this activity.
    		$idsToRemove[] = $itemInfo[$currentId];
    	}
    	$idsGoByebye =  implode(',',$idsToRemove);	
 		$sql = 'DELETE FROM '.$CFG->prefix.'grade_grades WHERE id IN ('.$idsGoByebye.')';
 		if($idsGoByebye && $DB->execute($sql))
 		{
 			//ok, now remove those ids from the greater records list. 
 			for($i=0;$i<count($recordsToModify);$i++)
 			{
 				if(in_array($recordsToModify[$i],$idsToRemove))
 				{
 					unset($recordsToModify[$i]);
 				}	
 			}	
 		}		
    }
    /**
     * Disables course enrollments for the old user.
     *  
     * The user_enrolments table is similar to grade_grades in that it also 
     * has a compound unique key. The approach here is not to replace the 
     * user in the case of a duplicate, but to disable the old user for that 
     * particular course.
     *  
     * @param $newId		The user inheriting the data 
     * @param $currentId	The user being replaced
     */
    function disableOldUserEnrollments($newId,$currentId)
    {
    	global $CFG,$DB;

    	$sql = 'SELECT id, enrolid, userid,status from '.$CFG->prefix.'user_enrolments WHERE userid in ('.$currentId.','.$newId.') AND status !=2';
    	$result = $DB->get_records_sql($sql);
		if(empty($result)){echo "No enrollments found<br/>";return;}
		
    	$enrolArr = array();
    	$idsToDisable = array();
    	$enrollmentsToUpdate = array();
    	foreach($result as $id=>$resObj)
    	{
    		$enrolArr[$resObj->enrolid][$resObj->userid] = $id;
    	}
    	foreach($enrolArr as $enrolId=>$enrolInfo)
    	{
    		if(sizeof($enrolInfo)!=2)//if we don't have 2 results, then these users did not both complete this activity.
    		{
    			if(key($enrolInfo)==$currentId)//if we have the old user, we have to assign this course to the new user. 
    			{
    				$enrollmentsToUpdate[] = $enrolInfo[$currentId];
    				continue;
    			}
    			else{continue;}//we don't have anything here for this course. We actually shouldn't get to this point ever. 
    		}
    		$idsToDisable[] = $enrolInfo[$currentId];
    	}
    	
		if(!empty($enrollmentsToUpdate))//its possible we won't have any
		{
	    	//First, lets move the courses belonging to the old user over to the new one.
	    	$updateIds = implode(',',$enrollmentsToUpdate);
	    	$sql = 'UPDATE '.$CFG->prefix.'user_enrolments SET userid = "'.$newId.'" WHERE id IN ('.$updateIds.')';
	    	if($DB->execute($sql))
	    	{
	        	echo "<p style=\"color:#0C0;\">Enrollment update OK</p>";
	        }
	        else 
	        {
	        	echo "<p style=\"color:#F00;\">ERROR : Table update NOT OK!!! : ".mysql_error()."</p>";
	        }
		}
    	//ok, now let's lock this user out from using the common courses.
    	if(!empty($idsToDisable))
    	{
	    	$idsGoByebye =  implode(',',$idsToDisable);	
	    	$sql = 'UPDATE '.$CFG->prefix.'user_enrolments SET status = "2" WHERE id IN ('.$idsGoByebye.')  AND status = 0';
	    	if($DB->execute($sql))
	    	{
	        	echo "<p style=\"color:#0C0;\">Old course enrollments update OK</p>";
	        }
	        else 
	        {
	        	echo "<p style=\"color:#F00;\">ERROR : Table update NOT OK!!! : ".mysql_error()."</p>";
	        }
    	}
    }
    
    require_login();
    require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));
    if (!is_siteadmin())
    {
    	die('You must be admin to use this feature');
    }
    
    print_header_simple();

    echo("<h1>Be careful!</h1>");
    echo("<h2>Merging $currentUser into $newUser</h2>");
	$tableNames = $DB->get_records_sql('SHOW TABLES like "'.$CFG->prefix.'%"');
	$numtables = sizeof($tableNames);
    echo "<h2>".$numtables. " tables found in database &quot;".$CFG->dbname."&quot;</h2>";

	//these are tables we don't want to modify due to logging or security reasons.
    $tablesToSkip = array($CFG->prefix.'user_lastaccess',$CFG->prefix.'user_preferences',$CFG->prefix.'user_private_key');
    
	  foreach($tableNames as $table_name=>$objWeCanIgnore){
        if(!trim($table_name)) { //This section should never be executed due to the way Moodle returns its resultsets
            echo("Skipping due to blank table name.<br />");
            continue;
        } elseif(in_array($table_name,$tablesToSkip)) {
        	echo("<p style='color:#FFE600;'>For logging or security reasons we are skipping <strong>$table_name</strong>. 
        		  <br/>To remove these entries, delete the old user once this script has run successfully.<br /></p>");
            continue;
        } else {
            echo("Processing table = $table_name<br />");
        }

		$columnList = "SHOW COLUMNS FROM ".$table_name." where Field IN ('userid','user_id','id_user')";
		
		$columns = $DB->get_records_sql($columnList);
	
        if(count($columns)!==1){continue;}//no matching or multiple matching fields in this table, move onto the next table.
		//Now we have the appropriate fieldname and we know what to update!
        $field_name = array_shift($columns)->field;//get the fieldname
		
        $recordsToUpdate = $DB->get_records_sql("SELECT `".PRIMARY_KEY."` FROM `".$table_name."` WHERE `".$field_name."` = '".$currentUser."'");
		if(count($recordsToUpdate)==0){continue;}//this userid is not present in this table
		
		$recordsToModify = array_keys($recordsToUpdate);//get the 'id' field from the resultset
		
		if($table_name == $CFG->prefix.'grade_grades')//Grades must be specially adjusted.
		{
			/* pass $recordsToModify by reference so that the function can take care of some of our work for us */
			mergeGrades($newUser,$currentUser,&$recordsToModify);
		}
        if($table_name == $CFG->prefix.'user_enrolments')//User enrollments must be specially adjusted
		{
			disableOldUserEnrollments($newUser,$currentUser);
			continue;//go onto next table
		}
		$idString = implode(',', $recordsToModify);
         $updateRecords = "UPDATE `".$table_name."` SET `".$field_name."` = '".$newUser."' WHERE `".PRIMARY_KEY."` IN (".$idString.")";
        if($DB->execute($updateRecords))
        {
        	echo "<p style=\"color:#0C0;\">Table update OK</p>";
        }
        else 
        {
        	echo "<p style=\"color:#F00;\">ERROR : Table update NOT OK!!! : ".mysql_error()."</p>";
        }
    }
    #TODO: An optional step at this point would be to disable or delete altogether the $currentUser. 
    
    echo "<h1>DB update OK</h1>";
    $OUTPUT->footer();
  
?>

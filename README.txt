Merge users script for Moodle 1.9
=================================


This script will merge two Moodle user accounts, "user A" and "user B". 
The intent of the script is to assign all activity & records from user A to 
user B. This will give the effect of user B seeming to have done everything 
both users have ever done in Moodle. The basic function of the script is to 
loop through the tables and update the userid of every record from user A to  
user B. This works well for most tables. We do however, have a few special 
cases:
 
	Special Case #1: The grade_grades table has a compound unique key on userid 
    and itemid. This prevents a simple update statement if the two users have 
    done the same activity. What this script does is determine which activities 
    have been completed by both users and delete the entry for the old user 
    from this table. Data is not lost because a duplicate entry can be found in 
    the grade_grades_history table, which is correctly updated by the regular 
    processing of the script.   
   
	Special Case #2: The user_enrolments table controls which user is enrolled 
    in which course. Rather than unenroll the old user from the course, this 
    script simply updates their access to the course to "2" which makes them 
    completely unable to access the course. To remove these records all 
    together I recomend disabling or deleting the entire old user account once 
    the migration has been successful.
  
    Special Case #3: There are 3 logging/preference tables 
    (user_lastaccess, user_preferences,user_private_key) which exist in 
    Moodle 2.0. This script is simply skipping these tables since there's no 
    legitimate purpose to updating the userid value here. This would lead to 
    duplicate rows for the new user which is silly. Again, if you want to 
    remove these records I would recommend deleting the old user after this 
    script runs sucessfully.  
  
BEFORE YOU RUN THIS SCRIPT, BACK UP YOUR DATABASE.
There is no provision in this script for rollbacks, so if something 
were to fail midway through you will end up with a half-updated DB.
This. Is. Bad. Practice safe script. Always backup first.
 
 
MINIMUM REQUIREMENTS:
- MySQL v5.0
- Moodle v2.0
 
How to proceed:
- be careful not to let anyone except you execute this script on your Moodle site;
- fill the "BLANK"s (on lines 79-80): replace "BLANK" with faulty user ID and good user ID;
- if you wish to do things right (ie be safe if anything turns out badly):
	- stop your Moodle server (at least MySQL);
    - backup the complete Moodle database;
    - restart your Moodle server, to make it accessible again;
    - if anything goes wrong in the next steps, you may always restart your 
      MySQL server from the last backup.
    - verify your backup script contains SQL. Again, if this script fails 
      and you have no backup, you're sadly SOL. 
- upload this script to your Moodle site 
  (say, http://moodle.example.com/mergeusers.php);
- login as an admin user
- visit the script (ie: http://moodle.example.com/mergeusers.php) 
  with your web browser;
- let this script do its thing (it should finish saying "DB update OK" at the bottom);
- check the two user accounts, and verify that :
    - the "bad one" is no more associated with any course;
    - the "good one" is associated with the courses that were associated with the "bad one" before;
- disable or delete the "bad" account, so that the user cannot log into this one any more;
- check the login details of the "good" account (now the only one), and provide the user with his/her new login details (if needed).

Based on the mergeusers_v2.php script written by Nicolas Dunand.
Updated for Moodle 2.0 by Mike Holzer [mike.holzer AT psu DOT edu]

Mike Holzer
Web Technology Manager	
The Center for Sustainability at Penn State
The Pennsylvania State University
University Park, PA 16801  

@author Mike Holzer


Merge users script for Moodle 2.x
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
- MySQL v5.x or MSSQL or Postgres
- Moodle v2.x
 
Based on the mergeusers_v2.php script written by Nicolas Dunand.
Updated for Moodle 2.0 by Mike Holzer [m DOT e DOT holzer AT gmail DOT com]
Moodle 2.x report by Forrest Gaston
Plugin maintained by Nicolas Dunand [nicolas.dunand AT unil DOT ch]



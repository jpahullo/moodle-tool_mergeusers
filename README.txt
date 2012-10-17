Merge users script for Moodle 1.9
=================================

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
     

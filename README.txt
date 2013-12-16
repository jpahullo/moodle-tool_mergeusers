Merge users script for Moodle 2.x
=================================


This script will merge two Moodle user accounts, "user A" and "user B".
The intent of the script is to assign all activity & records from user A to
user B. This will give the effect of user B seeming to have done everything
both users have ever done in Moodle. The basic function of the script is to
loop through the tables and update the userid of every record from user A to
user B. This works well for most tables. We do however, have a few special
cases:

* Special Case #1: The grade_grades table has a compound unique key on userid
    and itemid. This prevents a simple update statement if the two users have
    done the same activity. What this script does is determine which activities
    have been completed by both users and delete the entry for the old user
    from this table. Data is not lost because a duplicate entry can be found in
    the grade_grades_history table, which is correctly updated by the regular
    processing of the script.
* Special Case #2: The user_enrolments table controls which user is enrolled
    in which course. Rather than unenroll the old user from the course, this
    script simply updates their access to the course to "2" which makes them
    completely unable to access the course. To remove these records all
    together I recomend disabling or deleting the entire old user account once
    the migration has been successful.
* Special Case #3: There are 3 logging/preference tables
    (user_lastaccess, user_preferences,user_private_key) which exist in
    Moodle 2.0. This script is simply skipping these tables since there's no
    legitimate purpose to updating the userid value here. This would lead to
    duplicate rows for the new user which is silly. Again, if you want to
    remove these records I would recommend deleting the old user after this
    script runs sucessfully.
* Special Case #4: mod/journal plugin has a record per user and journal on
    journal_entries table. In case there is a record for both users, we delete
    the record related to the old user. For the rest of cases, this operates as usual.
* Special Case #5: groups_members table has a record per user and group.
    Updating always the old user id for the new one is incorrect if both users
    appear in that group. In that case, this plugin deletes the record related
    to the old user. For the rest of cases, this plugin operates as usual.
* Special Case #6: course_completions table has a record per user and course.
    Updating always the old user id for the new one is incorrect if both users
    appear in that group. In that case, this plugin deletes the record related
    to the old user. For the rest of cases, this plugin operates as usual.
* Special case #7: message_contacts table has a record per user and contact id,
    which is again a user.id. If replacing the old id by the new one means
    index conflict, this means actually that the resulting record already exists,
    so we can securely remove the old record. In addition, this checking is performed
    for both column names (userid and contactid) looking for matching on both
    in the same way.


Command-line script
---

A cli/climerger.php script is added. You can now perform user mergings by command line having
their user ids.

You can go further and develop your own CLI script by extending the Gathering interface
(see lib/cligathering.php for an example). Ok, but let us explain how to do it step by step:

1. Develop a class, namely MyGathering, in lib/mygathering.php, implementing the interface Gathering.
Be sure the class name and the filename are the same, but filename all in lowercase ending with ".php".
See lib/cligathering for an example.
2. Create or edit the file config/config.local.php with at least the following content:
```php
<?php

return array(

    // gathering tool
    'gathering' => 'MyGathering',
);
```
3. Run as a command line in a form like this: *$ time php cli/climerger.php*.


Correct way of testing this plugin
---

These are the main steps to do so:

1. You should have a replica of your Moodle instance, with a full replica of your Moodle database.
2. Run a sufficient amount of user merging to check if anything goes wrong.
3. What if...?
    1. ... all was ok? You are almost secure all will be ok also in your production instance of Moodle.
    Above all, check **if your database type and version supports transcations**. If so,
    **no action will actually be committed if something went wrong**.
    2. ... something went wrong? There are several reasons for that:
        1. Non-core plugins installed on your Moodle and not assumed in this plugin.
        2. Local database changes on Moodle that may affect to the normal execution of this plugin.
        3. Some compound index not detected yet.

If in your tests or already in production something went wrong, please, report the error log on the
official plugin website on moodle.org. And if you have some PHP skill, you can try to solve it
and share both the error and the patch to solve it ;-)


Common sense
---

Before running this plugin, it is highly recommended to back up your database.
That will help you to restore the state before any merging action was done.

This plugin stores a log for any user merging, with the list of actions done or errors produced.
But, there is no provision for automatic rollbacks, so if something
were to fail midway through you will end up with a half-updated database.
This. Is. Bad. Practice safe script. Always backup first.


Minimum requirements
---

- MySQL v5.x or MSSQL or Postgres
- Moodle v2.x


License
---

GNU GPL v3 or later. http://www.gnu.org/copyleft/gpl.html


Contributors
---

* Based on the mergeusers_v2.php script written by Nicolas Dunand.
* Updated for Moodle 2.0 by Mike Holzer [m.e.holzer AT gmail DOT com]
* Moodle 2.x report by Forrest Gaston
* Updated by Jordi Pujol-Ahulló (at SREd, Universitat Rovira i Virgili) with:
    * several compound index on database tables,
    * selector for the type of user identification (username, id, idnumber),
    * more Moodle-like web and code, including web renderer,
    * config.php with current configuration settings, used for merging,
    * config.local.php to include local settings on your Moodle instance,
    * log of any merging action in database for further reference
* Plugin maintained by Nicolas Dunand [nicolas.dunand AT unil DOT ch]

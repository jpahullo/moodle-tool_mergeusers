Release notes
=============

2023061900

1. 2023-06-19 #247 - Fix proper support for Moodle 4.2, thanks to Matthias Opitz.

2023040402

1. 2023-04-13 #243 - remove unused class with API inconsistence.

2023040401

1. 2023-04-04 #211 - add CSV export for merged user logs; added mergedbyuserid field, thanks to Mark Johnson.

2023040400

1. 2023-04-04 Fix CI to run only for supported versions, supporting Moodle 3.11 and up.
2. 2022-12-15 #228 - Add compound indexes for customcert_issues table, thanks to Leon Stringer.
3. 2021-08-01 #197 - Use Github Actions, remove Travis CI usage.

2021072200

1. 2021-07-23 #193 - Allow automatic Moodle Plugins release when defining git tab.
2. 2021-07-14 #175 - Reaggregate completion for target user, thanks to Andrew Hancox.
3. 2021-07-14 #194 - Update unit tests for Moodle 3.10+, thanks to Alistair Spark.
4. 2021-07-02 #177 - Move observer functions into classes to bypass include file error, thanks to Andrew Madden.
5. 2021-06-10 #181 - Guarantee processing any grade item.
6. 2020-02-23 #169 - Fix wrong entries deleted in case of conflict, thanks to Tim Schroeder.
7. 2019-08-18 #166 - Support for duplicated assign submissions and other fixes.
8. 2019-08-16 #67 - Improve and clean up settings.php.
9. 2019-08-16 #163 - Force user to keep not to be suspended.
10. 2019-08-16 #161 - Split in chunks the list of record ids to delete/update to prevent buffer overflow on SQL sentences.
11. 2019-08-15 #147 - Config: Add logstore_standard_log columns related to user.id.
12. 2019-08-15 #151 - Config: Add composed keys for wikis.
13. 2019-08-15 #146 - Fix searching by user.id on pgsql.
14. 2019-08-15 #152 - Support any supported Moodle database.

For a more extense list of changes, [see git logs for changes before April 2019](https://github.com/jpahullo/moodle-tool_mergeusers/commits/master).

Contributors
============

Maintained by:

* [Jordi Pujol-Ahulló](https://recursoseducatius.urv.cat).
* [Nicolas Dunand](https://moodle.org/plugins/browse.php?list=contributor&id=141933).

[See all Github contributors](https://github.com/ndunand/moodle-tool_mergeusers/graphs/contributors)

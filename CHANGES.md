# Release notes

If not specified, each change is performed in the version date.
It means that if version is YYYYMMDDOO, the change was performed on YYYY-MM-DD.

## 2026080800

1. improvement: #402: profile-field search is now gated by its own master
   switch, `tool_mergeusers/searchbyprofilefieldsenabled` (off by default),
   separate from the field allow-list itself. This avoids overloading the
   allow-list's emptiness with dual meaning ("nothing selected yet" vs.
   "deliberately disabled"): the multiselect stays a plain list of fields,
   and the toggle alone decides whether it is consulted at all.

## 2026080704

1. improvement: #402: allow searching for a user to merge by a custom user
   profile field (e.g. an institution's "employee ID" field), in addition to
   the existing username/email/etc. search. Which profile fields are
   searchable is opt-in via a new `tool_mergeusers/searchbyprofilefields`
   setting (empty by default). Originally proposed in #231. Thanks to
   @Tsheke for their contributions.

## 2026080703

1. cleanup: #414: remove the `E_STRICT` reference in `cli/listuserfields.php`. `E_STRICT`
   has been part of `E_ALL` since PHP 5.4, and its notices were progressively
   reclassified into `E_NOTICE` starting in PHP 7.0 (completed in PHP 8.0); the
   constant itself is now deprecated in PHP 8.4. `E_ALL` already covers everything
   it used to add. Thanks to @danowar2k for raising the issue.

## 2026080702

1. improvement: #416: the "find users to merge" search on the main page now caps
   how many matching users are shown at once, defaulting to 25
   (`tool_mergeusers/maxsearchresults`, a dropdown of 25/50/100/200). When a search
   matches more users than that, the first ones (sorted by last name, first name,
   same order as before) are still shown and can still be picked to merge as usual,
   alongside a message stating how many matched in total and asking to narrow the
   search - mirroring the precedent in Moodle core's role assignment page
   (`/admin/roles/assign.php`), but showing the capped results instead of hiding
   them outright. The warning sits between the results table and the "save
   selection" button (same form as the table), not above the table nor after the
   button, so it only draws attention when actually needed. Thanks to @lcaylat
   for raising the issue.
2. improvement: #416: the search-results and review-before-merging tables no
   longer repeat the email/idnumber already shown in the "User" column. The review
   table shows the user's description on a line underneath the user info instead
   of as its own column, to help tell similar users apart when confirming who is
   about to be merged, without wasting space on very long descriptions. Long
   descriptions are collapsed to a short preview (core's `shorten_text()`, so
   markup is never cut in half) with the full text reachable on demand via a
   native `<details>` element - no JavaScript needed.
3. bugfix: #416: both tables are now left-aligned under their section heading,
   instead of shifted towards the centre of the page.

## 2026080701

1. improvement: #363: CI's `codechecker` step is now a hard gate
   (`codechecker_max_warnings: 0`): a coding-standard warning fails the build, not
   just an error. It turns out this step (and `phpdoc`) had actually been enabled
   since `690a5cf`, just never enforced strictly and never documented as such.
2. improvement: #363: local development now has its own `.bin/moodle-plugin-ci.phar`
   (gitignored, auto-synced to the latest GitHub release at most once a day), the
   same tool CI itself is built on. `make ci-<step>`/`make ci-local` run every
   `moodle-ci.yml` step that has a local equivalent (`phplint`, `codechecker`,
   `validate`, `savepoints`, `mustache`, `phpdoc`) and are now the authoritative way
   to check "will CI pass" - superseding the old `local_codechecker`-based `phpcs`
   target (removed; `make phpcbf` stays for auto-fixing, moodle-plugin-ci has no
   equivalent). `local_codechecker`'s bundled `moodle-cs` can lag behind what
   moodle-plugin-ci installs and disagree with it on specific sniffs, so it should no
   longer be used to judge CI outcomes, including with the stricter `moodle-extra`
   standard, which CI never checks at all.
3. improvement: #363: the `Makefile` derives every path (plugin dir, Moodle dirroot,
   `vendor/`) from where the `Makefile` itself lives instead of hardcoding it, so it
   works the same on Moodle's classic dirroot layout and on 5.x's split `public/`
   docroot. `docker exec` wrapping is now based on whether `/.dockerenv` indicates
   make is actually running inside the dev container, rather than solely on whether
   `.env` defines `container_name` - so the same `.env` works unmodified whether
   invoked from the host or from a shell already inside the container. Also fixed a
   latent bug where `ifndef x:`'s stray trailing colon made those blocks
   unconditionally true, silently ignoring any override from `.env`.
4. fix: #363: `renderer::results_page()`'s phpdoc documented `$status` as `string`,
   but the parameter is `?string`; caught by the new `make ci-phpdoc`.

## 2026080700

1. improvement: #420: the merge log listing (`view.php`) is now paginated instead of
   rendering every merge log row at once, using a configurable page size
   (`tool_mergeusers/logpagesize`, a free-text setting with a minimum of 1 and no
   upper bound, default 100) and core's `\core\output\paging_bar` - no JavaScript
   involved. A new search box replaces the browser `Ctrl+F` that no longer covers
   every row once paginated: it matches the numeric id/touserid/fromuserid/
   mergedbyuserid, the current username/firstname/lastname/email of the users
   involved, their combined full name (firstname + lastname together, so a
   "firstname lastname"-shaped term still matches even with a two-part surname, as
   is common in Spanish naming), the merge status, and the beginning of the stored
   log content (`log`, capped by the new `tool_mergeusers/logsearchmaxlength`
   setting - also free-text, minimum 100, no upper bound, default 1000 characters) -
   which keeps a username/email snapshot from merge time, so a merge involving a
   user who has since been deleted can still be found by their old username. The CSV
   export (`view.php?export=1`) now honors an active search (exporting only matching
   rows), but is never limited by pagination - it always downloads every matching
   row. A new `mdl_toolmerg_tim_ix` index on `timemodified` (the default sort column)
   was added to support this.

## 2026080600

1. improvement: #390: `gathering::current()`/`key()` now declare `merge_request`/`int` return
   types instead of `mixed`; the searched-field hint values are now backed by
   `logger::SEARCHED_FIELD_USERNAME`/`SEARCHED_FIELD_IDNUMBER`/`SEARCHED_FIELD_EMAIL` constants
   instead of raw string literals. Thanks to @matthewhilton for raising the issue.

### UPGRADING

This version tightens the `gathering` interface's contract: `current()` now must return
a `merge_request` instance (instead of `mixed`) and `key()` must return an `int` (instead of
`mixed`). This is a **breaking change** for any custom `gathering` implementation (e.g. a
bulk/nightly merge process outside the web UI) that does not already return a `merge_request`
object from `current()`. Before upgrading, review any custom `gathering` in use and adapt its
`current()` method to construct and return a `merge_request` (setting `fromid`/`toid`, and
optionally the `fromsearchedfield`/`fromsearchedvalue`/`tosearchedfield`/`tosearchedvalue` hint
properties), and its `key()` method to return an `int`. Failing to do so will raise a fatal PHP
error ("Declaration ... must be compatible ...") the next time that gathering class is loaded.

## 2026080500

1. improvement: #393: merge logs now capture a normalized snapshot of both users' identity
   (username, email, idnumber, suspended/deleted) at merge time, replacing the previous
   scattered/duplicated identity text on the results page with a single consolidated "who
   was merged" table, including the shared capture timestamp.
2. improvement: #393: `classes/privacy/provider.php` is no longer a `null_provider`: now that
   merge logs store real identifying user data, GDPR erasure requests (`delete_data_for_user()`,
   `delete_data_for_users()`, `delete_data_for_all_users_in_context()`) properly erase the
   affected snapshot fields, marking each erased side with `erasedforgdpr`/`timeerased` so it
   stays visually distinct on the results page from a side that simply never had any data.
3. improvement: #393: an external "gathering" (e.g. a bulk/nightly merge process outside the
   web UI) can now optionally report which field and value it searched for when it could not
   resolve a user to a real id, shown on the results page instead of a bare "not found"
   message. Fully optional and backward compatible: any existing gathering that does not
   expose this keeps working unchanged.
4. fix: #393: the merge log list showed the generic "user was deleted" text for a side that
   was never a real user id (id <= 0, e.g. an unresolved gathering search) instead of the
   clearer "not found at merge time" message the results page already used for the same case.
5. fix: #393: a merge where NEITHER side could be resolved (both ids <= 0, a real case for
   gatherings that search both users and find neither) was misreported as "the same user",
   hiding that both sides were actually unresolved.
6. fix: #393: `db/upgrade.php`'s `2026080100` normalization step could turn a genuinely
   legacy `log` row (the original pre-2025-11-12 flat action-list shape, with no
   `user_snapshots`/`actions` wrapper at all) into `{"0": ..., "1": ..., "user_snapshots":
   {...}}` — a shape with no `actions` key, so the results page silently showed no
   recorded actions for these merges. Added a new upgrade step that moves any leftover
   top-level numeric keys into `actions`, without touching `user_snapshots`.

### UPGRADING

This version changes the internal JSON structure stored in the `tool_mergeusers.log` column
(see the improvements above) to support the new snapshot and privacy features. Upgrading runs
two data-migration steps over every existing row of the `tool_mergeusers` table (`db/upgrade.php`
savepoints `2026080100` and `2026080500`), so **the upgrade can take several minutes on
installations with a large merge history** — around 3 minutes for ~16,000 rows in our own
testing. Plan accordingly if triggering the upgrade from the web UI on a busy site; running it
via CLI (`php admin/cli/upgrade.php`) is recommended for large installations. No manual action
is required beyond that: both steps are idempotent, so the migration is safe to run more than
once (e.g. if interrupted and retried).

Also worth calling out while reviewing this release: merging users can be queued as an adhoc
task from the web UI when the `enableadhocmerge` setting is turned on (see the `2026052713`
entry below). CLI/CRON-driven bulk merges (`cli/climerger.php`, or any custom `gathering`)
never go through the adhoc task queue — they always merge synchronously, regardless of that
setting. This distinction only applies to merges triggered from the web interface, at least
for now.


## 2026080400

1. fix: #393: CI: codechecker errors (blank line before `finally`; alphabetical order of `provider::implements`).
2. fix: #393: adhoc task notification crashed with undefined method `moodle_page::has_context()`; use
   `set_context()` on the system context instead.
3. fix: #393: PHPUnit risky-test warnings from output buffers left open when `merge_users_task::execute()` failed
   inside a test.
4. fix: #393: `user_merger::merge_users()` now catches `Throwable` instead of `Exception`, so a real merge failure
   can never fire the `user_merged_failure` event twice.
5. improvement: #393: `merge_users_task` now enforces, by default and with no configuration required, that only
   one merge runs at a time and that a failed merge is never retried — guaranteeing chained merges (e.g. A into B,
   then B into C) always execute in the order they were requested.
6. improvement: #393: settings page now shows a note only when an administrator has explicitly overridden the
   default concurrency limit above via `$CFG->task_concurrency_limit`, since doing so can let merges run out of
   order; see `README.md` for details.
7. fix: #393: `db/upgrade.php` savepoints for the `status`/`timecreated` schema changes were dated in 2025, before
   the officially released `2026052713`. Anyone upgrading from that release had `$oldversion` already past those
   savepoints, so they were silently skipped and the schema never actually changed. Renumbered them past
   `2026052713`; added a test asserting savepoints stay ordered and within `$plugin->version`.


## 2026052713

1. 2025-10-07: improvement: #378: add support for asynchronous user merging via adhoc task.
   1. New adhoc task `merge_users_task` allows queuing merge operations to run during cron execution.
   2. New setting `enableadhocmerge` to enable/disable adhoc task-based merging from web interface.
   3. When adhoc merge is enabled, web-based merges are queued and processed asynchronously,
      reducing timeout risks for large merge operations.
   4. Thanks to @nihaalshaikh and @luukverhoeven.
4. fix: #409: CI: codechecker passes for all plugin files.


## 2026052700

1. fix: #411: regrading after merging users: prevent errors when plugin is uninstalled or
plugin table is missing. Thanks to @terryaulenbach for reporting the issue.
   1. Skipped grade records from uninstalled plugins at database level, improving performance.
   2. Aborting merge when plugin is installed but table is missing, treating it as critical database corruption consistent with other data integrity checks.
   3. Added tests covering all edge cases.


## 2026050500

1. task: #407: add support for Moodle 5.2.
2. fix: #405: remove execution permissions for db/install.xml and db/upgrade.php.
3. fix: #399: fix suspended image path. Thanks @matheus1002 for reporting.


## 2025102100

1. bug: #379: remove table lines for >= Moodle 5.0 and Bootstrap 5.0. Thanks @lucaboesch.


## 2025101701

1. task: #383: Moodle 5.1 compatible.


## 2025101700

1. fix: #381: add all user-related compound indexes into default plugin settings.
   1. default_db_config.php updated manually with structured section about compound indexes.
   2. listuserfields.php CLI script improved to list all user-related compound indexes. This script must help administrators to identify other compound indexes that affect their Moodle instances.


## 2025101400

1. fix: #382: ensure grade_grades table is merged properly. Thanks Daniel Tomé.
   1. Added tests for the new grade_grades table merger.
   2. Improved some existing tests.
   3. Improve Makefile to let run phpcs/phpcbf more easily.


## 2025092100

1. improvement: #372: add output from last steps of regrading and reaggregation of course completions.
   Also, reaggregation of course completion now happens inside the time of the merge process, and not after as before.
2. task: #362: remove YUI code. Simplified javascript code to the maximum.


## 2025091800

1. fix: #371: listuserfields.php CLI scripts supports tables that does not exist on the XML database schema.


## 2025090401

1. fix: #367: database settings tab did no show properly
   the default nor calculated settings.
2. fix: #369: add PHP attributes to hook.


## 2025082301

1. improvement: #360: new class added to manage session-based
   users selection when merging users from web administration.
2. improvement: #358: add "suspended" tag besides the
   user detail on all pages (including user search, user review and logs).
   Single log page now shows the full user detail, as in the rest of pages.
3. fix: #358: users selection table showed always the user
   detail of the user to remove. Now, it shows properly both users.
   It was detected while working on the improvement of #358.


## 2025082200

1. improvement: #356: code reorganization on the whole plugin.
   1. Placed all suitable file under `/classes/` for autoloading.
   2. Revisited all files (except `/tests/`) with phpcs (using `local_codechecker`)
   3. Removed content from `lib.php` in favour of `settingslib.php` and
      a new class `database_transactions::are_supported()`.
   4. Added a `Makefile` with some targets for helping while developing.
   5. All tests passes again.
   6. All clicks on the web work again.
   7. Moved the link to see merge logs into the `Reports` administration menu.

### UPGRADING

When upgrading to this version, you have to choose one of these paths:

1. **In case you have local plugin customizations:** you must check twice
   the new plugin structure since there has been a major refactor
   of the whole plugin. The `lib/` directory was removed, and
   most of the plugin classes were moved inside the `classes/` directory
   for a better code organization and with the benefit of autoloading.
   Also, we removed the vast majority of functions from the `lib.php`,
   leaving there only the necessary Moodle callbacks.
   Update your local customizations appropriately according to new
   classes and file structure.
2. **In case you DO NOT have local plugin customizations:** you can freely
   upgrade the plugin without worrying.


## 2025082000

1. #354: ensure that setting `tool_mergeusers/uniquekeynewidtomaintain`
   applies.


## 2025081900

1. improvement: #350: added hook to proceed with operations after
   all tables have been merged, and before registering the end of the merge.
   1. If these hook's callbacks are invoked, it means that all went ok
      and table mergers processed the merge with success till now.
   2. The callbacks for this hook are meant to process any kind of operations
      from Moodle internals or plugin specific tasks, that are transversal,
      (operations not specific for a single table) or any kind of
      aggregation operation, not updated by the table mergers.
   3. To provide you an example, we have moved the regrading of the users and
      the course recompletions into callbacks for this hook.
   4. We think this hook will help Moodle and plugin developers to adjust the
      merge users tool to better fit any Moodle instance (with a variable
      number of custom Moodle changes and plugins).

### NOTE
Actually, with callbacks for both hooks, Moodle core and plugins
can make work this plugin as they need to merge users properly. Why?
This plugin provides a generic way to merge users, but internals from
Moodle core (subsystems, and so) and plugins really know how user's
information is managed. So, their maintainers have the full knowledge
and they can provide callbacks for both hooks:
1. Callbacks for `add_settings_before_merging` hook may help providing specific
 database-related settings: mainly table mergers (setting `tablemergers`),
 compound indexes (setting `compoundindexes`) or user-related table columns
 (setting `userfieldnames`).
2. Callbacks for `after_merged_all_tables` hook may help providing specific
 post-processes.

### UPGRADING
**Just as a clarification:** The inclusion of the hooks does not alter the
way of using this plugin at all. It will behave as it did.

However, these two hooks provide you as a developer and maintainer of your
plugin or Moodle instance powerful tools to customize the behaviour of the merge,
just placing the necessary callbacks and related stuff in your own
code, to ensure merge users is processed properly.

## 2025081800

1. improvement: #348: added hook to load database-related settings.
   1. This is though to help Moodle and plugin developers to adjust their code to help
      this plugin merge users properly.
   2. The settings that are loaded by this hook are those populated on the old
      `config/config.php` and `config/config.local.php` files. These files are not supported any more.
   3. The content of the old `config/config.php` is now placed on `classes/local/default_db_config.php`.
      This must help this plugin maintainers to keep in a single place the default behaviour.
   4. Added tests to ensure the database-related settings are kept properly.
   5. Priority of the settings (more priority settings are kept, in front of subsequent settings):
      1. Custom admin setting: the set of settings with the highest priority.
         This must let administrators adjusting plugin behaviour at any time.
      2. Hook settings: settings populated from this hook's callbacks are the second set of
         settings in priority.
      3. Default settings: the plugin's default settings are kept as with the lowest priority.
         Any existing setting from hooks and custom settings will replace default ones.

## 2025081700

1. fix: #308: reportedly, extension `pcntl` seems to be loaded sometimes but its `pcntl_*` functions
 are not available. We have removed support for aborting for `Ctrl+C` (`SIGINT`) using `pcntl` extension.
 No panic: in several instances we have, we can cancel the CLI script execution with `Ctrl+C` without
 the `pcntl` extension loaded. It was necessary on initial version of this plugin. Nowadays it seems unnecessary.
2. improvement: #308: updated the CLI script help to show that when using `--alwaysrollback` option
 there is no loop for merging pairs of users.

## 2025081603

1. improvement: #244: allow resetting web user selection. Unified search and review tables.
 Added extra column on search table to show whether a user is already suspended (probably already merged).
2. improvement: #345: move config.local.php into a new admin setting, in JSON format, for being human-readable.
   1. Also, consider that setting with name `alwaysRollback` was renamed to `alwaysrollback` to unify the case insensitiveness
   of the rest of the configuration settings. It applies within the code and also on the CLI script parameters.

### UPGRADING

**Recommendation when upgrading:** Keep your `config/config.local.php` in place. It will help
updating the value of the new admin setting  `tool_mergeusers/customdbsettings` **automatically**,
without the need to convert your old `config/config.local.php` to JSON.
But, it is only a recommendation.

Otherwise, you will have to update that admin setting manually with the content of your previous
`config/config.local.php` on the new admin setting. To help you, you can use the
`tool_mergeusers\local\jsonizer::to_json($customsettings)` with an array with all your
`$customsettings`, and it will provide you the JSON content to place.

If you did not have any customization or file on `config/config.local.php`, you have to do nothing
with this upgrading step.

## 2025081500

1. improvement: #328: added support for Moodle 5.0.

## 2025081402

1. fix: #336: deleted users are excluded from everywhere when searching by and merging users.
2. improvement: #281: add debug info into log when no course module is found when regrading. Thanks to @Richardvi.
3. fix: #277: revisit all Moodle tables and user-related fields to
   update config.php settings. Provide a CLI script to help.

## 2025081300

1. fix: #275: make web user search work on any database engine. Thanks to @leonstr.
2. fix: #273: exclude deleted users from the web search. Thanks to @leonstr.

## 2025081205

1. fix: #329: set renumber quiz attempts as the default setting.
2. fix: #331: prevents web browser alert from leaving form page on the summary page.
3. improvement: #311: use Catalyst CI. Thanks to @matthewhilton.
4. improvement: #306: remove user profile fields; use profile page hook to show merging detail. Thanks to @matthewhilton.
5. fix: #68: move logger to proper namespace. Thanks to @matthewhilton.
6. improvement: #306: define new capability to see logs, either as admin or from user profile. Thanks to @matthewhilton.
7. improvement: #306: add information about whether a user is deletable from this plugin viewpoint.
8. improvement: #306: add settings item to inform that prior custom user profile fields added by this plugin still exist and should be deleted.
 There are no longer used nor updated.
9. improvement: #306: add PHP API to list deletable users.

### UPGRADING
Before upgrading to this version, please check your own automatization processes in case they use the plugin profile fields.
You should adapt them to use the logs from the plugin table `tool_mergeusers`. In this case,
we provide a PHP API on `tool_mergeusers\local\last_merge::list_all_deletable_users()`
to list all candidate users to be deletable. You can adapt your scripts starting from
this new API.

## 2025081100

1. fix: #315: replaced print_error() functions for moodle_exception. Thanks to @ManaElmountasir and @minhduchoang195.
2. bump version and update CHANGES.md
3. fix: #326: remove deprecation warnings on CLI script and tests. Thanks to @matthewhilton.

## 2025020505

1. bump version to make a new plugin version available for M4.5.

## 2025020503

1. fix: URL on old and new user profile fields definition.
2. improvement: CLI script now shows better log of merging operations.

## 2025020502

1. fix: #295: remove deprecation warning on CLIGathering, related to Iterator. Thanks to @CatSema.

## 2025020501

1. bump version, update CHANGES.md, and kept support only for M4.1.

## 2025020500

1. bump version and update CHANGES.md
2. new feature: #283: use custom profile fields to identify merged old and new users (to both users). Partly contributed thanks to @sampraxis and @ClausSchmidtPraxis on 2024-11-14. PHPUnit test ensures the behaviour is the expected.
3. fix: #294: pass tests on mod_assign again. Thanks to @leonstr.
4. fix: #304: set up again suspended image to merged source user.
5. fix: #253: CRLF codification passed to LF.
6. fix: #299: file content to get them properly uploaded into AMOS.

## 2025012300

1. #299: make lang file compatible with AMOS to be translatable.
2. #299: add $plugin->release property as releasing new plugin version warns it.

## 2025012200

1. #295: CliGathering: remove deprecation warnings from Iterator implementation.
2. #292: Bump plugin version and requires Moodle 4.5 onwards.
   1. CI passes on green, as before, for PHP 8.1, 8.2 and 8.3 only for core MOODLE_405_STABLE.
   2. version.php updated.
   3. Improve type detection on IDE.
   4. Uses new trait on the assign_test class.
3. #291: Make web administration work on merge users.
   1. Removed lines requiring "lib/outputcomponents.php" from two files.

## 2024060300

1. #268: CI: verify it is working from M4.1 to M4.4 and master. Solves #263 too.
2. #268: Fixed PHPDoc issues and revisited output from merger when running from CLI.

## 2023061900

1. #245: Add list of incremental changes on file CHANGES.md.
2. #247: Fix proper support for Moodle 4.2, thanks to Matthias Opitz.

## 2023040402

1. #243: remove unused class with API inconsistence.

## 2023040401

1. #211: add CSV export for merged user logs; added mergedbyuserid field, thanks to Mark Johnson.

## 2023040400

1. 2023-04-04 Fix CI to run only for supported versions, supporting Moodle 3.11 and up.
2. 2022-12-15 #228: Add compound indexes for customcert_issues table, thanks to Leon Stringer.
3. 2021-08-01 #197: Use Github Actions, remove Travis CI usage.

## 2021072200

1. 2021-07-23 #193: Allow automatic Moodle Plugins release when defining git tab.
2. 2021-07-14 #175: Reaggregate completion for target user, thanks to Andrew Hancox.
3. 2021-07-14 #194: Update unit tests for Moodle 3.10+, thanks to Alistair Spark.
4. 2021-07-02 #177: Move observer functions into classes to bypass include file error, thanks to Andrew Madden.
5. 2021-06-10 #181: Guarantee processing any grade item.
6. 2020-02-23 #169: Fix wrong entries deleted in case of conflict, thanks to Tim Schroeder.
7. 2019-08-18 #166: Support for duplicated assign submissions and other fixes.
8. 2019-08-16 #67: Improve and clean up settings.php.
9. 2019-08-16 #163: Force user to keep not to be suspended.
10. 2019-08-16 #161: Split in chunks the list of record ids to delete/update to prevent buffer overflow on SQL sentences.
11. 2019-08-15 #147: Config: Add logstore_standard_log columns related to user.id.
12. 2019-08-15 #151: Config: Add composed keys for wikis.
13. 2019-08-15 #146: Fix searching by user.id on pgsql.
14. 2019-08-15 #152: Support any supported Moodle database.

## Older changes

For a more extense list of changes, [see git logs for changes before April 2019](https://github.com/jpahullo/moodle-tool_mergeusers/commits/master).

# Contributors

Maintained by:

* [Jordi Pujol-Ahulló](https://www.urv.cat).
* [Nicolas Dunand](https://moodle.org/plugins/browse.php?list=contributor&id=141933).

[See all Github contributors](https://github.com/jpahullo/moodle-tool_mergeusers/graphs/contributors)

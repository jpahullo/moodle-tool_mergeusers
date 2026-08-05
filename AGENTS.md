# AGENTS.md — tool_mergeusers

Guidance for any AI coding agent working on this plugin.

## What this plugin does

Merges two Moodle user accounts: all activity and records from user A (user
to remove) are reassigned to user B (user to keep), so that B appears to
have done everything both users ever did. Useful in institutions where a
person's user identifier can change over time, leaving duplicate accounts
for the same real person.

Full behavioural documentation lives in `README.md`; version history and
per-release notes live in `CHANGES.md`. Supported Moodle versions and the
current plugin version are declared in `version.php`.

## Critical invariants — never break these

- **All-or-nothing commit.** `classes/local/user_merger.php::merge_users()`
  opens a single delegated transaction, processes every table, then runs the
  `after_merged_all_tables` hook. It commits **only if no errors were
  recorded** during any of that; otherwise it rolls back the entire
  transaction. Never introduce a code path that commits per-table or leaves
  a merge partially applied.
- **Always log and always fire an event.** Every merge attempt, successful
  or failed, must call `classes/local/logger.php::log()` and dispatch a
  `user_merged_success` or `user_merged_failure` event. Do not add a code
  path that skips either.
- **Respect the transaction-support gate.** `database_transactions.php` and
  the `tool_mergeusers/transactions_only` setting stop a merge from running
  on a database engine without transaction support, because a failed merge
  there cannot be rolled back. Do not remove or bypass this check.
- **Keep the human confirmation step.** The web UI only triggers a merge
  after an explicit review screen and JS confirmation dialog
  (`classes/output/review_user_form.php`, `index.php`). A merge is
  irreversible — never add a path that performs it without that explicit
  confirmation.
- **Prefer the extension points below over editing core merge logic.**
  Special-casing a table directly inside `user_merger.php` or the generic
  merger is an anti-pattern; there is almost always a better place for it
  (see next section).

## Architecture at a glance

- **Orchestrator**: `classes/local/user_merger.php`. Flow:
   1. init (scan every DB table for user-related columns, load config),
   2. per-table merge,
   3. the `after_merged_all_tables` hook,
   4. commit or rollback.
- **Table mergers** (`classes/local/merger/`): Strategy + Template Method.
   1. `table_merger` is the interface;
   2. `generic_table_merger` is the default implementation with
      protected methods meant to be overridden
      (`build_sql_query`, `process_duplicated_records_for_compound_index`,
      `update_all_records`, ...).
   3. Special cases subclass it:
      1. `grade_grades_table_merger`,
      2. `quiz_attempts_table_merger`,
      3. `assign_submission_table_merger`.
- **Configuration**, three layers merged in priority order (highest wins):
   1. `tool_mergeusers/customdbsettings` admin JSON setting.
   2. Callbacks registered for the `add_settings_before_merging` hook.
   3. `classes/local/default_db_config.php` — the plugin's built-in table
      registry (compound indexes, user-field column names, table-merger
      assignments, excluded tables).
- **CLI**: `cli/climerger.php` drives `classes/local/cli/gathering_merger.php`,
  which calls the same `user_merger` used by the web UI for every
  `(fromid, toid)` pair produced by a `gathering` (an `Iterator`). The
  default gathering prompts interactively; a custom `gathering`
  implementation can source pairs from anywhere for bulk/scheduled merging.
- **Logging/auditing**: a dedicated `tool_mergeusers` table (not Moodle's
  standard log store), managed by `classes/local/logger.php` and
  `classes/local/last_merge.php`; reviewable via `view.php` and `log.php`.
- No `amd/` or `templates/` directories — the UI uses classic
  `moodleform`/`html_writer`/renderer code, not Mustache/AMD.

## Preferred extension points

To support a new table or plugin without touching core merge logic:

1. Add an entry to `classes/local/default_db_config.php`
   (`compoundindexes` / `userfieldnames` / `tablemergers`) — submit this
   upstream if it's a general case.
2. Or register a callback for the `add_settings_before_merging` hook
   (site- or plugin-specific configuration) or the `after_merged_all_tables`
   hook (cross-table post-processing, e.g. regrading, completion
   recalculation).
3. For a genuinely new conflict-resolution case, subclass
   `generic_table_merger` and override only the specific protected method
   you need, then register the subclass in the `tablemergers` config.
4. Use `cli/listuserfields.php` (read-only) to detect tables or compound
   indexes missing from `default_db_config.php` locally in your Moodle instance. The list of plugins is specific on
   any Moodle instance: this will help you identify details
   not covered by the default configuration of this plugin.
   You can, then, register your custom settings in the `add_settings_before_merging` hook or update the
   administration settings from the UI.

## Coding standards

- Moodle coding style takes precedence over PSR-12 where they conflict.
- 4-space indentation, no tabs, Unix LF line endings, no trailing
  whitespace.
- Max line length 180 characters (aim for 132 where practical).
- Classes and functions: `snake_case`. Global functions use the
  `tool_mergeusers_` Frankenstyle prefix. Constants: `UPPERCASE`.
- PHPDoc required on all files, classes, methods and functions. GPL licence
  header required on every new source file.
- Database access only through the `$DB` API, with placeholders for all
  variables and table names wrapped in braces (`{tool_mergeusers}`) — never
  raw connections or string-concatenated SQL.
- Validate all input (`required_param()` / `optional_param()`, never raw
  superglobals), check `require_login()` / `require_capability()` /
  `require_sesskey()` on state-changing requests, and escape all output
  (`s()`, `format_string()`, `format_text()`).
- CI does **not** run `phpcs`, `phpdoc` or `grunt` checks automatically
  (they are explicitly disabled in the CI workflow) — run them locally
  before proposing a change: `make phpcs` / `make phpcbf` (these ignore
  `tests/`).

## Testing requirements

Merging users can be destructive and, once done, irreversible, and in some cases requires
deleting conflicting records to keep the data model correct. Because of
that, **no change to merge logic (table mergers, config layering, hooks,
CLI, logging) should be considered complete without a test covering it** —
this matches the project's own history, where almost every fix in
`CHANGES.md` is accompanied by new or updated tests.

Conventions:
- One PHPUnit file per concern under `tests/`, extending
  `\advanced_testcase`, calling `resetAfterTest(true)` in `setUp()`.
- Tag every test method's class with `@group tool_mergeusers` plus a more
  specific sub-group (e.g. `tool_mergeusers_config`); add `@covers` where it
  clarifies intent.
- Hook callback test doubles live in `tests/fixtures/`, as matched pairs of
  a `*_hooks.php` registration file and a `*_callbacks.php` implementation
  — follow the existing pattern when adding a new one.
- Run tests with `vendor/bin/phpunit -c [public/]admin/tool/mergeusers`,
  `vendor/bin/phpunit --group tool_mergeusers[_subgroup] --testdox`, or
  `make pass-tests`.

### Known coverage gaps — priorities for future work

1. `classes/privacy/provider.php` has no test at all, despite the plugin's
   entire purpose being to manipulate personal user data at scale.
2. Transaction atomicity/rollback has no direct test beyond the
   `--alwaysrollback` CLI flag; `database_transactions.php` itself is
   untested.
3. `classes/local/merger/finder/assign_submission_db_finder.php` — the real
   DB-backed finder — is never exercised by tests; only
   `in_memory_assign_submission_finder.php` is used.
4. No tests for the `user_merged_success` / `user_merged_failure` events or
   for the `olduser` / `keptuser` observers.
5. Lower-level CLI classes (`gathering_merger`, `merge_request`,
   `cli_gathering`) are only touched indirectly via `clioptions_test.php`.

Any change that touches these areas should add the missing coverage rather
than extend the gap.

## Git and release conventions

- Repository: `github.com/jpahullo/moodle-tool_mergeusers`.
- Commit messages follow `#<github-issue-number> - <short description>`,
  every change tied to a GitHub issue.
- Plugin version is a Moodle-style timestamp `YYYYMMDDvv`, tracked in both
  `version.php` and `CHANGES.md`; releases happen roughly monthly to
  bimonthly.
- Pushing a tag matching `2*` triggers the `moodle-release.yml` workflow,
  which publishes the release to the Moodle plugins directory.
- **Never edit a `db/upgrade.php` savepoint once it has been committed** —
  not even before it has been released or tagged. Any environment (a
  developer's local install included) may already have run it, and Moodle
  only re-runs an upgrade step whose savepoint number is newer than the
  site's currently stored version; editing an already-executed step's body
  silently never re-applies the edit anywhere it already ran, and the only
  fix is a manual plugin downgrade + re-upgrade. Any further change to the
  upgrade logic — even one line — must be a brand new `if ($oldversion <
  ...)` block with its own new savepoint number.

## Quick file reference

| File | Purpose |
|---|---|
| `classes/local/user_merger.php` | Merge orchestrator, transaction boundary |
| `classes/local/default_db_config.php` | Default table registry (indexes, user fields, mergers) |
| `classes/local/merger/generic_table_merger.php` | Default per-table merge strategy |
| `classes/local/logger.php` | Persists merge logs to the `tool_mergeusers` table |
| `cli/climerger.php` | CLI entry point for interactive/bulk merging |
| `README.md` | Full behavioural documentation |
| `CHANGES.md` | Version history and release notes |

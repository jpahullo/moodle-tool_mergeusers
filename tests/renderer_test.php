<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for renderer functionality.
 *
 * @package   tool_mergeusers
 * @author    Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright 2025 Catalyst IT Australia
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers;

use advanced_testcase;
use moodle_url;
use tool_mergeusers\local\logger;
use tool_mergeusers\output\renderer;
use tool_mergeusers_renderer;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer tests
 *
 * @package   tool_mergeusers
 * @author    Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright 2025 Catalyst IT Australia
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_mergeusers\output\renderer
 */
final class renderer_test extends advanced_testcase {
    /**
     * Get plugin renderer
     * @return renderer
     */
    private function get_renderer(): renderer {
        global $PAGE;
        return $PAGE->get_renderer('tool_mergeusers');
    }

    /**
     * Tests get_merge_display_text function with a user that does exist
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     * @throws \dml_exception
     */
    public function test_get_merge_detail_missing_user(): void {
        // User does not exist, should contain 'unknown profile' lang string.
        $dummylog = (object) [
            'fromuserid' => -5,
            'timemodified' => 0,
            'status' => 'success',
            'id' => 0,
        ];
        $dummyuser = (object) [
            'id' => 0,
        ];
        $lastmerge = new in_memory_last_merge($dummyuser->id, false, $dummylog, null);
        $unknownprofilelang = get_string('unknownprofile', 'tool_mergeusers', -5);
        $displaytext = $this->get_renderer()->get_merge_detail($dummyuser, $lastmerge);
        $this->assertStringContainsString($unknownprofilelang, $displaytext);
    }

    /**
     * Tests get_merge_display_text function with a user that does exist
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_get_merge_detail_existing_user(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $dummylog = (object) [
            'fromuserid' => $user->id,
            'timemodified' => 0,
            'status' => 'success',
            'id' => 0,
        ];
        $dummyuser = (object) [
            'id' => 0,
            'suspended' => '1',
        ];
        $lastmerge = new in_memory_last_merge($dummyuser->id, true, $dummylog, null);

        // Should contain their fullname.
        $fullname = fullname($user);
        $displaytext = $this->get_renderer()->get_merge_detail($dummyuser, $lastmerge);
        $this->assertStringContainsString($fullname, $displaytext);
    }

    /**
     * Data provider for the results_page() status coverage below.
     *
     * @return array
     */
    public static function results_page_status_provider(): array {
        return [
            'pending' => ['pending', null],
            'inprogress' => ['inprogress', null],
            'success' => ['success', 'logok'],
            'error' => ['error', 'logko'],
        ];
    }

    /**
     * Test that results_page() never leaks the [[ok]]/[[ko]] invalid string
     * identifiers, and shows the status box with the correct status label.
     *
     * The status box is rendered through $this->notification(), whose actual
     * markup (HTML alert-* classes vs the CLI renderer's plain-text markers)
     * depends on the render target, which differs under PHPUnit - so this
     * only asserts on the status label text, not on any specific CSS class.
     *
     * @param string $status
     * @param string|null $expectedcaptionkey
     *
     * @dataProvider results_page_status_provider
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_results_page_shows_status_box_without_broken_strings(
        string $status,
        ?string $expectedcaptionkey,
    ): void {
        $this->resetAfterTest();

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $output = $this->get_renderer()->results_page($touser, $fromuser, $status, ['Some log line.'], 1);

        $this->assertStringNotContainsString('[[ok]]', $output);
        $this->assertStringNotContainsString('[[ko]]', $output);
        $this->assertStringContainsString(get_string('status:' . $status, 'tool_mergeusers'), $output);

        if ($expectedcaptionkey !== null) {
            $this->assertStringContainsString(get_string($expectedcaptionkey, 'tool_mergeusers'), $output);
        }
    }

    /**
     * Test that results_page() shows the consolidated user info table, sourced
     * from a normalized snapshot, with a profile link for each live user and the
     * shared capture timestamp - and no longer the old duplicated identity line.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_results_page_shows_consolidated_user_info_table(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $logger = new logger();
        $logid = $logger->log($touser->id, $fromuser->id, true, ['Some action.']);
        $stored = $logger->detail_from($logid);

        $output = $this->get_renderer()->results_page(
            $touser,
            $fromuser,
            $stored->status,
            $stored->log,
            $logid,
            $stored->timecreated,
            $stored->timemodified,
        );

        $this->assertStringContainsString($touser->username, $output);
        $this->assertStringContainsString($fromuser->username, $output);
        $this->assertStringContainsString('/user/profile.php?id=' . $touser->id, $output);
        $this->assertStringContainsString('/user/profile.php?id=' . $fromuser->id, $output);
        $this->assertStringContainsString(userdate($stored->log->user_snapshots->timemodified), $output);
    }

    /**
     * Test that an "in progress" merge always shows "Queued at", never "Executed
     * at" - even though update_log_status() also bumps timemodified when a task
     * transitions to in-progress, which would otherwise make timecreated and
     * timemodified differ and look like a genuinely completed (adhoc) merge.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_results_page_shows_queued_not_executed_for_inprogress_status(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $timecreated = time() - 60;
        $timeinprogress = time();

        $output = $this->get_renderer()->results_page(
            $touser,
            $fromuser,
            'inprogress',
            [],
            1,
            $timecreated,
            $timeinprogress,
        );

        $this->assertStringContainsString(get_string('snapshot_queued', 'tool_mergeusers'), $output);
        $this->assertStringNotContainsString(get_string('snapshot_executed', 'tool_mergeusers'), $output);
        $this->assertStringContainsString(userdate($timecreated), $output);
    }

    /**
     * Test that the "Executed at" timestamp appears directly under the "for further
     * reference, log id X" line, before the "queries sent to the DB" caption - not
     * after it, as previously rendered.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_results_page_shows_executed_at_right_after_logline(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $timestamp = time();

        $output = $this->get_renderer()->results_page(
            $touser,
            $fromuser,
            'success',
            ['Some action.'],
            1,
            $timestamp,
            $timestamp,
        );

        $loglinepos = strpos($output, get_string('logline', 'tool_mergeusers', $this->get_renderer()->render_logid(1)));
        $executedpos = strpos($output, get_string('snapshot_executed', 'tool_mergeusers'));
        $logokpos = strpos($output, get_string('logok', 'tool_mergeusers'));

        $this->assertNotFalse($loglinepos);
        $this->assertNotFalse($executedpos);
        $this->assertNotFalse($logokpos);
        $this->assertLessThan($executedpos, $loglinepos, '"Executed at" must appear after the log id reference.');
        $this->assertLessThan($logokpos, $executedpos, '"Executed at" must appear before the "logok" caption.');
    }

    /**
     * Test that a side with no real user id at merge time (id <= 0, e.g. from an
     * external gathering that could not resolve a username) shows the dedicated
     * "not found" message rather than the generic "unavailable" one.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_results_page_shows_not_found_for_zero_id(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();

        $logger = new logger();
        $logid = $logger->log($touser->id, 0, false, ['Could not resolve username.']);
        $stored = $logger->detail_from($logid);

        $fromstub = (object) ['id' => 0, 'username' => get_string('deleted'), 'deleted' => 1];

        $output = $this->get_renderer()->results_page($touser, $fromstub, $stored->status, $stored->log, $logid);

        $this->assertStringContainsString(get_string('usernotfoundatmerge', 'tool_mergeusers'), $output);
    }

    /**
     * Test that a not-found side with a searched-field hint (e.g. from an external
     * gathering that searched by 'email' and could not find a match) shows only that
     * one field alongside the "not found" message, not the full identity field set a
     * found user would show.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_results_page_shows_only_the_searched_field_for_not_found_with_hint(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();

        $logger = new logger();
        $logid = $logger->log(
            $touser->id,
            0,
            false,
            ['Could not resolve email.'],
            null,
            null,
            ['field' => logger::SEARCHED_FIELD_EMAIL, 'value' => 'missing@example.com'],
        );
        $stored = $logger->detail_from($logid);

        $output = $this->get_renderer()->results_page($touser, (object) ['id' => 0], $stored->status, $stored->log, $logid);

        // Scope assertions to the "user to remove" box only: the "user to keep" box is
        // a normal recoverable user and legitimately shows its own username/email/etc.
        $fromboxstart = strpos($output, get_string('olduser', 'tool_mergeusers'));
        $toboxstart = strpos($output, get_string('newuser', 'tool_mergeusers'));
        $frombox = substr($output, $fromboxstart, $toboxstart - $fromboxstart);

        $this->assertStringContainsString(get_string('usernotfoundatmerge', 'tool_mergeusers'), $frombox);
        $this->assertStringContainsString(get_string('snapshot_email', 'tool_mergeusers'), $frombox);
        $this->assertStringContainsString('missing@example.com', $frombox);
        $this->assertStringNotContainsString(get_string('snapshot_username', 'tool_mergeusers'), $frombox);
        $this->assertStringNotContainsString(get_string('snapshot_idnumber', 'tool_mergeusers'), $frombox);
    }

    /**
     * Test that logs_page() shows the same "not found" message as the results page
     * for a row whose fromuserid/touserid is <= 0, instead of the generic "deleted"
     * text that a merely-since-deleted real user id would show.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_shows_not_found_message_for_zero_id(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();

        $logger = new logger();
        $logger->log($touser->id, 0, false, ['Could not resolve username.']);

        $logs = $logger->get();

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php');
        $output = $this->get_renderer()->logs_page($logs, count($logs), 0, 20, $baseurl);

        $this->assertStringContainsString(get_string('usernotfoundatmerge', 'tool_mergeusers'), $output);
        $this->assertStringNotContainsString(get_string('deleted', 'tool_mergeusers', 0), $output);
    }

    /**
     * Test that logs_page() shows the searched field and value alongside the "not
     * found" message, using the translated field label (no trailing colon) - so an
     * admin can tell which user a merge tried and failed to resolve without having to
     * click into the detail page.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_shows_searched_field_hint_for_not_found_row(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();

        $logger = new logger();
        $logger->log(
            $touser->id,
            0,
            false,
            ['Could not resolve username.'],
            null,
            null,
            ['field' => logger::SEARCHED_FIELD_USERNAME, 'value' => 'jsmith123'],
        );

        $logs = $logger->get();
        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php');
        $output = $this->get_renderer()->logs_page($logs, count($logs), 0, 20, $baseurl);

        $this->assertStringContainsString(
            get_string('usernotfoundatmergewithhint', 'tool_mergeusers', (object) ['field' => 'Username', 'value' => 'jsmith123']),
            $output,
        );
    }

    /**
     * Test that logs_page() shows the plain "no logs" string when there is no
     * search active and the log table is genuinely empty.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_shows_nologs_string_when_no_search_and_zero_total(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php');
        $output = $this->get_renderer()->logs_page([], 0, 0, 20, $baseurl, null);

        $this->assertStringContainsString(get_string('nologs', 'tool_mergeusers'), $output);
    }

    /**
     * Test that logs_page() shows a search-specific "no results" string (naming the
     * search term) instead of the generic "no logs" string, when a search is active
     * and it matched nothing.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_shows_nologsforsearch_string_when_search_active_and_zero_total(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php', ['search' => 'nomatch']);
        $output = $this->get_renderer()->logs_page([], 0, 0, 20, $baseurl, 'nomatch');

        $this->assertStringContainsString(
            get_string('nologsforsearch', 'tool_mergeusers', 'nomatch'),
            $output,
        );
        $this->assertStringNotContainsString(get_string('nologs', 'tool_mergeusers'), $output);
    }

    /**
     * Test that logs_page() shows the total number of matching logs alongside the
     * "here is the list" message when there is no search active.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_shows_total_count_when_no_search(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php');
        $output = $this->get_renderer()->logs_page([], 5, 0, 20, $baseurl, null);

        $this->assertStringContainsString(get_string('loglist', 'tool_mergeusers', 5), $output);
    }

    /**
     * Test that logs_page() shows how many logs matched the active search, and the
     * search term itself, instead of the generic "here is the list" message.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_shows_matching_count_and_term_when_search_active(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php', ['search' => 'jsmith']);
        $output = $this->get_renderer()->logs_page([], 3, 0, 20, $baseurl, 'jsmith');

        $this->assertStringContainsString(
            get_string('loglistforsearch', 'tool_mergeusers', (object) ['count' => 3, 'search' => 'jsmith']),
            $output,
        );
    }

    /**
     * Test that logs_page() renders paging bar links when there are more matching
     * rows than fit on a single page.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_renders_paging_bar_links_when_totalcount_exceeds_perpage(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $logger->log($touser->id, 0, true, ['ok']);

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php');
        // 1 row fits on a page of 1, but a totalcount of 3 forces at least a second page link.
        $output = $this->get_renderer()->logs_page([current($logger->get())], 3, 0, 1, $baseurl);

        $this->assertStringContainsString('page=1', $output);
    }

    /**
     * Test that a "show all N" link appears at the foot of the listing when there
     * are more matching rows than the current page size, and that it points at
     * perpage=renderer::SHOW_ALL_PAGE_SIZE.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_shows_showall_link_when_paginated(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('logpagesize', 20, 'tool_mergeusers');

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php');
        $output = $this->get_renderer()->logs_page([], 30, 0, 20, $baseurl);

        $this->assertStringContainsString('perpage=' . renderer::SHOW_ALL_PAGE_SIZE, $output);
        $this->assertStringContainsString(get_string('showall', '', 30), $output);
    }

    /**
     * Test that once "show all" is active (perpage >= SHOW_ALL_PAGE_SIZE), the
     * listing shows a "show N per page" link back to the configured page size
     * instead of the "show all" link.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_shows_showperpage_link_when_already_showing_all(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('logpagesize', 20, 'tool_mergeusers');

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php');
        $output = $this->get_renderer()->logs_page([], 30, 0, renderer::SHOW_ALL_PAGE_SIZE, $baseurl);

        $this->assertStringContainsString('perpage=20', $output);
        $this->assertStringContainsString(get_string('showperpage', '', 20), $output);
        $this->assertStringNotContainsString(get_string('showall', '', 30), $output);
    }

    /**
     * Test that no "show all"/"show per page" toggle link is rendered when every
     * matching row already fits on a single page.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_hides_showall_toggle_when_everything_fits_on_one_page(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('logpagesize', 20, 'tool_mergeusers');

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php');
        $output = $this->get_renderer()->logs_page([], 5, 0, 20, $baseurl);

        $this->assertStringNotContainsString('tool-mergeusers-logs-showall', $output);
    }

    /**
     * Test that the search input value is HTML-escaped and repopulated from the
     * current search term, so the admin sees what they searched for after
     * submitting the form.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_search_input_value_is_escaped_and_repopulated(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php', ['search' => '<b>jsmith</b>']);
        $output = $this->get_renderer()->logs_page([], 0, 0, 20, $baseurl, '<b>jsmith</b>');

        $this->assertStringContainsString('value="&lt;b&gt;jsmith&lt;/b&gt;"', $output);
        $this->assertStringNotContainsString('value="<b>jsmith</b>"', $output);
    }

    /**
     * Test that the CSV export link carries the current search term, so exporting
     * while a search is active only downloads the matching rows (view.php honors the
     * "search" param on the export path too).
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_export_link_carries_current_search_term(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php', ['search' => 'jsmith']);
        $output = $this->get_renderer()->logs_page([], 0, 0, 20, $baseurl, 'jsmith');

        $this->assertStringContainsString('export=1', $output);
        $this->assertStringContainsString('search=jsmith', $output);
    }

    /**
     * Test that a falsy-but-meaningful search term ("0") still reaches the export
     * link. array_filter()'s default callback would otherwise drop it, since "0" is
     * falsy in PHP, silently making the export ignore the active search.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_export_link_carries_zero_search_term(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php', ['search' => '0']);
        $output = $this->get_renderer()->logs_page([], 0, 0, 20, $baseurl, '0');

        $this->assertStringContainsString('export=1', $output);
        $this->assertStringContainsString('search=0', $output);
    }

    /**
     * Test that the search box has an accessible label (not just a placeholder),
     * so screen reader users can identify the field.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_search_input_has_accessible_label(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php');
        $output = $this->get_renderer()->logs_page([], 0, 0, 20, $baseurl);

        $this->assertMatchesRegularExpression(
            '/<label[^>]*for="tool-mergeusers-logs-search-input"[^>]*>/',
            $output,
        );
        $this->assertStringContainsString('id="tool-mergeusers-logs-search-input"', $output);
    }

    /**
     * Test that the search form and the export link are wrapped in the expected
     * toolbar markup, so a future refactor of logs_page() cannot silently drop the
     * grouping that keeps the header from looking cramped/mixed together.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_logs_page_wraps_search_and_export_in_toolbar_markup(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $baseurl = new moodle_url('/admin/tool/mergeusers/view.php');
        $output = $this->get_renderer()->logs_page([], 0, 0, 20, $baseurl);

        $this->assertStringContainsString('class="tool-mergeusers-logs-toolbar"', $output);
        $this->assertStringContainsString('class="tool-mergeusers-logs-toolbar__export"', $output);

        // The export link/help icon must be nested inside the toolbar, after the search form.
        $toolbarstart = strpos($output, 'class="tool-mergeusers-logs-toolbar"');
        $exportstart = strpos($output, 'class="tool-mergeusers-logs-toolbar__export"');
        $searchforminput = strpos($output, 'id="tool-mergeusers-logs-search-input"');
        $this->assertGreaterThan($toolbarstart, $searchforminput);
        $this->assertGreaterThan($searchforminput, $exportstart);
    }

    /**
     * Test the real-world "rename in place" case: a gathering that detects the "old"
     * and "new" users are actually the SAME Moodle account (only the username
     * changed) can log a merge with touserid === fromuserid, reporting the OLD
     * username as the "from" side's hint. The results page must show the old
     * username on the "from" box and the CURRENT username on the "to" box, while
     * every other field (email, idnumber, id) is identical on both, since it is
     * genuinely the same account.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_renderer
     */
    public function test_results_page_shows_hinted_username_prevailing_on_a_same_account_rename(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Explicit, distinct email: the generator otherwise derives it from the
        // username, which would make it overlap with the username assertions below.
        $user = $this->getDataGenerator()->create_user(['username' => 'newusername', 'email' => 'person@example.com']);

        $logger = new logger();
        $logid = $logger->log(
            $user->id,
            $user->id,
            true,
            ['Renamed username in place.'],
            null,
            null,
            ['field' => logger::SEARCHED_FIELD_USERNAME, 'value' => 'oldusername'],
        );
        $stored = $logger->detail_from($logid);

        $output = $this->get_renderer()->results_page($user, $user, $stored->status, $stored->log, $logid);

        $fromboxstart = strpos($output, get_string('olduser', 'tool_mergeusers'));
        $toboxstart = strpos($output, get_string('newuser', 'tool_mergeusers'));
        $frombox = substr($output, $fromboxstart, $toboxstart - $fromboxstart);
        $tobox = substr($output, $toboxstart);

        $this->assertStringContainsString('oldusername', $frombox);
        $this->assertStringNotContainsString('newusername', $frombox);
        $this->assertStringContainsString('newusername', $tobox);

        // Every other field is identical on both sides, since it is the same account.
        $this->assertStringContainsString($user->email, $frombox);
        $this->assertStringContainsString($user->email, $tobox);
        $this->assertStringContainsString('ID: ' . $user->id, $frombox);
        $this->assertStringContainsString('ID: ' . $user->id, $tobox);
    }
}

// @codingStandardsIgnoreStart

/**
 * In-memory implementation of last_merge for testing purposes.
 *
 * @package   tool_mergeusers
 * @copyright 2025 Catalyst IT Australia
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class in_memory_last_merge extends \tool_mergeusers\local\last_merge {
    /** @var int User ID. */
    private int $userid;
    /** @var bool Whether user is suspended. */
    private bool $suspended;
    /** @var mixed Merge to data. */
    private mixed $tome;
    /** @var mixed Merge from data. */
    private mixed $fromme;

    /**
     * Constructor.
     *
     * @param int $userid User ID
     * @param bool $suspended Whether user is suspended
     * @param mixed $tome Merge to data
     * @param mixed $fromme Merge from data
     */
    public function __construct(int $userid, bool $suspended, mixed $tome, mixed $fromme) {
        $this->userid = $userid;
        $this->suspended = $suspended;
        $this->tome = $tome;
        $this->fromme = $fromme;
    }

    /**
     * Get merge from data.
     *
     * @return null|\stdClass
     */
    public function fromme(): null|\stdClass {
        return $this->fromme;
    }

    /**
     * Get merge to data.
     *
     * @return null|\stdClass
     */
    public function tome(): null|\stdClass {
        return $this->tome;
    }

    /**
     * Check if this user is deletable.
     *
     * @return bool
     */
    public function is_this_user_deletable(): bool {
        return true;
    }
}

// @codingStandardsIgnoreEnd

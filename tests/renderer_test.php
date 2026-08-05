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

        $logger = new \tool_mergeusers\local\logger();
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

        $logger = new \tool_mergeusers\local\logger();
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

        $logger = new \tool_mergeusers\local\logger();
        $logid = $logger->log(
            $touser->id,
            0,
            false,
            ['Could not resolve email.'],
            null,
            null,
            ['field' => 'email', 'value' => 'missing@example.com'],
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

        $logger = new \tool_mergeusers\local\logger();
        $logger->log($touser->id, 0, false, ['Could not resolve username.']);

        $logs = $logger->get();

        $output = $this->get_renderer()->logs_page($logs);

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

        $logger = new \tool_mergeusers\local\logger();
        $logger->log(
            $touser->id,
            0,
            false,
            ['Could not resolve username.'],
            null,
            null,
            ['field' => 'username', 'value' => 'jsmith123'],
        );

        $output = $this->get_renderer()->logs_page($logger->get());

        $this->assertStringContainsString(
            get_string('usernotfoundatmergewithhint', 'tool_mergeusers', (object) ['field' => 'Username', 'value' => 'jsmith123']),
            $output,
        );
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

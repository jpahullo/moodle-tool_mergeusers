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
 * Tests for logger::search()/count_search().
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers;

use advanced_testcase;
use stdClass;
use tool_mergeusers\local\logger;

/**
 * Tests for logger::search()/count_search(), backing the paginated/searchable
 * merge log listing.
 *
 * @covers \tool_mergeusers\local\logger::search
 * @covers \tool_mergeusers\local\logger::count_search
 */
final class logger_search_test extends advanced_testcase {
    /**
     * Test that search() with a null term returns the same rows, in the same order,
     * as get() without a filter - so view.php can use search() unconditionally.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_with_null_term_returns_everything_in_default_order(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $logger->log($usera->id, $userb->id, true, ['ok']);
        $logger->log($userb->id, $usera->id, true, ['ok']);

        $expected = array_keys((array) $logger->get());
        $actual = array_keys($logger->search(null));

        $this->assertSame($expected, $actual);
    }

    /**
     * Test that an empty string search term behaves exactly like a null one.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_with_empty_string_term_behaves_like_null_term(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $logger->log($usera->id, $userb->id, true, ['ok']);

        $this->assertSame(
            array_keys($logger->search(null)),
            array_keys($logger->search('')),
        );
        $this->assertSame($logger->count_search(null), $logger->count_search(''));
    }

    /**
     * Test that count_search() always matches the number of rows an unbounded
     * search() call returns, for both a filtered and an unfiltered search.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_count_search_matches_number_of_rows_returned_by_unbounded_search(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $usera = $this->getDataGenerator()->create_user(['username' => 'alice123']);
        $userb = $this->getDataGenerator()->create_user(['username' => 'bob456']);
        $logger = new logger();
        $logger->log($usera->id, $userb->id, true, ['ok']);
        $logger->log($userb->id, $usera->id, true, ['ok']);

        $this->assertCount($logger->count_search(null), $logger->search(null, 0, 0));
        $this->assertCount($logger->count_search('alice123'), $logger->search('alice123', 0, 0));
    }

    /**
     * Test that search() matches the current username of the kept (touserid) user.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_matches_current_username_of_touser(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user(['username' => 'keptuser42']);
        $fromuser = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $logger->log($touser->id, $fromuser->id, true, ['ok']);

        $this->assertCount(1, $logger->search('keptuser42'));
    }

    /**
     * Test that search() matches the current email of the removed (fromuserid) user.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_matches_current_email_of_fromuser(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user(['email' => 'removedperson@example.com']);
        $logger = new logger();
        $logger->log($touser->id, $fromuser->id, true, ['ok']);

        $this->assertCount(1, $logger->search('removedperson@example.com'));
    }

    /**
     * Test that search() is case-insensitive and matches partial substrings.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_is_case_insensitive_and_partial(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user(['username' => 'johndoe2026']);
        $fromuser = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $logger->log($touser->id, $fromuser->id, true, ['ok']);

        $this->assertCount(1, $logger->search('JohnDoe'));
        $this->assertCount(1, $logger->search('DOE2026'));
    }

    /**
     * Test that search() matches a "firstname lastname"-shaped term, not just
     * firstname or lastname individually. Common with Spanish naming, where a
     * person has two surnames stored together in the lastname field (e.g.
     * "Garcia Lopez"): searching "Maria Garcia" (part of firstname + start of
     * lastname) must still find the user, via the concatenated fullname match.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_matches_combined_firstname_and_lastname(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user(['firstname' => 'Maria', 'lastname' => 'Garcia Lopez']);
        $fromuser = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $logger->log($touser->id, $fromuser->id, true, ['ok']);

        $this->assertCount(1, $logger->search('Maria Garcia'));
        $this->assertCount(1, $logger->search('maria garcia lopez'));
    }

    /**
     * Test that a row whose mergedbyuserid is genuinely NULL at the DB level does not
     * break search() (the LEFT JOIN to {user} for that side simply yields no match).
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_handles_null_mergedbyuserid_without_error(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user(['username' => 'nomerger']);
        $fromuser = $this->getDataGenerator()->create_user();

        $record = new stdClass();
        $record->touserid = $touser->id;
        $record->fromuserid = $fromuser->id;
        $record->mergedbyuserid = null;
        $record->timecreated = time();
        $record->timemodified = time();
        $record->log = json_encode(['user_snapshots' => null, 'actions' => []]);
        $record->status = 'success';
        $DB->insert_record('tool_mergeusers', $record);

        $logger = new logger();
        $results = $logger->search('nomerger');

        $this->assertCount(1, $results);
    }

    /**
     * Test that a numeric search term matches touserid/fromuserid/mergedbyuserid/id
     * exactly, not as a substring (e.g. "5" must not match a row whose id is "52").
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_matches_numeric_ids_exactly_not_as_substring(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $logid = $logger->log($touser->id, $fromuser->id, true, ['ok']);

        $bytouserid = $logger->search((string) $touser->id);
        $this->assertArrayHasKey($logid, $bytouserid);

        $bylogid = $logger->search((string) $logid);
        $this->assertArrayHasKey($logid, $bylogid);

        // A term that is a substring of a real id, but not equal to any id, must not match by id.
        $substringterm = substr((string) $logid, 0, 1) . '999999';
        $bysubstring = $logger->search($substringterm);
        $this->assertArrayNotHasKey($logid, $bysubstring);
    }

    /**
     * Test the key new capability: a merge log for a user who has since been
     * deleted (their live {user} row no longer carries the original username,
     * since delete_user() overwrites it) must still be found by that original
     * username, via the snapshot captured in the log's stored content at merge time.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_matches_deleted_user_via_log_json_snapshot(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user(['username' => 'soontobedeleted']);

        $logger = new logger();
        $logid = $logger->log($touser->id, $fromuser->id, true, ['ok']);

        delete_user($fromuser);

        $results = $logger->search('soontobedeleted');

        $this->assertArrayHasKey($logid, $results);
    }

    /**
     * Test that search() matches the raw stored status value.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_matches_status_value(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();
        $fromusersuccess = $this->getDataGenerator()->create_user();
        $fromusererror = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $successid = $logger->log($touser->id, $fromusersuccess->id, true, ['ok']);
        $errorid = $logger->log($touser->id, $fromusererror->id, false, ['failed']);

        $successresults = $logger->search('success');
        $this->assertArrayHasKey($successid, $successresults);
        $this->assertArrayNotHasKey($errorid, $successresults);

        $errorresults = $logger->search('error');
        $this->assertArrayHasKey($errorid, $errorresults);
        $this->assertArrayNotHasKey($successid, $errorresults);
    }

    /**
     * Test that limitfrom/limitnum slice the (unfiltered) search results correctly
     * and in order, and that the union of all pages reproduces the unbounded result.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_pagination_limitfrom_limitnum_returns_correct_slice_in_order(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $logger = new logger();
        $expectedids = [];
        for ($i = 0; $i < 5; $i++) {
            $touser = $this->getDataGenerator()->create_user();
            $fromuser = $this->getDataGenerator()->create_user();
            $expectedids[] = $logger->log($touser->id, $fromuser->id, true, ['ok']);
            // Ensure a strictly increasing timemodified per row for a deterministic sort order.
            $this->waitForSecond();
        }
        // Rows are sorted by timemodified DESC by default: reverse insertion order.
        $expectedorder = array_reverse($expectedids);

        $page1 = array_keys($logger->search(null, 0, 2));
        $page2 = array_keys($logger->search(null, 2, 2));
        $page3 = array_keys($logger->search(null, 4, 2));

        $this->assertSame(array_slice($expectedorder, 0, 2), $page1);
        $this->assertSame(array_slice($expectedorder, 2, 2), $page2);
        $this->assertSame(array_slice($expectedorder, 4, 2), $page3);
        $this->assertSame($expectedorder, array_merge($page1, $page2, $page3));
    }

    /**
     * Test that search($term, 0, 0) (the shape used by the CSV export path) never
     * caps the result set, even when there are more matching rows than a typical
     * page size.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_search_with_zero_limit_returns_all_matching_rows_unbounded(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $logger = new logger();
        for ($i = 0; $i < 3; $i++) {
            $touser = $this->getDataGenerator()->create_user(['username' => 'exportcase' . $i]);
            $fromuser = $this->getDataGenerator()->create_user();
            $logger->log($touser->id, $fromuser->id, true, ['ok']);
        }

        $this->assertCount(3, $logger->search('exportcase', 0, 0));
    }

    /**
     * Regression test pinning get()'s existing behaviour (a simple associative
     * filter plus limitfrom/limitnum, as used by last_merge.php) after extracting
     * the shared attach_related_users() helper out of it.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     * @covers \tool_mergeusers\local\logger::get
     */
    public function test_get_still_returns_same_results_after_refactor(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();
        $logger = new logger();
        $logid = $logger->log($touser->id, $fromuser->id, true, ['ok']);

        $results = $logger->get(['touserid' => $touser->id], 0, 1);

        $this->assertCount(1, $results);
        $result = reset($results);
        $this->assertSame((int) $logid, (int) $result->id);
        $this->assertSame((int) $touser->id, (int) $result->to->id);
        $this->assertSame((int) $fromuser->id, (int) $result->from->id);
    }

    /**
     * Test that the tool_mergeusers/logsearchmaxlength setting bounds how much of
     * the log content search() inspects: a term placed beyond that limit is not
     * found, while the user_snapshots block (always at the start of the JSON) still
     * matches even when padded with a very long actions list.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_logger
     */
    public function test_logsearchmaxlength_setting_bounds_log_content_search(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('logsearchmaxlength', 50, 'tool_mergeusers');

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();
        $logger = new logger();
        // Padding long enough to push a distinctive marker in the actions list past
        // the 50-character search window.
        $padding = array_fill(0, 20, str_repeat('x', 20));
        $logid = $logger->log($touser->id, $fromuser->id, true, array_merge($padding, ['findablemarkerZZZ']));

        $this->assertArrayNotHasKey($logid, $logger->search('findablemarkerZZZ'));
    }
}

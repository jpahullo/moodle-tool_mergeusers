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

namespace tool_mergeusers;

use core_external\external_api;
use required_capability_exception;
use tool_mergeusers\external\get_merge_request_status;
use tool_mergeusers\local\logger;
use tool_mergeusers\local\status;

/**
 * Tests for the tool_mergeusers_get_merge_request_status web service.
 *
 * @package    tool_mergeusers
 * @author     Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright  2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_mergeusers\external\get_merge_request_status
 */
final class external_get_merge_request_status_test extends \advanced_testcase {
    /** @var \stdClass[] */
    private array $users;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->users = [];
        for ($i = 0; $i < 6; $i++) {
            $this->users[] = $this->getDataGenerator()->create_user();
        }
    }

    /**
     * Calls the web service function and cleans the return value.
     *
     * @param int $logid
     * @param int $fromuserid
     * @param int $touserid
     * @param string $status
     * @param int $limitfrom
     * @param int $limitnum
     * @return array
     */
    private function call(
        int $logid = 0,
        int $fromuserid = 0,
        int $touserid = 0,
        string $status = '',
        int $limitfrom = 0,
        int $limitnum = 0,
    ): array {
        $result = get_merge_request_status::execute($logid, $fromuserid, $touserid, $status, $limitfrom, $limitnum);
        return external_api::clean_returnvalue(get_merge_request_status::execute_returns(), $result);
    }

    /**
     * Creates a pending log entry between two of the fixture users, by index.
     *
     * @param int $fromindex
     * @param int $toindex
     * @return int the created log id.
     */
    private function create_log(int $fromindex, int $toindex): int {
        global $USER;
        return (new logger())->create_pending_log($this->users[$toindex]->id, $this->users[$fromindex]->id, $USER->id);
    }

    /**
     * Fetching by a specific logid returns exactly that one entry.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_get_by_logid(): void {
        $logid = $this->create_log(0, 1);

        $result = $this->call(logid: $logid);

        $this->assertCount(1, $result['logs']);
        $this->assertSame($logid, $result['logs'][0]['id']);
        $this->assertSame((int) $this->users[0]->id, $result['logs'][0]['fromuserid']);
        $this->assertSame((int) $this->users[1]->id, $result['logs'][0]['touserid']);
        $this->assertSame(status::PENDING->value, $result['logs'][0]['status']);
    }

    /**
     * A logid matching no row returns an empty list, not an error.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_get_by_nonexistent_logid_returns_empty_list(): void {
        $result = $this->call(logid: 999999);

        $this->assertSame([], $result['logs']);
    }

    /**
     * No filters at all returns every log.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_lists_all_without_filters(): void {
        $this->create_log(0, 1);
        $this->create_log(2, 3);

        $result = $this->call();

        $this->assertCount(2, $result['logs']);
    }

    /**
     * fromuserid filters down to matching logs only.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_filters_by_fromuserid(): void {
        $this->create_log(0, 1);
        $this->create_log(2, 3);

        $result = $this->call(fromuserid: $this->users[2]->id);

        $this->assertCount(1, $result['logs']);
        $this->assertSame((int) $this->users[2]->id, $result['logs'][0]['fromuserid']);
    }

    /**
     * touserid filters down to matching logs only.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_filters_by_touserid(): void {
        $this->create_log(0, 1);
        $this->create_log(2, 3);

        $result = $this->call(touserid: $this->users[3]->id);

        $this->assertCount(1, $result['logs']);
        $this->assertSame((int) $this->users[3]->id, $result['logs'][0]['touserid']);
    }

    /**
     * status filters down to matching logs only.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_filters_by_status(): void {
        $logid = $this->create_log(0, 1);
        $this->create_log(2, 3);
        (new logger())->update_log_status($logid, status::ERROR->value, ['boom']);

        $result = $this->call(status: status::ERROR->value);

        $this->assertCount(1, $result['logs']);
        $this->assertSame($logid, $result['logs'][0]['id']);
    }

    /**
     * limitfrom/limitnum page through results the same way logger::get() does.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_paginates_with_limitfrom_and_limitnum(): void {
        set_config('logpagesize', '100', 'tool_mergeusers');
        $logids = [];
        for ($i = 0; $i < 3; $i++) {
            $logids[] = $this->create_log($i, $i + 3);
        }

        $page1 = $this->call(limitnum: 2);
        $page2 = $this->call(limitfrom: 2, limitnum: 2);

        $this->assertCount(2, $page1['logs']);
        $this->assertCount(1, $page2['logs']);
    }

    /**
     * limitnum above the configured logpagesize is capped down to it.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_limitnum_is_capped_to_logpagesize(): void {
        set_config('logpagesize', '2', 'tool_mergeusers');
        for ($i = 0; $i < 3; $i++) {
            $this->create_log($i, $i + 3);
        }

        $result = $this->call(limitnum: 100);

        $this->assertCount(2, $result['logs']);
    }

    /**
     * limitnum absent (0) uses logpagesize as-is, not the UI's unrelated 20000 safety cap.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_missing_limitnum_defaults_to_logpagesize(): void {
        set_config('logpagesize', '1', 'tool_mergeusers');
        $this->create_log(0, 1);
        $this->create_log(2, 3);

        $result = $this->call();

        $this->assertCount(1, $result['logs']);
    }

    /**
     * A caller without tool/mergeusers:viewlog is rejected.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_external
     */
    public function test_rejects_caller_without_capability(): void {
        $this->create_log(0, 1);
        $caller = $this->getDataGenerator()->create_user();
        $this->setUser($caller);

        $this->expectException(required_capability_exception::class);
        $this->call();
    }
}

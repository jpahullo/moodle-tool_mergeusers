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

use advanced_testcase;
use tool_mergeusers\task\merge_users_task;

/**
 * Tests for settings builder helper functions.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_mergeusers_is_adhoc_concurrency_configured
 */
final class settingslib_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        require_once(__DIR__ . '/../settingslib.php');
    }

    /**
     * Test that the concurrency limit is reported as not configured when unset.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_settings
     */
    public function test_is_adhoc_concurrency_configured_returns_false_when_unset(): void {
        global $CFG;

        unset($CFG->task_concurrency_limit);

        $this->assertFalse(tool_mergeusers_is_adhoc_concurrency_configured());
    }

    /**
     * Test that the concurrency limit is reported as configured when set to 1.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_settings
     */
    public function test_is_adhoc_concurrency_configured_returns_true_when_set_to_one(): void {
        global $CFG;

        $CFG->task_concurrency_limit = [
            merge_users_task::class => 1,
        ];

        $this->assertTrue(tool_mergeusers_is_adhoc_concurrency_configured());
    }

    /**
     * Test that the concurrency limit is reported as not configured when set to
     * anything other than 1 (e.g. left unlimited, or set to a looser value).
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_settings
     */
    public function test_is_adhoc_concurrency_configured_returns_false_when_set_to_other_value(): void {
        global $CFG;

        $CFG->task_concurrency_limit = [
            merge_users_task::class => 3,
        ];

        $this->assertFalse(tool_mergeusers_is_adhoc_concurrency_configured());
    }
}

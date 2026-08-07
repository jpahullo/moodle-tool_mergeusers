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
 * @covers \tool_mergeusers_is_adhoc_concurrency_limit_overridden
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
     * Test that the concurrency limit is reported as not overridden when unset.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_settings
     */
    public function test_is_adhoc_concurrency_limit_overridden_returns_false_when_unset(): void {
        global $CFG;

        unset($CFG->task_concurrency_limit);

        $this->assertFalse(tool_mergeusers_is_adhoc_concurrency_limit_overridden());
    }

    /**
     * Test that the concurrency limit is reported as not overridden when explicitly
     * set to 1, since that matches the plugin's own safe default.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_settings
     */
    public function test_is_adhoc_concurrency_limit_overridden_returns_false_when_set_to_one(): void {
        global $CFG;

        $CFG->task_concurrency_limit = [
            merge_users_task::class => 1,
        ];

        $this->assertFalse(tool_mergeusers_is_adhoc_concurrency_limit_overridden());
    }

    /**
     * Test that the concurrency limit is reported as overridden when set to
     * anything other than 1 (e.g. left unlimited, or set to a looser value).
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_settings
     */
    public function test_is_adhoc_concurrency_limit_overridden_returns_true_when_set_to_other_value(): void {
        global $CFG;

        $CFG->task_concurrency_limit = [
            merge_users_task::class => 3,
        ];

        $this->assertTrue(tool_mergeusers_is_adhoc_concurrency_limit_overridden());
    }

    /**
     * Test that the profile-field options list every real custom user profile field,
     * keyed by field id, and carry no other attribute (no "any field" sentinel to
     * represent, unlike tool_mergeusers_build_exceptions_options()).
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_settings
     */
    public function test_build_profilefields_options_lists_custom_fields(): void {
        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;

        $result = tool_mergeusers_build_profilefields_options();

        $this->assertArrayHasKey($fieldid, $result->options);
        $this->assertSame('Name of frog', $result->options[$fieldid]);
        $this->assertFalse(property_exists($result, 'defaultkey'));
    }
}

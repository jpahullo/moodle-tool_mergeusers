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
use tool_mergeusers\local\profile_fields;

/**
 * Tests for the profile_fields helper.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_mergeusers\local\profile_fields
 */
final class profile_fields_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that all() lists every real custom user profile field, with no sentinel
     * "any field" entry.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_all_lists_real_fields_without_sentinel(): void {
        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;

        $fields = profile_fields::all();

        $this->assertArrayHasKey($fieldid, $fields);
        $this->assertSame('Name of frog', $fields[$fieldid]);
        $this->assertNotContains(0, array_keys($fields), 'all() must never return the "any field" sentinel id 0.');
    }

    /**
     * Test that allowed() is empty when the setting is not configured.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_allowed_empty_when_setting_unset(): void {
        $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ]);

        $this->assertSame([], profile_fields::allowed());
    }

    /**
     * Test that allowed() only returns the fields listed in the
     * searchbyprofilefields setting, not every field defined on the site.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_allowed_returns_only_configured_subset(): void {
        $allowedid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'toadname', 'name' => 'Name of toad',
            'datatype' => 'text',
        ]);

        set_config('searchbyprofilefields', (string) $allowedid, 'tool_mergeusers');

        $this->assertSame([$allowedid => 'Name of frog'], profile_fields::allowed());
    }

    /**
     * Test that allowed() silently drops a configured field id that no longer
     * exists (e.g. deleted after being selected), instead of failing.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_allowed_ignores_deleted_field_id(): void {
        set_config('searchbyprofilefields', '999999', 'tool_mergeusers');

        $this->assertSame([], profile_fields::allowed());
    }
}

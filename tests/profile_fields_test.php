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
     * searchbyprofilefields setting, not every field defined on the site, keyed by
     * "profile_field_<shortname>" rather than the field's internal database id.
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

        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $allowedid, 'tool_mergeusers');

        $this->assertSame([profile_fields::FIELD_PREFIX . 'frogname' => 'Name of frog'], profile_fields::allowed());
    }

    /**
     * Test that allowed() silently drops a configured field id that no longer
     * exists (e.g. deleted after being selected), instead of failing.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_allowed_ignores_deleted_field_id(): void {
        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', '999999', 'tool_mergeusers');

        $this->assertSame([], profile_fields::allowed());
    }

    /**
     * Test that allowed() is empty while the master switch
     * (searchbyprofilefieldsenabled) is off, even if fields are already
     * selected in searchbyprofilefields - the toggle always wins.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_allowed_empty_when_disabled_even_if_fields_configured(): void {
        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;

        set_config('searchbyprofilefieldsenabled', 0, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        $this->assertSame([], profile_fields::allowed());
    }

    /**
     * Test that unavailable_field_notice() names the field when it can still be
     * resolved - the field itself still exists, it is just no longer allowed for
     * searching (e.g. the master switch was turned off, or it was deselected).
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_unavailable_field_notice_names_existing_field(): void {
        $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ]);

        $notice = profile_fields::unavailable_field_notice(profile_fields::FIELD_PREFIX . 'frogname');

        $this->assertSame(get_string('searchfieldnolongeravailable', 'tool_mergeusers', 'Name of frog'), $notice);
    }

    /**
     * Test that unavailable_field_notice() falls back to a generic message when the
     * shortname no longer resolves to any real field (e.g. it was deleted, not just
     * disallowed).
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_unavailable_field_notice_falls_back_when_field_deleted(): void {
        $notice = profile_fields::unavailable_field_notice(profile_fields::FIELD_PREFIX . 'neverexisted');

        $this->assertSame(get_string('searchfieldnolongeravailable_generic', 'tool_mergeusers'), $notice);
    }

    /**
     * Test that unavailable_field_notice() falls back to the generic message for a
     * value that is not even shaped like a profile field reference, rather than
     * erroring.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_unavailable_field_notice_falls_back_for_non_numeric_value(): void {
        $notice = profile_fields::unavailable_field_notice('');

        $this->assertSame(get_string('searchfieldnolongeravailable_generic', 'tool_mergeusers'), $notice);
    }

    /**
     * Test that resolve_allowed() resolves an allow-listed field's
     * "profile_field_<shortname>" reference to its internal database id.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_resolve_allowed_resolves_allowlisted_shortname(): void {
        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        $this->assertSame($fieldid, profile_fields::resolve_allowed(profile_fields::FIELD_PREFIX . 'frogname'));
    }

    /**
     * Test that resolve_allowed() returns null for a field that exists but is not
     * allow-listed.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_resolve_allowed_rejects_non_allowlisted_shortname(): void {
        $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ]);
        // Deliberately not added to tool_mergeusers/searchbyprofilefields.

        $this->assertNull(profile_fields::resolve_allowed(profile_fields::FIELD_PREFIX . 'frogname'));
    }

    /**
     * Test that resolve_allowed() returns null for a shortname that does not exist
     * at all.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_resolve_allowed_rejects_unknown_shortname(): void {
        $this->assertNull(profile_fields::resolve_allowed(profile_fields::FIELD_PREFIX . 'neverexisted'));
    }

    /**
     * Test that resolve_allowed() returns null for a value that is not shaped like a
     * profile field reference at all - it must never be mistaken for one.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_resolve_allowed_rejects_value_without_prefix(): void {
        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        $this->assertNull(profile_fields::resolve_allowed((string) $fieldid));
        $this->assertNull(profile_fields::resolve_allowed('username'));
    }

    /**
     * Test that allowed_ids() returns the raw allow-listed field ids, for the
     * internal "search across every allowed field at once" SQL use case.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_allowed_ids_returns_raw_ids(): void {
        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefieldsenabled', 1, 'tool_mergeusers');
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        $this->assertSame([$fieldid], profile_fields::allowed_ids());
    }
}

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

use basic_testcase;
use tool_mergeusers\local\db_config;

/**
 * Testing of db_config instance.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 onwards to Universitat Rovira i Virgili
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_mergeusers\local\db_config
 */
final class db_config_test extends basic_testcase {
    /**
     * Test that config is initialized empty.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_db_config
     */
    public function test_config_is_initialized_empty(): void {
        $config = new db_config();
        $this->assertTrue($config->empty());
    }

    /**
     * Test that config is initialized with valid settings.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_db_config
     */
    public function test_config_is_initialized_with_valid_settings(): void {
        $config = new db_config(['gathering' => 'somevalue']);
        $this->assertFalse($config->empty());
        $this->assertEquals('somevalue', $config->gathering);
    }

    /**
     * Test that config is initialized with invalid settings and not considered.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_db_config
     */
    public function test_config_is_initialized_with_invalid_settings_and_not_considered(): void {
        $config = new db_config(['invalidkey' => 'somevalue']);
        $this->assertTrue($config->empty());
        $this->assertNull($config->invalidkey);
    }

    /**
     * Test that config merges content and first settings are kept.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_db_config
     */
    public function test_config_merges_content_and_first_settings_are_kept(): void {
        $config = new db_config(['gathering' => 'somevalue']);
        $config->add('gathering', 'othervalue');
        $this->assertEquals('somevalue', $config->gathering);
    }

    /**
     * Test that config merges content and first array settings are kept.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_db_config
     */
    public function test_config_merges_content_and_first_array_settings_are_kept(): void {
        $initialsettings = [
            'compoundindexes' => [
                'grade_grades' => [
                    'userfield' => ['userid'],
                    'otherfields' => ['itemid'],
                ],
            ],
        ];
        $additionalsettings = [
            'compoundindexes' => [
                'grade_grades' => [
                    'userfield' => ['otheruserid'],
                    'otherfields' => ['otheritemid'],
                ],
                'groups_members' => [
                    'userfield' => ['userid'],
                    'otherfields' => ['groupid'],
                ],
            ],
        ];
        $expectedsettings = [
            'grade_grades' => [
                'userfield' => ['userid'],
                'otherfields' => ['itemid'],
            ],
            'groups_members' => [
                'userfield' => ['userid'],
                'otherfields' => ['groupid'],
            ],
        ];
        $config = new db_config($initialsettings);
        $config->add_raw($additionalsettings);

        $this->assertEquals($expectedsettings, $config->compoundindexes);
    }

    /**
     * Test that config merge with other config and first settings are kept.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_db_config
     */
    public function test_config_merge_with_other_config_and_first_settings_are_kept(): void {
        $config = new db_config(['gathering' => 'somevalue']);
        $config->merge_with(new db_config(['gathering', 'othervalue']));
        $this->assertEquals('somevalue', $config->gathering);
    }

    /**
     * Test that config settings are not settable.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_db_config
     */
    public function test_config_settings_are_not_settable(): void {
        $config = new db_config(['alwaysrollback' => false]);
        $config->alwaysrollback = true;
        $this->assertFalse($config->alwaysrollback);
    }
}

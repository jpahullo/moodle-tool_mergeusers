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
use tool_mergeusers_renderer;

/**
 * Renderer tests
 *
 * @package   tool_mergeusers
 * @author    Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright 2025 Catalyst IT Australia
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer_test extends advanced_testcase {
    /**
     * Get plugin renderer
     * @return tool_mergeusers_renderer
     */
    private function get_renderer(): tool_mergeusers_renderer {
        global $PAGE;
        return $PAGE->get_renderer('tool_mergeusers');
    }

    /**
     * Tests get_merge_display_text function with a user that does exist
     */
    public function test_get_merge_detail_missing_user() {
        // User does not exist, should contain 'unknown profile' lang string.
        $dummylog = (object) [
            'fromuserid' => -5,
            'timemodified' => 0,
            'success' => '1',
            'id' => 0
        ];
        $dummyuser = (object) [
            'id' => 0,
        ];
        $unknownprofilelang = get_string('unknownprofile', 'tool_mergeusers', -5);
        $displaytext = $this->get_renderer()->get_merge_detail($dummyuser, $dummylog, null);
        $this->assertStringContainsString($unknownprofilelang, $displaytext);
    }

    /**
     * Tests get_merge_display_text function with a user that does exist
     */
    public function test_get_merge_detail_existing_user() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $dummylog = (object) [
            'fromuserid' => $user->id,
            'timemodified' => 0,
            'success' => '1',
            'id' => 0,
        ];
        $dummyuser = (object) [
            'id' => 0,
            'suspended' => '1',
        ];

        // Should contain their fullname.
        $fullname = fullname($user);
        $displaytext = $this->get_renderer()->get_merge_detail($dummyuser, $dummylog, null);
        $this->assertStringContainsString($fullname, $displaytext);
    }
}

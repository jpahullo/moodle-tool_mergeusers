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
 * Renderer tests for tool_mergeusers.
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
 * @covers    \tool_mergeusers\output\renderer
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
            'success' => '1',
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
            'success' => '1',
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
}

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
/**
 * In-memory last merge helper class for testing.
 */
class in_memory_last_merge extends \tool_mergeusers\local\last_merge {
    /** @var int User ID. */
    private int $userid;
    /** @var bool Whether user is suspended. */
    private bool $suspended;
    /** @var mixed Log data for 'tome' merge. */
    private mixed $tome;
    /** @var mixed Log data for 'fromme' merge. */
    private mixed $fromme;

    /**
     * Constructor.
     *
     * @param int $userid User ID
     * @param bool $suspended Whether user is suspended
     * @param mixed $tome Log data for 'tome' merge
     * @param mixed $fromme Log data for 'fromme' merge
     */
    public function __construct(int $userid, bool $suspended, mixed $tome, mixed $fromme) {
        $this->userid = $userid;
        $this->suspended = $suspended;
        $this->tome = $tome;
        $this->fromme = $fromme;
    }

    /**
     * Get fromme log data.
     *
     * @return null|\stdClass
     */
    public function fromme(): null|\stdClass {
        return $this->fromme;
    }

    /**
     * Get tome log data.
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

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
use MergeUserTool;
use tool_mergeusers\local\last_merge;
use tool_mergeusers_renderer;

/**
 * Testing last_merge API.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class last_merge_test extends advanced_testcase {

    /**
     * Tests API when no user was merged.
     *
     * @throws \dml_exception
     */
    public function test_empty_list_of_deletable_users(): void {
        $users = last_merge::list_all_deletable_users();
        $this->assertEmpty($users);
    }

    /**
     * Tests the API when a user was merged and can be deletable.
     *
     * @throws \dml_exception
     */
    public function test_a_user_can_be_deletable(): void {
        global $CFG;
        require_once("$CFG->dirroot/admin/tool/mergeusers/lib/mergeusertool.php");
        $this->resetAfterTest(true);

        // Setup two users to merge.
        $user_remove = $this->getDataGenerator()->create_user();
        $user_keep = $this->getDataGenerator()->create_user();
        $mut = new MergeUserTool();
        $mut->merge($user_keep->id, $user_remove->id);

        $users = last_merge::list_all_deletable_users();

        $this->assertCount(1, $users);
        $this->assertEquals($user_remove->id, reset($users)->fromuserid);
    }
}

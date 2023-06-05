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
 * Version information
 *
 * @package    tool
 * @subpackage mergeusers
 * @author     Andrew Hancox <andrewdchancox@googlemail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_mergeusers_clioptions_testcase extends advanced_testcase {

    public function setUp(): void {
        global $CFG;
        require_once("$CFG->dirroot/admin/tool/mergeusers/lib/mergeusertool.php");
        $this->resetAfterTest(true);
    }

    public function tearDown(): void {
        $config = tool_mergeusers_config::instance();
        unset($config->alwaysRollback);
        unset($config->debugdb);
    }

    /**
     * Test option to always rollback merges.
     * @group tool_mergeusers
     * @group tool_mergeusers_clioptions
     */
    public function test_alwaysrollback() {
        global $DB;

        // Setup two users to merge.
        $user_remove = $this->getDataGenerator()->create_user();
        $user_keep = $this->getDataGenerator()->create_user();

        $mut = new MergeUserTool();
        list($success, $log, $logid) = $mut->merge($user_keep->id, $user_remove->id);

        // Check $user_remove is suspended.
        $user_remove = $DB->get_record('user', array('id' => $user_remove->id));
        $this->assertEquals(1, $user_remove->suspended);

        $user_keep = $DB->get_record('user', array('id' => $user_keep->id));
        $this->assertEquals(0, $user_keep->suspended);

        $user_remove_2 = $this->getDataGenerator()->create_user();

        $config = tool_mergeusers_config::instance();
        $config->alwaysRollback = true;

        $mut = new MergeUserTool($config);

        $this->expectException('Exception');
        $this->expectExceptionMessage('alwaysRollback option is set so rolling back transaction');
        list($success, $log, $logid) = $mut->merge($user_keep->id, $user_remove_2->id);
    }

    /**
     * Test option to always rollback merges.
     * @group tool_mergeusers
     * @group tool_mergeusers_clioptions
     */
    public function test_debugdb() {
        global $DB;

        // Setup two users to merge.
        $user_remove = $this->getDataGenerator()->create_user();
        $user_keep = $this->getDataGenerator()->create_user();

        $mut = new MergeUserTool();
        list($success, $log, $logid) = $mut->merge($user_keep->id, $user_remove->id);
        $this->assertFalse($this->hasOutput());

        // Check $user_remove is suspended.
        $user_remove = $DB->get_record('user', array('id' => $user_remove->id));
        $this->assertEquals(1, $user_remove->suspended);

        $user_keep = $DB->get_record('user', array('id' => $user_keep->id));
        $this->assertEquals(0, $user_keep->suspended);

        $user_remove_2 = $this->getDataGenerator()->create_user();

        $config = tool_mergeusers_config::instance();
        $config->debugdb = true;

        $mut = new MergeUserTool($config);

        list($success, $log, $logid) = $mut->merge($user_keep->id, $user_remove_2->id);

        $this->expectOutputRegex('/Query took/');
    }
}

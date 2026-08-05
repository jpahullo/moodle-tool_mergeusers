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

/**
 * Structural checks on db/upgrade.php savepoints.
 *
 * A savepoint dated earlier than an already-released version.php is silently
 * skipped by Moodle for anyone upgrading from that release, since
 * $oldversion is already past it - the schema change it carries then never
 * gets applied. This guards against that class of mistake.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \xmldb_tool_mergeusers_upgrade
 */
final class upgrade_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that every savepoint in db/upgrade.php is strictly increasing and
     * never exceeds the plugin's current version.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_upgrade
     */
    public function test_upgrade_savepoints_are_ordered_and_do_not_exceed_plugin_version(): void {
        global $CFG;

        $plugin = new \stdClass();
        require($CFG->dirroot . '/admin/tool/mergeusers/version.php');

        $upgradephp = file_get_contents($CFG->dirroot . '/admin/tool/mergeusers/db/upgrade.php');
        preg_match_all('/upgrade_plugin_savepoint\(\s*true\s*,\s*(\d+)\s*,/', $upgradephp, $matches);
        $savepoints = array_map('intval', $matches[1]);

        $this->assertNotEmpty($savepoints, 'Expected at least one upgrade_plugin_savepoint() call.');

        $sorted = $savepoints;
        sort($sorted);
        $this->assertSame($sorted, $savepoints, 'Savepoints in db/upgrade.php must appear in strictly increasing order.');

        $this->assertSame(
            array_unique($savepoints),
            $savepoints,
            'Savepoints in db/upgrade.php must be unique.',
        );

        foreach ($savepoints as $savepoint) {
            $this->assertLessThanOrEqual(
                $plugin->version,
                $savepoint,
                "Savepoint $savepoint exceeds \$plugin->version {$plugin->version}.",
            );
        }
    }
}

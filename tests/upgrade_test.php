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

    /**
     * Test that tool_mergeusers_normalize_user_snapshots() backfills a legacy row
     * (no user_snapshots key at all) and fixes an ambiguous one (a bare null side),
     * while leaving actions untouched and an already-normalized row alone.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_upgrade
     */
    public function test_normalize_user_snapshots_backfills_legacy_and_ambiguous_rows(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/admin/tool/mergeusers/db/upgrade.php');

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        // Legacy row: log has no user_snapshots key at all (pre-a6ec50c shape).
        $legacyid = $DB->insert_record('tool_mergeusers', (object) [
            'touserid' => $touser->id,
            'fromuserid' => $fromuser->id,
            'mergedbyuserid' => 2,
            'timecreated' => time() - DAYSECS,
            'timemodified' => time() - DAYSECS,
            'log' => json_encode(['actions' => ['Old action.']]),
            'status' => 'success',
        ]);

        // Ambiguous row: user_snapshots present, but a bare null side, and
        // fromuserid = 0 (today's ambiguity between notfound and unrecoverable).
        $ambiguousid = $DB->insert_record('tool_mergeusers', (object) [
            'touserid' => $touser->id,
            'fromuserid' => 0,
            'mergedbyuserid' => 2,
            'timecreated' => time() - DAYSECS,
            'timemodified' => time() - DAYSECS,
            'log' => json_encode([
                'user_snapshots' => ['to_user' => null, 'from_user' => null],
                'actions' => ['Another action.'],
            ]),
            'status' => 'success',
        ]);

        // Already-normalized row: must be left untouched.
        $normalizedlog = [
            'user_snapshots' => [
                'timemodified' => 12345,
                'to_user' => [
                    'notfound' => false,
                    'recoverable' => true,
                    'id' => $touser->id,
                    'username' => 'kept',
                    'erasedforgdpr' => false,
                    'timeerased' => null,
                ],
                'from_user' => [
                    'notfound' => true,
                    'recoverable' => false,
                    'id' => null,
                    'erasedforgdpr' => false,
                    'timeerased' => null,
                ],
            ],
            'actions' => ['Untouched.'],
        ];
        $normalizedid = $DB->insert_record('tool_mergeusers', (object) [
            'touserid' => $touser->id,
            'fromuserid' => 0,
            'mergedbyuserid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
            'log' => json_encode($normalizedlog),
            'status' => 'success',
        ]);

        tool_mergeusers_normalize_user_snapshots();

        $legacy = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $legacyid]), true);
        $this->assertArrayHasKey('timemodified', $legacy['user_snapshots']);
        $this->assertTrue($legacy['user_snapshots']['to_user']['recoverable']);
        $this->assertSame($touser->username, $legacy['user_snapshots']['to_user']['username']);
        $this->assertTrue($legacy['user_snapshots']['from_user']['recoverable']);
        $this->assertSame($fromuser->username, $legacy['user_snapshots']['from_user']['username']);
        $this->assertSame(['Old action.'], $legacy['actions']);

        $ambiguous = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $ambiguousid]), true);
        $this->assertTrue($ambiguous['user_snapshots']['to_user']['recoverable']);
        $this->assertTrue($ambiguous['user_snapshots']['from_user']['notfound']);
        $this->assertSame(['Another action.'], $ambiguous['actions']);

        $normalized = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $normalizedid]), true);
        $this->assertSame($normalizedlog, $normalized, 'An already-normalized row must be left untouched.');
    }
}

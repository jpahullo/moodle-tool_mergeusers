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

        // Already-wrapped row: has "actions" but no user_snapshots key yet (NOT the
        // original pre-a6ec50c flat shape - see upgrade_migrate_legacy_actions_test
        // below for that case, handled by a separate, later upgrade step).
        $wrappedwithoutsnapshotsid = $DB->insert_record('tool_mergeusers', (object) [
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

        $wrapped = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $wrappedwithoutsnapshotsid]), true);
        $this->assertArrayHasKey('timemodified', $wrapped['user_snapshots']);
        $this->assertTrue($wrapped['user_snapshots']['to_user']['recoverable']);
        $this->assertSame($touser->username, $wrapped['user_snapshots']['to_user']['username']);
        $this->assertTrue($wrapped['user_snapshots']['from_user']['recoverable']);
        $this->assertSame($fromuser->username, $wrapped['user_snapshots']['from_user']['username']);
        $this->assertSame(['Old action.'], $wrapped['actions']);

        $ambiguous = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $ambiguousid]), true);
        $this->assertTrue($ambiguous['user_snapshots']['to_user']['recoverable']);
        $this->assertTrue($ambiguous['user_snapshots']['from_user']['notfound']);
        $this->assertSame(['Another action.'], $ambiguous['actions']);

        $normalized = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $normalizedid]), true);
        $this->assertSame($normalizedlog, $normalized, 'An already-normalized row must be left untouched.');
    }

    /**
     * Test that tool_mergeusers_migrate_legacy_top_level_actions() correctly handles
     * every possible shape the log column's JSON can hold, moving any leftover
     * top-level numeric keys into "actions" without ever touching "user_snapshots",
     * and leaving already-correct rows completely untouched.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_upgrade
     */
    public function test_migrate_legacy_top_level_actions_handles_all_shapes(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/admin/tool/mergeusers/db/upgrade.php');

        // Case A: the original pre-a6ec50c flat shape, no wrapper object at all.
        $flatid = $DB->insert_record('tool_mergeusers', (object) [
            'touserid' => 1,
            'fromuserid' => 2,
            'mergedbyuserid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
            'log' => json_encode(['Old action 1.', 'Old action 2.']),
            'status' => 'success',
        ]);

        // Case B: the real-world broken shape - loose numeric keys alongside an
        // already-present user_snapshots, no "actions" key at all. This is exactly
        // what the (uneditable) 2026080100 step produces when it runs against a
        // case-A row.
        $brokenlog = ['Old action 1.', 'Old action 2.'];
        $brokenlog['user_snapshots'] = ['timemodified' => 12345, 'to_user' => null, 'from_user' => null];
        $brokenid = $DB->insert_record('tool_mergeusers', (object) [
            'touserid' => 1,
            'fromuserid' => 2,
            'mergedbyuserid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
            'log' => json_encode($brokenlog),
            'status' => 'success',
        ]);

        // Case C: already correct, with actions - must be left untouched.
        $correctlog = ['user_snapshots' => ['timemodified' => 12345, 'to_user' => null, 'from_user' => null], 'actions' => ['x']];
        $correctid = $DB->insert_record('tool_mergeusers', (object) [
            'touserid' => 1,
            'fromuserid' => 2,
            'mergedbyuserid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
            'log' => json_encode($correctlog),
            'status' => 'success',
        ]);

        // Case D: already correct, empty pending actions - must be left untouched.
        $emptylog = ['user_snapshots' => ['timemodified' => 12345, 'to_user' => null, 'from_user' => null], 'actions' => []];
        $emptyid = $DB->insert_record('tool_mergeusers', (object) [
            'touserid' => 1,
            'fromuserid' => 2,
            'mergedbyuserid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
            'log' => json_encode($emptylog),
            'status' => 'pending',
        ]);

        // Case E (defensive): loose numeric keys coexisting with an "actions" key
        // that shouldn't happen in practice given how the data is ever written, but
        // must never lose data if it somehow does.
        $mixedlog = ['before'];
        $mixedlog['user_snapshots'] = ['timemodified' => 12345, 'to_user' => null, 'from_user' => null];
        $mixedlog['actions'] = ['after'];
        $mixedid = $DB->insert_record('tool_mergeusers', (object) [
            'touserid' => 1,
            'fromuserid' => 2,
            'mergedbyuserid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
            'log' => json_encode($mixedlog),
            'status' => 'success',
        ]);

        tool_mergeusers_migrate_legacy_top_level_actions();

        $flat = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $flatid]), true);
        $this->assertArrayNotHasKey('user_snapshots', $flat);
        $this->assertSame(['Old action 1.', 'Old action 2.'], $flat['actions']);

        $broken = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $brokenid]), true);
        $this->assertSame(['timemodified' => 12345, 'to_user' => null, 'from_user' => null], $broken['user_snapshots']);
        $this->assertSame(['Old action 1.', 'Old action 2.'], $broken['actions']);
        $this->assertArrayNotHasKey('0', $broken);
        $this->assertArrayNotHasKey('1', $broken);

        $correct = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $correctid]), true);
        $this->assertSame($correctlog, $correct, 'An already-correct row with actions must be left untouched.');

        $empty = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $emptyid]), true);
        $this->assertSame($emptylog, $empty, 'An already-correct row with empty actions must be left untouched.');

        $mixed = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $mixedid]), true);
        $this->assertSame(['timemodified' => 12345, 'to_user' => null, 'from_user' => null], $mixed['user_snapshots']);
        $this->assertSame(['before', 'after'], $mixed['actions'], 'No data must be lost when actions already existed.');
    }

    /**
     * Test the real end-to-end upgrade sequence: a genuinely legacy row (the
     * original pre-a6ec50c flat shape) goes through tool_mergeusers_normalize_user_
     * snapshots() (savepoint 2026080100) first, exactly as db/upgrade.php runs them
     * in order, followed by tool_mergeusers_migrate_legacy_top_level_actions()
     * (savepoint 2026080500). The end result must have both a properly normalized
     * user_snapshots AND the original action lines under "actions" - reproducing,
     * and verifying the fix for, the exact bug reported from production.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_upgrade
     */
    public function test_migrate_legacy_top_level_actions_end_to_end_with_normalize_step(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/admin/tool/mergeusers/db/upgrade.php');

        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $legacyid = $DB->insert_record('tool_mergeusers', (object) [
            'touserid' => $touser->id,
            'fromuserid' => $fromuser->id,
            'mergedbyuserid' => 2,
            'timecreated' => time() - DAYSECS,
            'timemodified' => time() - DAYSECS,
            'log' => json_encode(['Old action 1.', 'Old action 2.']),
            'status' => 'success',
        ]);

        // Same order db/upgrade.php runs them in: 2026080100 before 2026080500.
        tool_mergeusers_normalize_user_snapshots();
        tool_mergeusers_migrate_legacy_top_level_actions();

        $result = json_decode($DB->get_field('tool_mergeusers', 'log', ['id' => $legacyid]), true);

        $this->assertArrayHasKey('timemodified', $result['user_snapshots']);
        $this->assertTrue($result['user_snapshots']['to_user']['recoverable']);
        $this->assertSame($touser->username, $result['user_snapshots']['to_user']['username']);
        $this->assertTrue($result['user_snapshots']['from_user']['recoverable']);
        $this->assertSame($fromuser->username, $result['user_snapshots']['from_user']['username']);
        $this->assertSame(['Old action 1.', 'Old action 2.'], $result['actions']);
    }
}

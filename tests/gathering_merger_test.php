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
 * Tests for gathering_merger.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers;

use advanced_testcase;
use tool_mergeusers\local\cli\gathering_merger;
use tool_mergeusers\local\cli\merge_request;
use tool_mergeusers\local\logger;
use tool_mergeusers\local\user_merger;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for tool_mergeusers\local\cli\gathering_merger.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_mergeusers\local\cli\gathering_merger
 */
final class gathering_merger_test extends advanced_testcase {
    /**
     * Setup for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test that a gathering action exposing 'fromsearchedfield'/'fromsearchedvalue' for
     * a side it could not resolve (fromid <= 0) reaches the stored log's snapshot.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_cli
     */
    public function test_merge_propagates_searched_field_hint_from_gathering_action(): void {
        $touser = $this->getDataGenerator()->create_user();

        $action = new merge_request();
        $action->toid = $touser->id;
        $action->fromid = 0;
        $action->fromsearchedfield = logger::SEARCHED_FIELD_USERNAME;
        $action->fromsearchedvalue = 'jsmith123';

        $mut = new gathering_merger(new user_merger());
        $mut->merge(new in_memory_gathering([$action]));

        $logger = new logger();
        $logs = $logger->get(['touserid' => $touser->id]);
        $stored = $logger->detail_from(reset($logs)->id);

        $this->assertSame('jsmith123', $stored->log->user_snapshots->from_user->username);
    }

    /**
     * Test that a hint reaches the stored snapshot even when the side it belongs to
     * DOES resolve to a real, existing user (fromid > 0) - not just for a notfound
     * side. This exercises the full CLI path (gathering_merger -> user_merger ->
     * logger) for the "hint prevails" behaviour already unit-tested in isolation on
     * logger::capture_user_snapshot().
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_cli
     */
    public function test_merge_propagates_searched_field_hint_for_a_resolved_side(): void {
        $touser = $this->getDataGenerator()->create_user();
        $fromuser = $this->getDataGenerator()->create_user();

        $action = new merge_request();
        $action->toid = $touser->id;
        $action->fromid = $fromuser->id;
        $action->fromsearchedfield = logger::SEARCHED_FIELD_USERNAME;
        $action->fromsearchedvalue = 'oldusername';

        $mut = new gathering_merger(new user_merger());
        $mut->merge(new in_memory_gathering([$action]));

        $logger = new logger();
        $logs = $logger->get(['touserid' => $touser->id, 'fromuserid' => $fromuser->id]);
        $stored = $logger->detail_from(reset($logs)->id);

        $this->assertTrue($stored->log->user_snapshots->from_user->recoverable);
        $this->assertSame('oldusername', $stored->log->user_snapshots->from_user->username);
        $this->assertSame($fromuser->email, $stored->log->user_snapshots->from_user->email);
    }

    /**
     * Test the real-world case reported from production: a gathering searching both
     * users by username can fail to resolve EITHER side, producing an action with
     * both toid = 0 and fromid = 0. Both sides' hints must reach their own snapshot
     * independently, and this must not be misreported as "the same user".
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_cli
     */
    public function test_merge_propagates_hints_for_both_sides_when_neither_is_resolved(): void {
        $action = new merge_request();
        $action->toid = 0;
        $action->fromid = 0;
        $action->tosearchedfield = logger::SEARCHED_FIELD_USERNAME;
        $action->tosearchedvalue = 'tokeep123';
        $action->fromsearchedfield = logger::SEARCHED_FIELD_USERNAME;
        $action->fromsearchedvalue = 'toremove123';

        $mut = new gathering_merger(new user_merger());
        $mut->merge(new in_memory_gathering([$action]));

        $logger = new logger();
        $logs = $logger->get(['touserid' => 0, 'fromuserid' => 0]);
        $stored = $logger->detail_from(reset($logs)->id);

        $this->assertSame('tokeep123', $stored->log->user_snapshots->to_user->username);
        $this->assertSame('toremove123', $stored->log->user_snapshots->from_user->username);
    }

    /**
     * Test that a gathering producing actions with none of the new hint properties -
     * exactly what every gathering implementation predating this feature does,
     * including the plain 'merge_request' shape - merges and logs exactly as before:
     * no error, and the resulting snapshot carries no hint at all.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_cli
     */
    public function test_merge_stays_fully_compatible_with_a_gathering_without_hint_properties(): void {
        $usertoremove = $this->getDataGenerator()->create_user();
        $usertokeep = $this->getDataGenerator()->create_user();

        $action = new merge_request();
        $action->toid = $usertokeep->id;
        $action->fromid = $usertoremove->id;

        $mut = new gathering_merger(new user_merger());
        $mut->merge(new in_memory_gathering([$action]));

        $logger = new logger();
        $logs = $logger->get(['touserid' => $usertokeep->id, 'fromuserid' => $usertoremove->id]);
        $stored = $logger->detail_from(reset($logs)->id);

        $this->assertTrue($stored->log->user_snapshots->from_user->recoverable);
        $this->assertSame($usertoremove->username, $stored->log->user_snapshots->from_user->username);
    }
}

// @codingStandardsIgnoreStart

/**
 * In-memory gathering implementation for testing purposes: iterates over a fixed
 * array of pre-built actions.
 *
 * @package   tool_mergeusers
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class in_memory_gathering implements \tool_mergeusers\local\cli\gathering {
    /** @var array list of actions to iterate over. */
    private array $actions;
    /** @var int current position. */
    private int $position = 0;

    /**
     * Constructor.
     *
     * @param array $actions list of merge_request actions with at least toid/fromid.
     */
    public function __construct(array $actions) {
        $this->actions = $actions;
    }

    /**
     * Rewinds the iterator.
     */
    public function rewind(): void {
        $this->position = 0;
    }

    /**
     * Tells whether the current position is valid.
     *
     * @return bool
     */
    public function valid(): bool {
        return isset($this->actions[$this->position]);
    }

    /**
     * Gets the current action.
     *
     * @return merge_request
     */
    public function current(): merge_request {
        return $this->actions[$this->position];
    }

    /**
     * Gets the current position.
     *
     * @return int
     */
    public function key(): int {
        return $this->position;
    }

    /**
     * Advances to the next action.
     */
    public function next(): void {
        $this->position++;
    }
}

// @codingStandardsIgnoreEnd

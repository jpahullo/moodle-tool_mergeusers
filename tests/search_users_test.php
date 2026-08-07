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
 * Search users
 *
 * @package    tool_mergeusers
 * @copyright  2024 Leon Stringer <leon.stringer@ntlworld.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers;

use advanced_testcase;
use coding_exception;
use dml_exception;
use tool_mergeusers\local\user_searcher;

/**
 * Tests for searching for users.
 * @covers \tool_mergeusers\local\user_searcher
 */
final class search_users_test extends advanced_testcase {
    /**
     * Test for searching for specific user fields.
     * Also, search must not return any matching deleted users.
     *
     * @param string $searchfield The field to search in.
     * @param string $input The search input value.
     * @param int $count The expected number of results.
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     * @dataProvider search_criteria
     * @throws dml_exception
     * @throws coding_exception
     */
    public function test_search_for_user_field_excluding_deleted_users(string $searchfield, string $input, int $count): void {
        $this->resetAfterTest(true);

        $deleteduser = $this->getDataGenerator()->create_user([
            'username' => 'student1', 'email' => 'student1@example.com',
            'firstname' => 'Student', 'lastname' => 'One',
            'idnumber' => 'ID001',
        ]);
        delete_user($deleteduser);
        $this->getDataGenerator()->create_user([
            'username' => 'student1', 'email' => 'student1@example.com',
            'firstname' => 'Student', 'lastname' => 'One',
            'idnumber' => 'ID001',
        ]);

        if (($searchfield === 'id') && ($input === 'id')) {
            $input = "{$deleteduser->id}";
        } else if ($searchfield === 'email') {
            $input = md5($deleteduser->username);
        }

        $mus = new user_searcher();
        $this->assertCount(
            $count,
            $mus->search_users($input, $searchfield)
        );
    }

    /**
     * Test various allowed values for MergeUserSearch->search_users()'s
     * $searchfield parameter.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public static function search_criteria(): array {
        return [
            'id' => [
                'searchfield' => 'id',
                'input' => 'id', // Special case, to be swapped with real ID into the test.
                'count' => 0,
            ],
            // Special case for database engines: compare the "id" int field against a string value.
            'id_with_letters' => [
                'searchfield' => 'id',
                'input' => 'abc',
                'count' => 0,
            ],
            'id_empty' => [
                'searchfield' => 'id',
                'input' => '',
                'count' => 0,
            ],
            'id_existing' => [
                'searchfield' => 'id',
                'input' => '1',
                'count' => 1, // Guest.
            ],
            'username' => [
                'searchfield' => 'username',
                'input' => 'student1',
                'count' => 1,
            ],
            'firstname' => [
                'searchfield' => 'firstname',
                'input' => 'Student',
                'count' => 1,
            ],
            'firstname_partial' => [
                'searchfield' => 'firstname',
                'input' => 'Stu',
                'count' => 1,
            ],
            'firstname_case_insensitive' => [
                'searchfield' => 'firstname',
                'input' => 'STUDENT',
                'count' => 1,
            ],
            'lastname' => [
                'searchfield' => 'lastname',
                'input' => 'One',
                'count' => 1,
            ],
            'email' => [
                'searchfield' => 'email',
                'input' => 'student1',
                'count' => 0,
            ],
            'idnumber' => [
                'searchfield' => 'idnumber',
                'input' => '', // Equates to '%%' which matches all idnumbers.
                'count' => 3, // Users guest + admin + student1.
            ],
            'all' => [
                'searchfield' => 'all',
                'input' => 'student1',
                'count' => 1,
            ],
            'all_partial' => [
                'searchfield' => 'all',
                'input' => 'stu',
                'count' => 1,
            ],
            'all_case_insensitive' => [
                'searchfield' => 'all',
                'input' => 'STUDENT1',
                'count' => 1,
            ],
        ];
    }

    /**
     * Test that count_users() reports the same number of matches search_users()
     * would return unbounded, regardless of any limit later applied to search_users().
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_count_users_matches_unbounded_search_users_count(): void {
        $this->resetAfterTest(true);

        for ($i = 0; $i < 5; $i++) {
            $this->getDataGenerator()->create_user(['firstname' => 'Findme', 'lastname' => "Number$i"]);
        }

        $mus = new user_searcher();

        $this->assertSame(5, $mus->count_users('Findme', 'firstname'));
        $this->assertCount(5, $mus->search_users('Findme', 'firstname'));
    }

    /**
     * Test that search_users()'s $limitnum caps the number of returned rows, while
     * count_users() still reports the true, uncapped total - the combination that
     * lets the "too many results" warning show an accurate count alongside a
     * deliberately truncated table.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_search_users_limitnum_caps_results_but_count_users_does_not(): void {
        $this->resetAfterTest(true);

        for ($i = 0; $i < 5; $i++) {
            $this->getDataGenerator()->create_user(['firstname' => 'Findme', 'lastname' => "Number$i"]);
        }

        $mus = new user_searcher();

        $this->assertCount(2, $mus->search_users('Findme', 'firstname', 2));
        $this->assertSame(5, $mus->count_users('Findme', 'firstname'));
    }

    /**
     * Test that search_users() with $limitnum = 0 (the default) still returns every
     * matching row, i.e. the new parameter does not change existing behaviour when
     * left at its default.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_search_users_default_limitnum_returns_everything(): void {
        $this->resetAfterTest(true);

        for ($i = 0; $i < 5; $i++) {
            $this->getDataGenerator()->create_user(['firstname' => 'Findme', 'lastname' => "Number$i"]);
        }

        $mus = new user_searcher();

        $this->assertCount(5, $mus->search_users('Findme', 'firstname'));
        $this->assertCount(5, $mus->search_users('Findme', 'firstname', 0));
    }

    /**
     * Test that a numeric $searchfield not listed in the searchbyprofilefields
     * setting is rejected and falls back to the "search all fields" behaviour,
     * instead of exposing an arbitrary profile field's data.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_search_users_ignores_non_allowlisted_profile_field_id(): void {
        global $DB;
        $this->resetAfterTest(true);

        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        // Deliberately not added to tool_mergeusers/searchbyprofilefields.

        $user = $this->getDataGenerator()->create_user();
        $uid = new \stdClass();
        $uid->userid = $user->id;
        $uid->fieldid = $fieldid;
        $uid->data = 'frogvalueone';
        $DB->insert_record('user_info_data', $uid);

        $mus = new user_searcher();

        $this->assertCount(0, $mus->search_users('frogvalueone', (string) $fieldid));
    }

    /**
     * Test that the "search all fields" branch also matches allow-listed profile
     * field data, not just the built-in user columns.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_search_users_all_fields_also_matches_allowlisted_profile_field(): void {
        global $DB;
        $this->resetAfterTest(true);

        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Nomatch', 'lastname' => 'Nomatch']);
        $uid = new \stdClass();
        $uid->userid = $user->id;
        $uid->fieldid = $fieldid;
        $uid->data = 'uniquefrogvalue';
        $DB->insert_record('user_info_data', $uid);

        $mus = new user_searcher();

        $this->assertCount(1, $mus->search_users('uniquefrogvalue', 'all'));
        $this->assertSame(1, $mus->count_users('uniquefrogvalue', 'all'));
    }

    /**
     * Regression test: once an allow-listed profile field extends the "search all
     * fields" WHERE clause with an extra OR term, a deleted user matching one of
     * the built-in fields (username/name/email/idnumber/id) must still be excluded.
     * SQL's AND binds tighter than OR, so appending "AND deleted = 0" without
     * parenthesising the whole OR expression would only constrain the last term.
     *
     * @group tool_mergeusers
     * @group tool_mergeusers_search_users
     */
    public function test_search_users_all_fields_excludes_deleted_users_when_profile_field_allowed(): void {
        $this->resetAfterTest(true);

        $fieldid = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'frogname', 'name' => 'Name of frog',
            'datatype' => 'text',
        ])->id;
        set_config('searchbyprofilefields', (string) $fieldid, 'tool_mergeusers');

        $deleteduser = $this->getDataGenerator()->create_user(['username' => 'deletedmatch']);
        delete_user($deleteduser);

        $mus = new user_searcher();

        $this->assertCount(0, $mus->search_users('deletedmatch', 'all'));
        $this->assertSame(0, $mus->count_users('deletedmatch', 'all'));
    }
}

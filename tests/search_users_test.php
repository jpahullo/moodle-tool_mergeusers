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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib/mergeusersearch.php');

/**
 * Tests for searching for users.
 */
final class search_users_test extends \advanced_testcase {
    /**
     * Test search criteria works with PostgreSQL because this will generate an
     * error when comparing and integer column (mdl_user.id) with a string.
     * @dataProvider search_criteria
     */
    public function test_pgsqlsearch($searchfield, $input, $count): void {
        global $DB;

        // Skip tests if not using PostgreSQL.
        if ($DB->get_dbfamily() != 'postgres') {
            $this->markTestSkipped('PostgreSQL-only test');
        }

        $mus = new \MergeUserSearch();
        $this->assertIsArray($mus->search_users($input, $searchfield));
    }

    /**
     * Test deleted users are not returned with any search criteria.
     * @dataProvider search_criteria
     */
    public function test_nodeletedusers($searchfield, $input, $count): void {
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

        if ($searchfield === 'id') {
            $input = $deleteduser->id;
        } else if ($searchfield === 'email') {
            $input = md5($deleteduser->username);
        }

        $mus = new \MergeUserSearch();
        $this->assertEquals($count,
                    count($mus->search_users($input, $searchfield)));
    }

    /**
     * Test various allowed values for MergeUserSearch->search_users()'s
     * $searchfield parameter.
     */
    public static function search_criteria(): array {
        return [
            'id' => [
                'searchfield' => 'id',
                'input' => '',
                'count' => 0,
            ],
            'id2' => [
                'searchfield' => 'id',
                'input' => 'abc',
                'count' => 0,
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
                'input' => '',  // Equates to '%%' which matches all idnumbers.
                'count' => 3,   // Users guest + admin + student1.
            ],
            'all' => [
                'searchfield' => 'all',
                'input' => 'student1',
                'count' => 1,
            ],
        ];
    }
}

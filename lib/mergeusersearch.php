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
 * Utility file.
 *
 * The effort of all given authors below gives you this current version of the file.
 *
 * @package    tool
 * @subpackage mergeusers
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @author     Mike Holzer
 * @author     Forrest Gaston
 * @author     Juan Pablo Torres Herrera
 * @author     Jordi Pujol-Ahulló <jordi.pujol@urv.cat>,  SREd, Universitat Rovira i Virgili
 * @author     John Hoopes <hoopes@wisc.edu>, University of Wisconsin - Madison
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/config.php';

global $CFG;

require_once $CFG->dirroot . '/lib/clilib.php';
require_once __DIR__ . '/autoload.php';

/**
 * A class to perform user search and lookup (verification)
 *
 * @author John Hoopes <hoopes@wisc.edu>
 */
class MergeUserSearch{


    /**
     * Searches the user table based on the input.
     *
     * @param mixed $input input
     * @param string $searchfield The field to search on.  empty string means all fields
     * @return array $results the results of the search
     */
    public function search_users($input, $searchfield){
        global $DB;

        switch($searchfield){
            case 'id': // search on id field

                // sql_cast_to_char() prevents PostgreSQL error when comparing
                // id column when $input is not an integer.
                $where = $DB->sql_cast_to_char('id') . ' = :userid';
                $params = ['userid' => $input];

                break;

            case 'username': // search on username
            case 'firstname': // search on firstname
            case 'lastname': // search on lastname
            case 'email': // search on email
            case 'idnumber': // search on idnumber

                $where = $DB->sql_like($searchfield, ":$searchfield", false, false);
                $params = [$searchfield => '%' . $input . '%'];

                break;

            default: // search on all fields by default

                $where = '(' .
                         $DB->sql_cast_to_char('id') . ' = :userid OR ' .
                         $DB->sql_like('username', ':username', false, false)
                         . ' OR ' .
                         $DB->sql_like('firstname', ':firstname', false, false)
                         . ' OR ' .
                         $DB->sql_like('lastname', ':lastname', false, false)
                         . ' OR ' .
                         $DB->sql_like('email', ':email', false, false)
                         . ' OR ' .
                         $DB->sql_like('idnumber', ':idnumber', false, false)
                         . ')';

                $params['userid'] = $input;
                $params['username'] = '%' . $input . '%';
                $params['firstname'] = '%' . $input . '%';
                $params['lastname'] = '%' . $input . '%';
                $params['email'] = '%' . $input . '%';
                $params['idnumber'] = '%' . $input . '%';

                break;
        }

        $where .= ' AND deleted = 0';
        return $DB->get_records_select('user', $where, $params, 'lastname, firstname');
    }

    /**
     * Verifies whether or not a user exists based upon the user information
     * to verify and the column that matches that information
     *
     * @param mixed $uinfo The identifying information about the user
     * @param string $column The column name to verify against.  (should not be direct user input)
     *
     * @return array
     *      (
     *          0 => Either NULL or the user object.  Will be NULL if not valid user,
     *          1 => Message for invalid user to display/log
     *      )
     */
    public function verify_user($uinfo, $column){
        global $DB;
        $message = '';
        try {
            $user = $DB->get_record('user', array($column => $uinfo), '*', MUST_EXIST);
        } catch (Exception $e) {
            $message = get_string('invaliduser', 'tool_mergeusers'). '('.$column . '=>' . $uinfo .'): ' . $e->getMessage();
            $user = null;
        }

        return array($user, $message);
    }


}

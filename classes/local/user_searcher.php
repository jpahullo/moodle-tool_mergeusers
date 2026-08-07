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
 * @package   tool_mergeusers
 * @author    Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @author    Mike Holzer
 * @author    Forrest Gaston
 * @author    Juan Pablo Torres Herrera
 * @author    Jordi Pujol-Ahulló <jordi.pujol@urv.cat>, Universitat Rovira i Virgili
 * @author    John Hoopes <hoopes@wisc.edu>, University of Wisconsin - Madison
 * @copyright Universitat Rovira i Virgili (https://www.urv.cat)
 * @copyright University of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local;

use coding_exception;
use dml_exception;
use Exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/clilib.php');

/**
 * A class to perform user search and verification.
 *
 * @author John Hoopes <hoopes@wisc.edu>
 */
final class user_searcher {
    /**
     * Searches users matching a condition on a given field and text to match partially for that field.
     *
     * @param string $input Term to search by.
     * @param string $searchfield The user's field to search by. Empty string means searching by all fields.
     * @param int $limitnum maximum number of records to get. 0 to get all.
     * @return array the results of the search, at most $limitnum entries when $limitnum > 0.
     * @throws dml_exception
     */
    public function search_users(string $input, string $searchfield, int $limitnum = 0): array {
        global $DB;

        [$where, $params] = $this->build_search_where($input, $searchfield);
        return $DB->get_records_select('user', $where, $params, 'lastname, firstname', '*', 0, $limitnum);
    }

    /**
     * Counts users matching the same condition search_users() would use, regardless of any limit.
     * Used to detect a search that is too broad before rendering every matching user.
     *
     * @param string $input Term to search by.
     * @param string $searchfield The user's field to search by. Empty string means searching by all fields.
     * @return int number of matching users.
     * @throws dml_exception
     */
    public function count_users(string $input, string $searchfield): int {
        global $DB;

        [$where, $params] = $this->build_search_where($input, $searchfield);
        return $DB->count_records_select('user', $where, $params);
    }

    /**
     * Builds the WHERE clause and bound parameters shared by search_users() and count_users().
     *
     * @param string $input Term to search by.
     * @param string $searchfield The user's field to search by. Empty string means searching by all fields.
     * @return array{0: string, 1: array} [$where, $params].
     */
    private function build_search_where(string $input, string $searchfield): array {
        global $DB;

        switch ($searchfield) {
            // Search on id field.
            case 'id':
                // The sql_cast_to_char() prevents PostgreSQL error when comparing id column when $input is not an integer.
                $where = $DB->sql_cast_to_char('id') . ' = :userid';
                $params = ['userid' => $input];
                break;
            // Searching by any of these fields.
            case 'username':
            case 'firstname':
            case 'lastname':
            case 'email':
            case 'idnumber':
                $where = $DB->sql_like($searchfield, ":$searchfield", false, false);
                $params = [$searchfield => '%' . $input . '%'];
                break;
            // Search on all fields by default.
            default:
                $allowedfields = array_keys(profile_fields::allowed());

                if (is_numeric($searchfield) && in_array((int) $searchfield, $allowedfields, true)) {
                    // Search on a specific custom user profile field, allow-listed at settings.
                    $where = 'id IN (SELECT userid FROM {user_info_data} WHERE fieldid = :fieldid AND ' .
                             $DB->sql_like('data', ':data', false, false) . ')';
                    $params = ['fieldid' => (int) $searchfield, 'data' => '%' . $input . '%'];
                } else {
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

                    // The "all fields" search also looks inside any custom user profile field
                    // allow-listed at settings, via a subquery - never a JOIN, so no risk of
                    // duplicate {user} rows.
                    if (!empty($allowedfields)) {
                        [$insql, $inparams] = $DB->get_in_or_equal($allowedfields, SQL_PARAMS_NAMED, 'apf');
                        $where .= ' OR id IN (SELECT userid FROM {user_info_data} WHERE fieldid ' . $insql .
                                  ' AND ' . $DB->sql_like('data', ':pfdata', false, false) . ')';
                        $params += $inparams;
                        $params['pfdata'] = '%' . $input . '%';
                    }

                    // Parenthesise the whole OR expression: AND binds tighter than OR in SQL, so
                    // without this the "AND deleted = :deleted" appended below would only apply to
                    // the last OR term, letting deleted users leak back into the results.
                    $where = '(' . $where . ')';
                }
                break;
        }

        $where .= ' AND deleted = :deleted';
        $params['deleted'] = 0;
        return [$where, $params];
    }

    /**
     * Verifies whether a user exists based upon the user information
     * to verify and the column that matches that information.
     *
     * The result has this structure:
     *   [
     *       0 => Either NULL or the user object.  Will be NULL if not valid user or without actual selection,
     *       1 => Message for invalid user to display/log. Empty string for no actual selection.
     *   ]
     *
     * @param ?string $value The identifying information about the user. Null when no actual selection was done.
     * @param string $field The column name to verify against. (Should not be direct user input)
     *
     * @return array two positions with the results of the verification.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function verify_user(?string $value, string $field): array {
        global $DB;

        // Inform there is no actual selection this time.
        if (is_null($value)) {
            return [null, ''];
        }

        // Check for existing user matching the specified criteria.
        $message = '';
        if (is_numeric($field)) {
            // The field is a custom user profile field id. Reject it outright if it
            // is not allow-listed, rather than falling back to any other search
            // strategy; and require an *exact* match on a single user - unlike
            // search_users(), which does partial (LIKE) matching and could
            // otherwise silently resolve to the wrong one of several users sharing
            // an overlapping profile-field value.
            $fieldid = (int) $field;
            if (!array_key_exists($fieldid, profile_fields::allowed())) {
                $message = get_string('invaliduser', 'tool_mergeusers', ['field' => $field, 'value' => $value]);
                $user = null;
            } else {
                try {
                    $user = $DB->get_record_sql(
                        'SELECT u.* FROM {user} u JOIN {user_info_data} d ON d.userid = u.id ' .
                        'WHERE d.fieldid = :fieldid AND d.data = :data AND u.deleted = 0',
                        ['fieldid' => $fieldid, 'data' => $value],
                        MUST_EXIST,
                    );
                } catch (Exception $e) {
                    $message = get_string('invaliduser', 'tool_mergeusers', ['field' => $field, 'value' => $value]);
                    $user = null;
                }
            }
        } else {
            try {
                $user = $DB->get_record('user', [$field => $value, 'deleted' => 0], '*', MUST_EXIST);
            } catch (Exception $e) {
                $message = get_string('invaliduser', 'tool_mergeusers', ['field' => $field, 'value' => $value]);
                $user = null;
            }
        }

        return [$user, $message];
    }
}

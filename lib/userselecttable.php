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
 * User select table util file
 *
 * @package    tool_mergeusers
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @author     Mike Holzer
 * @author     Forrest Gaston
 * @author     Juan Pablo Torres Herrera
 * @author     Jordi Pujol-Ahulló, Sred, Universitat Rovira i Virgili
 * @author     John Hoopes <hoopes@wisc.edu>, Univeristy of Wisconsin - Madison
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__, 4) . '/config.php');

global $CFG;

// Require needed library files.
require_once($CFG->dirroot . '/lib/clilib.php');
require_once(__DIR__ . '/autoload.php');

/**
 * Extend the html table to provide a build function inside for creating a table
 * for user selecting.
 *
 * @author  John Hoopes <hoopes@wisc.edu>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class UserSelectTable extends html_table implements renderable {
    /** @var tool_mergeusers_renderer Renderer to show user info. */
    protected $renderer;

    /**
     * Call parent construct
     *
     * @param array $users
     * @param tool_mergeusers_renderer $renderer
     *
     * @throws coding_exception
     */
    public function __construct(array $users, tool_mergeusers_renderer $renderer) {
        parent::__construct();
        $this->renderer = $renderer;
        $this->buildtable($users);
    }

    /**
     * Build the user select table using the extension of html_table
     *
     * @param array $users array of user results
     * @throws coding_exception
     */
    protected function buildtable($users): void {
        // Reset any existing data
        $this->data = array();

        $this->id = 'merge_users_tool_user_select_table';
        $this->attributes['class'] = 'generaltable boxaligncenter';
        $suspendedstr = get_string('suspended');

        $columns = [
            'col_reset' => get_string('reset'),
            'col_select_olduser' => get_string('olduser', 'tool_mergeusers'),
            'col_master_newuser' => get_string('newuser', 'tool_mergeusers'),
            'col_userid' => 'Id',
            'col_suspended' => $suspendedstr,
            'col_username' => get_string('user'),
            'col_email' => get_string('email'),
            'col_idnumber' => get_string('idnumber'),
        ];

        $this->head = array_values($columns);
        $this->colclasses = array_keys($columns);
        $reset = get_string('reset');

        foreach ($users as $userid => $user) {
            $row = [];
            $spanclass = ($user->suspended) ? ('usersuspended') : ('');
            $suspendedstrrow = ($user->suspended) ? $suspendedstr : '';
            $row[] = html_writer::tag(
                'a',
                $reset,
                [
                    'href' => "javascript:rbo=document.getElementById('olduser$userid'); " .
                        "rbn=document.getElementById('newuser$userid'); " .
                        "rbo.checked=false; rbn.checked=false; rbo.disabled=false; rbn.disabled=false;",
                ],
            );
            $row[] = html_writer::empty_tag(
                'input',
                ['type' => 'radio', 'name' => 'olduser', 'value' => $userid, 'id' => 'olduser' . $userid],
            );
            $row[] = html_writer::empty_tag(
                'input',
                ['type' => 'radio', 'name' => 'newuser', 'value' => $userid, 'id' => 'newuser' . $userid],
            );
            $row[] = html_writer::tag('span', $user->id, ['class' => $spanclass]);
            $row[] = html_writer::tag('span', $suspendedstrrow, ['class' => $spanclass]);
            $row[] = html_writer::tag('span', $this->renderer->show_user($user->id, $user), ['class' => $spanclass]);
            $row[] = html_writer::tag('span', $user->email, ['class' => $spanclass]);
            $row[] = html_writer::tag('span', $user->idnumber, ['class' => $spanclass]);
            $this->data[] = $row;
        }
    }
}

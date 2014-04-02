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
 * @package    tool
 * @subpackage mergeusers
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @author     Mike Holzer
 * @author     Forrest Gaston
 * @author     Juan Pablo Torres Herrera
 * @author     Jordi Pujol-Ahulló, Sred, Universitat Rovira i Virgili
 * @author     John Hoopes <hoopes@wisc.edu>, Univeristy of Wisconsin - Madison
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(dirname(__DIR__)))) . '/config.php');

global $CFG;

// require needed library files
require_once($CFG->dirroot . '/lib/clilib.php');
require_once(__DIR__ . '/autoload.php');
require_once($CFG->dirroot.'/lib/outputcomponents.php');

/**
 * Extend the html table to provide a build function inside for creating a table for user selecting
 *
 * @author  John Hoopes <hoopes@wisc.edu>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class UserSelectTable extends html_table implements renderable{

    /**
     * Call parent construct
     *
     * @param array $users
     *
     */
    public function __construct($users){
        parent::__construct();
        $this->buildtable($users);
    }

    /**
     * Build the user select table using the extension of html_table
     *
     * @param array $users array of user results
     *
     */
    public function buildtable($users){

        // Reset any existing data
        $this->data = array();

        $this->id = 'merge_users_tool_user_select_table';
        $this->attributes['class'] = 'generaltable boxaligncenter';

        $columns = array(
            'col_select_olduser' => get_string('selecttable_select_user', 'tool_mergeusers'),
            'col_master_newuser' => get_string('selecttable_select_master_user', 'tool_mergeusers'),
            'col_userid'         => get_string('selecttable_userid', 'tool_mergeusers'),
            'col_username'       => get_string('selecttable_username', 'tool_mergeusers'),
            'col_firstname'      => get_string('selecttable_firstname', 'tool_mergeusers'),
            'col_lastname'       => get_string('selecttable_lastname', 'tool_mergeusers'),
            'col_email'          => get_string('selecttable_email', 'tool_mergeusers'),
        );
        $this->head = array_values($columns);
        $this->colclasses = array_keys($columns);

        foreach($users as $userid => $user){

            $row = array();

            $row[] = html_writer::empty_tag('input', array('type'=>'radio', 'name'=>'olduser', 'value'=>$userid));
            $row[] = html_writer::empty_tag('input', array('type'=>'radio', 'name'=>'newuser', 'value'=>$userid));
            $row[] = $user->id;
            $row[] = $user->username;
            $row[] = $user->firstname;
            $row[] = $user->lastname;
            $row[] = $user->email;
            $this->data[] = $row;
        }
    }


}
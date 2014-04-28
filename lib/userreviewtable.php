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
 * User review table util file
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
 * Extend the html table to provide a build function inside for creating a table for reviewing the users to merge
 *
 * @author  John Hoopes <hoopes@wisc.edu>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class UserReviewTable extends html_table implements renderable{

    /** @var stdClass $olduser The olduser db object */
    protected $olduser;

    /** @var stdClass $newuser The newuser db object */
    protected $newuser;

    /** @var bool $showmergebutton Whether or not to show the merge button on rendering */
    protected $showmergebutton = false;

    /**
     * Call parent construct and then build table
     */
    public function __construct(){
        global $SESSION;

        // Call parent constructor
        parent::__construct();

        if(!empty($SESSION->mut)){
            if(!empty($SESSION->mut->olduser)){
                $this->olduser = $SESSION->mut->olduser;
            }
            if(!empty($SESSION->mut->newuser)){
                $this->newuser = $SESSION->mut->newuser;
            }
        }
        $this->buildtable();
    }

    /**
     * Build the user select table using the extension of html_table
     *
     */
    public function buildtable(){

        // Reset any existing data
        $this->data = array();

        if(!empty($this->olduser) || !empty($this->newuser)){ // if there is a user add table rows and columns

            $this->id = 'merge_users_tool_user_review_table';
            $this->attributes['class'] = 'generaltable boxaligncenter';

            $columns = array(
                'col_label'          => '',
                'col_userid'         => 'Id',
                'col_username'       => get_string('username'),
                'col_firstname'      => get_string('firstname'),
                'col_lastname'       => get_string('lastname'),
                'col_email'          => get_string('email'),
            );
            $this->head = array_values($columns);
            $this->colclasses = array_keys($columns);

            // Always display both rows so that the end user can see what is selected/not selected

            // Add old user row
            $olduserrow = array();
            $olduserrow[] = get_string('olduser', 'tool_mergeusers');
            if(!empty($this->olduser)){ // if there is an old user display it
                $olduserrow[] = $this->olduser->id;
                $olduserrow[] = $this->olduser->username;
                $olduserrow[] = $this->olduser->firstname;
                $olduserrow[] = $this->olduser->lastname;
                $olduserrow[] = $this->olduser->email;
            }else{ // otherwise display empty fields
                $olduserrow[] = '';
                $olduserrow[] = '';
                $olduserrow[] = '';
                $olduserrow[] = '';
                $olduserrow[] = '';
            }
            $this->data[] = $olduserrow;

            // Add new user row
            $newuserrow = array();
            $newuserrow[] = get_string('newuser', 'tool_mergeusers');
            if(!empty($this->newuser)){ // if there is an new user display it
                $newuserrow[] = $this->newuser->id;
                $newuserrow[] = $this->newuser->username;
                $newuserrow[] = $this->newuser->firstname;
                $newuserrow[] = $this->newuser->lastname;
                $newuserrow[] = $this->newuser->email;
            }else{ // otherwise display empty fields
                $newuserrow[] = '';
                $newuserrow[] = '';
                $newuserrow[] = '';
                $newuserrow[] = '';
                $newuserrow[] = '';
            }
            $this->data[] = $newuserrow;

            // If both are not empty this means we can show merge button
            if(!empty($this->olduser) && !empty($this->newuser)){
                $this->showmergebutton = true;
            }
        }
    }

    /**
     * Simple get function so that the showmergebutton var is protected from outside
     *
     * @return bool whether or not to show the button
     */
    public function show_button(){
        return $this->showmergebutton;
    }
}

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
 * Version information
 *
 * @package    tool
 * @subpackage mergeusers
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @author     Mike Holzer
 * @author     Forrest Gaston
 * @author     Juan Pablo Torres Herrera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once($CFG->libdir.'/formslib.php'); /// forms library

/**
 * Define form snippet for getting the userids of the two users to merge
 */
class mergeuserform extends moodleform {

    /**
     * Form definition
     *
     * @uses $CFG
     */
    public function definition() {
        global $CFG;

        $mform =& $this->_form;

        $strrequired = get_string('required');

        // Add elements
        $mform->addElement('text', 'olduserid', get_string('olduserid', 'report_mergeusers'), 'size="10"');
        $mform->setType('olduserid', PARAM_INT);
        $mform->addRule('olduserid', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'newuserid', get_string('newuserid', 'report_mergeusers'), 'size="10"');
        $mform->setType('newuserid', PARAM_INT);
        $mform->addRule('newuserid', $strrequired, 'required', null, 'client');

        $this->add_action_buttons(false, get_string('mergeusers', 'report_mergeusers'));
    }

}


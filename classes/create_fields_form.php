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

use moodleform;

/**
 * Create recommended user profile fields form.
 *
 * @package    tool
 * @subpackage mergeusers
 * @author     Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_fields_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'profilefield_category', get_string('profilefieldcategory', 'tool_mergeusers'));
        $mform->setType('profilefield_category', 'text');
        $mform->addRule('profilefield_category', get_string('error'), 'required');
        $mform->addElement('submit', 'submit', get_string('createrecommendedfields', 'tool_mergeusers'));
    }
}

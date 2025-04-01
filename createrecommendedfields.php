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
 * Create recommended user profile fields tool.
 *
 * @package    tool
 * @subpackage mergeusers
 * @author     Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mergeusers\create_fields_form;
use tool_mergeusers\local\profile_fields;

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$categoryname = optional_param('profilefield_category', '', PARAM_TEXT);

admin_externalpage_setup('tool_mergeusers_createrecommendedfields');

$form = new create_fields_form();

if (!empty($categoryname)) {
    profile_fields::setup_recommended_fields($categoryname);
    redirect($PAGE->url, get_string('fieldscreatedsuccessfully', 'tool_mergeusers'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

echo html_writer::tag('p', nl2br(get_string('createrecommendedfields_explanation', 'tool_mergeusers', implode(', ', profile_fields::DEFAULT_MERGE_FIELD_SHORTNAMES))));

$form->display();

echo $OUTPUT->footer();

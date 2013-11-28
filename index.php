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

// Report all PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require('../../../config.php');

global $CFG;

// Report all PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/weblib.php');

require_once('./index_form.php');
require_once('./locallib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

admin_externalpage_setup('toolmergeusers');

// Define the form
$mergeuserform = new mergeuserform();
$mut = new MergeUserTool();
$renderer = $PAGE->get_renderer('tool_mergeusers');

$data = $mergeuserform->get_data();
$mut->init();

// Any submitted data?
if ($data) {

    // Get the userids
    $log = array();
    $success = true;
    $fromuser = null;
    $touser = null;

    try {
        $fromuser = $DB->get_record('user', array($data->oldusergroup['olduseridtype'] => $data->oldusergroup['olduserid']), '*', MUST_EXIST);
    } catch (Exception $e) {
        $log[] = get_string('invaliduser', 'tool_mergeusers'). '('.$data->oldusergroup['olduseridtype'] . '=>' . $data->oldusergroup['olduserid'].'): ' . $e->getMessage();
        $success = false;
    }
    try {
        $touser = $DB->get_record('user', array($data->newusergroup['newuseridtype'] => $data->newusergroup['newuserid']), '*', MUST_EXIST);
    } catch (Exception $e) {
        $log[] = get_string('invaliduser', 'tool_mergeusers'). '('.$data->newusergroup['newuseridtype'] . '=>' . $data->newusergroup['newuserid'].'): ' . $e->getMessage();
        $success = false;
    }

    if ($success) {
        list($success, $log) = $mut->merge($touser->id, $fromuser->id);
    }
    echo $renderer->results_page($touser, $fromuser, $success, $log);

}  else {

    // no form submitted data
    echo $renderer->index_page($mergeuserform);
}

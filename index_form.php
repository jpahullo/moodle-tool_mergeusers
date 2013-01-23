<?php

/**
 * Form definition for mergeusers report
 *  
 * @package    report
 * @subpackage mergeusers
 * @author     Forrest Gaston & Juan Pablo Torres Herrera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    2012052300
 *
 * Based on:
 *
 * @author Shane Elliott, Pukunui Technology
 * @link http://moodle.org/mod/forum/discuss.php?d=103425
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package admin-report-mergeusers
 * @version 2010092300
 *
 */

require_once($CFG->libdir.'/formslib.php'); /// forms library

/**
 * Define form snippet for getting the userids of the two users to merge
 */
class mergeuserform extends moodleform{

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

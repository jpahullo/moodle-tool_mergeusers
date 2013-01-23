<?php

/**
 * Version for the mergeusers report
 *
 * @author     Forrest Gaston & Juan Pablo Torres Herrera
 * @package    report
 * @subpackage mergeusers
 * @link       http://moodle.org/mod/forum/discuss.php?d=103425
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    2012052500
 */
 
require_once(dirname(__FILE__) . '/../../config.php');

$ADMIN->add('reports', new admin_externalpage('reportmergeusers', get_string('mergeusers', 'report_mergeusers'), "$CFG->wwwroot/report/mergeusers/index.php", 'report/mergeusers:view'));

$settings = null;

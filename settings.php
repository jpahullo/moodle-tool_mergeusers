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

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('accounts',
            new admin_category('tool_mergeusers', get_string('pluginname', 'tool_mergeusers')));
    $ADMIN->add('tool_mergeusers',
            new admin_externalpage('tool_mergeusers_merge', get_string('pluginname', 'tool_mergeusers'),
            $CFG->wwwroot.'/'.$CFG->admin.'/tool/mergeusers/index.php',
            'moodle/site:config'));
    $ADMIN->add('tool_mergeusers',
            new admin_externalpage('tool_mergeusers_viewlog', get_string('viewlog', 'tool_mergeusers'),
            $CFG->wwwroot.'/'.$CFG->admin.'/tool/mergeusers/view.php',
            'moodle/site:config'));
}

// Add configuration for making user suspension optional
$settings = new admin_settingpage('mergeusers_settings',
    get_string('pluginname', 'tool_mergeusers'));

$settings->add(new admin_setting_configcheckbox('tool_mergeusers/suspenduser',
    get_string('suspenduser_setting', 'tool_mergeusers'),
    get_string('suspenduser_setting_desc', 'tool_mergeusers'),
    1));

$settings->add(new admin_setting_configcheckbox('tool_mergeusers/transactions',
    get_string('transactions_setting', 'tool_mergeusers'),
    get_string('transactions_setting_desc', 'tool_mergeusers'),
    1));

// Add settings
$ADMIN->add('tools', $settings);

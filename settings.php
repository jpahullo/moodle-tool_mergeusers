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
 * @author     John Hoopes <hoopes@wisc.edu>, University of Wisconsin - Madison
 * @author     Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// load settings under the Administration -> User section.
if (has_capability('tool/mergeusers:mergeusers', context_system::instance())) {
    require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/mergeusers/lib/autoload.php');
    require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/mergeusers/lib.php');

    $ADMIN->add('accounts',
            new admin_category('tool_mergeusers', get_string('pluginname', 'tool_mergeusers')));
    $ADMIN->add('tool_mergeusers',
            new admin_externalpage('tool_mergeusers_merge', get_string('pluginname', 'tool_mergeusers'),
            $CFG->wwwroot.'/'.$CFG->admin.'/tool/mergeusers/index.php',
            'tool/mergeusers:mergeusers'));
    $ADMIN->add('tool_mergeusers',
            new admin_externalpage('tool_mergeusers_viewlog', get_string('viewlog', 'tool_mergeusers'),
            $CFG->wwwroot.'/'.$CFG->admin.'/tool/mergeusers/view.php',
            'tool/mergeusers:mergeusers'));
}

// only system settings only when necessary.
if ($hassiteconfig) {
    //TODO: can we make cache/singleton for the settings? It is processed twice when loading.
    // ****************** General settings ************************************
    // Add configuration for making user suspension optional
    $settings = new admin_settingpage('mergeusers_settings',
        get_string('pluginname', 'tool_mergeusers'));

    $settings->add(new admin_setting_configcheckbox('tool_mergeusers/suspenduser',
        get_string('suspenduser', 'tool_mergeusers'),
        get_string('suspenduser_desc', 'tool_mergeusers'),
        1));

    $supporting_lang = (tool_mergeusers_transactionssupported()) ? 'transactions_supported' : 'transactions_not_supported';

    $settings->add(new admin_setting_configcheckbox('tool_mergeusers/transactions_only',
        get_string('transactions', 'tool_mergeusers'),
        get_string('transactions_desc', 'tool_mergeusers') . '<br /><br />' .
            get_string($supporting_lang, 'tool_mergeusers'),
        1));


    // ****************** TableMerger settings *********************************
    $config = tool_mergeusers_config::instance();

    foreach ($config->tablemergers as $tablemerger) {
        $instance = new $tablemerger();
        $specific_settings = $instance->getSettings();
        if (!empty($specific_settings)) {
            $settings->add(new admin_setting_heading('tool_mergeusers/tablemerger_settings',
                get_string('tablemerger_settings', 'tool_mergeusers', $tablemerger),
                get_string('tablemerger_settings_desc', 'tool_mergeusers', $tablemerger)));
            foreach($specific_settings as $setting) {
                $settings->add($setting);
            }
        }
    }

    // ****************** Cron settings ************************************
    $settings->add(new admin_setting_heading('tool_mergeusers/cronsettings',
        get_string('cronsettings', 'tool_mergeusers') . $OUTPUT->help_icon('cronsettings', 'tool_mergeusers'),
        get_string('cronsettings_desc', 'tool_mergeusers')));

    $setting = new admin_setting_configtext('tool_mergeusers/gathering',
        get_string('gathering', 'tool_mergeusers'),
        get_string('gathering_desc', 'tool_mergeusers'),
        'CLIGathering',
        PARAM_ALPHANUMEXT);
    $settings->add($setting);

    // ****************** Table settings ************************************

    $settings->add(new admin_setting_heading('tool_mergeusers/exclude_tables_settings',
        get_string('exclude_tables_settings', 'tool_mergeusers') . $OUTPUT->help_icon('exclude_tables_settings', 'tool_mergeusers'),
        get_string('exclude_tables_settings_desc', 'tool_mergeusers')));

    global $DB;
    $tables = $DB->get_tables(false);
    $options_with_all_tables = array_combine($tables, $tables);

    // tables to be excluded from merging users
    $excluded_tables_default = array(
        'user_preferences',
        'user_private_key',
        'user_info_data',
        'my_pages',
    );
    $setting = new admin_setting_configmultiselect('tool_mergeusers/excluded_tables',
        get_string('excluded_tables', 'tool_mergeusers'),
        get_string('excluded_tables_desc', 'tool_mergeusers'),
        $excluded_tables_default,
        $options_with_all_tables);
    $current_options = $setting->get_setting();
    if (empty($current_options) || array_search('my_pages', $current_options) === false) {
        $current_options[] = 'my_pages'; // this has always to be selected.
    }
    $setting->write_setting($current_options);
    $settings->add($setting);


    $settings->add(new admin_setting_heading('tool_mergeusers/tablesettings',
        get_string('tablesettings', 'tool_mergeusers') . $OUTPUT->help_icon('tablesettings', 'tool_mergeusers'),
        get_string('specifiedtablesettingsoperation', 'tool_mergeusers')));

    $column_options = $config->getAllColumns();

    $default_column_options = array('authorid', 'reviewerid', 'userid', 'user_id', 'id_user', 'user');
    foreach ($default_column_options as $key => $column_name) {
        if (!isset($column_options[$column_name])) {
            unset($default_column_options[$key]);
        }
    }

    $setting = new admin_setting_configmultiselect('tool_mergeusers/user_related_columns_for_default',
        get_string('user_related_columns_for_default_setting', 'tool_mergeusers'),
        get_string('user_related_columns_for_default_setting_desc', 'tool_mergeusers'),
        $default_column_options,
        $column_options);
    $settings->add($setting);

    $defaultFields = array(
        'message_contacts' => array(
            'userid',
            'contactid',
        ),
        'message_read' => array(
            'useridfrom',
            'useridto',
        ),
        'message' => array(
            'useridfrom',
            'useridto',
        ),
        'question' => array(
            'createdby',
            'modifiedby',
        ),
    );

    $setting = new admin_setting_configmultiselect('tool_mergeusers/tables_with_custom_user_related_columns',
        get_string('tables_with_custom_user_related_columns', 'tool_mergeusers'),
        get_string('tables_with_custom_user_related_columns_desc', 'tool_mergeusers'),
        array_keys($defaultFields),
        $options_with_all_tables);
    $tables_with_custom_user_related_columns = $setting->get_setting();
    $settings->add($setting);

    if (!empty($tables_with_custom_user_related_columns)) {
        foreach ($tables_with_custom_user_related_columns as $table) {
            $columns = $DB->get_columns($table, false);
            $columnnames = array_keys($columns);
            $options_for_table = array_combine($columnnames, $columnnames);
            $setting = new admin_setting_configmultiselect('tool_mergeusers/user_related_columns_for_'.$table,
                $table,
                get_string('user_related_columns_for_table_setting_desc', 'tool_mergeusers'),
                ((isset($defaultFields[$table]))?$defaultFields[$table]:array()),
                $options_for_table);
            $settings->add($setting);
        }
    }

    $renderer = $PAGE->get_renderer('tool_mergeusers');
    $indexesOptions = $config->getCompoundIndexes();
    $settings->add(new admin_setting_heading('tool_mergeusers/unique_indexes_settings',
        get_string('unique_indexes_settings', 'tool_mergeusers'),
        get_string('unique_indexes_settings_desc', 'tool_mergeusers') . '<br><br>' .
            $renderer->build_indexes_table($indexesOptions, 1)));

    $settings->add(new admin_setting_heading('tool_mergeusers/nonunique_index_settings',
        get_string('nonunique_index_settings', 'tool_mergeusers') . $OUTPUT->help_icon('nonunique_index_settings', 'tool_mergeusers'),
        get_string('nonunique_index_operation', 'tool_mergeusers')));

    $nonuniqueoptions = $config->getNonuniqueIndexOptions($renderer);

    $defaultnonuniquetables = array(
        'event' => true,
        'role_assignments' => true,
    );
    $nonuniquetables = array();
    foreach ($nonuniqueoptions as $key => $value) {
        list($table, $columns) = explode(':', $key);
        $nonuniquesetting = new admin_setting_configcheckbox('tool_mergeusers/nonunique_indexes_for_' . $table,
            $table, $value, (int)isset($defaultnonuniquetables[$table]));
        $settingvalue = $nonuniquesetting->get_setting();
        if ($settingvalue !== false && $settingvalue != 0) {
            $nonuniquetables[$table] = $table;
        }
        $settings->add($nonuniquesetting);
    }

    $settings->add(new admin_setting_heading('tool_mergeusers/tables_with_adhoc_indexes_settings',
        get_string('tables_with_adhoc_indexes_settings', 'tool_mergeusers') . $OUTPUT->help_icon('tables_with_adhoc_indexes_settings', 'tool_mergeusers'),
        get_string('tables_with_adhoc_indexes_settings_desc', 'tool_mergeusers')));

    $adhoctables = array_filter($options_with_all_tables, function($value) use ($nonuniquetables, $config) {
        return !isset($nonuniquetables[$value]) && !$config->tableHasUniqueIndex($value);
    });

    $setting = new admin_setting_configmultiselect('tool_mergeusers/tables_with_adhoc_indexes',
        get_string('tables_with_adhoc_indexes', 'tool_mergeusers'),
        get_string('tables_with_adhoc_indexes_desc', 'tool_mergeusers'),
        array('groups_members','journal_entries'),
        $adhoctables);
    $tables_with_adhoc_indexes = $setting->get_setting();
    $settings->add($setting);

    if ($tables_with_adhoc_indexes) {

        $adhocIndexFields = array(
            'groups_members' => array(
                'groupid',
                'userid',
            ),
            'journal_entries' => array(
                'userid',
                'journal',
            ),
        );

        foreach ($tables_with_adhoc_indexes as $table) {
            $columns = $DB->get_columns($table, false);
            $columnnames = array_keys($columns);
            $options_for_table = array_combine($columnnames, $columnnames);
            $setting = new admin_setting_configmultiselect(
                'tool_mergeusers/columns_for_adhoc_index_for_'.$table,
                $table,
                get_string('columns_for_adhoc_index_for_table_setting_desc', 'tool_mergeusers'),
                ((isset($adhocIndexFields[$table]))?$adhocIndexFields[$table]:array()),
                $options_for_table);
            $setting->set_updatedcallback('tool_mergeusers_save_settings');
            $settings->add($setting);
        }
    }

    $checkingOptions = $config->getIndexesToCheckOptions($nonuniquetables);
    $settings->add(new admin_setting_heading('tool_mergeusers/check_indexes_settings',
        get_string('check_indexes_settings', 'tool_mergeusers'),
        get_string('check_indexes_settings_desc', 'tool_mergeusers') . '<br><br>' .
            $renderer->build_indexes_table($checkingOptions)));

    // Add settings
    $tool_mergeusers_settings = $settings;
    $ADMIN->add('tools', $settings);
}

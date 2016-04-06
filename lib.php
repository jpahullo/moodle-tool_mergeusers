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
 * mergeusers functions.
 *
 * @package    tool_mergeusers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Gets whether database transactions are allowed.
 * @global moodle_database $DB
 * @return bool true if transactions are allowed. false otherwise.
 */
function tool_mergeusers_transactionssupported() {
    global $DB;

    // Tricky way of getting real transactions support, without re-programming it.
    // May be in the future, as phpdoc shows, this method will be publicly accessible.
    $method = new ReflectionMethod($DB, 'transactions_supported');
    $method->setAccessible(true); //method is protected; make it accessible.
    return $method->invoke($DB);
}

function tool_mergeusers_save_settings($fullname)
{

}

function tool_mergeusers_reset_settings()
{
    set_config('suspenduser', 1, 'tool_mergeusers');
    set_config('transactions', 1, 'tool_mergeusers');
    set_config('quizattemptsaction', 'remain', 'tool_mergerusers');
    set_config('gathering', 'CLIGathering', 'tool_mergeusers');
    set_config('excluded_tables', 'my_pages,user_info_data,user_preferences,user_private_key', 'tool_mergeusers');
    set_config('user_related_columns_for_default', 'userid,reviewerid,authorid', 'tool_mergeusers');
    set_config('tables_with_custom_user_related_columns', 'message_contacts,message,message_read,question', 'tool_mergeusers');
    set_config('user_related_columns_for_message_contacts', 'userid,contactid', 'tool_mergeusers');
    set_config('user_related_columns_for_message', 'useridfrom,useridto', 'tool_mergeusers');
    set_config('user_related_columns_for_message_read', 'useridfrom,useridto', 'tool_mergeusers');
    set_config('user_related_columns_for_question', 'createdby,modifiedby', 'tool_mergeusers');
    set_config('tables_with_adhoc_indexes', 'groups_members,journal_entries', 'tool_mergeusers');
    set_config('columns_for_adhoc_index_for_groups_members', 'group,userid', 'tool_mergeusers');
    set_config('columns_for_adhoc_index_for_journal_entries', 'userid,journal', 'tool_mergeusers');
    set_config('nonunique_indexes', 'role_assignments:userid,contextid,roleid', 'tool_mergeusers');
    set_config('', '', 'tool_mergeusers');
    set_config('', '', 'tool_mergeusers');
    set_config('', '', 'tool_mergeusers');
    set_config('', '', 'tool_mergeusers');
    set_config('', '', 'tool_mergeusers');
    set_config('', '', 'tool_mergeusers');
}

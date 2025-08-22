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
 * Callback to update user's completion for the merged user to keep.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local\callbacks;

use dml_exception;
use tool_mergeusers\hook\after_merged_all_tables;

/**
 * Callback that updates the user's completion for the user to keep.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_completion_after_merged_callback {
    /**
     * Updates the course_completions.reaggretate field with the current time.
     *
     * This makes Moodle core updating course completions for the user to keep.
     * Moodle internals updates the course's completion status in short.
     *
     * @param after_merged_all_tables $hook
     * @return void
     * @throws dml_exception
     */
    public static function update_completion(after_merged_all_tables $hook): void {
        global $DB;

        $DB->execute(
            'UPDATE {course_completions}
                    SET reaggregate = :now
                  WHERE userid = :toid
                    AND (timecompleted IS NULL OR timecompleted = 0)',
            [
                'now' => time(),
                'toid' => $hook->toid,
            ],
        );
    }
}

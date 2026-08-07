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
 * Setting to store how many leading characters of a merge log's stored content
 * are inspected when searching the merge log listing.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local\settings;

use admin_setting_configtext;

/**
 * Setting to store how many leading characters of a merge log's stored content
 * are inspected when searching the merge log listing.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logsearchmaxlength_setting extends admin_setting_configtext {
    /** @var int minimum allowed length. */
    private const MIN = 100;

    /**
     * Setting to store how many leading characters of the merge log content are searched.
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param string $defaultsetting
     */
    public function __construct(string $name, string $visiblename, string $description, string $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, PARAM_INT, 6);
    }

    /**
     * Checks that the content is a whole number of at least self::MIN. No upper
     * bound is enforced: this is a plain text field precisely so an administrator
     * can widen the search window as far as their site needs.
     *
     * @param string $data value to validate.
     * @return bool|string string with the error message; true on valid content.
     */
    public function validate($data): bool|string {
        $parentresult = parent::validate($data);
        if ($parentresult !== true) {
            return $parentresult;
        }

        if (!ctype_digit((string) $data) || (int) $data < self::MIN) {
            return get_string('logsearchmaxlength_setting_invalid', 'tool_mergeusers', self::MIN);
        }

        return true;
    }
}

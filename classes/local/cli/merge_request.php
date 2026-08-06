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
 * Merge request.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local\cli;

/**
 * Merge request.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class merge_request {
    /** @var int user.id from the user to remove. */
    public int $fromid;
    /** @var int user.id from the user to keep. */
    public int $toid;
    /**
     * @var string|null field name searched for the 'from' user when fromid could not be resolved.
     * One of \tool_mergeusers\local\logger::SEARCHED_FIELD_USERNAME,
     * \tool_mergeusers\local\logger::SEARCHED_FIELD_IDNUMBER or
     * \tool_mergeusers\local\logger::SEARCHED_FIELD_EMAIL; any other value is silently ignored.
     */
    public ?string $fromsearchedfield = null;
    /** @var string|null value searched for the 'from' user when fromid could not be resolved. */
    public ?string $fromsearchedvalue = null;
    /**
     * @var string|null field name searched for the 'to' user when toid could not be resolved.
     * One of \tool_mergeusers\local\logger::SEARCHED_FIELD_USERNAME,
     * \tool_mergeusers\local\logger::SEARCHED_FIELD_IDNUMBER or
     * \tool_mergeusers\local\logger::SEARCHED_FIELD_EMAIL; any other value is silently ignored.
     */
    public ?string $tosearchedfield = null;
    /** @var string|null value searched for the 'to' user when toid could not be resolved. */
    public ?string $tosearchedvalue = null;
}

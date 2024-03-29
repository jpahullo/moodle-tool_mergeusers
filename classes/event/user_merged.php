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
 * The user_merged event.
 *
 * The base class for merge user accounts related actions.
 *
 * @package tool
 * @subpackage mergeusers
 * @author Gerard Cuello Adell <gerard.urv@gmail.com>
 * @copyright 2016 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The user_merged abstract event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - array usersinvolved: associative array with:
 *              'toid'   => int userid,
 *              'fromid' => int userid.
 *      - string log: the log data associated to.
 * }
 *
 * @since Moodle 3.0.2+
 * @author Gerard Cuello Adell <gerard.urv@gmail.com>
 * @copyright 2016 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class user_merged extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'u';      // Usually we perform update db queries so 'u' its ok!
        $this->data['level'] = self::LEVEL_OTHER; // fixing backwards compatibility
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }
}

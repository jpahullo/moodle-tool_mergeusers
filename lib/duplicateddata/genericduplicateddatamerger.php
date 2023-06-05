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
 * @author    Daniel Tomé <danieltomefer@gmail.com>
 * @copyright 2018 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/duplicateddatamerger.php');
require_once(__DIR__ . '/duplicateddata.php');

class GenericDuplicatedDataMerger implements DuplicatedDataMerger
{
    private $duplicateddata;

    public function __construct() {
        $this->duplicateddata = DuplicatedData::from_empty();
    }

    public function merge($data, $fromuserid, $touserid): DuplicatedData {
        if (isset($data[$touserid]) && isset($data[$fromuserid])) {
            return DuplicatedData::from_remove($data[$fromuserid]);
        }

        return $this->duplicateddata;
    }
}

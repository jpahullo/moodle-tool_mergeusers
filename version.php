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
 * @author     Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @author     John Hoopes <hoopes@wisc.edu>, University of Wisconsin - Madison
 * @author     Nicola Vallinoto <n.vallinoto@liguriadigitale.it>, Liguria Digitale 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
$plugin->version   = 2023040402;
$plugin->requires  = 2020061500; // Moodle 3.9, 15 June 2020, https://docs.moodle.org/dev/Releases#Moodle_3.9_.28LTS.29
$plugin->component = 'tool_mergeusers';
$plugin->maturity = MATURITY_STABLE;

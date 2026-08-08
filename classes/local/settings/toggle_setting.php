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
 * Checkbox setting rendered as a toggle switch, matching core's own "Turn editing
 * on" switch and the notification preferences page, instead of a plain checkbox.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local\settings;

use admin_setting_configcheckbox;
use html_writer;

/**
 * Checkbox setting rendered as a toggle switch, matching core's own "Turn editing
 * on" switch and the notification preferences page, instead of a plain checkbox.
 *
 * Behaviour (validation, storage) is entirely inherited from
 * admin_setting_configcheckbox - only the rendered markup changes, reusing the
 * Bootstrap "form-switch" classes core itself uses for the same visual (there is no
 * ready-made core admin_setting class for it; every switch-styled checkbox in core
 * is hand-built the same way, per its own template).
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggle_setting extends admin_setting_configcheckbox {
    /**
     * Renders the checkbox as a toggle switch.
     *
     * @param string $data If $data matches yes then the switch is on.
     * @param string $query
     * @return string HTML field, wrapped the same way format_admin_setting() wraps
     * every other admin setting.
     */
    public function output_html($data, $query = ''): string {
        $ischecked = (string) $data === $this->yes;
        $readonly = $this->is_readonly();

        $element = html_writer::start_tag('div', ['class' => 'form-check form-switch']);
        if (!$readonly) {
            $element .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $this->get_full_name(),
                'value' => $this->no,
            ]);
        }

        $inputattributes = [
            'type' => 'checkbox',
            'class' => 'form-check-input',
            'name' => $this->get_full_name(),
            'value' => $this->yes,
            'id' => $this->get_id(),
        ];
        if ($ischecked) {
            $inputattributes['checked'] = 'checked';
        }
        if ($readonly) {
            $inputattributes['disabled'] = 'disabled';
        }
        $element .= html_writer::empty_tag('input', $inputattributes);
        $element .= html_writer::end_tag('div');

        $default = $this->get_defaultsetting();
        $defaultinfo = null;
        if (!is_null($default)) {
            $defaultinfo = ((string) $default === $this->yes)
                ? get_string('checkboxyes', 'admin')
                : get_string('checkboxno', 'admin');
        }

        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', $defaultinfo, $query);
    }
}

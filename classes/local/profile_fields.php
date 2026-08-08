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
 * Lists custom user profile fields available for searching users to merge.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local;

/**
 * Class that abstracts how to list custom user profile fields, and which of them the
 * site administrator has allowed for searching users to merge.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class profile_fields {
    /**
     * Lists every custom user profile field defined on this site.
     *
     * @return array<int,string> fieldid => display name.
     */
    public static function all(): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $fields = [];
        foreach (profile_get_custom_fields() as $fieldid => $field) {
            $fields[$fieldid] = $field->name;
        }
        return $fields;
    }

    /**
     * Lists the custom user profile fields the site administrator has allowed for
     * searching users to merge, via the tool_mergeusers/searchbyprofilefields setting.
     * Always empty unless tool_mergeusers/searchbyprofilefieldsenabled is also on -
     * the single master switch every caller of this method implicitly respects.
     * Silently ignores any configured field id that no longer exists.
     *
     * @return array<int,string> fieldid => display name.
     */
    public static function allowed(): array {
        if (empty(get_config('tool_mergeusers', 'searchbyprofilefieldsenabled'))) {
            return [];
        }

        $allowedids = get_config('tool_mergeusers', 'searchbyprofilefields');
        if (empty($allowedids)) {
            return [];
        }

        $allowedids = array_map('intval', explode(',', $allowedids));
        return array_intersect_key(self::all(), array_flip($allowedids));
    }

    /**
     * Builds the notice to show when a submitted search/verification field turned out
     * to be null after moodleform processing - meaning the submitted value (most
     * likely a custom profile field id) no longer matches any option the form
     * currently defines. This happens when another administrator disables profile-
     * field search, or deselects that specific field, between this page being loaded
     * and being submitted.
     *
     * @param string $rawfield the raw submitted field value, read directly from the
     * request (moodleform already discarded it, since it did not match any option).
     * @return string the notice message, naming the field by name when it can still
     * be resolved (the profile field itself still exists, just is no longer allowed
     * for searching), or generically otherwise.
     */
    public static function unavailable_field_notice(string $rawfield): string {
        $fieldname = self::all()[(int) $rawfield] ?? null;
        if ($fieldname !== null) {
            return get_string('searchfieldnolongeravailable', 'tool_mergeusers', $fieldname);
        }
        return get_string('searchfieldnolongeravailable_generic', 'tool_mergeusers');
    }
}

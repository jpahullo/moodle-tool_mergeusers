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
     * Prefix identifying a custom user profile field by its shortname, e.g.
     * "profile_field_staffid" - the same convention Moodle core itself uses to
     * identify custom profile fields from outside (see user/externallib.php,
     * admin/tool/uploaduser/locallib.php). Used instead of the field's internal,
     * environment-specific database id wherever a field is referenced by an
     * external caller (web service) or round-tripped through a web form.
     */
    public const FIELD_PREFIX = 'profile_field_';

    /**
     * Lists every custom user profile field defined on this site.
     *
     * @return array<int,string> fieldid => display name.
     */
    public static function all(): array {
        $fields = [];
        foreach (self::all_raw() as $fieldid => $field) {
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
     * @return array<string,string> "profile_field_<shortname>" => display name.
     */
    public static function allowed(): array {
        $allowedids = self::allowed_ids();
        if (empty($allowedids)) {
            return [];
        }

        $result = [];
        foreach (self::all_raw() as $fieldid => $field) {
            if (in_array($fieldid, $allowedids, true)) {
                $result[self::FIELD_PREFIX . $field->shortname] = $field->name;
            }
        }
        return $result;
    }

    /**
     * Resolves a "profile_field_<shortname>" identifier to its database field id,
     * when it names a custom profile field that is currently allow-listed for
     * searching users to merge.
     *
     * @param string $field
     * @return int|null the field id, or null when $field is not the expected shape,
     * does not match any custom profile field, or is not allow-listed.
     */
    public static function resolve_allowed(string $field): ?int {
        if (!str_starts_with($field, self::FIELD_PREFIX)) {
            return null;
        }

        $shortname = substr($field, strlen(self::FIELD_PREFIX));
        $allowedids = self::allowed_ids();
        foreach (self::all_raw() as $fieldid => $fieldobj) {
            if (in_array($fieldid, $allowedids, true) && $fieldobj->shortname === $shortname) {
                return $fieldid;
            }
        }
        return null;
    }

    /**
     * Builds the notice to show when a submitted search/verification field turned out
     * to be null after moodleform processing - meaning the submitted value (most
     * likely a custom profile field reference) no longer matches any option the form
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
        if (str_starts_with($rawfield, self::FIELD_PREFIX)) {
            $shortname = substr($rawfield, strlen(self::FIELD_PREFIX));
            foreach (self::all_raw() as $field) {
                if ($field->shortname === $shortname) {
                    return get_string('searchfieldnolongeravailable', 'tool_mergeusers', $field->name);
                }
            }
        }
        return get_string('searchfieldnolongeravailable_generic', 'tool_mergeusers');
    }

    /**
     * Lists every custom user profile field defined on this site, with its full
     * field configuration (at least ->name and ->shortname).
     *
     * @return array<int,object> fieldid => field object.
     */
    private static function all_raw(): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        return profile_get_custom_fields();
    }

    /**
     * The raw list of field ids allow-listed via tool_mergeusers/searchbyprofilefields,
     * always empty unless tool_mergeusers/searchbyprofilefieldsenabled is also on. For
     * internal SQL use only (e.g. an "IN (...)" clause spanning every allowed field at
     * once) - external callers must always go through resolve_allowed() instead, never
     * a raw field id.
     *
     * @return int[]
     */
    public static function allowed_ids(): array {
        if (empty(get_config('tool_mergeusers', 'searchbyprofilefieldsenabled'))) {
            return [];
        }

        $allowedids = get_config('tool_mergeusers', 'searchbyprofilefields');
        if (empty($allowedids)) {
            return [];
        }

        return array_map('intval', explode(',', $allowedids));
    }
}

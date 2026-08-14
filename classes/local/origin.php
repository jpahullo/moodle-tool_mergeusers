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

namespace tool_mergeusers\local;

// phpcs:disable moodle.Commenting.InlineComment.DocBlock

/**
 * Origin enum for a merge/rename request: where it was requested from.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum origin: string {
    case WEB = 'web';
    case CLI = 'cli';
    case WS = 'ws';

    /**
     * Safely converts a string value to an origin enum, with a fallback for NULL/
     * invalid values - needed for legacy logs recorded before this column existed.
     *
     * @param string|null $value The origin string value, or NULL for legacy logs.
     * @return self|null The corresponding origin enum, or null for NULL/invalid values.
     */
    public static function safe_from(?string $value): ?self {
        if ($value === null) {
            return null;
        }
        return self::tryFrom($value);
    }
}

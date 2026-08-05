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
 * Status enum for user merging process.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2025 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum status: string {
    case PENDING = 'pending';
    case INPROGRESS = 'inprogress';
    case SUCCESS = 'success';
    case ERROR = 'error';

    /**
     * Returns the status based on the success flag: either SUCCESS or ERROR statuses.
     *
     * @param bool $success true if merge was successful, false otherwise.
     * @return self
     */
    public static function from_success(bool $success): self {
        return $success ? self::SUCCESS : self::ERROR;
    }

    /**
     * Safely converts a string value to a status enum, with fallback for NULL/invalid values.
     *
     * This method handles legacy merge logs where the status field may be NULL,
     * as well as any unexpected/invalid status strings. Returns ERROR as the default
     * fallback, which is the safest assumption for logs without a valid status.
     *
     * @param string|null $value The status string value, or NULL for legacy logs.
     * @return self The corresponding status enum, or ERROR for NULL/invalid values.
     */
    public static function safe_from(?string $value): self {
        if ($value === null) {
            return self::ERROR;
        }
        return self::tryFrom($value) ?? self::ERROR;
    }
}

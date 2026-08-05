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
 * Resolves a normalized user snapshot into what the results page should display.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local;

use moodle_url;
use stdClass;

/**
 * Value object combining a normalized user snapshot (identity, frozen at capture
 * time) with a single lightweight live lookup (suspended/deleted, and whether a
 * profile link is currently valid) - see logger::capture_user_snapshot() for the
 * normalized snapshot shape this is built from.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol Ahulló <jordi.pujol@urv.cat>
 * @copyright 2026 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class merge_user_display {
    /** @var int user.id (0 when $notfound is true). */
    public readonly int $id;

    /** @var bool true when the merge never had a real user id for this side. */
    public readonly bool $notfound;

    /** @var bool true when there is any identity data to show. */
    public readonly bool $recoverable;

    /** @var string|null */
    public readonly ?string $displayname;

    /** @var string|null */
    public readonly ?string $username;

    /** @var string|null */
    public readonly ?string $email;

    /** @var string|null */
    public readonly ?string $idnumber;

    /** @var bool|null current value when live, last known value otherwise. */
    public readonly ?bool $suspended;

    /** @var bool current value when live, last known value otherwise. */
    public readonly bool $deleted;

    /** @var moodle_url|null only set when a live, non-deleted user exists now. */
    public readonly ?moodle_url $profileurl;

    /**
     * Constructor.
     *
     * @param int $id user.id (0 when $notfound is true).
     * @param bool $notfound true when the merge never had a real user id for this side.
     * @param bool $recoverable true when there is any identity data to show.
     * @param string|null $displayname
     * @param string|null $username
     * @param string|null $email
     * @param string|null $idnumber
     * @param bool|null $suspended current value when live, last known value otherwise.
     * @param bool $deleted current value when live, last known value otherwise.
     * @param moodle_url|null $profileurl only set when a live, non-deleted user exists now.
     */
    private function __construct(
        int $id,
        bool $notfound,
        bool $recoverable,
        ?string $displayname,
        ?string $username,
        ?string $email,
        ?string $idnumber,
        ?bool $suspended,
        bool $deleted,
        ?moodle_url $profileurl,
    ) {
        $this->id = $id;
        $this->notfound = $notfound;
        $this->recoverable = $recoverable;
        $this->displayname = $displayname;
        $this->username = $username;
        $this->email = $email;
        $this->idnumber = $idnumber;
        $this->suspended = $suspended;
        $this->deleted = $deleted;
        $this->profileurl = $profileurl;
    }

    /**
     * Builds the display info for one side of a merge from its normalized snapshot.
     *
     * @param stdClass $snapshot see logger::capture_user_snapshot().
     * @return self
     */
    public static function from_snapshot(stdClass $snapshot): self {
        if (!empty($snapshot->notfound)) {
            return new self(0, true, false, null, null, null, null, null, false, null);
        }

        if (empty($snapshot->recoverable)) {
            return new self((int) $snapshot->id, false, false, null, null, null, null, null, false, null);
        }

        global $DB;
        $liveuser = $DB->get_record('user', ['id' => $snapshot->id], 'id, deleted, suspended');

        if ($liveuser) {
            $deleted = (bool) $liveuser->deleted;
            $suspended = (bool) $liveuser->suspended;
            $profileurl = $deleted ? null : new moodle_url('/user/profile.php', ['id' => $snapshot->id]);
        } else {
            $deleted = (bool) ($snapshot->deleted ?? false);
            $suspended = (bool) ($snapshot->suspended ?? false);
            $profileurl = null;
        }

        $displayname = trim(($snapshot->firstname ?? '') . ' ' . ($snapshot->lastname ?? ''));

        return new self(
            (int) $snapshot->id,
            false,
            true,
            $displayname !== '' ? $displayname : null,
            $snapshot->username ?? null,
            $snapshot->email ?? null,
            $snapshot->idnumber ?? null,
            $suspended,
            $deleted,
            $profileurl,
        );
    }
}

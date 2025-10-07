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
 * Adhoc task to merge users asynchronously.
 *
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @package     tool_mergeusers
 * @copyright   07/10/2025 LdesignMedia.nl - Luuk Verhoeven
 * @author      Nihaal Shaikh
 */

namespace tool_mergeusers\task;

use core\task\adhoc_task;
use Throwable;
use tool_mergeusers\local\user_merger;

defined('MOODLE_INTERNAL') || die();

/**
 * Processes a queued merge request in the adhoc task queue.
 *
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @package     tool_mergeusers
 * @copyright   07/10/2025 LdesignMedia.nl - Luuk Verhoeven
 * @author      Nihaal Shaikh
 */
final class merge_users_task extends adhoc_task {

    /**
     * @return string
     */
    public function get_component(): string {
        return 'tool_mergeusers';
    }

    /**
     * Executes the merge.
     */
    public function execute(): void {
        $data = $this->get_custom_data();
        $toid = isset($data->toid) ? (int) $data->toid : 0;
        $fromid = isset($data->fromid) ? (int) $data->fromid : 0;

        if (empty($toid) || empty($fromid)) {
            mtrace('tool_mergeusers: merge_users_task missing user identifiers, skipping execution.');

            return;
        }

        try {
            $merger = new user_merger();
            [$success] = $merger->merge($toid, $fromid);

            if ($success) {
                mtrace("tool_mergeusers: merged user $fromid into $toid.");

                return;
            }

            mtrace("tool_mergeusers: merge $fromid -> $toid completed with errors. Review merge logs for details.");
        } catch (Throwable $e) {
            mtrace('tool_mergeusers: merge_users_task failed - ' . $e->getMessage());
            throw $e;
        }
    }

}


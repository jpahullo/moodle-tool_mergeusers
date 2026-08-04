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
use core_user;
use Throwable;
use tool_mergeusers\event\user_merged_failure;
use tool_mergeusers\local\status;
use tool_mergeusers\local\user_merger;

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
     * Get the component name.
     *
     * @return string The component name.
     */
    public function get_component(): string {
        return 'tool_mergeusers';
    }

    /**
     * Ensure at most one merge_users_task instance runs at a time across the
     * whole site, regardless of how many cron workers are processing adhoc
     * tasks in parallel. This is what guarantees chained merges (e.g. A into
     * B, then B into C) always execute in the order they were queued: Moodle
     * leaves any task it cannot grab a concurrency slot for untouched, so the
     * oldest queued task is always the next one picked up.
     *
     * Site administrators can override this via
     * $CFG->task_concurrency_limit[self::class] in config.php if they
     * explicitly understand and accept the ordering risk of a higher value.
     *
     * @return int
     */
    protected function get_default_concurrency_limit(): int {
        return 1;
    }

    /**
     * A failed merge must never be retried: retrying a partially-applied
     * merge could corrupt data, especially for chained merges. execute()
     * already never lets an exception escape uncaught, but this makes the
     * guarantee explicit and enforced natively by Moodle's task manager too
     * (it forces attemptsavailable to 1 when queuing this task).
     *
     * @return bool
     */
    public function retry_until_success(): bool {
        return false;
    }

    /**
     * Executes the merge.
     */
    public function execute(): void {
        global $DB;

        $data = $this->get_custom_data();
        $toid = isset($data->toid) ? (int) $data->toid : 0;
        $fromid = isset($data->fromid) ? (int) $data->fromid : 0;
        $logid = isset($data->logid) ? (int) $data->logid : 0;

        if (empty($toid) || empty($fromid)) {
            mtrace('tool_mergeusers: merge_users_task missing user identifiers, skipping execution.');

            return;
        }

        if (empty($logid)) {
            mtrace('tool_mergeusers: merge_users_task missing log id, skipping execution.');

            return;
        }

        $logger = new \tool_mergeusers\local\logger();

        try {
            // Get user records. They must exist and not be deleted.
            $touser = $DB->get_record('user', ['id' => $toid, 'deleted' => 0], '*', MUST_EXIST);
            $fromuser = $DB->get_record('user', ['id' => $fromid, 'deleted' => 0], '*', MUST_EXIST);

            // Mark as in progress.
            $logger->update_log_status($logid, status::INPROGRESS->value, []);

            $merger = new user_merger();
            [$success] = $merger->merge($toid, $fromid, $logid);

            // Get user records for notification.
            $touser = $DB->get_record('user', ['id' => $toid]);
            $fromuser = $DB->get_record('user', ['id' => $fromid]);

            if ($success) {
                mtrace("tool_mergeusers: merged user $fromid into $toid.");
                $this->send_notification($touser, $fromuser, true, $logid);

                return;
            }

            mtrace("tool_mergeusers: merge $fromid -> $toid completed with errors. Review merge logs for details.");
            $this->send_notification($touser, $fromuser, false, $logid);
        } catch (Throwable $e) {
            mtrace('tool_mergeusers: merge_users_task failed - ' . $e->getMessage());

            // Update log with error status.
            $logger->update_log_status($logid, 'error', ['Exception: ' . $e->getMessage()]);

            // Trigger failure event.
            $event = user_merged_failure::create([
                'context' => \context_system::instance(),
                'other' => [
                    'usersinvolved' => [
                        'toid' => $toid,
                        'fromid' => $fromid,
                    ],
                    'logid' => $logid,
                    'log' => ['Exception: ' . $e->getMessage()],
                ],
            ]);
            $event->trigger();

            // Try to get user records for notification (may be null if that's what caused the error).
            $touser = $DB->get_record('user', ['id' => $toid]);
            $fromuser = $DB->get_record('user', ['id' => $fromid]);

            if ($touser && $fromuser) {
                $this->send_notification($touser, $fromuser, false, $logid);
            }

            // Do not rethrow - adhoc tasks should always complete to prevent infinite requeueing.
        }
    }

    /**
     * Send notification to the user who initiated the merge.
     *
     * @param object $touser   The user to keep.
     * @param object $fromuser The user to remove.
     * @param bool $success    Whether the merge was successful.
     * @param int $logid       The log ID of the merge.
     */
    private function send_notification(object $touser, object $fromuser, bool $success, int $logid): void {
        $userid = $this->get_userid();

        if (empty($userid)) {
            return;
        }

        $user = core_user::get_user($userid);
        if (!$user) {
            return;
        }

        $message = new \core\message\message();
        $message->component = 'tool_mergeusers';
        $message->name = 'mergeusers_completion';
        $message->userfrom = core_user::get_noreply_user();
        $message->userto = $user;
        $message->notification = 1;

        global $PAGE;

        // Defensive check: $PAGE should be initialized by Moodle cron framework,
        // but we verify it's available before attempting to get the renderer.
        if (!isset($PAGE)) {
            mtrace('tool_mergeusers: Cannot send notification - PAGE not initialized');
            return;
        }

        // The renderer needs a context; in a cron/adhoc-task context $PAGE->context
        // is not set by anything else, so set it explicitly to the system context
        // (this notification is not tied to any more specific context).
        if (!$PAGE->context) {
            $PAGE->set_context(\context_system::instance());
        }

        //phpcs:disable
        /** @var \tool_mergeusers\output\renderer $renderer */
        $renderer = $PAGE->get_renderer('tool_mergeusers');
        //phpcs:enable

        $messagedata = new \stdClass();
        $messagedata->fromuser = $renderer->show_user($fromuser->id, $fromuser);
        $messagedata->touser = $renderer->show_user($touser->id, $touser);
        $messagedata->fromuserid = $fromuser->id;
        $messagedata->touserid = $touser->id;
        $messagedata->logid = $renderer->render_logid($logid);

        // Prepare content for the notifications.
        if ($success) {
            $subject = get_string('message:mergeusers_success_subject', 'tool_mergeusers');
            $bodyhtml = get_string('message:mergeusers_success_body', 'tool_mergeusers', $messagedata);
        } else {
            $subject = get_string('message:mergeusers_error_subject', 'tool_mergeusers');
            $bodyhtml = get_string('message:mergeusers_error_body', 'tool_mergeusers', $messagedata);
        }

        // Convert HTML body to plaintext for email clients that don't support HTML.
        $bodyplain = html_to_text($bodyhtml);

        // Populate the message with the content.
        $message->subject = $subject;
        $message->fullmessage = $bodyplain;
        $message->fullmessagehtml = $bodyhtml;
        $message->fullmessageformat = FORMAT_HTML;
        $message->smallmessage = $subject;

        message_send($message);
    }
}

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
 * Plugin renderer.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @author    John Hoopes <hoopes@wisc.edu>, University of Wisconsin - Madison
 * @copyright 2013 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\output;

use coding_exception;
use context_system;
use context_user;
use core\exception\moodle_exception;
use core\output\notification;
use core_user;
use dml_exception;
use html_table;
use html_table_row;
use html_writer;
use moodle_url;
use moodleform;
use plugin_renderer_base;
use single_button;
use stdClass;
use tool_mergeusers\local\database_transactions;
use tool_mergeusers\local\last_merge;
use tool_mergeusers\local\logger;
use tool_mergeusers\local\merge_user_display;
use tool_mergeusers\local\status;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/mergeusers/lib.php');

/**
 * Renderer for the merge user plugin.
 *
 * @package   tool_mergeuser
 * @author    Jordi Pujol-Ahulló
 * @copyright 2013 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /** On index page, show only the search form. */
    const INDEX_PAGE_SEARCH_STEP = 1;
    /** On index page, show both search and select forms. */
    const INDEX_PAGE_SEARCH_AND_SELECT_STEP = 2;
    /** On index page, show only the list of users to merge. */
    const INDEX_PAGE_CONFIRMATION_STEP = 3;
    /** On index page, show the merging results. */
    const INDEX_PAGE_RESULTS_STEP = 4;
    /** Safety cap for the "show all" merge log listing link: never render more rows than this at once. */
    public const SHOW_ALL_PAGE_SIZE = 20000;
    /** Longest a user description preview is allowed to be, on the review-before-merging table, before it is collapsed. */
    private const DESCRIPTION_PREVIEW_LENGTH = 200;

    /**
     * Renderers a progress bar.
     *
     * @param array $items An array of items
     * @return string
     */
    public function progress_bar(array $items): string {
        foreach ($items as &$item) {
            $text = $item['text'];
            unset($item['text']);
            if (array_key_exists('link', $item)) {
                $link = $item['link'];
                unset($item['link']);
                $item = html_writer::link($link, $text, $item);
            } else {
                $item = html_writer::tag('span', $text, $item);
            }
        }

        return html_writer::tag('div', join(get_separator(), $items), ['class' => 'merge_progress clearfix']);
    }

    /**
     * Returns the HTML for the progress bar, according to the current step.
     *
     * @param int $step current step
     * @return string HTML for the progress bar.
     */
    public function build_progress_bar(int $step): string {
        $steps = [
            ['text' => '1. ' . get_string('choose_users', 'tool_mergeusers')],
            ['text' => '2. ' . get_string('review_users', 'tool_mergeusers')],
            ['text' => '3. ' . get_string('results', 'tool_mergeusers')],
        ];

        switch ($step) {
            case self::INDEX_PAGE_SEARCH_STEP:
            case self::INDEX_PAGE_SEARCH_AND_SELECT_STEP:
                $steps[0]['class'] = 'bold';
                break;
            case self::INDEX_PAGE_CONFIRMATION_STEP:
                $steps[1]['class'] = 'bold';
                break;
            case self::INDEX_PAGE_RESULTS_STEP:
                $steps[2]['class'] = 'bold';
        }

        return $this->progress_bar($steps);
    }

    /**
     * Shows form for merging users.
     *
     * @param moodleform $mform             form for merging users.
     * @param int $step                     step to show in the index page.
     * @param user_select_table|null $ust   table for users to merge after searching
     * @param stdClass|null $toomanyresults when the search matched more users than
     * tool_mergeusers/maxsearchresults, an object with count/shown/search
     * properties, used to warn the admin - shown between the (truncated) $ust
     * table and its "save selection" button, both part of the same form.
     * @return string html to show on index page.
     * @throws coding_exception
     */
    public function index_page(
        moodleform $mform,
        int $step,
        ?user_select_table $ust = null,
        ?stdClass $toomanyresults = null,
    ): string {
        $output = $this->header();
        $output .= $this->heading_with_help(get_string('mergeusers', 'tool_mergeusers'), 'header', 'tool_mergeusers');

        $output .= $this->build_progress_bar($step);

        switch ($step) {
            case self::INDEX_PAGE_SEARCH_STEP:
                $output .= $this->moodleform($mform);
                break;
            case self::INDEX_PAGE_SEARCH_AND_SELECT_STEP:
                $output .= $this->moodleform($mform);
                // Render user select table if available.
                if ($ust !== null) {
                    $output .= $this->render_user_select_table($ust, $toomanyresults);
                }
                break;
            case self::INDEX_PAGE_CONFIRMATION_STEP:
                break;
        }

        $output .= $this->render_user_review_table($step);
        $output .= $this->footer();

        return $output;
    }

    /**
     * Renders user select table, plus a "too many results" warning right below it
     * and above the save button, when the search was too broad.
     *
     * @param user_select_table $ust the user select table
     * @param stdClass|null $toomanyresults when the search matched more users than
     * tool_mergeusers/maxsearchresults, an object with count/shown/search properties.
     *
     * @return string $tablehtml html string rendering
     */
    public function render_user_select_table(user_select_table $ust, ?stdClass $toomanyresults = null) {
        $warninghtml = null;
        if ($toomanyresults !== null) {
            $warninghtml = $this->notification(
                get_string('toomanyusersmatchsearch', 'tool_mergeusers', (object) [
                    'count' => $toomanyresults->count,
                    'shown' => $toomanyresults->shown,
                    'search' => s($toomanyresults->search),
                ]),
                notification::NOTIFY_WARNING,
                false,
            );
        }

        return $this->moodleform(new select_user_form($ust, $warninghtml));
    }

    /**
     * Builds and renders a user review table
     *
     * @param int $step current step.
     * @return string $reviewtable HTML of the review table section
     * @throws coding_exception
     */
    public function render_user_review_table($step) {
        return $this->moodleform(
            new review_user_form(
                new user_review_table($this),
                $this,
                $step === self::INDEX_PAGE_CONFIRMATION_STEP
            )
        );
    }

    /**
     * Displays merge users tool error message
     *
     * @param string $message  The error message
     * @param bool $showreturn Shows a return button to the index page
     *
     * @throws coding_exception
     */
    public function mu_error($message, $showreturn = true) {
        $errorhtml = '';

        echo $this->header();

        $errorhtml .= $this->output->box($message, 'generalbox align-center');
        if ($showreturn) {
            $returnurl = new moodle_url('/admin/tool/mergeusers/index.php');
            $returnbutton = new single_button($returnurl, get_string('error_return', 'tool_mergeusers'));

            $errorhtml .= $this->output->render($returnbutton);
        }

        echo $errorhtml;
        echo $this->footer();
    }

    /**
     * Shows the result of a merging action.
     *
     * @param object $to             stdClass with at least id and username fields (current/dynamic data).
     * @param object $from           stdClass with at least id and username fields (current/dynamic data).
     * @param string|null $status    status of the merging process.
     * @param array|stdClass $data   logs of actions done if success, or list of errors on failure.
     * @param int $logid             id of the record with the whole detail of this merging action.
     * @param int|null $timecreated  timestamp when merge was queued/initiated.
     * @param int|null $timemodified timestamp when merge was executed.
     * @return string html with the results.
     * @throws \coding_exception
     * @throws \ReflectionException
     */
    public function results_page(
        object $to,
        object $from,
        ?string $status,
        array|stdClass $data,
        int $logid,
        ?int $timecreated = null,
        ?int $timemodified = null
    ): string {
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }

        // Extract normalized user snapshots (see logger::capture_user_snapshot()).
        // A missing/malformed value should not happen once db/upgrade.php has
        // normalized every log, but fall back to an "unrecoverable" snapshot from
        // the live id passed in, rather than crash, just in case.
        $snapshots = (is_array($data) && is_array($data['user_snapshots'] ?? null)) ? $data['user_snapshots'] : null;
        $fromsnapshotdata = $snapshots['from_user'] ?? null;
        $tosnapshotdata = $snapshots['to_user'] ?? null;
        $fromsnapshot = is_array($fromsnapshotdata)
            ? (object) $fromsnapshotdata
            : logger::unrecoverable_snapshot((int) ($from->id ?? 0));
        $tosnapshot = is_array($tosnapshotdata)
            ? (object) $tosnapshotdata
            : logger::unrecoverable_snapshot((int) ($to->id ?? 0));
        $snapshotcapturedat = $snapshots['timemodified'] ?? null;
        if ($snapshots !== null) {
            $data = $data['actions'] ?? [];
        }

        $statusenum = status::safe_from($status);
        switch ($statusenum) {
            case status::PENDING:
                $resulttype = '';
                $dbmessage = "dbpending";
                $notifytype = 'info';
                break;
            case status::INPROGRESS:
                $resulttype = '';
                $dbmessage = "dbinprogress";
                $notifytype = 'info';
                break;
            case status::SUCCESS:
                $resulttype = 'ok';
                $dbmessage = 'dbok';
                $notifytype = $statusenum->value;
                break;
            case status::ERROR:
                $resulttype = 'ko';
                $dbmessage = (database_transactions::are_supported()) ?
                    'dbko_transactions' :
                    'dbko_no_transactions';
                $notifytype = $statusenum->value;
                break;
        }

        $output = $this->header();
        $output .= $this->heading(get_string('mergeusers', 'tool_mergeusers'));
        $output .= $this->build_progress_bar(self::INDEX_PAGE_RESULTS_STEP);
        $output .= html_writer::empty_tag('br');
        $output .= html_writer::start_tag('div', ['class' => 'result']);
        $output .= $this->notification(
            get_string('status:' . $statusenum->value, 'tool_mergeusers'),
            $this->status_notification_type($statusenum),
            false,
        );
        $output .= $this->render_merge_user_info(
            merge_user_display::from_snapshot($fromsnapshot),
            merge_user_display::from_snapshot($tosnapshot),
            $snapshotcapturedat,
        );
        $output .= html_writer::start_tag('div', ['class' => 'title']);
        $output .= get_string('logline', 'tool_mergeusers', $this->render_logid($logid));

        // Display timestamps right under the log id reference, if available.
        if ($timecreated !== null || $timemodified !== null) {
            $output .= html_writer::empty_tag('br');

            // Check if merge is still pending or in progress. This must be checked
            // BEFORE comparing the two timestamps: update_log_status() also bumps
            // timemodified when a task transitions to "in progress", so a pending/
            // in-progress row can already have timecreated !== timemodified despite
            // never having actually finished executing yet.
            $ispending = ($status === 'pending' || $status === 'inprogress');

            if ($ispending) {
                // Pending/in-progress: only the queue time is meaningful yet.
                if ($timecreated !== null) {
                    $output .= html_writer::tag(
                        'strong',
                        get_string('snapshot_queued', 'tool_mergeusers')
                    ) . ' ' . userdate($timecreated);
                }
            } else if ($timecreated !== null && $timemodified !== null && $timecreated !== $timemodified) {
                // Completed (success/error) via adhoc task: show both queue and execution times.
                $output .= html_writer::tag(
                    'strong',
                    get_string('snapshot_queued', 'tool_mergeusers')
                ) . ' ' . userdate($timecreated);
                $output .= html_writer::empty_tag('br');
                $output .= html_writer::tag(
                    'strong',
                    get_string('snapshot_executed', 'tool_mergeusers')
                ) . ' ' . userdate($timemodified);
            } else if ($timemodified !== null) {
                // Completed synchronously (no adhoc): show as "Executed at".
                $output .= html_writer::tag(
                    'strong',
                    get_string('snapshot_executed', 'tool_mergeusers')
                ) . ' ' . userdate($timemodified);
            } else if ($timecreated !== null) {
                // Fallback: just show created time.
                $output .= html_writer::tag(
                    'strong',
                    get_string('snapshot_created', 'tool_mergeusers')
                ) . ' ' . userdate($timecreated);
            }
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::empty_tag('br');

        if (!empty($resulttype)) {
            $output .= html_writer::start_tag('div', ['class' => 'title']);
            $output .= get_string('log' . $resulttype, 'tool_mergeusers');
            $output .= html_writer::end_tag('div');
            $output .= html_writer::empty_tag('br');
        }

        $output .= html_writer::start_tag('div', ['class' => 'resultset' . $resulttype]);
        foreach ($data as $item) {
            $output .= $item . html_writer::empty_tag('br');
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::tag('div', html_writer::empty_tag('br'));
        $output .= $this->notification(
            get_string($dbmessage, 'tool_mergeusers'),
            $notifytype,
            false,
        );
        $output .= html_writer::tag(
            'center',
            $this->single_button(new moodle_url('/admin/tool/mergeusers/index.php'), get_string('continue'), 'get'),
        );
        $output .= $this->footer();

        return $output;
    }

    /**
     * Renders a log id as a link to the log details page.
     *
     * @param int $logid
     * @return string HTML link to the log details page.
     */
    public function render_logid(int $logid): string {
        $logurl = new moodle_url('/admin/tool/mergeusers/log.php', ['id' => $logid]);

        return get_string(
            'logidurl',
            'tool_mergeusers',
            (object) [
                'id' => $logid,
                'url' => $logurl->out(false),
            ]
        );
    }

    /**
     * Helper method dealing with the fact we can not just fetch the output of moodleforms
     *
     * @param moodleform $mform
     * @return string HTML
     */
    protected function moodleform(moodleform $mform) {
        ob_start();
        $mform->display();
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    /**
     * This method produces the HTML to show the details of a user.
     *
     * @param int $userid  user.id
     * @param object $user an object with firstname and lastname attributes.
     * @return string the corresponding HTML.
     * @throws moodle_exception
     */
    public function show_user($userid, $user) {
        $suspendedstr = '';
        $suspendedclass = '';
        $deleted = isset($user->deleted) && $user->deleted;
        if ($deleted) {
            $text = get_string('deleted');
        } else {
            $text = fullname($user);
            $text .= " &lt;{$user->email}&gt;";
            $text .= " ({$user->username})";
            $text .= " {$user->idnumber}";
            if ($user->suspended) {
                $suspendedstr = get_string('suspended', 'moodle');
            }
        }
        if (!empty($suspendedstr)) {
            // Found on core here: admin/classes/reportbuilder/local/systemreports/users.php.
            $text .= \html_writer::tag('span', $suspendedstr, ['class' => 'badge badge-secondary ms-1']);
            $suspendedclass = 'usersuspended';
        }

        $attributes = ['class' => $suspendedclass];
        if ($deleted) {
            return html_writer::tag('span', $text, $attributes);
        }

        return html_writer::link(new moodle_url('/user/view.php', ['id' => $userid]), $text, $attributes);
    }

    /**
     * Same as show_user(), but with the user's profile description shown on a
     * second line underneath - always shown, even when empty, so there is never any
     * doubt about whether a user has one. Used on the review-before-merging table,
     * to help tell similar users apart before confirming who is about to be merged;
     * not used on the (much longer) search-results table, where a per-row
     * description would make every row needlessly tall.
     *
     * Long descriptions are collapsed to a short preview, expandable on demand with
     * no JavaScript via a native <details> element. The preview is built with core's
     * shorten_text(), which is HTML-tag-aware (it never cuts markup in half; it closes
     * any tag left open), so the truncation is always safe regardless of what
     * formatting the description contains.
     *
     * @param int $userid  user.id
     * @param object $user an object with firstname/lastname/description attributes.
     * @return string the corresponding HTML.
     * @throws moodle_exception
     */
    public function show_user_with_description(int $userid, object $user): string {
        $output = html_writer::start_tag('div');
        $output .= $this->show_user($userid, $user);
        $output .= html_writer::start_tag('div', ['class' => 'tool-mergeusers-user-description small text-muted']);

        if (empty($user->description)) {
            $output .= get_string('userdescription', 'tool_mergeusers', get_string('nouserdescription', 'tool_mergeusers'));
        } else {
            $description = format_text(
                $user->description,
                $user->descriptionformat ?? FORMAT_MOODLE,
                ['context' => context_user::instance($userid)],
            );
            $preview = shorten_text($description, self::DESCRIPTION_PREVIEW_LENGTH);

            $output .= get_string('userdescription', 'tool_mergeusers', $preview);
            if ($preview !== $description) {
                $output .= html_writer::start_tag('details', ['class' => 'tool-mergeusers-user-description__details']);
                $output .= html_writer::tag('summary', get_string('showfulldescription', 'tool_mergeusers'));
                $output .= $description;
                $output .= html_writer::end_tag('details');
            }
        }

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Same as show_user(), but distinguishes a user id that was never real to begin
     * with (id <= 0, e.g. a gathering that could not resolve a search into a real
     * user id) from one that no longer resolves to a live {user} row.
     *
     * @param int $userid user.id (touserid/fromuserid column value; can be <= 0).
     * @param object|false $user the live {user} record, or false when none was found
     * ($DB->get_record() returns false, not null, on a miss).
     * @param array|null $searchedhint [field, value] from extract_searched_hint(), if any.
     * @return string the corresponding HTML.
     * @throws moodle_exception
     */
    private function show_user_or_notfound(int $userid, object|false $user, ?array $searchedhint = null): string {
        if ($userid <= 0) {
            if ($searchedhint !== null) {
                return get_string('usernotfoundatmergewithhint', 'tool_mergeusers', (object) [
                    'field' => $this->searched_field_label($searchedhint[0]),
                    'value' => $searchedhint[1],
                ]);
            }

            return get_string('usernotfoundatmerge', 'tool_mergeusers');
        }

        return $user ? $this->show_user($userid, $user) : get_string('deleted', 'tool_mergeusers', $userid);
    }

    /**
     * Extracts the [field, value] a gathering reported having searched for, from a
     * notfound side of a user_snapshots value (see logger::notfound_snapshot()). At
     * most one of username/idnumber/email is ever non-null on a notfound side.
     *
     * @param array|object|null $side a to_user/from_user side, decoded as array or object.
     * @return array|null [field, value], or null when $side is not notfound or carries no hint.
     */
    private function extract_searched_hint($side): ?array {
        if ($side === null) {
            return null;
        }

        $side = (array) $side;
        if (empty($side['notfound'])) {
            return null;
        }

        foreach ([logger::SEARCHED_FIELD_USERNAME, logger::SEARCHED_FIELD_IDNUMBER, logger::SEARCHED_FIELD_EMAIL] as $field) {
            if (($side[$field] ?? null) !== null) {
                return [$field, $side[$field]];
            }
        }

        return null;
    }

    /**
     * Translated label for a searched field, without its trailing colon - e.g.
     * 'username' -> 'Username'. Reuses the same labels a found user's identity is
     * shown with (snapshot_username, ...), so no separate strings are needed.
     *
     * @param string $field one of 'username', 'idnumber', 'email'.
     * @return string
     */
    private function searched_field_label(string $field): string {
        $labelkeys = [
            logger::SEARCHED_FIELD_USERNAME => 'snapshot_username',
            logger::SEARCHED_FIELD_IDNUMBER => 'snapshot_idnumber',
            logger::SEARCHED_FIELD_EMAIL => 'snapshot_email',
        ];

        return rtrim(get_string($labelkeys[$field], 'tool_mergeusers'), ':');
    }

    /**
     * Produces the page with the list of logs: a search box, an export link, the
     * paginated table of logs and a paging bar above and below it.
     *
     * @param array $logs page of logs to show (already sliced to $perpage).
     * @param int $totalcount total number of logs matching $searchterm (all pages).
     * @param int $page current page number, 0-based.
     * @param int $perpage number of logs shown per page.
     * @param moodle_url $baseurl base URL for the paging bar and search form action (without the page param).
     * @param string|null $searchterm current search term, or null when no search is active.
     * @return string the corresponding HTML.
     * @throws \coding_exception
     * @throws moodle_exception
     */
    public function logs_page(
        array $logs,
        int $totalcount,
        int $page,
        int $perpage,
        moodle_url $baseurl,
        ?string $searchterm = null,
    ): string {
        global $CFG;

        $output = $this->header();
        $output .= $this->heading(get_string('viewlog', 'tool_mergeusers'));
        $output .= html_writer::start_tag('div', ['class' => 'result']);

        $output .= html_writer::start_tag('div', ['class' => 'tool-mergeusers-logs-toolbar']);
        $output .= $this->render_logs_search_form($searchterm);

        $exportparams = ['export' => 1];
        if ($searchterm !== null) {
            // Not array_filter(): a falsy-but-meaningful search term (e.g. the
            // literal string "0") must still reach the export URL.
            $exportparams['search'] = $searchterm;
        }
        $output .= html_writer::start_tag('div', ['class' => 'tool-mergeusers-logs-toolbar__export']);
        $output .= html_writer::link(
            new moodle_url('/admin/tool/mergeusers/view.php', $exportparams),
            get_string('exportlogs', 'tool_mergeusers')
        );
        $output .= $this->help_icon('exportlogs', 'tool_mergeusers');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div'); // Close .tool-mergeusers-logs-toolbar.

        if ($totalcount === 0) {
            $output .= html_writer::tag(
                'div',
                ($searchterm !== null)
                    ? get_string('nologsforsearch', 'tool_mergeusers', s($searchterm))
                    : get_string('nologs', 'tool_mergeusers'),
            );
        } else {
            $output .= html_writer::tag(
                'div',
                ($searchterm !== null)
                    ? get_string(
                        'loglistforsearch',
                        'tool_mergeusers',
                        (object) ['count' => $totalcount, 'search' => s($searchterm)],
                    )
                    : get_string('loglist', 'tool_mergeusers', $totalcount),
                ['class' => 'title'],
            );
            $output .= $this->render(new \core\output\paging_bar($totalcount, $page, $perpage, $baseurl));

            $table = new html_table();
            $table->align = ['center', 'center', 'center', 'center', 'center', 'center'];
            $table->head = [
                get_string('olduseridonlog', 'tool_mergeusers'),
                get_string('newuseridonlog', 'tool_mergeusers'),
                get_string('mergedbyuseridonlog', 'tool_mergeusers'),
                get_string('date'),
                get_string('status'),
                '',
            ];

            $rows = [];
            foreach ($logs as $i => $log) {
                $row = new html_table_row();

                // Determine status display.
                $statusdisplay = $this->render_status($log->status);

                // Display timecreated if available, otherwise fall back to timemodified.
                $displaytime = (!empty($log->timecreated)) ? $log->timecreated : $log->timemodified;

                $logdata = json_decode($log->log ?? '', true);
                $snapshots = is_array($logdata) ? ($logdata['user_snapshots'] ?? null) : null;
                $fromhint = $this->extract_searched_hint(is_array($snapshots) ? ($snapshots['from_user'] ?? null) : null);
                $tohint = $this->extract_searched_hint(is_array($snapshots) ? ($snapshots['to_user'] ?? null) : null);

                $row->cells = [
                    $this->show_user_or_notfound($log->fromuserid, $log->from, $fromhint),
                    $this->show_user_or_notfound($log->touserid, $log->to, $tohint),
                    ($log->mergedby)
                        ? $this->show_user($log->mergedbyuserid, $log->mergedby)
                        : get_string('nomergedby', 'tool_mergeusers'),
                    userdate($displaytime, get_string('strftimedaydatetime', 'langconfig')),
                    $statusdisplay,
                    html_writer::link(
                        new moodle_url(
                            '/' . $CFG->admin . '/tool/mergeusers/log.php',
                            ['id' => $log->id]
                        ),
                        get_string('more'),
                        ['target' => '_blank']
                    ),
                ];
                $rows[] = $row;
            }

            $table->data = $rows;
            $output .= html_writer::table($table);
            $output .= $this->render(new \core\output\paging_bar($totalcount, $page, $perpage, $baseurl));
            $output .= $this->render_logs_showall_toggle($totalcount, $perpage, $baseurl);
        }

        $output .= html_writer::end_tag('div');
        $output .= $this->footer();

        return $output;
    }

    /**
     * Renders the "show all" / "show N per page" toggle link shown at the foot of
     * the merge log listing, mirroring the pattern used by core screens like
     * report/participation/index.php: a link to re-request the same $baseurl (so it
     * keeps any active search) with a different "perpage" value. Reuses core's own
     * generic 'showall'/'showperpage' moodle.php strings rather than defining new
     * plugin-specific ones.
     *
     * @param int $totalcount total number of logs matching the current search.
     * @param int $perpage number of logs shown per page right now.
     * @param moodle_url $baseurl base URL (already carrying the active search term, if any).
     * @return string HTML for the toggle link, or '' when everything already fits on one page.
     */
    private function render_logs_showall_toggle(int $totalcount, int $perpage, moodle_url $baseurl): string {
        $defaultperpage = (int) get_config('tool_mergeusers', 'logpagesize');
        if ($defaultperpage < 1) {
            $defaultperpage = 100;
        }

        if ($perpage >= self::SHOW_ALL_PAGE_SIZE && $totalcount > $defaultperpage) {
            $url = new moodle_url($baseurl, ['perpage' => $defaultperpage]);
            return html_writer::div(
                html_writer::link($url, get_string('showperpage', '', $defaultperpage)),
                'tool-mergeusers-logs-showall',
            );
        }

        if ($perpage < $totalcount) {
            $showallcount = min($totalcount, self::SHOW_ALL_PAGE_SIZE);
            $url = new moodle_url($baseurl, ['perpage' => self::SHOW_ALL_PAGE_SIZE]);
            return html_writer::div(
                html_writer::link($url, get_string('showall', '', $showallcount)),
                'tool-mergeusers-logs-showall',
            );
        }

        return '';
    }

    /**
     * Renders the plain GET search form shown above the merge log list, with no
     * JavaScript: submitting it reloads view.php with the "search" param set (and
     * "page" reset to the first page, since it is not included in the form).
     *
     * @param string|null $searchterm current search term, to repopulate the input.
     * @return string HTML for the search form.
     */
    private function render_logs_search_form(?string $searchterm): string {
        $inputid = 'tool-mergeusers-logs-search-input';

        $output = html_writer::start_tag('form', [
            'method' => 'get',
            'action' => new moodle_url('/admin/tool/mergeusers/view.php'),
            'class' => 'tool-mergeusers-logs-search',
        ]);
        $output .= html_writer::tag(
            'label',
            get_string('searchlogs', 'tool_mergeusers'),
            ['for' => $inputid, 'class' => 'tool-mergeusers-logs-search__label'],
        );
        $output .= html_writer::empty_tag('input', [
            'type' => 'text',
            'id' => $inputid,
            'name' => 'search',
            'value' => $searchterm ?? '',
            'class' => 'tool-mergeusers-logs-search__input',
        ]);
        $output .= html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('search'),
            'class' => 'btn btn-secondary tool-mergeusers-logs-search__submit',
        ]);
        if ($searchterm !== null) {
            $output .= html_writer::link(
                new moodle_url('/admin/tool/mergeusers/view.php'),
                get_string('clear'),
                ['class' => 'tool-mergeusers-logs-search__clear'],
            );
        }
        $output .= html_writer::end_tag('form');

        return $output;
    }

    /**
     * Maps a merge status to the corresponding \core\output\notification type,
     * used for the results page's full-width status box.
     *
     * Not shared with render_status()'s badge classes below: Bootstrap badge
     * suffixes and notification types disagree for the error case
     * (badge-danger vs notification type 'error'), so sharing this mapping
     * silently broke the log-list status badge colours.
     *
     * @param status $statusenum
     * @return string one of the \core\output\notification::NOTIFY_* constants.
     */
    private function status_notification_type(status $statusenum): string {
        return match ($statusenum) {
            status::PENDING => notification::NOTIFY_WARNING,
            status::INPROGRESS => notification::NOTIFY_INFO,
            status::SUCCESS => notification::NOTIFY_SUCCESS,
            status::ERROR => notification::NOTIFY_ERROR,
        };
    }

    /**
     * Renders a status badge.
     *
     * @param string|null $status
     * @return string HTML badge
     */
    public function render_status(?string $status): string {
        $statusenum = status::safe_from($status);
        $statusbadgeclass = match ($statusenum) {
            status::PENDING => 'badge-warning',
            status::INPROGRESS => 'badge-info',
            status::SUCCESS => 'badge-success',
            status::ERROR => 'badge-danger',
            default => 'badge-secondary',
        };
        $statusstring = get_string('status:' . $statusenum->value, 'tool_mergeusers');

        return html_writer::tag('span', $statusstring, ['class' => 'badge ' . $statusbadgeclass]);
    }

    /**
     * Gathers detail data for merge detail display.
     *
     * @param int $userid
     * @param int $timemodified the time the merge occurred
     * @param int $logid        id of log
     * @param string $status    the merge status: pending, inprogress, success, error
     * @return array Containing profile link, formatted timestamp and log link.
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function get_merge_detail_data(int $userid, int $timemodified, int $logid, string $status): array {
        $profileuser = core_user::get_user($userid);
        $time = userdate($timemodified);
        $profilelink = !empty($profileuser) ? html_writer::link(
            new moodle_url('/user/profile.php', ['id' => $userid]),
            fullname($profileuser)
        ) : get_string('unknownprofile', 'tool_mergeusers', $userid);
        $loglink = html_writer::link(
            new moodle_url('/admin/tool/mergeusers/log.php', ['id' => $logid]),
            get_string('openlog', 'tool_mergeusers')
        );

        return [
            'profilelink' => $profilelink,
            'time' => $time,
            'loglink' => $loglink,
            'success' => strtolower(get_string('status:' . $status, 'tool_mergeusers')),
        ];
    }

    /**
     * Builds merge detail HTML.
     *
     * @param stdClass $user        User object.
     * @param last_merge $lastmerge last merge
     * @return string HTML to display
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_merge_detail(stdClass $user, last_merge $lastmerge): string {
        $tome = $lastmerge->tome();
        $fromme = $lastmerge->fromme();
        $tohtml = $tome ? get_string(
            'tomedetail',
            'tool_mergeusers',
            $this->get_merge_detail_data($tome->fromuserid, $tome->timemodified, $tome->id, $tome->status)
        ) : '';
        $fromhtml = $fromme ? get_string(
            'frommedetail',
            'tool_mergeusers',
            $this->get_merge_detail_data($fromme->touserid, $fromme->timemodified, $fromme->id, $fromme->status)
        ) : '';
        $output = implode('<br/>', array_filter([$tohtml, $fromhtml]));

        // Ok, there is no merge related to this user.
        if (empty($output)) {
            return get_string('none');
        }
        // Go on, this user was involved in some merge.

        if (!has_capability('moodle/user:delete', context_system::instance())) {
            return $output;
        }

        // Only when the user who is viewing the user profile can delete users, show whether this user is deletable.
        // This calculation is based on the behaviour of this plugin and last successful merges related to this user.
        $deletablestring = ($lastmerge->is_this_user_deletable()) ? 'deletableuser' : 'nondeletableuser';

        return $output . '<br/><br/>' . get_string($deletablestring, 'tool_mergeusers');
    }

    /**
     * Renders the "who's being merged" info box: a shared capture timestamp line
     * followed by the two user boxes (the user to remove, then the user to keep).
     *
     * @param merge_user_display $from
     * @param merge_user_display $to
     * @param int|null $capturedat unix timestamp shared by both snapshots, or null
     * when it could not be determined.
     * @return string HTML output.
     */
    private function render_merge_user_info(merge_user_display $from, merge_user_display $to, ?int $capturedat): string {
        $output = html_writer::start_tag('div', ['class' => 'tool-mergeusers-user-info']);
        $output .= html_writer::tag('h4', get_string('snapshot_header', 'tool_mergeusers'));

        if ($capturedat !== null) {
            $output .= html_writer::tag(
                'p',
                get_string('snapshot_capturedat', 'tool_mergeusers', userdate($capturedat)),
                ['class' => 'tool-mergeusers-user-info__capturedat'],
            );
        }

        $output .= html_writer::start_tag('div', ['class' => 'tool-mergeusers-user-info__row']);
        $output .= $this->render_merge_user_box($from, get_string('olduser', 'tool_mergeusers'));
        $output .= $this->render_merge_user_box($to, get_string('newuser', 'tool_mergeusers'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Renders a single user box within the merge user info section.
     *
     * @param merge_user_display $user
     * @param string $label
     * @return string HTML output.
     */
    private function render_merge_user_box(merge_user_display $user, string $label): string {
        $boxclass = 'tool-mergeusers-user-info__box';
        if (!$user->recoverable) {
            $boxclass .= ' tool-mergeusers-user-info__box--unavailable';
        }

        $output = html_writer::start_tag('div', ['class' => $boxclass]);
        $output .= html_writer::tag('strong', $label);
        $output .= html_writer::empty_tag('br');

        if ($user->notfound) {
            // At most one field is non-null: the one a gathering reported having
            // searched for, per logger::notfound_snapshot(). Reuses the same field
            // labels a found user's identity is shown with, but only for that one field
            // - not the full set, since there is nothing else known about this side.
            $searchedhint = $this->extract_searched_hint($user);
            if ($searchedhint !== null) {
                $output .= html_writer::tag(
                    'div',
                    $this->searched_field_label($searchedhint[0]) . ': ' . s($searchedhint[1]),
                );
            }

            $output .= html_writer::tag('em', get_string('usernotfoundatmerge', 'tool_mergeusers'));
            $output .= html_writer::end_tag('div');

            return $output;
        }

        if (!$user->recoverable) {
            $output .= html_writer::tag('div', get_string('snapshot_id', 'tool_mergeusers') . ' ' . $user->id);
            if ($user->erasedforgdpr) {
                $output .= html_writer::tag(
                    'em',
                    get_string('userinfo_erasedforgdpr', 'tool_mergeusers', userdate($user->timeerased)),
                );
            } else {
                $output .= html_writer::tag('em', get_string('userinfo_notavailable', 'tool_mergeusers', $user->id));
            }
            $output .= html_writer::end_tag('div');

            return $output;
        }

        if ($user->profileurl !== null) {
            $nametext = html_writer::link($user->profileurl, s($user->displayname ?? 'N/A'));
        } else {
            $nametext = s($user->displayname ?? 'N/A');
        }
        $output .= html_writer::tag('div', get_string('snapshot_name', 'tool_mergeusers') . ' ' . $nametext);

        $output .= html_writer::tag('div', get_string('snapshot_username', 'tool_mergeusers') . ' ' . s($user->username ?? 'N/A'));
        $output .= html_writer::tag('div', get_string('snapshot_email', 'tool_mergeusers') . ' ' . s($user->email ?? 'N/A'));
        $output .= html_writer::tag(
            'div',
            get_string('snapshot_idnumber', 'tool_mergeusers') . ' ' . s($user->idnumber ?? 'N/A'),
        );
        $output .= html_writer::tag('div', get_string('snapshot_id', 'tool_mergeusers') . ' ' . $user->id);

        $suspendedtext = $user->suspended ? get_string('yes') : get_string('no');
        $deletedtext = $user->deleted ? get_string('yes') : get_string('no');
        $output .= html_writer::tag('div', get_string('snapshot_suspended', 'tool_mergeusers') . ' ' . $suspendedtext);
        $output .= html_writer::tag('div', get_string('snapshot_deleted', 'tool_mergeusers') . ' ' . $deletedtext);

        $output .= html_writer::end_tag('div');

        return $output;
    }
}

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
 * @author Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2013 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for the merge user plugin.
 *
 * @package    tool
 * @subpackage mergeuser
 * @copyright  2013 Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_mergeusers_renderer extends plugin_renderer_base
{

    /**
     * Shows form for merging users.
     * @param moodleform $mform form for merging users.
     * @return string html to show on index page.
     */
    public function index_page(moodleform $mform)
    {
        $output = $this->header();
        $output .= $this->heading_with_help(get_string('mergeusers', 'tool_mergeusers'), 'header', 'tool_mergeusers');
        $output .= $this->moodleform($mform);
        $output .= $this->footer();
        return $output;
    }

    /**
     * Shows the result of a merging action.
     * @param object $to stdClass with at least id and username fields.
     * @param object $from stdClass with at least id and username fields.
     * @param bool $success true if merging was ok; false otherwise.
     * @param array $data logs of actions done if success, or list of errors on failure.
     * @param id $logid id of the record with the whole detail of this merging action.
     * @return string html with the results.
     */
    public function results_page($to, $from, $success, array $data, $logid)
    {
        if ($success) {
            $resulttype = 'ok';
            $notifytype = 'notifysuccess';
        } else {
            $resulttype = 'ko';
            $notifytype = 'notifyproblem';
        }

        $output = $this->header();
        $output .= $this->heading(get_string('mergeusers', 'tool_mergeusers'));
        $output .= html_writer::start_tag('div', array('class' => 'result'));
        $output .= html_writer::start_tag('div', array('class' => 'title'));
        $output .= get_string('merging', 'tool_mergeusers');
        if (!is_null($to) && !is_null($from)) {
            $output .= ' ' . get_string('usermergingheader', 'tool_mergeusers', $from) . ' ' .
                    get_string('into', 'tool_mergeusers') . ' ' .
                    get_string('usermergingheader', 'tool_mergeusers', $to);
        }
        $output .= html_writer::empty_tag('br') . html_writer::empty_tag('br');
        $output .= get_string('logid', 'tool_mergeusers', $logid);
        $output .= html_writer::empty_tag('br');
        $output .= get_string('log' . $resulttype, 'tool_mergeusers');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::empty_tag('br');

        $output .= html_writer::start_tag('div', array('class' => 'resultset' . $resulttype));
        foreach ($data as $item) {
            $output .= $item . html_writer::empty_tag('br');
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::tag('div', html_writer::empty_tag('br'));
        $output .= $this->notification(html_writer::tag('center', get_string('db' . $resulttype, 'tool_mergeusers')), $notifytype);
        $output .= html_writer::tag('center', $this->single_button(new moodle_url('/admin/tool/mergeusers/index.php'), get_string('continue'), 'get'));
        $output .= $this->footer();

        return $output;
    }

    /**
     * Helper method dealing with the fact we can not just fetch the output of moodleforms
     *
     * @param moodleform $mform
     * @return string HTML
     */
    protected function moodleform(moodleform $mform)
    {
        ob_start();
        $mform->display();
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    public function logs_page($logs)
    {
        global $CFG;

        $output  = $this->header();
        $output .= $this->heading(get_string('viewlog', 'tool_mergeusers'));
        $output .= html_writer::start_tag('div', array('class' => 'result'));
        if (empty($logs)) {
            $output .= get_string('nologs', 'tool_mergeusers');
        } else {
            $output .= html_writer::tag('div', get_string('loglist', 'tool_mergeusers'), array('class' => 'title'));

            //i/cross_red_big
            $flags = array();
            $flags[] = html_writer::empty_tag('img', array('src' => $this->pix_url('i/cross_red_big'))); //failure icon
            $flags[] = html_writer::empty_tag('img', array('src' => $this->pix_url('i/tick_green_big'))); //ok icon

            $table = new html_table();
            $table->align = array('center', 'center', 'center', 'center', 'center', 'center');
            $table->head = array(get_string('olduseridonlog', 'tool_mergeusers'), get_string('newuseridonlog', 'tool_mergeusers'), get_string('date'), get_string('status'), '');

            $rows = array();
            foreach ($logs as $i => $log) {
                $row = new html_table_row();
                $row->cells = array(
                    ($log->from)
                        ? html_writer::link(
                            new moodle_url('/user/view.php',
                                array('id'=> $log->fromuserid, 'sesskey' =>sesskey())),
                                fullname($log->from) . ' (' . $log->from->username . ')')
                        : get_string('deleted', 'tool_mergeusers', $log->fromuserid),
                    ($log->to)
                        ? html_writer::link(
                            new moodle_url('/user/view.php',
                                array('id'=> $log->touserid, 'sesskey' =>sesskey())),
                                fullname($log->to) . ' (' . $log->to->username . ')')
                        : get_string('deleted', 'tool_mergeusers', $log->fromuserid),
                    userdate($log->timemodified, get_string('strftimedaydatetime', 'langconfig')),
                    $flags[$log->success],
                    html_writer::link(
                        new moodle_url('/' . $CFG->admin . '/tool/mergeusers/log.php',
                            array('id'=> $log->id, 'sesskey' =>sesskey())),
                        get_string('more'),
                        array('target' => '_blank')),
                );
                $rows[] = $row;
            }

            $table->data = $rows;
            $output .= html_writer::table($table);
        }

        $output .= html_writer::end_tag('div');
        $output .= $this->footer();

        return $output;
    }
}

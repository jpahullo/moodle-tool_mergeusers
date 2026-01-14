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
 * Tests for assign submission duplicated data merger.
 *
 * @author    Daniel Tomé <danieltomefer@gmail.com>
 * @copyright 2018 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @package   tool_mergeusers
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mergeusers\local\merger\finder\in_memory_assign_submission_finder;
use tool_mergeusers\local\merger\duplicateddata\assign_submission_duplicated_data_merger;
use tool_mergeusers\local\merger\duplicateddata\duplicated_data;

/**
 * Test class for assign submission duplicated data merger.
 *
 * @package   tool_mergeusers
 * @author    Daniel Tomé <danieltomefer@gmail.com>
 * @copyright 2018 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \tool_mergeusers\local\merger\duplicateddata\assign_submission_duplicated_data_merger
 */
final class assign_submission_duplicated_test extends advanced_testcase {
    /**
     * Should do nothing with new submission and remove old submission when old user has no content submission
     * and new user has content submission
     *
     * @param array $expectedtomodify Expected records to modify
     * @param array $expectedtoremove Expected records to remove
     * @param object $oldusersubmission Old user's submission data
     * @param object $newusersubmission New user's submission data
     * @group tool_mergeusers
     * @group tool_mergeusers_assign_submission
     * @dataProvider remove_old_ignore_new_data_provider
     */
    public function test_remove_old_ignore_new($expectedtomodify, $expectedtoremove, $oldusersubmission, $newusersubmission): void {
        $duplicateddata = $this->get_duplicated_data($oldusersubmission, $newusersubmission);

        $this->assertEquals($duplicateddata->to_update(), $expectedtomodify);
        $this->assertEquals($duplicateddata->to_remove(), $expectedtoremove);
    }

    /**
     * Data provider for test_remove_old_ignore_new.
     *
     * @return array
     */
    public static function remove_old_ignore_new_data_provider(): array {
        return [
                "when old is a new submission, new is submitted" => [
                        [],
                        [1 => 1],
                        self::get_assign_submission_new(1, 1111),
                        self::get_assign_submission_submitted(2, 2222),
                ],
                "when old is a new submission, new is draft" => [
                        [],
                        [1 => 1],
                        self::get_assign_submission_new(1, 1111),
                        self::get_assign_submission_draft(2, 2222),
                ],
                "when old is a new submission, new is reopened" => [
                        [],
                        [1 => 1],
                        self::get_assign_submission_new(1, 1111),
                        self::get_assign_submission_reopened(2, 2222),
                ],
                "when both are new submissions" => [
                        [],
                        [1 => 1],
                        self::get_assign_submission_new(1, 1111),
                        self::get_assign_submission_new(2, 2222),
                ],
        ];
    }

    /**
     * Should update old submission and remove new submission when old user has submitted
     * submission and new user has new submission
     *
     * @param array $expectedtomodify Expected records to modify
     * @param array $expectedtoremove Expected records to remove
     * @param object $oldusersubmission Old user's submission data
     * @param object $newusersubmission New user's submission data
     * @group tool_mergeusers
     * @group tool_mergeusers_assign_submission
     * @dataProvider update_old_and_remove_new_data_provider
     */
    public function test_update_old_and_remove_new(
        $expectedtomodify,
        $expectedtoremove,
        $oldusersubmission,
        $newusersubmission
    ): void {
        $duplicateddata = $this->get_duplicated_data($oldusersubmission, $newusersubmission);

        $this->assertEquals($duplicateddata->to_update(), $expectedtomodify);
        $this->assertEquals($duplicateddata->to_remove(), $expectedtoremove);
    }

    /**
     * Data provider for test_update_old_and_remove_new.
     *
     * @return array
     */
    public static function update_old_and_remove_new_data_provider(): array {
        return [
                "when old is submitted" => [
                        [1 => 1],
                        [2 => 2],
                        self::get_assign_submission_submitted(1, 1111),
                        self::get_assign_submission_new(2, 2222),
                ],
                "when old is draft" => [
                        [1 => 1],
                        [2 => 2],
                        self::get_assign_submission_draft(1, 1111),
                        self::get_assign_submission_new(2, 2222),
                ],
                "when old is reopened" => [
                        [1 => 1],
                        [2 => 2],
                        self::get_assign_submission_reopened(1, 1111),
                        self::get_assign_submission_new(2, 2222),
                ],
        ];
    }

    /**
     * Should update first submission submitted and remove last when user has duplicated submission submitted
     *
     * @param array $expectedtomodify Expected records to modify
     * @param array $expectedtoremove Expected records to remove
     * @param object $oldusersubmission Old user's submission data
     * @param object $newusersubmission New user's submission data
     * @group tool_mergeusers
     * @group tool_mergeusers_assign_submission
     * @dataProvider update_first_and_remove_last_data_provider
     */
    public function test_update_first_and_remove_last(
        $expectedtomodify,
        $expectedtoremove,
        $oldusersubmission,
        $newusersubmission,
    ): void {
        $duplicateddata = $this->get_duplicated_data($oldusersubmission, $newusersubmission);

        $this->assertEquals($duplicateddata->to_update(), $expectedtomodify);
        $this->assertEquals($duplicateddata->to_remove(), $expectedtoremove);
    }

    /**
     * Data provider for test_update_first_and_remove_last.
     *
     * @return array
     */
    public static function update_first_and_remove_last_data_provider(): array {
        return [
                "when both submitted" => [
                        [1 => 1],
                        [2 => 2],
                        self::get_assign_submission_submitted_by_date(1, 1111, 123456),
                        self::get_assign_submission_submitted_by_date(2, 2222, 987654),
                ],
                "when first draft and second submitted" => [
                        [1 => 1],
                        [2 => 2],
                        self::get_assign_submission_draft_by_date(1, 1111, 123456),
                        self::get_assign_submission_submitted_by_date(2, 2222, 987654),
                ],
                "when both submitted in reverse submitted date" => [
                        [2 => 2],
                        [1 => 1],
                        self::get_assign_submission_submitted_by_date(1, 1111, 987654),
                        self::get_assign_submission_submitted_by_date(2, 2222, 123456),
                ],
        ];
    }

    /**
     * Helper to create a submitted assignment submission.
     *
     * @param int $id Submission ID
     * @param int $assignid Assignment ID
     * @return object
     */
    private static function get_assign_submission_submitted($id, $assignid) {
        $anoldsubmittedassignsubmision = self::get_assign_submission($id);
        $anoldsubmittedassignsubmision->status = 'submitted';
        $anoldsubmittedassignsubmision->assignment = $assignid;

        return $anoldsubmittedassignsubmision;
    }

    /**
     * Helper to create a submitted assignment submission with specific date.
     *
     * @param int $id Submission ID
     * @param int $assignid Assignment ID
     * @param int $date Time modified
     * @return object
     */
    private static function get_assign_submission_submitted_by_date($id, $assignid, $date) {
        $anewsubmittedassignsubmission = self::get_assign_submission($id);
        $anewsubmittedassignsubmission->status = 'submitted';
        $anewsubmittedassignsubmission->assignment = $assignid;
        $anewsubmittedassignsubmission->timemodified = $date;

        return $anewsubmittedassignsubmission;
    }

    /**
     * Helper to create a new assignment submission.
     *
     * @param int $id Submission ID
     * @param int $assignid Assignment ID
     * @return object
     */
    private static function get_assign_submission_new($id, $assignid) {
        $anoldsubmittedassignsubmision = self::get_assign_submission($id);
        $anoldsubmittedassignsubmision->status = 'new';
        $anoldsubmittedassignsubmision->assignment = $assignid;

        return $anoldsubmittedassignsubmision;
    }

    /**
     * Helper to create a draft assignment submission with specific date.
     *
     * @param int $id Submission ID
     * @param int $assignid Assignment ID
     * @param int $date Time modified
     * @return object
     */
    private static function get_assign_submission_draft_by_date($id, $assignid, $date) {
        $draft = self::get_assign_submission_draft($id, $assignid);
        $draft->timemodified = $date;

        return $draft;
    }

    /**
     * Helper to create a draft assignment submission.
     *
     * @param int $id Submission ID
     * @param int $assignid Assignment ID
     * @return object
     */
    private static function get_assign_submission_draft($id, $assignid) {
        $anassignsubmissiondraft = self::get_assign_submission($id);
        $anassignsubmissiondraft->status = 'draft';
        $anassignsubmissiondraft->assignment = $assignid;

        return $anassignsubmissiondraft;
    }

    /**
     * Helper to create a reopened assignment submission.
     *
     * @param int $id Submission ID
     * @param int $assignid Assignment ID
     * @return object
     */
    private static function get_assign_submission_reopened($id, $assignid) {
        $anassignsubmissionreopened = self::get_assign_submission($id);
        $anassignsubmissionreopened->status = 'reopened';
        $anassignsubmissionreopened->assignment = $assignid;

        return $anassignsubmissionreopened;
    }

    /**
     * Helper to create a base assignment submission.
     *
     * @param int $id Submission ID
     * @return object
     */
    private static function get_assign_submission($id) {
        $anewassignsubmision = new stdClass();
        $anewassignsubmision->id = $id;
        $anewassignsubmision->assignment = 123456;
        $anewassignsubmision->userid = 1234;
        $anewassignsubmision->timecreated = 1189615462;
        $anewassignsubmision->timemodified = 1189615462;
        $anewassignsubmision->groupid = 1;
        $anewassignsubmision->attemptnumber = 0;
        $anewassignsubmision->latest = 1;

        return $anewassignsubmision;
    }

    /**
     * Get duplicated data for testing.
     *
     * @param object $oldusersubmission Old user submission
     * @param object $newusersubmission New user submission
     * @return duplicated_data
     */
    private function get_duplicated_data($oldusersubmission, $newusersubmission): duplicated_data {
        $data = [
            1111 => [1 => $oldusersubmission],
            2222 => [2 => $newusersubmission],
        ];

        $inmemoryfindbyquery = new in_memory_assign_submission_finder($data);
        $assignsubmissionduplicateddatamerger = new assign_submission_duplicated_data_merger($inmemoryfindbyquery);

        $duplicateddata = $assignsubmissionduplicateddatamerger->merge($oldusersubmission, $newusersubmission);

        return $duplicateddata;
    }
}

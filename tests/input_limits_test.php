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

namespace block_dixeo_modulegen;

use block_dixeo_modulegen\external\api;
use local_dixeo\util\input_limits;

/**
 * Length and format limits on generation requests and queue log rows.
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_modulegen\external\api::submit_generation
 * @covers     \block_dixeo_modulegen\queue_service
 */
final class input_limits_test extends \advanced_testcase {
    public function test_instructions_above_the_limit_are_refused(): void {
        global $DB;

        $course = $this->course_as_admin();
        $max = input_limits::MAX_INSTRUCTIONS_CHARS;

        $accepted = api::submit_generation((int) $course->id, 'page', str_repeat('é', $max));
        $this->assertTrue($accepted['success']);

        try {
            api::submit_generation((int) $course->id, 'page', str_repeat('é', $max + 1));
            $this->fail('Instructions above the limit must be refused');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:instructions_too_long', $e->errorcode);
        }
        $this->assertSame(1, $DB->count_records(queue_repository::TABLE, ['courseid' => $course->id]));
    }

    public function test_blank_instructions_are_refused(): void {
        $course = $this->course_as_admin();

        $this->expectException(\invalid_parameter_exception::class);
        api::submit_generation((int) $course->id, 'page', "  \n ");
    }

    public function test_language_must_be_a_language_code(): void {
        global $DB;

        $course = $this->course_as_admin();

        try {
            api::submit_generation((int) $course->id, 'page', 'Write an introduction', 0, 0, 'xx_invalid_long');
            $this->fail('An invalid language code must be refused');
        } catch (\invalid_parameter_exception $e) {
            $this->assertSame(0, $DB->count_records(queue_repository::TABLE, ['courseid' => $course->id]));
        }

        $result = api::submit_generation((int) $course->id, 'page', 'Write an introduction', 0, 0, '');
        $row = queue_repository::get_by_id((int) $result['queueid']);
        $this->assertSame(current_language(), $row->lang);
    }

    public function test_fill_log_rows_are_cut_to_their_limits(): void {
        $course = $this->course_as_admin();
        $max = input_limits::MAX_INSTRUCTIONS_CHARS;
        $title = str_repeat('t', 300);

        $failedid = queue_service::log_fill_failed(
            (int) $course->id,
            'page',
            str_repeat('i', $max + 5),
            0,
            null,
            $title,
            str_repeat('s', $max + 5),
            '',
            ''
        );
        $completedid = queue_service::log_fill_completed(
            (int) $course->id,
            'page',
            'Instructions',
            0,
            null,
            0,
            $title,
            'Summary',
            ''
        );
        $manualid = queue_service::log_manual_upload_completed((int) $course->id, 'resource', 0, null, 0, $title, 'notes.pdf');

        foreach ([$failedid, $completedid, $manualid] as $queueid) {
            $row = queue_repository::get_by_id($queueid);
            $params = json_decode($row->params, true);
            $this->assertSame(255, \core_text::strlen($row->title));
            $this->assertSame($row->title, $params['title']);
        }

        $failed = queue_repository::get_by_id($failedid);
        $this->assertSame($max, \core_text::strlen($failed->instructions));
        $this->assertSame($max, \core_text::strlen(json_decode($failed->params, true)['summary']));
    }

    /**
     * Create a course and log in as the administrator.
     *
     * @return \stdClass
     */
    private function course_as_admin(): \stdClass {
        $this->resetAfterTest();
        $this->setAdminUser();
        return $this->getDataGenerator()->create_course();
    }
}

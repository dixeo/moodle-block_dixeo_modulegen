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
 * Tests for fill-mode queue logging and retry helpers.
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

use advanced_testcase;

/**
 * Integration tests for fill-mode queue logging and retry helpers.
 *
 * @covers \block_dixeo_modulegen\queue_service
 * @covers \block_dixeo_modulegen\queue_task_mode
 */
final class queue_fill_integration_test extends advanced_testcase {
    public function test_queue_task_mode_defaults(): void {
        $this->assertSame(queue_task_mode::MODE_GENERATE, queue_task_mode::from_params(null));
        $this->assertFalse(queue_task_mode::is_fill(null));
    }

    public function test_log_fill_completed_inserts_row(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $jid = 'aaaaaaaa-bbbb-4ccc-dddd-eeeeeeeeeeee';

        $id = queue_service::log_fill_completed(
            (int) $course->id,
            'page',
            'Instructions',
            1,
            null,
            55,
            'My page',
            'Summary',
            $jid
        );

        $row = $DB->get_record(queue_repository::TABLE, ['id' => $id], '*', MUST_EXIST);
        $this->assertSame(queue_status::STATUS_COMPLETED, (int) $row->status);
        $this->assertSame(55, (int) $row->cmid);
        $params = json_decode($row->params, true);
        $this->assertIsArray($params);
        $this->assertSame('Summary', $params['summary'] ?? '');
        $this->assertTrue(queue_task_mode::is_fill($row->params));
    }

    public function test_start_next_invalidates_pending_fill(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $record = queue_repository::create_base_record(
            (int) $course->id,
            'page',
            'X',
            1,
            null,
            'en'
        );
        $record->status = queue_status::STATUS_PENDING;
        $record->jobid = \core\uuid::generate();
        $record->params = json_encode(['mode' => queue_task_mode::MODE_FILL]);
        $tid = queue_repository::insert($record);

        $this->assertNull(queue_service::start_next((int) $course->id));

        $row = $DB->get_record(queue_repository::TABLE, ['id' => $tid], '*', MUST_EXIST);
        $this->assertSame(queue_status::STATUS_FAILED, (int) $row->status);
    }

    public function test_complete_failed_fill_retry(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $record = queue_repository::create_base_record(
            (int) $course->id,
            'page',
            'X',
            1,
            null,
            'en'
        );
        $record->title = 'T';
        $record->status = queue_status::STATUS_FAILED;
        $record->jobid = 'old';
        $record->timecompleted = time();
        $record->params = json_encode([
            'mode' => queue_task_mode::MODE_FILL,
            'title' => 'T',
            'summary' => '',
            'dixeo_jobid' => 'old',
            'error' => 'e',
        ]);
        $tid = queue_repository::insert($record);

        $this->assertTrue(queue_service::complete_failed_fill_retry($tid, 77, 'newjob'));

        $row = $DB->get_record(queue_repository::TABLE, ['id' => $tid], '*', MUST_EXIST);
        $this->assertSame(queue_status::STATUS_COMPLETED, (int) $row->status);
        $this->assertSame(77, (int) $row->cmid);
        $params = json_decode($row->params, true);
        $this->assertArrayNotHasKey('error', $params);
    }

    public function test_log_manual_upload_completed_inserts_row(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $id = queue_service::log_manual_upload_completed(
            (int) $course->id,
            'scorm',
            1,
            null,
            88,
            'My SCORM',
            'package.zip'
        );

        $row = $DB->get_record(queue_repository::TABLE, ['id' => $id], '*', MUST_EXIST);
        $this->assertSame(queue_status::STATUS_COMPLETED, (int) $row->status);
        $this->assertSame(88, (int) $row->cmid);
        $this->assertTrue(queue_task_mode::is_manual($row->params));
        $params = json_decode($row->params, true);
        $this->assertSame('package.zip', $params['filename'] ?? '');
    }

    public function test_start_next_invalidates_pending_manual(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $record = queue_repository::create_base_record(
            (int) $course->id,
            'resource',
            'Manual',
            1,
            null,
            'en'
        );
        $record->status = queue_status::STATUS_PENDING;
        $record->jobid = \core\uuid::generate();
        $record->params = json_encode(['mode' => queue_task_mode::MODE_MANUAL]);
        $tid = queue_repository::insert($record);

        $this->assertNull(queue_service::start_next((int) $course->id));

        $row = $DB->get_record(queue_repository::TABLE, ['id' => $tid], '*', MUST_EXIST);
        $this->assertSame(queue_status::STATUS_FAILED, (int) $row->status);
    }
}

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
use block_dixeo_modulegen\task\cleanup_modulegen_queue;
use local_dixeo\dto\operation_result;
use local_dixeo\external\service_factory;
use local_dixeo\service\file_sync_service;
use local_dixeo\service\module_generation_service;

/**
 * Abandoned queue rows are failed so they expire and stop blocking their course.
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_modulegen\queue_service::expire_stale
 * @covers     \block_dixeo_modulegen\task\cleanup_modulegen_queue
 * @covers     \block_dixeo_modulegen\external\api::create_module_for_task
 */
final class queue_expiry_test extends \advanced_testcase {
    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    public function test_cleanup_fails_stale_rows_and_later_deletes_them(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $courseid = (int) $this->getDataGenerator()->create_course()->id;
        $now = time();
        $staleprocessing = $this->insert_row($courseid, queue_status::STATUS_PROCESSING, $now - DAYSECS, $now - 7 * HOURSECS);
        $stalepending = $this->insert_row($courseid, queue_status::STATUS_PENDING, $now - 8 * DAYSECS, 0);
        $recentprocessing = $this->insert_row($courseid, queue_status::STATUS_PROCESSING, $now - HOURSECS, $now - HOURSECS);
        $recentpending = $this->insert_row($courseid, queue_status::STATUS_PENDING, $now - DAYSECS, 0);

        $this->run_cleanup();

        $error = get_string('error_stale_task', 'block_dixeo_modulegen');
        foreach ([$staleprocessing, $stalepending] as $queueid) {
            $row = queue_repository::get_by_id($queueid);
            $this->assertSame(queue_status::STATUS_FAILED, (int) $row->status);
            $this->assertSame($error, json_decode($row->params, true)['error']);
            $this->assertGreaterThanOrEqual($now, (int) $row->timecompleted);
        }
        $this->assertSame(queue_status::STATUS_PROCESSING, (int) queue_repository::get_by_id($recentprocessing)->status);
        $this->assertSame(queue_status::STATUS_PENDING, (int) queue_repository::get_by_id($recentpending)->status);

        $retention = cleanup_modulegen_queue::RETENTION_DAYS * DAYSECS;
        [$insql, $params] = $DB->get_in_or_equal([$staleprocessing, $stalepending]);
        $DB->set_field_select(queue_repository::TABLE, 'timecompleted', $now - $retention - 1, "id {$insql}", $params);
        $this->run_cleanup();

        $this->assertNull(queue_repository::get_by_id($staleprocessing));
        $this->assertNull(queue_repository::get_by_id($stalepending));
        $this->assertNotNull(queue_repository::get_by_id($recentprocessing));
    }

    public function test_claim_fails_a_stale_processing_row_and_promotes_the_next_pending_row(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $courseid = (int) $this->getDataGenerator()->create_course()->id;
        $now = time();
        $stale = $this->insert_row($courseid, queue_status::STATUS_PROCESSING, $now - DAYSECS, $now - 7 * HOURSECS);
        $pending = $this->insert_row($courseid, queue_status::STATUS_PENDING, $now - HOURSECS, 0);

        $syncmock = $this->createMock(file_sync_service::class);
        service_factory::set_test_file_sync_service($syncmock);
        $jobid = \core\uuid::generate();
        $modulemock = $this->createMock(module_generation_service::class);
        $modulemock->method('set_component')->willReturnSelf();
        $modulemock->expects($this->once())
            ->method('submit_generate_job_for_course')
            ->willReturn(operation_result::pending($jobid));
        service_factory::set_test_module_generation_service($modulemock);

        $started = queue_service::process_next_pending($courseid);

        $this->assertSame($pending, $started['queueid']);
        $this->assertSame(queue_status::STATUS_FAILED, (int) queue_repository::get_by_id($stale)->status);
        $this->assertSame(queue_status::STATUS_PROCESSING, (int) queue_repository::get_by_id($pending)->status);
    }

    public function test_create_fails_a_row_whose_type_is_no_longer_supported(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $courseid = (int) $this->getDataGenerator()->create_course()->id;
        $queueid = $this->insert_row($courseid, queue_status::STATUS_PROCESSING, time(), time(), 'notamodule');

        $result = api::create_module_for_task($queueid);

        $this->assertFalse($result['success']);
        $this->assertSame(queue_status::STATUS_FAILED, (int) queue_repository::get_by_id($queueid)->status);
    }

    public function test_create_leaves_the_row_when_only_the_caller_cannot_add_the_type(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('mod/page:addinstance', CAP_PROHIBIT, $roleid, \context_course::instance($course->id)->id, true);
        accesslib_clear_all_caches_for_unit_testing();
        $queueid = $this->insert_row((int) $course->id, queue_status::STATUS_PROCESSING, time(), time());
        $this->setUser($teacher);

        try {
            api::create_module_for_task($queueid);
            $this->fail('A caller without addinstance must not create the activity');
        } catch (\required_capability_exception $e) {
            $this->assertSame(queue_status::STATUS_PROCESSING, (int) queue_repository::get_by_id($queueid)->status);
        }
    }

    /**
     * Insert a generate queue row.
     *
     * @param int $courseid Course id.
     * @param int $status Queue status.
     * @param int $timecreated Creation time.
     * @param int $timestarted Start time, 0 when not started.
     * @param string $modulename Dixeo module type.
     * @return int Queue row id.
     */
    private function insert_row(
        int $courseid,
        int $status,
        int $timecreated,
        int $timestarted,
        string $modulename = 'page'
    ): int {
        $record = queue_repository::create_base_record($courseid, $modulename, 'Instructions', null, null, 'en');
        $record->status = $status;
        $record->timecreated = $timecreated;
        $record->timestarted = $timestarted;
        $record->params = json_encode(['submittedby' => (int) get_admin()->id]);
        if ($status === queue_status::STATUS_PROCESSING) {
            $record->jobid = \core\uuid::generate();
        }
        return queue_repository::insert($record);
    }

    /**
     * Run the cleanup task without printing its trace.
     *
     * @return void
     */
    private function run_cleanup(): void {
        ob_start();
        try {
            (new cleanup_modulegen_queue())->execute();
        } finally {
            ob_end_clean();
        }
    }
}

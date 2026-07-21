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
 * Tests for modulegen queue cancel hub binding (R1b).
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

use local_dixeo\external\service_factory;
use local_dixeo\service\job_service;

/**
 * Cancelling a processing queue task must pass course binding to the hub.
 *
 * @covers \block_dixeo_modulegen\queue_service::cancel
 */
final class queue_cancel_binding_test extends \advanced_testcase {
    /** @var string Valid UUID for mocked remote jobs. */
    private const JOB_ID = '5f38d9aa-f40c-4992-9727-982f050ff9fd';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    public function test_cancel_processing_task_passes_course_binding(): void {
        $course = $this->getDataGenerator()->create_course();
        $record = queue_repository::create_base_record((int) $course->id, 'page', 'Generate intro', null, null, 'en');
        $record->status = queue_status::STATUS_PROCESSING;
        $record->jobid = self::JOB_ID;
        $record->timestarted = time();
        $queueid = queue_repository::insert($record);

        $jobservice = $this->createMock(job_service::class);
        $jobservice->expects($this->once())
            ->method('cancel_job')
            ->with(self::JOB_ID, (int) $course->id)
            ->willReturn([]);
        service_factory::set_test_job_service($jobservice);

        $this->assertTrue(queue_service::cancel($queueid));

        $updated = queue_repository::get_by_id($queueid);
        $this->assertNotNull($updated);
        $this->assertSame(queue_status::STATUS_CANCELLED, (int) $updated->status);
    }
}

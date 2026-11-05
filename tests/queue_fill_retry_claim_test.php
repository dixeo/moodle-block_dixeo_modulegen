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
 * Tests for the fill retry claim: the row is taken before any remote call.
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

use advanced_testcase;
use block_dixeo_modulegen\event\queue_task_failed;
use block_dixeo_modulegen\external\api;
use local_dixeo\dto\job_status;
use local_dixeo\dto\operation_result;
use local_dixeo\external\service_factory;
use local_dixeo\repository\job_repository;
use local_dixeo\service\file_sync_service;
use local_dixeo\service\job_service;
use local_dixeo\service\module_generation_service;

/**
 * A fill retry must claim its row before submitting, so a double click cannot fill twice.
 *
 * @covers \block_dixeo_modulegen\external\api::retry_fill_task
 * @covers \block_dixeo_modulegen\queue_service::start_fill_retry
 */
final class queue_fill_retry_claim_test extends advanced_testcase {
    /** @var string Job UUID returned by the mocked fill submission. */
    private const FILL_JOB_ID = '9c1f4b2a-1111-4222-8333-444455556666';

    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    public function test_retry_claims_row_before_submit_and_rejects_concurrent_retry(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_dixeo')->create_terms_acceptance(['userid' => get_admin()->id]);
        $course = $this->getDataGenerator()->create_course();
        $queueid = $this->insert_failed_fill_row((int) $course->id);

        service_factory::set_test_file_sync_service($this->createMock(file_sync_service::class));

        $observed = [];
        $modulemock = $this->createMock(module_generation_service::class);
        $modulemock->method('set_component')->willReturnSelf();
        $modulemock->expects($this->once())
            ->method('submit_fill_job_for_course')
            ->willReturnCallback(function () use (&$observed, $queueid, $course): operation_result {
                global $DB;
                $row = $DB->get_record(queue_repository::TABLE, ['id' => $queueid], '*', MUST_EXIST);
                $observed['status'] = (int) $row->status;
                $observed['jobid'] = (string) $row->jobid;
                $observed['timestarted'] = (int) $row->timestarted;
                $observed['concurrent'] = api::retry_fill_task($queueid, (int) $course->id);
                return operation_result::pending(self::FILL_JOB_ID);
            });
        service_factory::set_test_module_generation_service($modulemock);

        // Never completing job: the pipeline times out, which exercises the failure finalization.
        $jobmock = $this->createMock(job_service::class);
        $jobmock->method('wait_for_job')->willReturn(operation_result::pending(self::FILL_JOB_ID));
        service_factory::set_test_job_service($jobmock);

        $result = api::retry_fill_task($queueid, (int) $course->id);

        $this->assertSame(queue_status::STATUS_PROCESSING, $observed['status']);
        $this->assertSame('', $observed['jobid']);
        $this->assertGreaterThan(0, $observed['timestarted']);
        $this->assertFalse($observed['concurrent']['success']);
        $this->assertSame(
            get_string('retry_fill_notfailed', 'block_dixeo_modulegen'),
            $observed['concurrent']['message']
        );

        $this->assertFalse($result['success']);
        $row = $DB->get_record(queue_repository::TABLE, ['id' => $queueid], '*', MUST_EXIST);
        $this->assertSame(queue_status::STATUS_FAILED, (int) $row->status);
        $params = json_decode($row->params, true);
        $this->assertSame(get_string('retry_fill_timeout', 'block_dixeo_modulegen'), $params['error'] ?? '');
    }

    public function test_retry_returns_row_to_failed_when_submission_throws(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_dixeo')->create_terms_acceptance(['userid' => get_admin()->id]);
        $course = $this->getDataGenerator()->create_course();
        $queueid = $this->insert_failed_fill_row((int) $course->id);

        service_factory::set_test_file_sync_service($this->createMock(file_sync_service::class));
        service_factory::set_test_job_service($this->createMock(job_service::class));

        $modulemock = $this->createMock(module_generation_service::class);
        $modulemock->method('set_component')->willReturnSelf();
        $modulemock->expects($this->once())
            ->method('submit_fill_job_for_course')
            ->willThrowException(new \moodle_exception('retry_fill_failed', 'block_dixeo_modulegen'));
        service_factory::set_test_module_generation_service($modulemock);

        $result = api::retry_fill_task($queueid, (int) $course->id);

        $this->assertFalse($result['success']);
        $row = $DB->get_record(queue_repository::TABLE, ['id' => $queueid], '*', MUST_EXIST);
        $this->assertSame(queue_status::STATUS_FAILED, (int) $row->status);
        $params = json_decode($row->params, true);
        $this->assertNotEmpty($params['error'] ?? '');
    }

    public function test_retry_rejects_task_from_another_course(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_dixeo')->create_terms_acceptance(['userid' => get_admin()->id]);
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $queueid = $this->insert_failed_fill_row((int) $course->id);

        $result = api::retry_fill_task($queueid, (int) $other->id);

        $this->assertFalse($result['success']);
        $this->assertSame(get_string('retry_fill_notfound', 'block_dixeo_modulegen'), $result['message']);
    }

    public function test_retry_with_result_of_another_type_fails_the_row(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_dixeo')->create_terms_acceptance(['userid' => get_admin()->id]);
        $course = $this->getDataGenerator()->create_course();
        $queueid = $this->insert_failed_fill_row((int) $course->id);
        $this->mock_fill_job((int) $course->id, 'quiz');

        $sink = $this->redirectEvents();
        $result = api::retry_fill_task($queueid, (int) $course->id);
        $events = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof queue_task_failed
        ));
        $sink->close();
        $this->resetDebugging();

        $this->assertFalse($result['success']);
        $this->assertSame(0, $DB->count_records('course_modules', ['course' => $course->id]));
        $row = $DB->get_record(queue_repository::TABLE, ['id' => $queueid], '*', MUST_EXIST);
        $this->assertSame(queue_status::STATUS_FAILED, (int) $row->status);
        $this->assertCount(1, $events);
        $this->assertSame(queue_task_failed::REASON_TYPE_MISMATCH, $events[0]->other['reason']);
    }

    public function test_retry_with_result_of_the_row_type_creates_the_activity(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_dixeo')->create_terms_acceptance(['userid' => get_admin()->id]);
        $course = $this->getDataGenerator()->create_course();
        $queueid = $this->insert_failed_fill_row((int) $course->id);
        $this->mock_fill_job((int) $course->id, 'page');

        $result = api::retry_fill_task($queueid, (int) $course->id);
        $this->resetDebugging();

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, (int) $result['cmid']);
        $this->assertSame(1, $DB->count_records('course_modules', ['course' => $course->id]));
        $row = $DB->get_record(queue_repository::TABLE, ['id' => $queueid], '*', MUST_EXIST);
        $this->assertSame(queue_status::STATUS_COMPLETED, (int) $row->status);
    }

    /**
     * Mock file sync and fill submission, and serve a completed fill job bound to the course.
     *
     * The job service is real, so waiting for the job and creating the module read the same status.
     *
     * @param int $courseid Course id.
     * @param string $moduletype moduleType reported by the job result.
     * @return void
     */
    private function mock_fill_job(int $courseid, string $moduletype): void {
        service_factory::set_test_file_sync_service($this->createMock(file_sync_service::class));

        $modulemock = $this->createMock(module_generation_service::class);
        $modulemock->method('set_component')->willReturnSelf();
        $modulemock->method('submit_fill_job_for_course')
            ->willReturn(operation_result::pending(self::FILL_JOB_ID));
        service_factory::set_test_module_generation_service($modulemock);

        $repo = new job_repository();
        $repo->register(
            self::FILL_JOB_ID,
            $courseid,
            (int) get_admin()->id,
            'default',
            'fill_module',
            null,
            null,
            \local_dixeo\job_access_mode::COURSE_SHARED
        );
        $poller = $this->getMockBuilder(\local_dixeo\api\job_poller::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_job_status', 'sleep'])
            ->getMock();
        $poller->method('get_job_status')->willReturn(new job_status(
            jobid: self::FILL_JOB_ID,
            type: 'module',
            status: 'completed',
            progress: 100,
            createdat: time(),
            result: [
                'moduleType' => $moduletype,
                'creation' => [
                    [
                        'action' => 'create_module',
                        'save_as' => 'module',
                        'fields' => [
                            'name' => ['source' => '$.name'],
                            'intro' => ['source' => '$.intro'],
                            'content' => ['source' => '$.content'],
                        ],
                    ],
                ],
                'data' => [
                    'name' => 'Filled page',
                    'intro' => 'Intro',
                    'content' => '<p>Body</p>',
                ],
            ]
        ));
        service_factory::set_test_job_service(new job_service(null, $poller, $repo));
    }

    /**
     * Insert a failed fill row ready to be retried.
     *
     * @param int $courseid Course id.
     * @return int Queue row id.
     */
    private function insert_failed_fill_row(int $courseid): int {
        return queue_service::log_fill_failed(
            $courseid,
            'page',
            'Fill this page',
            0,
            null,
            'My page',
            'Summary',
            'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            'boom'
        );
    }
}

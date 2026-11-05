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
use local_dixeo\api\exception\api_exception;
use local_dixeo\dto\job_status;
use local_dixeo\external\service_factory;
use local_dixeo\repository\job_repository;
use local_dixeo\service\job_service;

/**
 * Queue state changes are checked on the server and fail closed.
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_modulegen\external\api::create_module_for_task
 * @covers     \block_dixeo_modulegen\external\api::update_task
 */
final class queue_state_checks_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_dixeo')->create_terms_acceptance(['userid' => get_admin()->id]);
    }

    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    public function test_unreadable_job_status_creates_nothing_and_keeps_the_row(): void {
        global $DB;

        [$courseid, $queueid, $jobid] = $this->processing_row();
        $jobservice = $this->createMock(job_service::class);
        $jobservice->expects($this->once())
            ->method('get_job_status')
            ->willThrowException(new api_exception('server_error', 'Upstream detail', 503));
        service_factory::set_test_job_service($jobservice);

        $result = api::create_module_for_task($queueid);
        $this->resetDebugging();

        $this->assertFalse($result['success']);
        $this->assertTrue($result['retry']);
        $this->assertSame(get_string('error_job_status_unavailable', 'block_dixeo_modulegen'), $result['message']);
        $this->assertSame(0, $DB->count_records('course_modules', ['course' => $courseid]));
        $this->assertSame(queue_status::STATUS_PROCESSING, (int) queue_repository::get_by_id($queueid)->status);
    }

    public function test_result_of_another_type_fails_the_row(): void {
        global $DB;

        [$courseid, $queueid, $jobid] = $this->processing_row();
        $this->completed_job($courseid, $jobid, 'quiz');

        $result = api::create_module_for_task($queueid);
        $this->resetDebugging();

        $this->assertFalse($result['success']);
        $this->assertSame(0, $DB->count_records('course_modules', ['course' => $courseid]));
        $this->assertSame(queue_status::STATUS_FAILED, (int) queue_repository::get_by_id($queueid)->status);
    }

    public function test_complete_action_is_refused(): void {
        [$courseid, $queueid, $jobid] = $this->processing_row();
        $cm = $this->getDataGenerator()->create_module('page', ['course' => $courseid]);

        $result = api::update_task($queueid, 'complete', (int) $cm->cmid, '', $jobid);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid action: complete', $result['message']);
        $this->assertSame(queue_status::STATUS_PROCESSING, (int) queue_repository::get_by_id($queueid)->status);
    }

    public function test_fail_is_refused_while_the_job_is_still_running(): void {
        [, $queueid, $jobid] = $this->processing_row();
        $this->job_service_reporting('processing');

        $result = api::update_task($queueid, 'fail', 0, 'Polling error', $jobid);

        $this->assertFalse($result['success']);
        $this->assertSame(queue_status::STATUS_PROCESSING, (int) queue_repository::get_by_id($queueid)->status);
    }

    public function test_fail_is_applied_when_the_job_failed(): void {
        [, $queueid, $jobid] = $this->processing_row();
        $this->job_service_reporting('failed');

        $result = api::update_task($queueid, 'fail', 0, 'Remote detail', $jobid);

        $this->assertTrue($result['success']);
        $row = queue_repository::get_by_id($queueid);
        $this->assertSame(queue_status::STATUS_FAILED, (int) $row->status);
        $this->assertSame(get_string('generationfailed', 'block_dixeo_modulegen'), json_decode($row->params, true)['error']);
    }

    public function test_fail_is_applied_to_a_stale_row_when_the_status_is_unreadable(): void {
        global $DB;

        [, $queueid, $jobid] = $this->processing_row();
        $DB->set_field(
            queue_repository::TABLE,
            'timestarted',
            time() - queue_service::STALE_PROCESSING_SECS - 1,
            ['id' => $queueid]
        );
        $jobservice = $this->createMock(job_service::class);
        $jobservice->method('get_job_status')->willThrowException(new \moodle_exception('error:job_not_found', 'local_dixeo'));
        service_factory::set_test_job_service($jobservice);

        $this->assertTrue(api::update_task($queueid, 'fail', 0, '', $jobid)['success']);
        $this->assertSame(queue_status::STATUS_FAILED, (int) queue_repository::get_by_id($queueid)->status);
    }

    /**
     * A processing generate row awaiting module creation.
     *
     * @return array{0: int, 1: int, 2: string} Course id, queue id and job id.
     */
    private function processing_row(): array {
        $courseid = (int) $this->getDataGenerator()->create_course()->id;
        $jobid = \core\uuid::generate();
        $record = queue_repository::create_base_record($courseid, 'page', 'Instructions', null, null, 'en');
        $record->status = queue_status::STATUS_PROCESSING;
        $record->jobid = $jobid;
        $record->timestarted = time();
        $record->params = json_encode(['submittedby' => (int) get_admin()->id]);
        return [$courseid, queue_repository::insert($record), $jobid];
    }

    /**
     * Bind a completed job of the given module type to the course.
     *
     * @param int $courseid Course id.
     * @param string $jobid Job id.
     * @param string $moduletype moduleType reported by the job result.
     * @return void
     */
    private function completed_job(int $courseid, string $jobid, string $moduletype): void {
        $repo = new job_repository();
        $repo->register(
            $jobid,
            $courseid,
            (int) get_admin()->id,
            'default',
            'module_generate',
            null,
            null,
            \local_dixeo\job_access_mode::COURSE_SHARED
        );
        $poller = $this->getMockBuilder(\local_dixeo\api\job_poller::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_job_status'])
            ->getMock();
        $poller->method('get_job_status')->willReturn(new job_status(
            jobid: $jobid,
            type: 'module',
            status: 'completed',
            progress: 100,
            createdat: time(),
            result: [
                'moduleType' => $moduletype,
                'creation' => [['action' => 'create_module', 'save_as' => 'module', 'fields' => []]],
                'data' => ['name' => 'Generated'],
            ]
        ));
        service_factory::set_test_job_service(new job_service(null, $poller, $repo));
    }

    /**
     * Job service whose status call reports the given remote status.
     *
     * @param string $status Remote job status.
     * @return void
     */
    private function job_service_reporting(string $status): void {
        $jobservice = $this->createMock(job_service::class);
        $jobservice->method('get_job_status')->willReturn(new job_status(
            jobid: \core\uuid::generate(),
            type: 'module',
            status: $status,
            progress: 50,
            createdat: time()
        ));
        service_factory::set_test_job_service($jobservice);
    }
}

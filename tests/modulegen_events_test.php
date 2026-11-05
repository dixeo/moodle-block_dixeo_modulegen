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
 * Tests for modulegen Moodle events (DIXEO-SEC-005).
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

use block_dixeo_modulegen\event\fill_task_retried;
use block_dixeo_modulegen\event\manual_upload_completed;
use block_dixeo_modulegen\event\queue_task_cancelled;
use block_dixeo_modulegen\event\queue_task_completed;
use block_dixeo_modulegen\event\queue_task_deleted;
use block_dixeo_modulegen\event\queue_task_failed;
use block_dixeo_modulegen\event\queue_task_submitted;
use block_dixeo_modulegen\external\api;
use local_dixeo\dto\job_status;
use local_dixeo\external\service_factory;
use local_dixeo\service\job_service;

/**
 * Sensitive modulegen actions must emit audit events without instructions in other.
 *
 * @covers \block_dixeo_modulegen\event\queue_task_submitted
 * @covers \block_dixeo_modulegen\event\queue_task_cancelled
 * @covers \block_dixeo_modulegen\event\queue_task_completed
 * @covers \block_dixeo_modulegen\event\queue_task_failed
 * @covers \block_dixeo_modulegen\event\queue_task_deleted
 * @covers \block_dixeo_modulegen\event\fill_task_retried
 * @covers \block_dixeo_modulegen\event\manual_upload_completed
 * @covers \block_dixeo_modulegen\external\api::submit_generation
 * @covers \block_dixeo_modulegen\external\api::update_task
 * @covers \block_dixeo_modulegen\external\api::delete_task
 * @covers \block_dixeo_modulegen\external\api::create_module_for_task
 * @covers \block_dixeo_modulegen\queue_service
 */
final class modulegen_events_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    /**
     * Assert event other contains queue identifiers only (no AI payload).
     *
     * @param \core\event\base $event
     */
    private function assert_minimal_queue_other(\core\event\base $event): void {
        $this->assertArrayHasKey('queueid', $event->other);
        $this->assertArrayHasKey('modulename', $event->other);
        $this->assertArrayNotHasKey('instructions', $event->other);
        $this->assertArrayNotHasKey('summary', $event->other);
        $this->assertArrayNotHasKey('filename', $event->other);
        $this->assertArrayNotHasKey('error', $event->other);
    }

    /**
     * Create a course with an enrolled editing teacher and set current user.
     *
     * @return array{0: \stdClass, 1: \stdClass}
     */
    private function create_course_and_editor(): array {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($user);
        return [$course, $user];
    }

    /**
     * Insert a processing generate queue row for complete/fail API tests.
     *
     * @param \stdClass $course Course record.
     * @param int $userid Acting user id stored in params.
     * @param string $jobid Remote job UUID.
     * @return int Queue row id.
     */
    private function create_processing_generate_task(\stdClass $course, int $userid, string $jobid): int {
        $record = queue_repository::create_base_record(
            (int) $course->id,
            'page',
            'Secret generate prompt',
            1,
            null,
            'en'
        );
        $record->status = queue_status::STATUS_PROCESSING;
        $record->jobid = $jobid;
        $record->timestarted = time();
        $record->params = json_encode([
            'jobid' => $jobid,
            'submittedby' => $userid,
        ]);

        return queue_repository::insert($record);
    }

    public function test_fail_emits_queue_task_failed(): void {
        [$course, $user] = $this->create_course_and_editor();
        $jobid = \core\uuid::generate();
        $queueid = $this->create_processing_generate_task($course, (int) $user->id, $jobid);
        $jobservice = $this->createMock(job_service::class);
        $jobservice->method('get_job_status')->willReturn(new job_status(
            jobid: $jobid,
            type: 'module',
            status: job_status::STATUS_FAILED,
            progress: 100,
            createdat: time()
        ));
        service_factory::set_test_job_service($jobservice);

        $sink = $this->redirectEvents();
        $result = api::update_task($queueid, 'fail', 0, 'Remote API secret error', $jobid);

        $this->assertTrue($result['success']);
        $failed = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof queue_task_failed
        ));
        $this->assertCount(1, $failed);
        $this->assertEquals($queueid, (int) $failed[0]->objectid);
        $this->assertEquals((int) $course->id, (int) $failed[0]->courseid);
        $this->assertEquals((int) $user->id, (int) $failed[0]->userid);
        $this->assertSame($jobid, $failed[0]->other['jobid']);
        $this->assert_minimal_queue_other($failed[0]);
        $this->assertStringNotContainsString('Remote API secret', $failed[0]->get_description());
    }

    public function test_submit_generation_emits_queue_task_submitted(): void {
        [$course, $user] = $this->create_course_and_editor();

        $sink = $this->redirectEvents();
        $result = api::submit_generation((int) $course->id, 'page', 'Secret prompt text', 1);

        $this->assertTrue($result['success']);
        $started = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof queue_task_submitted
        ));
        $this->assertCount(1, $started);
        $this->assertEquals((int) $result['queueid'], (int) $started[0]->objectid);
        $this->assertEquals((int) $course->id, (int) $started[0]->courseid);
        $this->assertEquals((int) $user->id, (int) $started[0]->userid);
        $this->assert_minimal_queue_other($started[0]);
        $this->assertStringNotContainsString('Secret', $started[0]->get_description());
    }

    public function test_cancel_emits_queue_task_cancelled(): void {
        [$course] = $this->create_course_and_editor();

        $submit = api::submit_generation((int) $course->id, 'page', 'Prompt', 1);
        $this->assertTrue($submit['success']);

        $sink = $this->redirectEvents();
        $result = api::update_task((int) $submit['queueid'], 'cancel');

        $this->assertTrue($result['success']);
        $cancelled = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof queue_task_cancelled
        ));
        $this->assertCount(1, $cancelled);
        $this->assertEquals((int) $submit['queueid'], (int) $cancelled[0]->other['queueid']);
        $this->assert_minimal_queue_other($cancelled[0]);
    }

    public function test_delete_emits_queue_task_deleted(): void {
        [$course] = $this->create_course_and_editor();

        $submit = api::submit_generation((int) $course->id, 'page', 'Prompt', 1);
        $this->assertTrue($submit['success']);

        $sink = $this->redirectEvents();
        $result = api::delete_task((int) $submit['queueid']);

        $this->assertTrue($result['success']);
        $deleted = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof queue_task_deleted
        ));
        $this->assertCount(1, $deleted);
        $this->assertEquals((int) $submit['queueid'], (int) $deleted[0]->other['queueid']);
        $this->assert_minimal_queue_other($deleted[0]);
    }

    public function test_manual_upload_completed_emits_event(): void {
        [$course, $user] = $this->create_course_and_editor();

        $queueid = queue_service::log_manual_upload_completed(
            (int) $course->id,
            'resource',
            1,
            null,
            77,
            'Uploaded doc',
            'notes.pdf',
            (int) $user->id
        );
        $task = queue_repository::get_by_id($queueid);
        $this->assertNotFalse($task);

        $sink = $this->redirectEvents();
        manual_upload_completed::create_from_task($task, (int) $user->id, 77)->trigger();

        $logged = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof manual_upload_completed
        ));
        $this->assertCount(1, $logged);
        $this->assertSame(77, (int) $logged[0]->other['cmid']);
        $this->assert_minimal_queue_other($logged[0]);
        $this->assertStringNotContainsString('notes.pdf', $logged[0]->get_description());
    }

    public function test_fill_task_retried_emits_event_after_successful_retry(): void {
        global $DB;

        [$course, $user] = $this->create_course_and_editor();

        $record = queue_repository::create_base_record(
            (int) $course->id,
            'page',
            'Fill instructions secret',
            1,
            null,
            'en'
        );
        $record->title = 'Fill me';
        $record->status = queue_status::STATUS_FAILED;
        $record->jobid = 'old-fill-job';
        $record->timecompleted = time();
        $record->params = json_encode([
            'mode' => queue_task_mode::MODE_FILL,
            'title' => 'Fill me',
            'summary' => 'Secret summary',
            'dixeo_jobid' => 'old-fill-job',
            'error' => 'failed',
            'submittedby' => (int) $user->id,
        ]);
        $queueid = queue_repository::insert($record);
        $this->assertTrue(queue_service::start_fill_retry($queueid));

        $sink = $this->redirectEvents();
        $this->assertTrue(queue_service::complete_failed_fill_retry($queueid, 88, 'new-fill-job'));
        $updated = queue_repository::get_by_id($queueid);
        $this->assertNotFalse($updated);
        fill_task_retried::create_from_task($updated, (int) $user->id, 88)->trigger();

        $retried = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof fill_task_retried
        ));
        $this->assertCount(1, $retried);
        $this->assertSame(88, (int) $retried[0]->other['cmid']);
        $this->assert_minimal_queue_other($retried[0]);
        $this->assertStringNotContainsString('Secret', $retried[0]->get_description());

        $row = $DB->get_record(queue_repository::TABLE, ['id' => $queueid], '*', MUST_EXIST);
        $this->assertSame(queue_status::STATUS_COMPLETED, (int) $row->status);
    }

    public function test_create_module_for_task_emits_queue_task_completed(): void {
        [$course, $user] = $this->create_course_and_editor();
        $jobid = \core\uuid::generate();
        $queueid = $this->create_processing_generate_task($course, (int) $user->id, $jobid);
        $this->completed_job((int) $course->id, (int) $user->id, $jobid, 'page');

        $sink = $this->redirectEvents();
        $result = api::create_module_for_task($queueid);
        $this->resetDebugging();

        $this->assertTrue($result['success']);
        $completed = $this->events_of($sink, queue_task_completed::class);
        $this->assertCount(1, $completed);
        $this->assertEquals((int) $user->id, (int) $completed[0]->userid);
        $this->assertSame((int) $result['cmid'], (int) $completed[0]->other['cmid']);
        $this->assert_minimal_queue_other($completed[0]);
        $this->assert_no_params_text($completed[0], $queueid);
    }

    public function test_create_module_for_task_logs_a_type_mismatch(): void {
        [$course, $user] = $this->create_course_and_editor();
        $jobid = \core\uuid::generate();
        $queueid = $this->create_processing_generate_task($course, (int) $user->id, $jobid);
        $this->completed_job((int) $course->id, (int) $user->id, $jobid, 'quiz');

        $sink = $this->redirectEvents();
        $result = api::create_module_for_task($queueid);
        $this->resetDebugging();

        $this->assertFalse($result['success']);
        $failed = $this->events_of($sink, queue_task_failed::class);
        $this->assertCount(1, $failed);
        $this->assertSame(queue_task_failed::REASON_TYPE_MISMATCH, $failed[0]->other['reason']);
        $this->assert_minimal_queue_other($failed[0]);
        $this->assert_no_params_text($failed[0], $queueid);
    }

    public function test_processor_sync_failure_is_logged_for_the_submitter(): void {
        [$course, $user] = $this->create_course_and_editor();
        $record = queue_repository::create_base_record((int) $course->id, 'page', 'Secret prompt', null, null, 'en');
        $record->status = queue_status::STATUS_PENDING;
        $record->params = json_encode(['submittedby' => (int) $user->id]);
        $queueid = queue_repository::insert($record);
        $syncmock = $this->createMock(\local_dixeo\service\file_sync_service::class);
        $syncmock->method('ensure_enabled_and_synchronized')
            ->willThrowException(new \moodle_exception('filesync_timeout', 'local_dixeo'));
        service_factory::set_test_file_sync_service($syncmock);
        $this->setAdminUser();

        $sink = $this->redirectEvents();
        $this->assertNull(queue_service::process_next_pending((int) $course->id));

        $failed = $this->events_of($sink, queue_task_failed::class);
        $this->assertCount(1, $failed);
        $this->assertEquals((int) $user->id, (int) $failed[0]->userid);
        $this->assertSame(queue_task_failed::REASON_SYNC, $failed[0]->other['reason']);
        $this->assert_no_params_text($failed[0], $queueid);
    }

    public function test_expired_rows_are_logged_as_stale(): void {
        [$course, $user] = $this->create_course_and_editor();
        $queueid = $this->create_processing_generate_task($course, (int) $user->id, \core\uuid::generate());
        $this->setAdminUser();

        $sink = $this->redirectEvents();
        $this->assertSame(1, queue_service::expire_stale(time() + queue_service::STALE_PROCESSING_SECS + 1));

        $failed = $this->events_of($sink, queue_task_failed::class);
        $this->assertCount(1, $failed);
        $this->assertEquals((int) get_admin()->id, (int) $failed[0]->userid);
        $this->assertSame(queue_task_failed::REASON_STALE, $failed[0]->other['reason']);
        $this->assert_no_params_text($failed[0], $queueid);
    }

    /**
     * Events of one class captured by a sink.
     *
     * @param \phpunit_event_sink $sink Event sink.
     * @param string $class Event class.
     * @return \core\event\base[]
     */
    private function events_of(\phpunit_event_sink $sink, string $class): array {
        return array_values(array_filter($sink->get_events(), static fn($event) => $event instanceof $class));
    }

    /**
     * Assert no value of the event's other data repeats text stored in the row's params.
     *
     * @param \core\event\base $event Event.
     * @param int $queueid Queue row id.
     * @return void
     */
    private function assert_no_params_text(\core\event\base $event, int $queueid): void {
        $params = json_decode((string) queue_repository::get_by_id($queueid)->params, true) ?: [];
        foreach (['error', 'title', 'summary', 'filename'] as $key) {
            if (isset($params[$key]) && is_string($params[$key])) {
                $this->assertNotContains($params[$key], array_values($event->other));
            }
        }
        $this->assertStringNotContainsString('Secret', json_encode($event->other));
    }

    /**
     * Bind a completed job to the course; its result describes a module of the given type.
     *
     * @param int $courseid Course id.
     * @param int $userid Initiating user.
     * @param string $jobid Job id.
     * @param string $moduletype moduleType reported by the job.
     * @return void
     */
    private function completed_job(int $courseid, int $userid, string $jobid, string $moduletype): void {
        $repo = new \local_dixeo\repository\job_repository();
        $repo->register(
            $jobid,
            $courseid,
            $userid,
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
            status: job_status::STATUS_COMPLETED,
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
                'data' => ['name' => 'Generated page', 'intro' => 'Intro', 'content' => '<p>Body</p>'],
            ]
        ));
        service_factory::set_test_job_service(new job_service(null, $poller, $repo));
    }
}

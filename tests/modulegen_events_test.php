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
use block_dixeo_modulegen\event\queue_task_deleted;
use block_dixeo_modulegen\event\queue_task_submitted;
use block_dixeo_modulegen\external\api;

/**
 * Sensitive modulegen actions must emit audit events without instructions in other.
 *
 * @covers \block_dixeo_modulegen\event\queue_task_submitted
 * @covers \block_dixeo_modulegen\event\queue_task_cancelled
 * @covers \block_dixeo_modulegen\event\queue_task_deleted
 * @covers \block_dixeo_modulegen\event\fill_task_retried
 * @covers \block_dixeo_modulegen\event\manual_upload_completed
 * @covers \block_dixeo_modulegen\external\api::submit_generation
 * @covers \block_dixeo_modulegen\external\api::update_task
 * @covers \block_dixeo_modulegen\external\api::delete_task
 */
final class modulegen_events_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
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
}

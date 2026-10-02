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
 * Tests that module generation requires addinstance for the requested type.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

use block_dixeo_modulegen\external\api;
use local_dixeo\dto\job_status;
use local_dixeo\external\service_factory;
use local_dixeo\service\job_service;

/**
 * Queue and creation honour mod/<type>:addinstance and the requested type.
 *
 * @covers \block_dixeo_modulegen\external\api
 */
final class generation_addinstance_test extends \advanced_testcase {
    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    public function test_submit_rejects_a_type_the_user_cannot_add(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $roleid = $generator->create_role();
        $context = \context_course::instance($course->id);
        assign_capability('local/dixeo:generate', CAP_ALLOW, $roleid, $context->id, true);
        assign_capability('moodle/course:manageactivities', CAP_ALLOW, $roleid, $context->id, true);
        assign_capability('mod/page:addinstance', CAP_ALLOW, $roleid, $context->id, true);
        role_assign($roleid, $user->id, $context->id);
        $this->setUser($user);

        $denied = api::submit_generation((int) $course->id, 'quiz', 'Make a quiz');
        $this->assertFalse($denied['success']);
        $this->assertSame(0, $DB->count_records(queue_repository::TABLE, ['courseid' => $course->id]));

        $allowed = api::submit_generation((int) $course->id, 'page', 'Make a page');
        $this->assertTrue($allowed['success']);
        $this->assertSame(1, $DB->count_records(queue_repository::TABLE, ['courseid' => $course->id]));
    }

    public function test_create_rejects_a_job_whose_type_differs_from_the_queue(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_and_enrol($course, 'editingteacher');
        $this->setUser($user);

        $jobid = \core\uuid::generate();
        $record = queue_repository::create_base_record((int) $course->id, 'page', 'Prompt', 0, null, 'en');
        $record->status = queue_status::STATUS_PROCESSING;
        $record->jobid = $jobid;
        $record->timestarted = time();
        $record->params = json_encode(['jobid' => $jobid, 'submittedby' => $user->id]);
        $queueid = queue_repository::insert($record);

        $jobs = $this->createMock(job_service::class);
        $jobs->method('get_job_status')->willReturn(new job_status(
            jobid: $jobid,
            type: 'generate_module',
            status: job_status::STATUS_COMPLETED,
            progress: 100,
            createdat: time(),
            result: [
                'moduleType' => 'quiz',
                'creation' => [['action' => 'create_module']],
                'data' => ['name' => 'Unexpected quiz'],
            ],
        ));
        service_factory::set_test_job_service($jobs);

        $result = api::create_module_for_task($queueid);

        $this->assertFalse($result['success']);
        $task = queue_repository::get_by_id($queueid);
        $this->assertSame(queue_status::STATUS_FAILED, (int) $task->status);
        $quizid = $DB->get_field('modules', 'id', ['name' => 'quiz']);
        $this->assertFalse($DB->record_exists('course_modules', [
            'course' => $course->id,
            'module' => $quizid,
        ]));
    }
}

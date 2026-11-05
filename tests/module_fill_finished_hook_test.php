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

use block_dixeo_modulegen\local\hooks\module_fill_finished as listener;
use local_dixeo\hook\module_fill_finished;

/**
 * Fill outcomes reported through the local_dixeo hook become queue rows.
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_modulegen\local\hooks\module_fill_finished
 */
final class module_fill_finished_hook_test extends \advanced_testcase {
    /** @var \stdClass Course. */
    private \stdClass $course;

    /** @var \stdClass Teacher who ran the fill. */
    private \stdClass $teacher;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $this->teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setAdminUser();
    }

    public function test_dispatched_completed_fill_is_logged_for_the_hook_user(): void {
        $page = $this->getDataGenerator()->create_module('page', ['course' => $this->course->id, 'section' => 2]);
        $jobid = \core\uuid::generate();

        \core\di::get(\core\hook\manager::class)->dispatch($this->hook([
            'success' => true,
            'cmid' => (int) $page->cmid,
            'sectionnumber' => 1,
            'filljobid' => $jobid,
        ]));

        $row = $this->only_row();
        $params = json_decode($row->params, true);
        $this->assertSame(queue_status::STATUS_COMPLETED, (int) $row->status);
        $this->assertSame((int) $page->cmid, (int) $row->cmid);
        $this->assertSame(2, section_resolver::get_number((int) $this->course->id, (int) $row->sectionid));
        $this->assertSame($jobid, $row->jobid);
        $this->assertSame((int) $this->teacher->id, (int) $params['submittedby']);
        $this->assertTrue(queue_task_mode::is_fill($row->params));
    }

    public function test_failed_fill_stores_the_generic_error_for_the_hook_user(): void {
        listener::callback($this->hook(['success' => false, 'sectionnumber' => 1]));

        $row = $this->only_row();
        $params = json_decode($row->params, true);
        $this->assertSame(queue_status::STATUS_FAILED, (int) $row->status);
        $this->assertSame(get_string('generationfailed', 'block_dixeo_modulegen'), $params['error']);
        $this->assertSame((int) $this->teacher->id, (int) $params['submittedby']);
        $this->assertSame(section_resolver::get_id((int) $this->course->id, 1), (int) $row->sectionid);
    }

    public function test_invalid_payloads_write_nothing(): void {
        global $DB;

        $othercourse = $this->getDataGenerator()->create_course();
        $foreignpage = $this->getDataGenerator()->create_module('page', ['course' => $othercourse->id]);
        $deleted = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        delete_user($deleted);

        listener::callback($this->hook(['success' => true, 'cmid' => (int) $foreignpage->cmid]));
        listener::callback($this->hook(['courseid' => (int) $othercourse->id + 1000]));
        listener::callback($this->hook(['courseid' => SITEID]));
        listener::callback($this->hook(['userid' => (int) $deleted->id]));
        listener::callback($this->hook(['userid' => 0]));
        listener::callback($this->hook(['modulename' => '<>']));
        listener::callback($this->hook(['sectionnumber' => 99]));

        $this->assertSame(0, $DB->count_records(queue_repository::TABLE));
    }

    public function test_invalid_fill_job_id_and_foreign_insert_position_are_dropped(): void {
        $othercourse = $this->getDataGenerator()->create_course();
        $foreignpage = $this->getDataGenerator()->create_module('page', ['course' => $othercourse->id]);

        listener::callback($this->hook([
            'filljobid' => 'not-a-job-id',
            'beforemod' => (int) $foreignpage->cmid,
        ]));

        $row = $this->only_row();
        $this->assertNotSame('not-a-job-id', $row->jobid);
        $this->assertTrue(\local_dixeo\api\job_id::is_valid($row->jobid));
        $this->assertStringNotContainsString('not-a-job-id', $row->params);
        $this->assertEmpty($row->beforemod);
    }

    public function test_long_title_is_truncated(): void {
        listener::callback($this->hook(['displaytitle' => str_repeat('x', 300)]));

        $this->assertSame(255, \core_text::strlen($this->only_row()->title));
    }

    public function test_errors_while_logging_are_contained(): void {
        global $DB;

        listener::callback($this->hook(['modulename' => str_repeat('a', 150)]));

        $this->assertDebuggingCalled();
        $this->assertSame(0, $DB->count_records(queue_repository::TABLE));
    }

    /**
     * Hook for a failed fill in section 1 by the teacher, with values overridden.
     *
     * @param array $overrides Constructor values to replace.
     * @return module_fill_finished
     */
    private function hook(array $overrides = []): module_fill_finished {
        $values = array_merge([
            'courseid' => (int) $this->course->id,
            'userid' => (int) $this->teacher->id,
            'modulename' => 'page',
            'instructions' => 'Fill instructions',
            'sectionnumber' => 1,
            'beforemod' => null,
            'displaytitle' => 'Fill title',
            'summary' => 'Fill summary',
            'success' => false,
            'cmid' => 0,
            'filljobid' => '',
        ], $overrides);
        return new module_fill_finished(...$values);
    }

    /**
     * The single queue row of the course.
     *
     * @return \stdClass
     */
    private function only_row(): \stdClass {
        global $DB;

        $rows = $DB->get_records(queue_repository::TABLE, ['courseid' => $this->course->id]);
        $this->assertCount(1, $rows);
        return reset($rows);
    }
}

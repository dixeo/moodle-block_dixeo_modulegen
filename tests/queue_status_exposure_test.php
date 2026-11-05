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
use core_external\external_api;

/**
 * Error text and queue metadata kept on the server.
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_modulegen\queue_service::log_fill_failed
 * @covers     \block_dixeo_modulegen\queue_presenter
 * @covers     \block_dixeo_modulegen\external\api::get_queue_status
 */
final class queue_status_exposure_test extends \advanced_testcase {
    public function test_failed_fill_row_stores_the_generic_message(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $queueid = queue_service::log_fill_failed(
            (int) $course->id,
            'page',
            'Instructions',
            0,
            null,
            'Title',
            'Summary',
            '',
            'SQLSTATE secret'
        );

        $params = json_decode(queue_repository::get_by_id($queueid)->params, true);
        $this->assertSame(get_string('generationfailed', 'block_dixeo_modulegen'), $params['error']);
        $this->assertStringNotContainsString('SQLSTATE', queue_repository::get_by_id($queueid)->params);
    }

    public function test_queue_status_returns_no_params_or_description(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $queueid = queue_service::log_fill_failed(
            (int) $course->id,
            'page',
            'Instructions',
            0,
            null,
            'Title',
            'Summary',
            '',
            'SQLSTATE secret'
        );
        $DB->set_field(queue_repository::TABLE, 'description', 'Internal note', ['id' => $queueid]);

        $result = external_api::clean_returnvalue(
            api::get_queue_status_returns(),
            api::get_queue_status((int) $course->id)
        );

        $this->assertCount(1, $result['tasks']);
        $task = $result['tasks'][0];
        $this->assertArrayNotHasKey('params', $task);
        $this->assertArrayNotHasKey('description', $task);
        $this->assertSame($queueid, $task['id']);
        $this->assertSame(queue_task_mode::MODE_FILL, $task['queuemode']);
        $this->assertSame('Title', $task['displaytitle']);
        $this->assertStringNotContainsString('SQLSTATE', json_encode($result));
        $this->assertStringNotContainsString('submittedby', json_encode($result));
    }
}

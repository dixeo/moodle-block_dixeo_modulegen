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
 * Schedules background processing of the module generation queue.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

/**
 * Queues adhoc tasks to process pending modulegen jobs per course.
 */
class queue_processor {
    /**
     * Schedule background processing for a course queue.
     *
     * One adhoc task is queued per course: a task with the same course is not queued twice.
     * The task runs as the cron user and submits each row as the user who queued it.
     *
     * @param int $courseid The course ID.
     * @return void
     */
    public static function schedule(int $courseid): void {
        if ($courseid <= SITEID) {
            return;
        }

        $task = new task\process_modulegen_queue();
        $task->set_custom_data((object) ['courseid' => $courseid]);

        \core\task\manager::queue_adhoc_task($task, true);
    }
}

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
 * Adhoc task: ensure file sync then submit the next pending modulegen job.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen\task;

use block_dixeo_modulegen\queue_service;

/**
 * Processes one pending generate task for a course (sync then API submit).
 */
class process_modulegen_queue extends \core\task\adhoc_task {
    /**
     * Get the name of this task for admin UIs.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_process_modulegen_queue', 'block_dixeo_modulegen');
    }

    /**
     * Execute the adhoc task: sync files then submit the next pending job.
     *
     * @return void
     */
    public function execute(): void {
        $data = $this->get_custom_data();

        if (empty($data->courseid)) {
            mtrace('process_modulegen_queue: No course ID provided');
            return;
        }

        $courseid = (int) $data->courseid;
        $userid = isset($data->userid) ? (int) $data->userid : 0;

        mtrace("process_modulegen_queue: Processing queue for course {$courseid}");

        $result = queue_service::process_next_pending($courseid, $userid);

        if ($result === null) {
            mtrace("process_modulegen_queue: No pending task started for course {$courseid}");
            return;
        }

        mtrace("process_modulegen_queue: Started queue {$result['queueid']} job {$result['jobid']}");
    }
}

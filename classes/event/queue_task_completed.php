<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Event when a module generation queue task completes successfully.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen\event;

/**
 * Fired after a processing generate task is marked completed.
 */
class queue_task_completed extends queue_task_base {
    /**
     * Init method.
     */
    protected function init(): void {
        parent::init();
        $this->data['crud'] = 'u';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventqueuetaskcompleted', 'block_dixeo_modulegen');
    }

    /**
     * Non-localised description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        $jobid = clean_param((string) ($this->other['jobid'] ?? ''), PARAM_TEXT);
        return get_string('eventqueuetaskcompleteddesc', 'block_dixeo_modulegen', (object) [
            'userid' => $this->userid,
            'courseid' => $this->courseid,
            'queueid' => (int) ($this->other['queueid'] ?? 0),
            'modulename' => clean_param((string) ($this->other['modulename'] ?? ''), PARAM_ALPHA),
            'jobid' => $jobid !== '' ? $jobid : '-',
            'cmid' => (int) ($this->other['cmid'] ?? 0),
        ]);
    }

    /**
     * Create an event for a completed queue task.
     *
     * @param object $task Queue row.
     * @param int $userid User who completed the task.
     * @param int $cmid Created course module id.
     * @return self
     */
    public static function create_from_task(object $task, int $userid, int $cmid): self {
        $jobid = trim((string) ($task->jobid ?? ''));
        return self::create(self::build_queue_task_data($task, $userid, [
            'jobid' => $jobid !== '' ? $jobid : null,
            'cmid' => $cmid,
        ]));
    }
}

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
 * Event when a module generation queue task is deleted.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen\event;

/**
 * Fired after a queue row is removed from the database.
 */
class queue_task_deleted extends queue_task_base {
    /**
     * Init method.
     */
    protected function init(): void {
        parent::init();
        $this->data['crud'] = 'd';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventqueuetaskdeleted', 'block_dixeo_modulegen');
    }

    /**
     * Non-localised description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('eventqueuetaskdeleteddesc', 'block_dixeo_modulegen', (object) [
            'userid' => $this->userid,
            'courseid' => $this->courseid,
            'queueid' => (int) ($this->other['queueid'] ?? 0),
            'modulename' => clean_param((string) ($this->other['modulename'] ?? ''), PARAM_ALPHA),
        ]);
    }

    /**
     * Create an event for a deleted queue task.
     *
     * @param object $task Queue row before deletion.
     * @param int $userid User who deleted the task.
     * @return self
     */
    public static function create_from_task(object $task, int $userid): self {
        return self::create(self::build_queue_task_data($task, $userid));
    }
}

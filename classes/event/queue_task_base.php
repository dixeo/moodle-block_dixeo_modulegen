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
 * Base event for modulegen queue audit records.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen\event;

use block_dixeo_modulegen\queue_repository;

/**
 * Shared helpers for queue-scoped Moodle events.
 *
 * Payload is limited to queue identifiers — no instructions or file content.
 */
abstract class queue_task_base extends \core\event\base {
    /**
     * Init method.
     */
    protected function init(): void {
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = queue_repository::TABLE;
    }

    /**
     * Relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/course/view.php', ['id' => $this->courseid]);
    }

    /**
     * Build event data for a queue row.
     *
     * @param object $task Queue row with id, courseid, modulename.
     * @param int $userid Acting user id.
     * @param array $extraother Optional extra other fields (jobid, cmid).
     * @return array Event data array for self::create().
     */
    protected static function build_queue_task_data(object $task, int $userid, array $extraother = []): array {
        $other = [
            'queueid' => (int) $task->id,
            'modulename' => clean_param((string) ($task->modulename ?? ''), PARAM_ALPHA),
        ];

        foreach ($extraother as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $other[$key] = $value;
        }

        return [
            'context' => \context_course::instance((int) $task->courseid),
            'objectid' => (int) $task->id,
            'userid' => $userid,
            'courseid' => (int) $task->courseid,
            'other' => $other,
        ];
    }

    /**
     * Custom validation.
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (empty($this->other['queueid'])) {
            throw new \coding_exception('The \'queueid\' value must be set in other.');
        }
    }

    /**
     * Object id mapping for backup/restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return ['db' => queue_repository::TABLE, 'restore' => \core\event\base::NOT_MAPPED];
    }

    /**
     * Other mapping for backup/restore.
     *
     * @return false
     */
    public static function get_other_mapping() {
        return false;
    }
}

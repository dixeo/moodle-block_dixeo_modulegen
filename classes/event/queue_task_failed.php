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
 * Event when a module generation queue task fails.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen\event;

/**
 * Fired after a queue task is marked failed.
 *
 * The optional reason in other['reason'] is one of the REASON_* codes; no error text is stored.
 */
class queue_task_failed extends queue_task_base {
    /** @var string The completed job describes another activity type than the one requested. */
    public const REASON_TYPE_MISMATCH = 'type_mismatch';

    /** @var string The task did not finish in time. */
    public const REASON_STALE = 'stale';

    /** @var string The submitter may no longer generate this activity, or Dixeo is off. */
    public const REASON_NOT_ALLOWED = 'not_allowed';

    /** @var string Course file sync failed before submission. */
    public const REASON_SYNC = 'sync_failed';

    /** @var string Submitting the job to Dixeo, or waiting for its result, failed. */
    public const REASON_SUBMIT = 'submit_failed';

    /** @var string The activity could not be created from the job result. */
    public const REASON_CREATE = 'create_failed';
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
        return get_string('eventqueuetaskfailed', 'block_dixeo_modulegen');
    }

    /**
     * Non-localised description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        $jobid = clean_param((string) ($this->other['jobid'] ?? ''), PARAM_TEXT);
        return get_string('eventqueuetaskfaileddesc', 'block_dixeo_modulegen', (object) [
            'userid' => $this->userid,
            'courseid' => $this->courseid,
            'queueid' => (int) ($this->other['queueid'] ?? 0),
            'modulename' => clean_param((string) ($this->other['modulename'] ?? ''), PARAM_ALPHA),
            'jobid' => $jobid !== '' ? $jobid : '-',
        ]);
    }

    /**
     * Create an event for a failed queue task.
     *
     * @param object $task Queue row.
     * @param int $userid User who reported the failure, or for whom the task ran.
     * @param string|null $reason One of the REASON_* codes, or null when the client reported the failure.
     * @return self
     */
    public static function create_from_task(object $task, int $userid, ?string $reason = null): self {
        $jobid = trim((string) ($task->jobid ?? ''));
        return self::create(self::build_queue_task_data($task, $userid, [
            'jobid' => $jobid !== '' ? $jobid : null,
            'reason' => $reason,
        ]));
    }
}

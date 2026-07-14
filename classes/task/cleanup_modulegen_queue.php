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
 * Scheduled task: purge terminal modulegen queue rows older than the retention period.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen\task;

use block_dixeo_modulegen\queue_repository;

/**
 * Deletes completed, failed, and cancelled queue rows past the retention window.
 */
class cleanup_modulegen_queue extends \core\task\scheduled_task {

    /** @var int Retention period for terminal queue rows (days). */
    public const RETENTION_DAYS = 90;

    /**
     * Get the name of this task for admin UIs.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanup_modulegen_queue', 'block_dixeo_modulegen');
    }

    /**
     * Delete terminal queue rows older than {@see self::RETENTION_DAYS}.
     *
     * @return void
     */
    public function execute(): void {
        $cutoff = time() - (DAYSECS * self::RETENTION_DAYS);
        $deleted = queue_repository::delete_terminal_older_than($cutoff);

        if ($deleted > 0) {
            mtrace("[block_dixeo_modulegen] Deleted {$deleted} terminal queue row(s) older than "
                . self::RETENTION_DAYS . ' days.');
        }
    }
}

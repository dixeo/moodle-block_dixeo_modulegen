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

namespace block_dixeo_modulegen\local\hooks;

use block_dixeo_modulegen\queue_service;
use block_dixeo_modulegen\section_resolver;
use local_dixeo\api\job_id;
use local_dixeo\hook\module_fill_finished as fill_finished_hook;

/**
 * Records Dixeo activity fills run by other plugins as rows of the generation queue.
 *
 * Any plugin can dispatch the hook, so the payload is checked before a row is written: the
 * course must exist, the user must be a real active account, a completed fill must point to an
 * activity of that course, and a failed fill must target a section of that course. A payload
 * that fails a check writes nothing. The callback never throws, makes no remote call and
 * triggers no event.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_fill_finished {
    /**
     * Log the fill outcome in the course generation queue.
     *
     * @param fill_finished_hook $hook Fill outcome.
     * @return void
     */
    public static function callback(fill_finished_hook $hook): void {
        try {
            self::record($hook);
        } catch (\Throwable $e) {
            debugging(
                'The Dixeo activity fill could not be logged in the generation queue: ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }

    /**
     * Validate the payload and write the completed or failed fill row.
     *
     * @param fill_finished_hook $hook Fill outcome.
     * @return void
     */
    private static function record(fill_finished_hook $hook): void {
        global $DB;

        $courseid = $hook->courseid;
        if ($courseid <= SITEID || !$DB->record_exists('course', ['id' => $courseid])) {
            return;
        }

        $user = \core_user::get_user($hook->userid);
        if (!$user || !empty($user->deleted) || !\core_user::is_real_user((int) $user->id)) {
            return;
        }

        $modulename = clean_param($hook->modulename, PARAM_ALPHANUMEXT);
        if ($modulename === '') {
            return;
        }

        $filljobid = job_id::is_valid($hook->filljobid) ? $hook->filljobid : '';
        $beforemod = self::course_cmid($courseid, (int) $hook->beforemod) ?: null;

        if ($hook->success) {
            $cm = get_coursemodule_from_id('', $hook->cmid, $courseid, false, IGNORE_MISSING);
            if (!$cm) {
                return;
            }
            queue_service::log_fill_completed(
                $courseid,
                $modulename,
                $hook->instructions,
                section_resolver::get_number($courseid, (int) $cm->section),
                $beforemod,
                (int) $cm->id,
                $hook->displaytitle,
                $hook->summary,
                $filljobid,
                (int) $user->id
            );
            return;
        }

        if (section_resolver::get_id($courseid, $hook->sectionnumber) <= 0) {
            return;
        }
        queue_service::log_fill_failed(
            $courseid,
            $modulename,
            $hook->instructions,
            $hook->sectionnumber,
            $beforemod,
            $hook->displaytitle,
            $hook->summary,
            $filljobid,
            '',
            (int) $user->id
        );
    }

    /**
     * Id of a course module when it belongs to the course.
     *
     * @param int $courseid Course id.
     * @param int $cmid Course module id, 0 for none.
     * @return int The course module id, or 0 when it is missing or in another course.
     */
    private static function course_cmid(int $courseid, int $cmid): int {
        if ($cmid <= 0) {
            return 0;
        }
        $cm = get_coursemodule_from_id('', $cmid, $courseid, false, IGNORE_MISSING);
        return $cm ? (int) $cm->id : 0;
    }
}

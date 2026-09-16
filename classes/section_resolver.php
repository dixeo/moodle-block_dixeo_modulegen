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
 * Translations between course section ids and section numbers.
 *
 * The queue stores the section id, which survives insertions and moves; the number
 * is only resolved when a module is actually placed, so a course reordered between
 * submission and creation still receives the module in the requested section.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

/**
 * Section id and section number lookups for a course.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_resolver {
    /**
     * Section number for a section id, or 0 when the section is gone or belongs to another course.
     *
     * @param int $courseid The course ID.
     * @param int|null $sectionid The course section ID.
     * @return int The section number.
     */
    public static function get_number(int $courseid, ?int $sectionid): int {
        global $DB;

        if (empty($sectionid)) {
            return 0;
        }

        $number = $DB->get_field('course_sections', 'section', [
            'id' => $sectionid,
            'course' => $courseid,
        ]);

        return $number === false ? 0 : (int) $number;
    }

    /**
     * Section id for a section number, or 0 when the course has no such section.
     *
     * @param int $courseid The course ID.
     * @param int $sectionnumber The section number.
     * @return int The course section ID.
     */
    public static function get_id(int $courseid, int $sectionnumber): int {
        global $DB;

        $id = $DB->get_field('course_sections', 'id', [
            'course' => $courseid,
            'section' => $sectionnumber,
        ]);

        return $id === false ? 0 : (int) $id;
    }
}

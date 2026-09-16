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
 * Tests for section targeting of queued generations.
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Tests that queued generations follow their section when the course is reordered.
 *
 * @covers \block_dixeo_modulegen\section_resolver
 * @covers \block_dixeo_modulegen\queue_service::submit
 */
final class section_targeting_test extends advanced_testcase {
    /**
     * Section id of a given section number in a course.
     *
     * @param int $courseid The course ID.
     * @param int $sectionnumber The section number.
     * @return int The course section ID.
     */
    private function section_id(int $courseid, int $sectionnumber): int {
        global $DB;

        return (int) $DB->get_field('course_sections', 'id', [
            'course' => $courseid,
            'section' => $sectionnumber,
        ], MUST_EXIST);
    }

    public function test_get_number_resolves_and_falls_back_to_zero(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);
        $othercourse = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $sectionid = $this->section_id((int) $course->id, 2);

        $this->assertSame(2, section_resolver::get_number((int) $course->id, $sectionid));
        $this->assertSame($sectionid, section_resolver::get_id((int) $course->id, 2));

        // A section of another course, a deleted section and no section at all all mean section 0.
        $this->assertSame(0, section_resolver::get_number((int) $othercourse->id, $sectionid));
        $this->assertSame(0, section_resolver::get_number((int) $course->id, null));
        $DB->delete_records('course_sections', ['id' => $sectionid]);
        $this->assertSame(0, section_resolver::get_number((int) $course->id, $sectionid));
    }

    public function test_queued_generation_follows_its_section_when_a_section_is_inserted(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);
        $sectionid = $this->section_id((int) $course->id, 2);

        $result = queue_service::submit((int) $course->id, 'page', 'Generate a page', $sectionid);
        $row = $DB->get_record(queue_repository::TABLE, ['id' => $result['queueid']], '*', MUST_EXIST);
        $this->assertSame($sectionid, (int) $row->sectionid);

        // The course format inserts a section before the target: its number shifts, its id does not.
        course_create_section($course, 1);

        $this->assertSame(3, section_resolver::get_number((int) $course->id, (int) $row->sectionid));
    }
}

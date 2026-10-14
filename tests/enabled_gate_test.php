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
 * Module generator visibility when Dixeo is off.
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

use block_dixeo_modulegen\local\hooks\output\before_http_headers;

/**
 * The module generator block and its card script stay out when Dixeo is off.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_modulegen::get_content
 * @covers     \block_dixeo_modulegen\local\hooks\output\before_http_headers::callback
 */
final class enabled_gate_test extends \advanced_testcase {
    /**
     * Course off empties the block and skips the card script.
     */
    public function test_module_generator_is_empty_when_the_course_is_off(): void {
        global $COURSE, $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $COURSE = $course;
        $block = $this->load_block($course);
        $config = \local_dixeo\external\service_factory::get_settings_config();

        $shown = $block->get_content();
        $this->assertNotNull($shown);
        $this->assertNotSame('', $shown->text);
        $this->assertStringContainsString('block_dixeo_modulegen/activitychooser', $this->hook_end_code($course));

        $config->set('local_dixeo', 'enabled', \context_course::instance($course->id), '0');
        $block->content = null;
        $this->assertNull($block->get_content());
        $this->assertSame('', $block->title);
        $this->assertStringNotContainsString('dixeo-block-disabled', $block->html_attributes()['class']);
        $this->assertSame(get_string('disabled_block_course', 'local_dixeo'), $this->editing_text($block));
        $this->assertSame(get_string('pluginname', 'block_dixeo_modulegen'), $block->title);
        $this->assertStringContainsString('dixeo-block-disabled', $this->editing_classes($block));
        $PAGE = $this->course_page($course);
        $this->assertStringNotContainsString(
            'block_dixeo_modulegen/activitychooser',
            $this->hook_end_code($course)
        );
    }

    /**
     * Dixeo off empties the generator even when the course stores On.
     */
    public function test_module_generator_is_empty_when_dixeo_is_off(): void {
        global $COURSE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $COURSE = $course;
        $block = $this->load_block($course);
        $config = \local_dixeo\external\service_factory::get_settings_config();
        $config->set('local_dixeo', 'enabled', \context_system::instance(), '0');
        $config->set('local_dixeo', 'enabled', \context_course::instance($course->id), '1');

        $this->assertNull($block->get_content());
        $this->assertSame('', $block->title);
        $this->assertStringNotContainsString('dixeo-block-disabled', $block->html_attributes()['class']);
        $this->assertSame(get_string('disabled_block_site', 'local_dixeo'), $this->editing_text($block));
        $this->assertSame(get_string('pluginname', 'block_dixeo_modulegen'), $block->title);
        $this->assertStringContainsString('dixeo-block-disabled', $this->editing_classes($block));
        $this->assertStringNotContainsString(
            'block_dixeo_modulegen/activitychooser',
            $this->hook_end_code($course)
        );
    }

    /**
     * Block text while the current user is editing the page.
     *
     * @param \block_base $block
     * @return string
     */
    private function editing_text(\block_base $block): string {
        global $USER;
        $USER->editing = 1;
        $block->content = null;
        $text = (string) $block->get_content()->text;
        $USER->editing = 0;
        $block->content = null;
        return $text;
    }

    /**
     * Block classes while the current user is editing the page.
     *
     * @param \block_base $block
     * @return string
     */
    private function editing_classes(\block_base $block): string {
        global $USER;
        $USER->editing = 1;
        $classes = $block->html_attributes()['class'];
        $USER->editing = 0;
        return $classes;
    }

    /**
     * Load the module generator block on a course page.
     *
     * @param \stdClass $course
     * @return \block_dixeo_modulegen
     */
    private function load_block(\stdClass $course): \block_dixeo_modulegen {
        global $PAGE;

        $this->getDataGenerator()->create_block('dixeo_modulegen', [
            'parentcontextid' => \context_course::instance($course->id)->id,
            'showinsubcontexts' => 1,
            'pagetypepattern' => 'course-view-*',
        ]);

        $page = $this->course_page($course);
        $page->blocks->load_blocks();
        $PAGE = $page;
        $this->use_page_output($page);
        foreach ($page->blocks->get_regions() as $region) {
            foreach ($page->blocks->get_blocks_for_region($region) as $block) {
                if ($block instanceof \block_dixeo_modulegen) {
                    return $block;
                }
            }
        }
        $this->fail('Module generator block was not loaded on the course page');
    }

    /**
     * Point global output at the page renderer.
     *
     * @param \moodle_page $page
     * @return void
     */
    private function use_page_output(\moodle_page $page): void {
        global $OUTPUT, $PAGE;
        $PAGE = $page;
        $page->initialise_theme_and_output();
        $OUTPUT = $page->get_renderer('core');
    }

    /**
     * Footer hook output for the course page.
     *
     * @param \stdClass $course
     * @return string
     */
    private function hook_end_code(\stdClass $course): string {
        global $PAGE;

        if ((int) $PAGE->course->id !== (int) $course->id) {
            $PAGE = $this->course_page($course);
        }
        $hook = new \core\hook\output\before_http_headers($PAGE->get_renderer('core'));
        before_http_headers::callback($hook);
        return $PAGE->requires->get_end_code();
    }

    /**
     * Course page the block can render on.
     *
     * @param \stdClass $course
     * @return \moodle_page
     */
    private function course_page(\stdClass $course): \moodle_page {
        $page = new \moodle_page();
        $page->set_context(\context_course::instance($course->id));
        $page->set_course($course);
        $page->set_pagelayout('course');
        $page->set_pagetype('course-view-topics');
        $page->set_url(new \moodle_url('/course/view.php', ['id' => $course->id]));
        return $page;
    }
}

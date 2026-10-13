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
 * Module Generator block definition.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Block class for the Dixeo Module Generator.
 *
 * Displays an AI module generation interface in courses.
 * Provides functionality for generating course modules using AI.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_dixeo_modulegen extends block_base {
    /** @var string Required capability to view the block. */
    private const REQUIRED_CAPABILITY = 'local/dixeo:generate';

    /** @var string Additional capability required to manage course activities. */
    private const MANAGE_ACTIVITIES_CAPABILITY = 'moodle/course:manageactivities';

    /** @var string Default pagetype pattern for block instances. */
    private const DEFAULT_PAGETYPE_PATTERN = 'course-view-*';

    /** @var int Default weight for block instances. */
    private const DEFAULT_WEIGHT = -5;

    /**
     * Initialize the block.
     *
     * Sets the block title from language strings.
     */
    public function init(): void {
        $this->title = get_string('blocktitle', 'block_dixeo_modulegen');
    }

    /**
     * Define where the block can be displayed.
     *
     * @return array Applicable formats.
     */
    public function applicable_formats(): array {
        return [
            'course-view' => true,
            'site' => false,
            'mod' => false,
            'my' => false,
        ];
    }

    /**
     * Block has global configuration.
     *
     * @return bool True as block has settings.
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Specialization callback.
     */
    public function specialization(): void {
        $this->title = '';
    }

    /**
     * Only one instance per course.
     *
     * @return bool False to prevent multiple instances.
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Get block content.
     *
     * Renders the block content if the user has the required capability
     * and is on an allowed page. Initializes JavaScript modules and renders
     * the card template.
     *
     * @return stdClass|null The block content object, or null if not accessible.
     */
    public function get_content(): ?stdClass {
        global $OUTPUT, $COURSE, $CFG;

        require_once($CFG->libdir . '/filelib.php');

        // Return cached content to avoid re-rendering on every call (Moodle may call get_content multiple times).
        if ($this->content !== null) {
            return $this->content;
        }

        // Dixeo off leaves the block empty, except in editing mode, where it explains why.
        if (!$this->dixeo_is_enabled()) {
            if (!$this->page->user_is_editing()) {
                return $this->content;
            }
            $this->content = new stdClass();
            $this->content->text = $this->disabled_block_text();
            $this->content->footer = '';
            return $this->content;
        }

        // Check user capability.
        if (!$this->can_user_view_block($COURSE->id)) {
            return $this->content;
        }

        // Check if on allowed page.
        if (!$this->is_allowed_page()) {
            return $this->content;
        }

        // Initialize content structure.
        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = '';

        // CSS/AMD are registered early via before_http_headers hook.
        $this->content->text = $OUTPUT->render_from_template('block_dixeo_modulegen/card', []);

        return $this->content;
    }

    /**
     * Whether Dixeo features run on this block's page.
     *
     * A page inside a course uses that course. Any other page uses the Dixeo layer.
     *
     * @return bool
     */
    private function dixeo_is_enabled(): bool {
        return \local_dixeo\external\service_factory::get_settings_config()->enabled_for($this->dixeo_page_context());
    }

    /**
     * Editing-mode explanation for why this block is hidden.
     *
     * @return string
     */
    private function disabled_block_text(): string {
        return \local_dixeo\external\service_factory::get_settings_config()->disabled_block_text($this->dixeo_page_context());
    }

    /**
     * Course context for a page inside a course, otherwise the Dixeo layer.
     *
     * @return \context
     */
    private function dixeo_page_context(): \context {
        $courseid = (int) ($this->page->course->id ?? 0);
        return $courseid > SITEID
            ? \context_course::instance($courseid)
            : \context_system::instance();
    }

    /**
     * Check if the current user can view the block.
     *
     * @param int $courseid The course ID.
     * @return bool True if user has required capability.
     */
    private function can_user_view_block(int $courseid): bool {
        $context = context_course::instance($courseid);
        return has_capability(self::REQUIRED_CAPABILITY, $context)
            && has_capability(self::MANAGE_ACTIVITIES_CAPABILITY, $context);
    }

    /**
     * Check if the current page is an allowed page for the block.
     *
     * @return bool True on a course view page (core or format-declared).
     */
    private function is_allowed_page(): bool {
        return \block_dixeo_modulegen\local\page_assets::is_course_view_page($this->page);
    }

    /**
     * Handle block instance creation.
     *
     * Configures the block instance to show in subcontexts and sets
     * default pagetype pattern and weight for optimal display.
     *
     * @return bool True on success, false on failure.
     */
    public function instance_create(): bool {
        global $DB;

        $bi = $DB->get_record('block_instances', ['id' => $this->instance->id]);
        if (!$bi) {
            return false;
        }

        $bi->showinsubcontexts = 1;
        $bi->pagetypepattern = self::DEFAULT_PAGETYPE_PATTERN;
        $bi->defaultweight = self::DEFAULT_WEIGHT;

        return $DB->update_record('block_instances', $bi);
    }
}

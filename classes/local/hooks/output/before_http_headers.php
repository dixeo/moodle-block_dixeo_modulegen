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
 * Hook callbacks for block_dixeo_modulegen page asset registration.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen\local\hooks\output;

use block_dixeo_modulegen\local\page_assets;

/**
 * Register block CSS/AMD before HTTP headers are sent.
 */
class before_http_headers {

    /**
     * Register page assets for the module generator when applicable.
     *
     * @param \core\hook\output\before_http_headers $hook Hook instance.
     * @return void
     */
    public static function callback(\core\hook\output\before_http_headers $hook): void {
        global $PAGE, $CFG;

        if (during_initial_install() || isset($CFG->upgraderunning)) {
            return;
        }

        page_assets::require_for_page($PAGE);
    }
}

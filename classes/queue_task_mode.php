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
 * Queue task mode (generate vs fill) stored in task params JSON.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

/**
 * Mode constants for {@see queue_repository} params JSON key `mode`.
 */
final class queue_task_mode {

    /** @var string AI generate-mode queue task. */
    public const MODE_GENERATE = 'generate';

    /** @var string AI fill-mode queue task. */
    public const MODE_FILL = 'fill';

    /** @var string Manual upload queue task. */
    public const MODE_MANUAL = 'manual';

    /**
     * Resolve the task mode from a params JSON string.
     *
     * @param string|null $paramsjson Task params JSON, or null.
     * @return string One of MODE_GENERATE, MODE_FILL, or MODE_MANUAL.
     */
    public static function from_params(?string $paramsjson): string {
        if ($paramsjson === null || $paramsjson === '') {
            return self::MODE_GENERATE;
        }
        $p = json_decode($paramsjson, true);
        if (!is_array($p) || empty($p['mode'])) {
            return self::MODE_GENERATE;
        }
        if ($p['mode'] === self::MODE_FILL) {
            return self::MODE_FILL;
        }
        if ($p['mode'] === self::MODE_MANUAL) {
            return self::MODE_MANUAL;
        }
        return self::MODE_GENERATE;
    }

    /**
     * Whether the params JSON represents a fill-mode task.
     *
     * @param string|null $paramsjson Task params JSON, or null.
     * @return bool
     */
    public static function is_fill(?string $paramsjson): bool {
        return self::from_params($paramsjson) === self::MODE_FILL;
    }

    /**
     * Whether the params JSON represents a manual-upload task.
     *
     * @param string|null $paramsjson Task params JSON, or null.
     * @return bool
     */
    public static function is_manual(?string $paramsjson): bool {
        return self::from_params($paramsjson) === self::MODE_MANUAL;
    }
}

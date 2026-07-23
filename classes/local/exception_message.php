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
 * Format exception messages for clients and persisted queue rows.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen\local;

use local_dixeo\api\exception\api_exception;

/**
 * Formats throwables into safe localized messages; logs technical detail for developers.
 */
class exception_message {
    /**
     * Format a message safe to return to an AJAX/WS client.
     *
     * Preserves Moodle-localized exceptions (capability, validation, etc.).
     * Replaces Dixeo API and unexpected exceptions with a generic string.
     *
     * @param \Throwable $e Caught exception.
     * @param string $fallbackkey Lang string key in block_dixeo_modulegen.
     * @return string
     */
    public static function format_for_client(\Throwable $e, string $fallbackkey = 'error_unexpected'): string {
        if (self::should_log_exception($e)) {
            debugging(get_class($e) . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        if ($e instanceof \moodle_exception && !($e instanceof api_exception)) {
            return $e->getMessage();
        }

        return get_string($fallbackkey, 'block_dixeo_modulegen');
    }

    /**
     * Format a short message safe to persist on a queue row (params.error).
     *
     * @param \Throwable $e Caught exception.
     * @param string $fallbackkey Lang string key in block_dixeo_modulegen.
     * @return string
     */
    public static function format_for_queue(\Throwable $e, string $fallbackkey = 'generationfailed'): string {
        if (self::should_log_exception($e)) {
            debugging(get_class($e) . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        if ($e instanceof \moodle_exception && !($e instanceof api_exception)) {
            return clean_param($e->getMessage(), PARAM_TEXT);
        }

        return get_string($fallbackkey, 'block_dixeo_modulegen');
    }

    /**
     * Whether an exception should emit developer debugging output.
     *
     * Expected API and Moodle operational failures are surfaced to users/queue rows
     * without polluting cron logs.
     *
     * @param \Throwable $e Caught exception.
     * @return bool
     */
    private static function should_log_exception(\Throwable $e): bool {
        return !($e instanceof \moodle_exception);
    }
}

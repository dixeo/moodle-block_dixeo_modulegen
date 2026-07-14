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
 * Unified external API for the Dixeo Module Generator block.
 *
 * Consolidates all web service functions:
 * - submit_generation: Queue a new module generation
 * - get_queue_status: Get tasks and stats for a course
 * - update_task: Complete, fail, or cancel a task
 * - retry_fill_task: Retry a failed fill-mode queue row
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use block_dixeo_modulegen\queue_service;
use block_dixeo_modulegen\queue_repository;
use block_dixeo_modulegen\queue_presenter;
use block_dixeo_modulegen\queue_status;
use block_dixeo_modulegen\queue_task_mode;
use block_dixeo_modulegen\local\exception_message;
use local_dixeo\api\exception\api_exception;
use local_dixeo\external\create_module_from_job;
use local_dixeo\external\service_factory;

/**
 * Unified external API class for module generation.
 *
 * Provides all web service functions for the block:
 * - submit_generation: Queue a new module generation request
 * - get_queue_status: Get tasks and statistics for a course
 * - update_task: Complete, fail, or cancel a task
 *
 * All methods include proper parameter validation, capability checks,
 * and standardized error handling.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api extends external_api {
    /**
     * Validate course access and capabilities.
     *
     * Ensures the user is logged into the course and has manageactivities capability.
     * Sets PAGE context for security and correct output during the request.
     *
     * @param int $courseid The course ID.
     * @return \context_course The course context.
     * @throws \required_capability_exception If user lacks capability.
     */
    private static function validate_course_access(int $courseid): \context_course {
        global $PAGE;
        require_course_login($courseid);
        $context = \context_course::instance($courseid);
        self::validate_context($context);
        $PAGE->set_context($context);
        require_capability('local/dixeo:generate', $context);
        require_capability('moodle/course:manageactivities', $context);
        return $context;
    }

    /**
     * Create a standardized error response for submit_generation.
     *
     * Matches the structure defined in submit_generation_returns().
     *
     * @param string $code Error code for programmatic handling.
     * @param string $message Human-readable error message.
     * @return array Standardized error response structure.
     */
    private static function create_error_response(string $code, string $message): array {
        return [
            'success' => false,
            'queueid' => 0,
            'jobid' => '',
            'status' => 'error',
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    /**
     * Create a standardized error response for update_task.
     *
     * Matches the structure defined in update_task_returns().
     *
     * @param string $message Human-readable error message.
     * @return array Standardized error response structure.
     */
    private static function create_update_error_response(string $message): array {
        return [
            'success' => false,
            'message' => $message,
        ];
    }

    // Submit generation: queue a new module generation request.

    /**
     * Parameters for submit_generation.
     *
     * @return external_function_parameters
     */
    public static function submit_generation_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'modulename' => new external_value(PARAM_TEXT, 'Module type to generate'),
            'instructions' => new external_value(PARAM_RAW, 'Instructions for the AI'),
            'sectionnumber' => new external_value(PARAM_INT, 'Section number', VALUE_DEFAULT, 0),
            'beforemod' => new external_value(PARAM_INT, 'Insert before this module ID', VALUE_DEFAULT, 0),
            'lang' => new external_value(PARAM_TEXT, 'Language code', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Submit a module generation request.
     *
     * Inserts a PENDING queue row and schedules background processing (file sync then API submit).
     * Returns immediately with status queued; jobid is empty until the processor runs.
     *
     * @param int $courseid The course ID.
     * @param string $modulename The module type to generate.
     * @param string $instructions Instructions for the AI.
     * @param int|null $sectionnumber Section number to add module to.
     * @param int|null $beforemod Course module ID to insert before.
     * @param string|null $lang Language code for content.
     * @return array Result with queue_id, empty job_id, and status queued.
     */
    public static function submit_generation(
        int $courseid,
        string $modulename,
        string $instructions,
        ?int $sectionnumber = 0,
        ?int $beforemod = 0,
        ?string $lang = null
    ): array {
        // Validate parameters.
        $params = self::validate_parameters(self::submit_generation_parameters(), [
            'courseid' => $courseid,
            'modulename' => $modulename,
            'instructions' => $instructions,
            'sectionnumber' => $sectionnumber,
            'beforemod' => $beforemod,
            'lang' => $lang,
        ]);

        self::validate_course_access($params['courseid']);

        try {
            $result = queue_service::submit(
                $params['courseid'],
                $params['modulename'],
                $params['instructions'],
                $params['sectionnumber'] ?: null,
                $params['beforemod'] ?: null,
                $params['lang']
            );

            return [
                'success' => true,
                'queueid' => $result['queueid'],
                'jobid' => $result['jobid'] ?? '',
                'status' => $result['status'],
            ];
        } catch (api_exception $e) {
            return self::create_error_response(
                $e->get_error_code(),
                exception_message::format_for_client($e, 'error_queue_failed')
            );
        } catch (\Throwable $e) {
            return self::create_error_response(
                'submission_failed',
                exception_message::format_for_client($e, 'error_queue_failed')
            );
        }
    }

    /**
     * Return values for submit_generation.
     *
     * @return external_single_structure
     */
    public static function submit_generation_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether submission succeeded'),
            'queueid' => new external_value(PARAM_INT, 'Queue record ID'),
            'jobid' => new external_value(PARAM_RAW, 'Dixeo job UUID (empty if queued)', VALUE_DEFAULT, ''),
            'status' => new external_value(PARAM_TEXT, 'Status: queued on success, or error'),
            'error' => new external_single_structure([
                'code' => new external_value(PARAM_TEXT, 'Error code'),
                'message' => new external_value(PARAM_TEXT, 'Error message'),
            ], 'Error details', VALUE_OPTIONAL),
        ]);
    }

    // Get queue status: tasks and statistics for a course.

    /**
     * Parameters for get_queue_status.
     *
     * @return external_function_parameters
     */
    public static function get_queue_status_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Get queue status including tasks and statistics.
     *
     * @param int $courseid The course ID.
     * @return array Tasks list and statistics.
     */
    public static function get_queue_status(int $courseid): array {
        $params = self::validate_parameters(self::get_queue_status_parameters(), [
            'courseid' => $courseid,
        ]);

        self::validate_course_access($params['courseid']);

        // Fetch raw tasks and format them for display.
        $rawtasks = queue_repository::get_all_by_course($params['courseid']);
        $tasks = queue_presenter::format_tasks($rawtasks);

        // Aggregate status counts into statistics.
        $statuscounts = queue_repository::get_status_counts($params['courseid']);
        $stats = queue_presenter::calculate_statistics($statuscounts);

        return [
            'tasks' => $tasks,
            'stats' => $stats,
        ];
    }

    /**
     * Return values for get_queue_status.
     *
     * @return external_single_structure
     */
    public static function get_queue_status_returns(): external_single_structure {
        $task = new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Task ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_OPTIONAL),
            'modulename' => new external_value(PARAM_TEXT, 'Module type'),
            'title' => new external_value(PARAM_TEXT, 'Module title', VALUE_OPTIONAL),
            'instructions' => new external_value(PARAM_RAW, 'AI instructions', VALUE_OPTIONAL),
            'status' => new external_value(PARAM_INT, 'Status code'),
            'statuslabel' => new external_value(PARAM_TEXT, 'Status label', VALUE_OPTIONAL),
            'jobid' => new external_value(PARAM_RAW, 'Dixeo job UUID', VALUE_OPTIONAL),
            'cmid' => new external_value(PARAM_INT, 'Created module ID', VALUE_OPTIONAL),
            'sectionnumber' => new external_value(PARAM_INT, 'Section number', VALUE_OPTIONAL),
            'beforemod' => new external_value(PARAM_INT, 'Insert before module', VALUE_OPTIONAL),
            'link' => new external_value(PARAM_URL, 'Link to created module', VALUE_OPTIONAL),
            'displaytitle' => new external_value(PARAM_TEXT, 'Display title (New MODULETYPE or activity title)', VALUE_OPTIONAL),
            'completedonshort' => new external_value(PARAM_TEXT, 'Short completion date for completed tasks', VALUE_OPTIONAL),
            'timestamp' => new external_value(PARAM_TEXT, 'Display timestamp', VALUE_OPTIONAL),
            'timecreated' => new external_value(PARAM_INT, 'Creation time', VALUE_OPTIONAL),
            'timestarted' => new external_value(PARAM_INT, 'Start time', VALUE_OPTIONAL),
            'timecompleted' => new external_value(PARAM_INT, 'Completion time', VALUE_OPTIONAL),
            'params' => new external_value(PARAM_RAW, 'JSON params', VALUE_OPTIONAL),
            'sortorder' => new external_value(PARAM_INT, 'Sort order (deprecated)', VALUE_OPTIONAL),
            'description' => new external_value(PARAM_RAW, 'Description', VALUE_OPTIONAL),
            'lang' => new external_value(PARAM_TEXT, 'Language', VALUE_OPTIONAL),
            'queuemode' => new external_value(PARAM_ALPHA, 'Task mode: generate, fill, or manual', VALUE_OPTIONAL),
        ], 'Task record');

        $stats = new external_single_structure([
            'active' => new external_value(PARAM_INT, 'Active/queued tasks count (pending + processing)'),
            'errors' => new external_value(PARAM_INT, 'Tasks needing attention (failed + cancelled)'),
        ]);

        return new external_single_structure([
            'tasks' => new external_multiple_structure($task),
            'stats' => $stats,
        ]);
    }

    // Update task: complete, fail, or cancel a task.

    /**
     * Parameters for update_task.
     *
     * @return external_function_parameters
     */
    public static function update_task_parameters(): external_function_parameters {
        return new external_function_parameters([
            'queueid' => new external_value(PARAM_INT, 'Queue record ID'),
            'action' => new external_value(PARAM_ALPHA, 'Action: complete, fail, or cancel'),
            'cmid' => new external_value(PARAM_INT, 'Created module ID (for complete)', VALUE_DEFAULT, 0),
            'error' => new external_value(PARAM_RAW, 'Error message (for fail)', VALUE_DEFAULT, ''),
            'jobid' => new external_value(
                PARAM_TEXT,
                'Job UUID required for complete/fail when task has a jobid',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    /**
     * Update a task status (complete, fail, or cancel).
     *
     * For complete/fail actions, also starts the next pending task if one exists.
     *
     * @param int $queueid The queue record ID.
     * @param string $action The action: complete, fail, or cancel.
     * @param int $cmid The created module ID (for complete action).
     * @param string $error The error message (for fail action).
     * @param string $jobid Job UUID correlating to the processing task (required for complete/fail).
     * @return array Result with success status and next_task info if applicable.
     */
    public static function update_task(
        int $queueid,
        string $action,
        int $cmid = 0,
        string $error = '',
        string $jobid = ''
    ): array {
        $params = self::validate_parameters(self::update_task_parameters(), [
            'queueid' => $queueid,
            'action' => $action,
            'cmid' => $cmid,
            'error' => $error,
            'jobid' => $jobid,
        ]);

        // Get task to verify course access.
        $task = queue_repository::get_by_id($params['queueid']);
        if (!$task) {
            return self::create_update_error_response('Task not found');
        }

        self::validate_course_access($task->courseid);

        switch ($params['action']) {
            case 'complete':
                $validationerror = self::validate_complete_or_fail_transition($task, $params['jobid']);
                if ($validationerror !== null) {
                    return self::create_update_error_response($validationerror);
                }
                if ($params['cmid'] <= 0) {
                    return self::create_update_error_response('cmid required for complete action');
                }
                if (!self::cmid_belongs_to_course($params['cmid'], (int) $task->courseid)) {
                    return self::create_update_error_response('cmid does not belong to the task course');
                }
                if (!queue_service::complete($params['queueid'], $params['cmid'])) {
                    return self::create_update_error_response('Cannot complete this task');
                }
                break;

            case 'fail':
                $validationerror = self::validate_complete_or_fail_transition($task, $params['jobid']);
                if ($validationerror !== null) {
                    return self::create_update_error_response($validationerror);
                }
                // Do not persist client-supplied remote/API error text on the queue row.
                if (
                    !queue_service::fail(
                        $params['queueid'],
                        get_string('generationfailed', 'block_dixeo_modulegen')
                    )
                ) {
                    return self::create_update_error_response('Cannot fail this task');
                }
                break;

            case 'cancel':
                $success = queue_service::cancel($params['queueid']);
                return [
                    'success' => $success,
                    'message' => $success ? 'Task cancelled' : 'Cannot cancel this task',
                ];

            default:
                return self::create_update_error_response('Invalid action: ' . $params['action']);
        }

        return [
            'success' => true,
            'message' => 'Task updated',
        ];
    }

    /**
     * Ensure complete/fail only apply to processing tasks with a matching jobid.
     *
     * @param \stdClass $task Queue row.
     * @param string $jobid Caller-supplied job UUID.
     * @return string|null Error message, or null when valid.
     */
    private static function validate_complete_or_fail_transition(\stdClass $task, string $jobid): ?string {
        if ((int) $task->status !== queue_status::STATUS_PROCESSING) {
            return 'Invalid task state for this action';
        }

        $taskjobid = trim((string) ($task->jobid ?? ''));
        if ($taskjobid === '') {
            return 'Task has no jobid';
        }

        if (trim($jobid) === '' || trim($jobid) !== $taskjobid) {
            return 'jobid mismatch';
        }

        return null;
    }

    /**
     * Check that a course module exists and belongs to the given course.
     *
     * @param int $cmid Course module ID.
     * @param int $courseid Expected course ID.
     * @return bool
     */
    private static function cmid_belongs_to_course(int $cmid, int $courseid): bool {
        $cm = get_coursemodule_from_id(null, $cmid, $courseid, false, IGNORE_MISSING);
        return $cm !== false && (int) $cm->course === $courseid;
    }

    /**
     * Return values for update_task.
     *
     * @return external_single_structure
     */
    public static function update_task_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether update succeeded'),
            'message' => new external_value(PARAM_TEXT, 'Result message', VALUE_OPTIONAL),
            'next_task' => new external_single_structure([
                'queueid' => new external_value(PARAM_INT, 'Next task queue ID'),
                'jobid' => new external_value(PARAM_RAW, 'Next task job UUID'),
                'modulename' => new external_value(PARAM_TEXT, 'Module type'),
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'sectionnumber' => new external_value(PARAM_INT, 'Section number', VALUE_OPTIONAL),
                'beforemod' => new external_value(PARAM_INT, 'Insert before module', VALUE_OPTIONAL),
            ], 'Next task that was started', VALUE_OPTIONAL),
        ]);
    }

    // Retry fill task: retry failed fill-mode row (Dixeo fill_module + create).

    /**
     * Parameters for retry_fill_task.
     *
     * @return external_function_parameters
     */
    public static function retry_fill_task_parameters(): external_function_parameters {
        return new external_function_parameters([
            'queueid' => new external_value(PARAM_INT, 'Queue record ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID (must match task)'),
        ]);
    }

    /**
     * Retry a failed fill task (params.mode = fill).
     *
     * @param int $queueid Queue row id.
     * @param int $courseid Course id.
     * @return array success, message, cmid
     */
    public static function retry_fill_task(int $queueid, int $courseid): array {
        $params = self::validate_parameters(self::retry_fill_task_parameters(), [
            'queueid' => $queueid,
            'courseid' => $courseid,
        ]);

        $task = queue_repository::get_by_id($params['queueid']);
        if (!$task || (int) $task->courseid !== $params['courseid']) {
            return [
                'success' => false,
                'message' => get_string('retry_fill_notfound', 'block_dixeo_modulegen'),
                'cmid' => 0,
            ];
        }

        self::validate_course_access($params['courseid']);

        if ((int) $task->status !== queue_status::STATUS_FAILED) {
            return [
                'success' => false,
                'message' => get_string('retry_fill_notfailed', 'block_dixeo_modulegen'),
                'cmid' => 0,
            ];
        }
        if (!queue_task_mode::is_fill($task->params ?? null)) {
            return [
                'success' => false,
                'message' => get_string('retry_fill_notfill', 'block_dixeo_modulegen'),
                'cmid' => 0,
            ];
        }

        $p = $task->params ? json_decode($task->params, true) : [];
        $p = is_array($p) ? $p : [];
        $rawtitle = isset($p['title']) ? trim((string) $p['title']) : trim((string) ($task->title ?? ''));
        $summary = isset($p['summary']) ? trim((string) $p['summary']) : '';
        $filldisplay = $rawtitle !== '' ? $rawtitle : get_string('filltask_defaulttitle', 'block_dixeo_modulegen');
        $nameoverride = $rawtitle !== '' ? $rawtitle : null;
        $beforemod = !empty($task->beforemod) ? (int) $task->beforemod : null;

        $out = self::run_fill_retry_pipeline(
            (string) $task->modulename,
            (string) $task->instructions,
            (int) $task->courseid,
            (int) ($task->sectionnumber ?? 0),
            $beforemod,
            $filldisplay,
            $nameoverride,
            $summary
        );

        if (!empty($out['success']) && !empty($out['cmid'])) {
            queue_service::complete_failed_fill_retry(
                $params['queueid'],
                (int) $out['cmid'],
                (string) ($out['fill_jobid'] ?? '')
            );
            return [
                'success' => true,
                'message' => '',
                'cmid' => (int) $out['cmid'],
            ];
        }

        if (!empty($out['error'])) {
            queue_service::fail_fill_retry($params['queueid'], (string) $out['error']);
        }

        return [
            'success' => false,
            'message' => !empty($out['error'])
                ? (string) $out['error']
                : get_string('retry_fill_failed', 'block_dixeo_modulegen'),
            'cmid' => 0,
        ];
    }

    /**
     * Return values for retry_fill_task.
     *
     * @return external_single_structure
     */
    public static function retry_fill_task_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether fill succeeded'),
            'message' => new external_value(PARAM_RAW, 'Error or empty', VALUE_DEFAULT, ''),
            'cmid' => new external_value(PARAM_INT, 'Created course module id on success'),
        ]);
    }

    /**
     * Run fill_module job, wait, create activity (used for fill retry only).
     *
     * @param string $modulename Dixeo module type identifier.
     * @param string $instructions Fill instructions.
     * @param int $courseid Course id.
     * @param int $sectionnumber Course section number.
     * @param int|null $beforemod Insert-before cm id or null.
     * @param string $filldisplaytitle Display title used for the fill job.
     * @param string|null $nameoverride Optional activity name override.
     * @param string $summaryraw Fill summary payload.
     * @return array{success: bool, cmid: int, error: string, fill_jobid: string}
     */
    private static function run_fill_retry_pipeline(
        string $modulename,
        string $instructions,
        int $courseid,
        int $sectionnumber,
        ?int $beforemod,
        string $filldisplaytitle,
        ?string $nameoverride,
        string $summaryraw
    ): array {
        global $USER;

        $moduleservice = service_factory::get_module_generation_service();
        $jobservice = service_factory::get_job_service();
        $filljobid = '';
        try {
            service_factory::get_file_sync_service()->ensure_enabled_and_synchronized(
                $courseid,
                (int) $USER->id
            );

            $operation = $moduleservice->submit_fill_job_for_course(
                $modulename,
                $instructions,
                $courseid,
                $sectionnumber,
                $filldisplaytitle,
                $summaryraw
            );
            $filljobid = (string) ($operation->jobid ?? '');

            $waitresult = $jobservice->wait_for_job($operation->jobid, 'fill_module');
            if (!$waitresult->is_completed()) {
                return [
                    'success' => false,
                    'cmid' => 0,
                    'error' => get_string('retry_fill_timeout', 'block_dixeo_modulegen'),
                    'fill_jobid' => $filljobid,
                ];
            }

            $introoverride = $summaryraw !== '' ? format_text($summaryraw, FORMAT_PLAIN) : null;

            $result = create_module_from_job::execute(
                $operation->jobid,
                $courseid,
                $sectionnumber,
                $beforemod,
                $nameoverride,
                $introoverride
            );

            if (empty($result['success'])) {
                return [
                    'success' => false,
                    'cmid' => 0,
                    'error' => get_string('retry_fill_createfailed', 'block_dixeo_modulegen'),
                    'fill_jobid' => $filljobid,
                ];
            }

            return [
                'success' => true,
                'cmid' => (int) ($result['cmid'] ?? 0),
                'error' => '',
                'fill_jobid' => $filljobid,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'cmid' => 0,
                'error' => exception_message::format_for_client($e, 'retry_fill_failed'),
                'fill_jobid' => $filljobid,
            ];
        }
    }

    // Delete task: remove a task from the queue (database).

    /**
     * Parameters for delete_task.
     *
     * @return external_function_parameters
     */
    public static function delete_task_parameters(): external_function_parameters {
        return new external_function_parameters([
            'queueid' => new external_value(PARAM_INT, 'Queue record ID'),
        ]);
    }

    /**
     * Delete a task. Allowed for queued, completed, failed, cancelled. Not for processing.
     *
     * @param int $queueid The queue record ID.
     * @return array Result with success and message.
     */
    public static function delete_task(int $queueid): array {
        $params = self::validate_parameters(self::delete_task_parameters(), [
            'queueid' => $queueid,
        ]);

        $task = queue_repository::get_by_id($params['queueid']);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        self::validate_course_access($task->courseid);

        $success = queue_service::delete($params['queueid']);

        return [
            'success' => $success,
            'message' => $success ? 'Task removed' : 'Cannot remove this task',
        ];
    }

    /**
     * Return values for delete_task.
     *
     * @return external_single_structure
     */
    public static function delete_task_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether delete succeeded'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}

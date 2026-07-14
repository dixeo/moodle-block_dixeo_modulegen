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
 * Privacy API implementation for block_dixeo_modulegen.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen\privacy;

use block_dixeo_modulegen\queue_repository;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for the module generation queue and Dixeo/AI transfers.
 *
 * Queue rows are course-scoped. User association uses params.submittedby when present.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata about personal data stored or transmitted by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            queue_repository::TABLE,
            [
                'courseid' => 'privacy:metadata:queue:courseid',
                'modulename' => 'privacy:metadata:queue:modulename',
                'title' => 'privacy:metadata:queue:title',
                'description' => 'privacy:metadata:queue:description',
                'instructions' => 'privacy:metadata:queue:instructions',
                'sectionnumber' => 'privacy:metadata:queue:sectionnumber',
                'beforemod' => 'privacy:metadata:queue:beforemod',
                'cmid' => 'privacy:metadata:queue:cmid',
                'lang' => 'privacy:metadata:queue:lang',
                'params' => 'privacy:metadata:queue:params',
                'status' => 'privacy:metadata:queue:status',
                'jobid' => 'privacy:metadata:queue:jobid',
                'timestarted' => 'privacy:metadata:queue:timestarted',
                'timecompleted' => 'privacy:metadata:queue:timecompleted',
                'timecreated' => 'privacy:metadata:queue:timecreated',
            ],
            'privacy:metadata:queue'
        );

        $collection->add_external_location_link(
            'dixeo_api',
            [
                'courseid' => 'privacy:metadata:external:courseid',
                'modulename' => 'privacy:metadata:external:modulename',
                'instructions' => 'privacy:metadata:external:instructions',
                'filename' => 'privacy:metadata:external:filename',
                'jobid' => 'privacy:metadata:external:jobid',
            ],
            'privacy:metadata:externalpurpose'
        );

        return $collection;
    }

    /**
     * Get course contexts that contain queue data for the given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        $courseids = [];
        foreach (self::get_queue_records_for_userid($userid) as $record) {
            $courseids[(int) $record->courseid] = true;
        }
        $courseids = array_keys($courseids);
        if ($courseids === []) {
            return $contextlist;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :contextlevel
                   AND ctx.instanceid $insql";
        $contextlist->add_from_sql($sql, array_merge(
            ['contextlevel' => CONTEXT_COURSE],
            $inparams
        ));

        return $contextlist;
    }

    /**
     * Get users who have submitted queue rows in the given course context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }

        $courseid = (int) $context->instanceid;
        foreach (self::get_queue_records_for_course($courseid) as $record) {
            $submitter = self::extract_submittedby($record->params ?? null);
            if ($submitter > 0) {
                $userlist->add_user($submitter);
            }
        }
    }

    /**
     * Export queue rows for the user in the approved course contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        if (empty($contextlist->count())) {
            return;
        }

        $userid = (int) $contextlist->get_user()->id;
        $pluginname = get_string('pluginname', 'block_dixeo_modulegen');
        $queuepath = get_string('privacy:path:queue', 'block_dixeo_modulegen');

        foreach ($contextlist as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }

            $courseid = (int) $context->instanceid;
            foreach (self::get_queue_records_for_course($courseid) as $record) {
                if (self::extract_submittedby($record->params ?? null) !== $userid) {
                    continue;
                }

                $path = [$pluginname, $queuepath, (string) $record->id];
                $data = (object) [
                    'courseid' => (int) $record->courseid,
                    'modulename' => $record->modulename,
                    'title' => $record->title,
                    'description' => $record->description,
                    'instructions' => $record->instructions,
                    'sectionnumber' => $record->sectionnumber,
                    'beforemod' => $record->beforemod,
                    'cmid' => (int) $record->cmid,
                    'lang' => $record->lang,
                    'params' => $record->params,
                    'status' => $record->status,
                    'jobid' => $record->jobid,
                    'timestarted' => transform::datetime((int) $record->timestarted),
                    'timecompleted' => transform::datetime((int) $record->timecompleted),
                    'timecreated' => transform::datetime((int) $record->timecreated),
                ];
                writer::with_context($context)->export_data($path, $data);
            }
        }
    }

    /**
     * Delete all queue rows for a course context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_course) {
            return;
        }

        $DB->delete_records(queue_repository::TABLE, ['courseid' => (int) $context->instanceid]);
    }

    /**
     * Delete queue rows submitted by the user in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (empty($contextlist->count())) {
            return;
        }

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            self::delete_user_rows_in_course((int) $context->instanceid, [$userid]);
        }
    }

    /**
     * Delete queue rows for multiple users in a single course context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }

        $userids = array_values(array_unique(array_map('intval', $userlist->get_userids())));
        if ($userids === []) {
            return;
        }

        self::delete_user_rows_in_course((int) $context->instanceid, $userids);
    }

    /**
     * Delete queue rows in a course whose params.submittedby is in the userid list.
     *
     * @param int $courseid
     * @param int[] $userids
     */
    private static function delete_user_rows_in_course(int $courseid, array $userids): void {
        global $DB;

        $useridmap = array_fill_keys($userids, true);
        foreach (self::get_queue_records_for_course($courseid) as $record) {
            $submitter = self::extract_submittedby($record->params ?? null);
            if ($submitter > 0 && isset($useridmap[$submitter])) {
                $DB->delete_records(queue_repository::TABLE, ['id' => (int) $record->id]);
            }
        }
    }

    /**
     * Load queue rows for a course.
     *
     * @param int $courseid
     * @return \stdClass[]
     */
    private static function get_queue_records_for_course(int $courseid): array {
        global $DB;
        return $DB->get_records(queue_repository::TABLE, ['courseid' => $courseid], 'id ASC');
    }

    /**
     * Load queue rows that may belong to a user (params contain submittedby).
     *
     * @param int $userid
     * @return \stdClass[]
     */
    private static function get_queue_records_for_userid(int $userid): array {
        global $DB;

        if ($userid <= 0) {
            return [];
        }

        // Narrow candidates with LIKE, then confirm exact integer match in PHP.
        $like = $DB->sql_like('params', ':pattern', false);
        $candidates = $DB->get_records_select(
            queue_repository::TABLE,
            $like,
            ['pattern' => '%"submittedby":' . $userid . '%'],
            'id ASC'
        );

        $matched = [];
        foreach ($candidates as $record) {
            if (self::extract_submittedby($record->params ?? null) === $userid) {
                $matched[$record->id] = $record;
            }
        }

        return $matched;
    }

    /**
     * Read submittedby from queue params JSON.
     *
     * @param string|null $paramsjson
     * @return int
     */
    private static function extract_submittedby(?string $paramsjson): int {
        if ($paramsjson === null || $paramsjson === '') {
            return 0;
        }

        $params = json_decode($paramsjson, true);
        if (!is_array($params)) {
            return 0;
        }

        return (int) ($params['submittedby'] ?? 0);
    }
}

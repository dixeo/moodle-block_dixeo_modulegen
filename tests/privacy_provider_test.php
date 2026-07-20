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
 * Privacy provider tests for block_dixeo_modulegen (DIXEO-PRIV-004).
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

use block_dixeo_modulegen\privacy\provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Queue privacy: generate, fill, and manual rows keyed by params.submittedby.
 *
 * @covers \block_dixeo_modulegen\privacy\provider
 */
final class privacy_provider_test extends \core_privacy\tests\provider_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_metadata_includes_queue_table(): void {
        $collection = new collection('block_dixeo_modulegen');
        $newcollection = provider::get_metadata($collection);
        $names = array_map(static fn($item) => $item->get_name(), $newcollection->get_collection());
        $this->assertContains(queue_repository::TABLE, $names);
        $this->assertContains('dixeo_api', $names);
    }

    public function test_get_contexts_for_userid_includes_course_for_generate_fill_and_manual(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $coursecontext = \context_course::instance($course->id);

        $this->setUser($user);
        queue_service::submit((int) $course->id, 'page', 'Generate instructions', 1);
        queue_service::log_fill_completed(
            (int) $course->id,
            'page',
            'Fill instructions',
            1,
            null,
            0,
            'Fill title',
            'Fill summary',
            'fill-job-1',
            (int) $user->id
        );
        queue_service::log_manual_upload_completed(
            (int) $course->id,
            'resource',
            1,
            null,
            0,
            'Manual title',
            'notes.pdf',
            (int) $user->id
        );

        // Row without submittedby must not associate with any user.
        $anon = queue_repository::create_base_record(
            (int) $course->id,
            'page',
            'Anonymous fill',
            1,
            null,
            'en'
        );
        $anon->status = queue_status::STATUS_COMPLETED;
        $anon->params = json_encode([
            'mode' => queue_task_mode::MODE_FILL,
            'title' => 'No submitter',
            'summary' => '',
            'dixeo_jobid' => 'fill-job-anon',
        ]);
        queue_repository::insert($anon);

        $contextlist = provider::get_contexts_for_userid((int) $user->id);
        $this->assertEquals([(int) $coursecontext->id], array_map('intval', $contextlist->get_contextids()));
    }

    public function test_export_and_delete_generate_fill_and_manual_rows_by_submitter(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $peer = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $coursecontext = \context_course::instance($course->id);

        $this->setUser($user);
        $generate = queue_service::submit((int) $course->id, 'page', 'User generate prompt', 1);
        $fillid = queue_service::log_fill_completed(
            (int) $course->id,
            'page',
            'User fill prompt',
            1,
            null,
            42,
            'Fill activity',
            'Summary text',
            'fill-job-user',
            (int) $user->id
        );
        $manualid = queue_service::log_manual_upload_completed(
            (int) $course->id,
            'scorm',
            1,
            null,
            99,
            'SCORM package',
            'course.zip',
            (int) $user->id
        );

        $this->setUser($peer);
        $peergenerate = queue_service::submit((int) $course->id, 'page', 'Peer generate prompt', 1);
        $peerfillid = queue_service::log_fill_completed(
            (int) $course->id,
            'page',
            'Peer fill prompt',
            1,
            null,
            0,
            'Peer fill',
            '',
            'fill-job-peer',
            (int) $peer->id
        );

        $approved = new approved_contextlist($user, 'block_dixeo_modulegen', [(int) $coursecontext->id]);
        writer::reset();
        provider::export_user_data($approved);

        $writer = writer::with_context($coursecontext);
        $this->assertTrue($writer->has_any_data());

        provider::delete_data_for_user($approved);

        $this->assertFalse($DB->record_exists(queue_repository::TABLE, ['id' => $generate['queueid']]));
        $this->assertFalse($DB->record_exists(queue_repository::TABLE, ['id' => $fillid]));
        $this->assertFalse($DB->record_exists(queue_repository::TABLE, ['id' => $manualid]));
        $this->assertTrue($DB->record_exists(queue_repository::TABLE, ['id' => $peergenerate['queueid']]));
        $this->assertTrue($DB->record_exists(queue_repository::TABLE, ['id' => $peerfillid]));
    }

    public function test_get_users_in_context_and_delete_data_for_users(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $user2 = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $coursecontext = \context_course::instance($course->id);

        $this->setUser($user1);
        $u1generate = queue_service::submit((int) $course->id, 'page', 'U1 generate', 1);
        $u1manual = queue_service::log_manual_upload_completed(
            (int) $course->id,
            'resource',
            1,
            null,
            10,
            'U1 manual',
            'u1.pdf',
            (int) $user1->id
        );

        $this->setUser($user2);
        $u2fill = queue_service::log_fill_failed(
            (int) $course->id,
            'page',
            'U2 fill',
            1,
            null,
            'U2 fill title',
            '',
            'fill-job-u2',
            'failed',
            (int) $user2->id
        );

        $userlist = new userlist($coursecontext, 'block_dixeo_modulegen');
        provider::get_users_in_context($userlist);
        $userids = array_map('intval', $userlist->get_userids());
        $this->assertContains((int) $user1->id, $userids);
        $this->assertContains((int) $user2->id, $userids);

        $approved = new approved_userlist($coursecontext, 'block_dixeo_modulegen', [(int) $user1->id]);
        provider::delete_data_for_users($approved);

        $this->assertFalse($DB->record_exists(queue_repository::TABLE, ['id' => $u1generate['queueid']]));
        $this->assertFalse($DB->record_exists(queue_repository::TABLE, ['id' => $u1manual]));
        $this->assertTrue($DB->record_exists(queue_repository::TABLE, ['id' => $u2fill]));
    }

    public function test_delete_data_for_all_users_in_context_removes_course_rows(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $coursecontext = \context_course::instance($course->id);

        $this->setUser($user);
        queue_service::submit((int) $course->id, 'page', 'Generate', 1);
        queue_service::log_fill_completed(
            (int) $course->id,
            'page',
            'Fill',
            1,
            null,
            0,
            'Title',
            '',
            'fill-all',
            (int) $user->id
        );

        $this->assertNotEmpty($DB->get_records(queue_repository::TABLE, ['courseid' => $course->id]));
        provider::delete_data_for_all_users_in_context($coursecontext);
        $this->assertEmpty($DB->get_records(queue_repository::TABLE, ['courseid' => $course->id]));
    }
}

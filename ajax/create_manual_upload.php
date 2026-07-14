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
 * Manual upload AJAX endpoint for block_dixeo_modulegen.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

header('Content-Type: application/json');

try {
    $modtype = required_param('modtype', PARAM_ALPHA);
    $courseid = required_param('courseid', PARAM_INT);
    $sectionnumber = optional_param('sectionnumber', 0, PARAM_INT);
    $beforemod = optional_param('beforemod', 0, PARAM_INT);

    // Fail closed: validate course context and capabilities before delegating to local_dixeo.
    require_course_login($courseid);
    $context = context_course::instance($courseid);
    require_capability('local/dixeo:generate', $context);
    require_capability('moodle/course:manageactivities', $context);

    if (!in_array($modtype, ['scorm', 'resource'], true)) {
        throw new moodle_exception('error_unsupported_module', 'block_dixeo_modulegen', '', $modtype);
    }

    // Defense-in-depth: reject bad uploads/placement early. Type, size and MIME rules stay in local_dixeo.
    $uploadedfile = $_FILES['file'] ?? null;
    if ($uploadedfile === null || !isset($uploadedfile['error'])) {
        throw new moodle_exception('manual_upload_error_missing', 'block_dixeo_modulegen');
    }
    if ((int) $uploadedfile['error'] !== UPLOAD_ERR_OK) {
        throw new moodle_exception('manual_upload_error_failed', 'block_dixeo_modulegen');
    }
    if (empty($uploadedfile['tmp_name']) || !is_uploaded_file($uploadedfile['tmp_name'])) {
        throw new moodle_exception('manual_upload_error_missing', 'block_dixeo_modulegen');
    }
    $filename = clean_param($uploadedfile['name'] ?? '', PARAM_FILE);
    if ($filename === '') {
        throw new moodle_exception('manual_upload_error_missing', 'block_dixeo_modulegen');
    }

    $modinfo = get_fast_modinfo($courseid);
    if (!$modinfo->get_section_info($sectionnumber)) {
        throw new moodle_exception('manual_upload_error_invalid_section', 'block_dixeo_modulegen');
    }
    if ($beforemod) {
        if (!isset($modinfo->cms[$beforemod]) || (int) $modinfo->cms[$beforemod]->course !== $courseid) {
            throw new moodle_exception('manual_upload_error_invalid_beforemod', 'block_dixeo_modulegen');
        }
    }

    $service = \local_dixeo\external\service_factory::get_manual_upload_service();
    $result = $service->create_from_upload(
        $modtype,
        $courseid,
        $sectionnumber,
        $beforemod ?: null,
        $uploadedfile
    );

    $cmid = (int) $result['cmid'];
    $activityname = (string) ($result['name'] ?? '');
    $link = (new moodle_url('/mod/' . $modtype . '/view.php', ['id' => $cmid]))->out(false);

    $queueid = \block_dixeo_modulegen\queue_service::log_manual_upload_completed(
        $courseid,
        $modtype,
        $sectionnumber,
        $beforemod ?: null,
        $cmid,
        $activityname,
        $filename
    );

    echo json_encode([
        'success' => true,
        'cmid' => $cmid,
        'id' => $result['id'],
        'queueid' => $queueid,
        'activityname' => $activityname,
        'modtype' => $modtype,
        'link' => $link,
        'courseid' => $courseid,
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => \block_dixeo_modulegen\local\exception_message::format_for_client(
            $e,
            'manual_upload_error_failed'
        ),
    ]);
}

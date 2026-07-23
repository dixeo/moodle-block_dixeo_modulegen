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
 * Language strings for the Dixeo Module Generator block.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$string['activequeued'] = 'Active/Queued';
$string['add'] = 'Add';
$string['aiactivities'] = 'Dixeo Content Generator';
$string['blocktitle'] = 'Add AI generated content';
$string['cancelgeneration'] = 'Cancel generation';
$string['cancelled'] = 'Cancelled';
$string['canceltask'] = 'Cancel';
$string['canceltaskconfirm'] = 'Are you sure you want to cancel this task? This action cannot be undone.';
$string['category_assessment'] = 'Assessment';
$string['category_content'] = 'Content';
$string['category_interactive'] = 'Interactive';
$string['category_resource'] = 'Resources';
$string['completed'] = 'Completed';
$string['completedon'] = 'Completed on {$a}';
$string['copyinstructions'] = 'Copy instructions';
$string['dixeo_modulegen:addinstance'] = 'Add a Dixeo Content Generator block';
$string['dixeo_modulegen:myaddinstance'] = 'Add a Dixeo Content Generator block to Dashboard';
$string['error_invalid_fill_pending'] = 'Invalid queue state: fill tasks cannot be pending.';
$string['error_invalid_manual_pending'] = 'Invalid queue state: manual upload tasks cannot be pending.';
$string['error_missing_submitter'] = 'Missing submitter user for file sync.';
$string['error_queue_failed'] = 'Failed to add task to the generation queue.';
$string['error_title'] = 'Oops!';
$string['error_unexpected'] = 'Something went wrong. Please try again or contact your administrator.';
$string['error_unsupported_module'] = 'Unsupported module type: {$a}';
$string['eventfilltaskretried'] = 'Dixeo module fill task retried';
$string['eventfilltaskretrieddesc'] = 'The user with id \'{$a->userid}\' successfully retried fill queue task \'{$a->queueid}\' (module type \'{$a->modulename}\', cmid={$a->cmid}) in course \'{$a->courseid}\'.';
$string['eventmanualuploadcompleted'] = 'Dixeo manual module upload logged';
$string['eventmanualuploadcompleteddesc'] = 'The user with id \'{$a->userid}\' logged manual upload queue task \'{$a->queueid}\' (module type \'{$a->modulename}\', cmid={$a->cmid}) in course \'{$a->courseid}\'.';
$string['eventqueuetaskcancelled'] = 'Dixeo module generation task cancelled';
$string['eventqueuetaskcancelleddesc'] = 'The user with id \'{$a->userid}\' cancelled queue task \'{$a->queueid}\' (module type \'{$a->modulename}\', jobid=\'{$a->jobid}\') in course \'{$a->courseid}\'.';
$string['eventqueuetaskcompleted'] = 'Dixeo module generation task completed';
$string['eventqueuetaskcompleteddesc'] = 'The user with id \'{$a->userid}\' completed queue task \'{$a->queueid}\' (module type \'{$a->modulename}\', jobid=\'{$a->jobid}\', cmid={$a->cmid}) in course \'{$a->courseid}\'.';
$string['eventqueuetaskdeleted'] = 'Dixeo module generation task deleted';
$string['eventqueuetaskdeleteddesc'] = 'The user with id \'{$a->userid}\' deleted queue task \'{$a->queueid}\' (module type \'{$a->modulename}\') from course \'{$a->courseid}\'.';
$string['eventqueuetaskfailed'] = 'Dixeo module generation task failed';
$string['eventqueuetaskfaileddesc'] = 'The user with id \'{$a->userid}\' marked queue task \'{$a->queueid}\' as failed (module type \'{$a->modulename}\', jobid=\'{$a->jobid}\') in course \'{$a->courseid}\'.';
$string['eventqueuetasksubmitted'] = 'Dixeo module generation task submitted';
$string['eventqueuetasksubmitteddesc'] = 'The user with id \'{$a->userid}\' queued module generation task \'{$a->queueid}\' (module type \'{$a->modulename}\') in course \'{$a->courseid}\'.';
$string['filltask_defaulttitle'] = 'New activity';
$string['generate'] = 'Generate';
$string['generation_complete'] = 'Your content has been generated successfully! Refresh the page to see it.';
$string['generationcancelled'] = 'Generation cancelled';
$string['generationerror'] = 'Generation error';
$string['generationfailed'] = 'Generation failed';
$string['generationinprogress'] = 'Generation in progress (<span class="elapsed-time">0:00</span>)';
$string['generationqueued'] = 'Waiting in queue';
$string['idle'] = 'Idle';
$string['instructionscopied'] = 'Instructions copied';
$string['loading'] = 'Generating...';
$string['manual_upload_browse'] = 'Choose a file';
$string['manual_upload_drag'] = 'Drag and drop a file here, or click to browse';
$string['manual_upload_error_failed'] = 'Could not create the activity.';
$string['manual_upload_error_file_too_large'] = 'File is too large. Please upload a file smaller than {$a->maxsize}.';
$string['manual_upload_error_invalid_beforemod'] = 'The insert position does not belong to this course.';
$string['manual_upload_error_invalid_resource'] = 'Only these file formats are accepted: {$a->ragformats}.';
$string['manual_upload_error_invalid_scorm'] = 'Only Articulate Storyline SCORM packages (.zip) are accepted.';
$string['manual_upload_error_invalid_section'] = 'The selected course section is not valid.';
$string['manual_upload_error_missing'] = 'A file is required.';
$string['manual_upload_resource_description'] = 'Accepted formats: {$a->ragformats}. (Max {$a->maxsize})';
$string['manual_upload_scorm_description'] = 'Articulate Storyline SCORM packages (.zip) only.';
$string['manual_upload_success'] = 'Activity "<a href="{$a->link}">{$a->name}</a>" was added. File sync has started.';
$string['manual_upload_uploading'] = 'Uploading...';
$string['needsattention'] = 'Needs attention';
$string['newmoduletype'] = 'New {$a}';
$string['next'] = 'Next';
$string['noinstructions'] = 'No instructions for this task.';
$string['notasksinthequeue'] = 'The task queue is currently empty.';
$string['notavailable'] = 'This module is not available or not properly configured. Please try again later or contact your administrator.';
$string['opengeneratorqueue'] = 'Open generator queue';
$string['pluginname'] = 'Dixeo Content Generator';
$string['pluginrequired'] = 'Install the {$a} plugin to generate this activity type.';
$string['privacy:metadata:external:courseid'] = 'The course ID associated with the generation or upload request.';
$string['privacy:metadata:external:filename'] = 'Uploaded file names that may be sent for processing or indexing.';
$string['privacy:metadata:external:instructions'] = 'Generation or fill instructions entered by the teacher.';
$string['privacy:metadata:external:jobid'] = 'Remote Dixeo job identifiers used to track processing.';
$string['privacy:metadata:external:modulename'] = 'The activity module type being generated or uploaded.';
$string['privacy:metadata:externalpurpose'] = 'Instructions, file content or filenames, course and module context, and job identifiers are sent to Dixeo/AI services via local_dixeo to generate or process activities.';
$string['privacy:metadata:queue'] = 'Course-scoped generation queue rows: prompts, titles, filenames, job IDs, errors, and status. Terminal rows (completed, failed, cancelled) are deleted after 90 days.';
$string['privacy:metadata:queue:beforemod'] = 'Optional course module ID used as the insert position.';
$string['privacy:metadata:queue:cmid'] = 'The created course module ID when generation completes.';
$string['privacy:metadata:queue:courseid'] = 'The course that owns the queue row.';
$string['privacy:metadata:queue:description'] = 'Optional description stored with the queue row.';
$string['privacy:metadata:queue:instructions'] = 'Free-text generation or fill instructions that may include personal data.';
$string['privacy:metadata:queue:jobid'] = 'The Dixeo or local job identifier for the queue row.';
$string['privacy:metadata:queue:lang'] = 'The language code used for the request.';
$string['privacy:metadata:queue:modulename'] = 'The module type for the queued task.';
$string['privacy:metadata:queue:params'] = 'JSON metadata (submitter user ID when known, filenames, errors, mode flags).';
$string['privacy:metadata:queue:sectionnumber'] = 'The course section targeted for the activity.';
$string['privacy:metadata:queue:status'] = 'The queue status of the task.';
$string['privacy:metadata:queue:timecompleted'] = 'When the task completed, failed, or was cancelled.';
$string['privacy:metadata:queue:timecreated'] = 'When the queue row was created.';
$string['privacy:metadata:queue:timestarted'] = 'When processing of the task started.';
$string['privacy:metadata:queue:title'] = 'A display title for the queue row (activity name or upload label).';
$string['privacy:notice:avoidpersonaldata'] = 'Content may be processed by Dixeo services. Do not include unnecessary personal data about students in prompts or uploaded files.';
$string['privacy:path:queue'] = 'Generation queue';
$string['processing'] = 'Processing';
$string['prompt_placeholder'] = 'Generation instructions for Dixeo';
$string['queue_manual_upload_label'] = 'Manual upload';
$string['queue_processor'] = 'Dixeo Content Generation Queue Processor';
$string['queued'] = 'Queued';
$string['queuemodaltitle'] = 'Generation Queue';
$string['removefromdisplay'] = 'Remove from display';
$string['removefromqueue'] = 'Remove from queue';
$string['retry'] = 'Retry';
$string['retry_fill_createfailed'] = 'Could not create the activity from the fill result.';
$string['retry_fill_failed'] = 'Module fill did not complete.';
$string['retry_fill_notfailed'] = 'Only failed tasks can be retried this way.';
$string['retry_fill_notfill'] = 'This retry applies to fill tasks only.';
$string['retry_fill_notfound'] = 'Queue task not found for this course.';
$string['retry_fill_timeout'] = 'The AI fill job did not complete in time.';
$string['retrygeneration'] = 'Retry generation';
$string['scorm_package_help'] = 'Upload a SCORM package (.zip)';
$string['scorm_package_invalid'] = 'The uploaded file is not a valid SCORM package.';
$string['status_0'] = 'Pending';
$string['status_1'] = 'Processing';
$string['status_2'] = 'Completed';
$string['status_3'] = 'Failed';
$string['status_4'] = 'Cancelled';
$string['success_message'] = 'A new content generation task has been added to the queue.';
$string['success_title'] = 'Success!';
$string['task_cleanup_modulegen_queue'] = 'Clean up old Dixeo module generation queue entries';
$string['task_completed_success'] = 'Activity "<a href="{$a->link}">{$a->name}</a>" was created.';
$string['task_failed'] = 'Module generation failed: {$a->error}';
$string['task_process_modulegen_queue'] = 'Process Dixeo module generation queue';
$string['taskcancelerror'] = 'An error occurred while trying to cancel the task. Please try again later.';
$string['taskcancelled'] = 'The task has been cancelled successfully.';
$string['timecancelled'] = 'Cancelled at: {$a}';
$string['timecompleted'] = 'Completed at: {$a}';
$string['timecreated'] = 'Created at: {$a}';
$string['timestarted'] = 'Started at: {$a}';
$string['viewinstructions'] = 'View instructions';

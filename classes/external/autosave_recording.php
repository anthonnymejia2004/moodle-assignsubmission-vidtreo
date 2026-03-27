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
 * External function for auto-saving video recording data as a draft submission.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_vidtreo\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Auto-save video recording data as a draft submission.
 */
class autosave_recording extends external_api {

    /**
     * Describes the parameters for the execute function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID', VALUE_REQUIRED),
            'recordingid'  => new external_value(PARAM_TEXT, 'Vidtreo recording ID', VALUE_REQUIRED),
            'publicid'     => new external_value(PARAM_TEXT, 'Vidtreo public ID', VALUE_REQUIRED),
            'duration'     => new external_value(PARAM_INT, 'Duration in seconds', VALUE_DEFAULT, 0),
            'status'       => new external_value(PARAM_TEXT, 'Recording status', VALUE_DEFAULT, 'complete'),
            'metadata'     => new external_value(PARAM_RAW, 'JSON metadata', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Describes the return value for the execute function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'      => new external_value(PARAM_BOOL, 'Operation success'),
            'submissionid' => new external_value(PARAM_INT, 'Submission ID'),
            'message'      => new external_value(PARAM_TEXT, 'Status message', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Auto-save video recording data.
     *
     * @param int $assignmentid The assignment ID.
     * @param string $recordingid The Vidtreo recording ID.
     * @param string $publicid The Vidtreo public ID.
     * @param int $duration Duration in seconds.
     * @param string $status Recording status.
     * @param string $metadata JSON metadata.
     * @return array Result with success, submissionid, and optional message.
     */
    public static function execute(
        int $assignmentid,
        string $recordingid,
        string $publicid,
        int $duration = 0,
        string $status = 'complete',
        string $metadata = ''
    ): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'assignmentid' => $assignmentid,
            'recordingid'  => $recordingid,
            'publicid'     => $publicid,
            'duration'     => $duration,
            'status'       => $status,
            'metadata'     => $metadata,
        ]);

        $assignmentid = $params['assignmentid'];
        $recordingid  = $params['recordingid'];
        $publicid     = $params['publicid'];
        $duration     = $params['duration'];
        $status       = $params['status'];
        $metadata     = $params['metadata'];

        // DEBUG: Log what we received to help diagnose the issue.
        error_log('[VIDTREO AUTOSAVE] Received data: ' . json_encode([
            'assignmentid' => $assignmentid,
            'recordingid' => $recordingid,
            'publicid' => $publicid,
            'duration' => $duration,
            'status' => $status,
            'metadata' => $metadata,
        ]));

        // Validate recording_id is not empty or whitespace-only.
        if (trim($recordingid) === '') {
            error_log('[VIDTREO AUTOSAVE] VALIDATION FAILED: recordingid is empty');
            return [
                'success'      => false,
                'submissionid' => 0,
                'message'      => get_string('autosave:error_validation', 'assignsubmission_vidtreo'),
            ];
        }

        // Get course module context from assignment id.
        $cm = get_coursemodule_from_instance('assign', $assignmentid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        // Check capability.
        require_capability('assignsubmission/vidtreo:use', $context);

        // Get the assign instance.
        $assign = new \assign($context, $cm, null);

        // Get or create draft submission.
        $submission = $assign->get_user_submission($USER->id, true);

        // Upsert record in assignsubmission_vidtreo table.
        $existing = $DB->get_record('assignsubmission_vidtreo', ['submission' => $submission->id]);

        $record = new \stdClass();
        $record->recording_id = trim($recordingid);
        $record->public_id    = trim($publicid);
        $record->duration     = $duration;
        $record->status       = trim($status);
        $record->metadata     = $metadata;

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('assignsubmission_vidtreo', $record);
        } else {
            $record->assignment = $assignmentid;
            $record->submission = $submission->id;
            $DB->insert_record('assignsubmission_vidtreo', $record);
        }

        return [
            'success'      => true,
            'submissionid' => (int) $submission->id,
        ];
    }
}

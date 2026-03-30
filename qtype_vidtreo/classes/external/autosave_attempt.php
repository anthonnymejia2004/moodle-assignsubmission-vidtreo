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
 * External function for auto-saving video recording data in a quiz attempt.
 *
 * @package    qtype_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_vidtreo\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class autosave_attempt extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid'   => new external_value(PARAM_INT, 'Quiz attempt ID'),
            'slot'        => new external_value(PARAM_INT, 'Question slot'),
            'recordingid' => new external_value(PARAM_TEXT, 'Vidtreo recording ID'),
            'publicid'    => new external_value(PARAM_TEXT, 'Vidtreo public ID'),
            'duration'    => new external_value(PARAM_INT, 'Duration in seconds', VALUE_DEFAULT, 0),
            'status'      => new external_value(PARAM_TEXT, 'Recording status', VALUE_DEFAULT, 'complete'),
            'metadata'    => new external_value(PARAM_RAW, 'JSON metadata', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
            'message' => new external_value(PARAM_TEXT, 'Status message', VALUE_OPTIONAL),
        ]);
    }

    public static function execute(
        int $attemptid,
        int $slot,
        string $recordingid,
        string $publicid,
        int $duration = 0,
        string $status = 'complete',
        string $metadata = ''
    ): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'attemptid'   => $attemptid,
            'slot'        => $slot,
            'recordingid' => $recordingid,
            'publicid'    => $publicid,
            'duration'    => $duration,
            'status'      => $status,
            'metadata'    => $metadata,
        ]);

        if (trim($params['recordingid']) === '') {
            return ['success' => false, 'message' => 'Recording ID is required'];
        }

        // Load the quiz attempt.
        $attemptobj = quiz_attempt::create($params['attemptid']);
        $context = $attemptobj->get_context();
        self::validate_context($context);

        require_capability('mod/quiz:attempt', $context);

        // Get the question attempt for this slot.
        $qa = $attemptobj->get_question_attempt($params['slot']);

        // Save response data into question_attempt_step_data via the question engine.
        $qa->process_action([
            'recording_id' => trim($params['recordingid']),
            'public_id'    => trim($params['publicid']),
            'duration'     => $params['duration'],
            'status'       => trim($params['status']),
        ]);

        return ['success' => true];
    }
}

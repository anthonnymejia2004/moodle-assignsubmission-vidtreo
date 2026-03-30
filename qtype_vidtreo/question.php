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
 * Question class for the Vidtreo video recording question type.
 *
 * @package    qtype_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class qtype_vidtreo_question extends question_graded_automatically {

    public $maxrecordingtime = 300;
    public $instructions = '';
    public $enablesourceswitching = 1;
    public $enablepause = 1;

    public function get_expected_data() {
        return [
            'recording_id' => PARAM_TEXT,
            'public_id'    => PARAM_TEXT,
            'duration'     => PARAM_INT,
            'status'       => PARAM_TEXT,
        ];
    }

    public function is_complete_response(array $response) {
        return !empty($response['recording_id']) && trim($response['recording_id']) !== '';
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
            $prevresponse, $newresponse, 'recording_id');
    }

    public function get_correct_response() {
        return null;
    }

    public function grade_response(array $response) {
        return [0, question_state::$needsgrading];
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('error:no_recording', 'local_vidtreo');
    }

    public function is_gradable_response(array $response) {
        return $this->is_complete_response($response);
    }

    public function summarise_response(array $response) {
        if (!empty($response['recording_id'])) {
            $duration = isset($response['duration']) ? (int)$response['duration'] : 0;
            return get_string('recording_submitted', 'qtype_vidtreo', $duration);
        }
        return get_string('norecording', 'qtype_vidtreo');
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        return false;
    }
}

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
 * Renderer for the Vidtreo video recording question type.
 *
 * @package    qtype_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class qtype_vidtreo_renderer extends qtype_renderer {

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        global $PAGE;

        $question = $qa->get_question();

        $apikey               = get_config('local_vidtreo', 'apikey');
        $backendurl           = get_config('local_vidtreo', 'backendurl');
        $cdnurl               = get_config('local_vidtreo', 'cdnurl');
        $playercdnurl         = get_config('local_vidtreo', 'player_cdnurl');

        if (empty($apikey)) {
            return html_writer::div(
                get_string('error:noapikey', 'local_vidtreo'),
                'alert alert-danger'
            );
        }

        if (empty($backendurl)) {
            return html_writer::div(
                get_string('error:nobackendurl', 'local_vidtreo'),
                'alert alert-danger'
            );
        }

        $currentresponse = $qa->get_last_qt_data();
        $existingrecordingid = isset($currentresponse['recording_id']) ? $currentresponse['recording_id'] : '';

        // If already answered and readonly, show player.
        if ($options->readonly && !empty(trim($existingrecordingid))) {
            $output = '';
            
            // Show question text in readonly mode too.
            $questiontext = $question->format_questiontext($qa);
            if (!empty($questiontext)) {
                $output .= html_writer::div($questiontext, 'qtext');
            }
            
            $templatecontext = [
                'context_id'   => $qa->get_slot(),
                'recording_id' => $existingrecordingid,
                'duration'     => isset($currentresponse['duration']) ? (int)$currentresponse['duration'] : 0,
                'player_cdnurl' => $playercdnurl,
            ];
            $output .= $this->render_from_template('local_vidtreo/player', $templatecontext);
            $PAGE->requires->js_call_amd('local_vidtreo/player', 'init', [$qa->get_slot(), ['playerCdnUrl' => $playercdnurl, 'apiKey' => $apikey, 'backendUrl' => $backendurl]]);
            return $output;
        }

        // If readonly but no recording, show info message.
        if ($options->readonly && empty(trim($existingrecordingid))) {
            return html_writer::div(
                get_string('norecording', 'local_vidtreo'),
                'alert alert-info'
            );
        }

        $contextid = $qa->get_slot();
        $existingdata = null;
        if (!empty($existingrecordingid)) {
            $existingdata = [
                'recordingId' => $existingrecordingid,
                'publicId'    => isset($currentresponse['public_id']) ? $currentresponse['public_id'] : '',
                'duration'    => isset($currentresponse['duration']) ? (int)$currentresponse['duration'] : 0,
                'status'      => isset($currentresponse['status']) ? $currentresponse['status'] : 'complete',
            ];
        }

        $config = [
            'apiKey'               => $apikey,
            'backendUrl'           => $backendurl,
            'cdnUrl'               => $cdnurl,
            'maxRecordingTime'     => (int)$question->maxrecordingtime,
            'enableSourceSwitching' => (bool)$question->enablesourceswitching,
            'enablePause'          => (bool)$question->enablepause,
            'lang'                 => current_language(),
            'contextId'            => $contextid,
            'fieldNames'           => [
                'recordingId' => $qa->get_qt_field_name('recording_id'),
                'publicId'    => $qa->get_qt_field_name('public_id'),
                'duration'    => $qa->get_qt_field_name('duration'),
                'status'      => $qa->get_qt_field_name('status'),
            ],
        ];

        $templatecontext = [
            'context_id'       => $contextid,
            'config_json'      => json_encode($config),
            'existing_data'    => $existingdata ? json_encode($existingdata) : 'null',
            'has_existing'     => !empty($existingdata),
            'existing_duration' => $existingdata ? $existingdata['duration'] : 0,
            'completion_message' => get_string('recording_completed', 'local_vidtreo'),
        ];

        $output = '';

        // Show question text.
        $questiontext = $question->format_questiontext($qa);
        if (!empty($questiontext)) {
            $output .= html_writer::div($questiontext, 'qtext');
        }

        // Show instructions if set.
        if (!empty($question->instructions)) {
            $output .= html_writer::div(
                format_text($question->instructions, FORMAT_HTML),
                'vidtreo-question-instructions'
            );
        }

        $output .= $this->render_from_template('local_vidtreo/recorder', $templatecontext);

        // Hidden fields for question attempt data.
        $output .= html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => $qa->get_qt_field_name('recording_id'),
            'value' => $existingrecordingid,
            'id'    => $qa->get_qt_field_name('recording_id'),
        ]);
        $output .= html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => $qa->get_qt_field_name('public_id'),
            'value' => isset($currentresponse['public_id']) ? $currentresponse['public_id'] : '',
        ]);
        $output .= html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => $qa->get_qt_field_name('duration'),
            'value' => isset($currentresponse['duration']) ? (int)$currentresponse['duration'] : 0,
        ]);
        $output .= html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => $qa->get_qt_field_name('status'),
            'value' => isset($currentresponse['status']) ? $currentresponse['status'] : '',
        ]);

        $PAGE->requires->js_call_amd('local_vidtreo/recorder', 'init', [$contextid, $config]);

        return $output;
    }

    public function specific_feedback(question_attempt $qa) {
        $response = $qa->get_last_qt_data();
        if (!empty($response['recording_id'])) {
            $duration = isset($response['duration']) ? (int)$response['duration'] : 0;
            return html_writer::div(
                get_string('recording_submitted', 'qtype_vidtreo', $duration),
                'vidtreo-feedback'
            );
        }
        return '';
    }

    public function correct_response(question_attempt $qa) {
        return ''; // Manual grading — no automatic correct response.
    }
}

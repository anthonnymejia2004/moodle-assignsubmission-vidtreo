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
 * Core submission class for assignsubmission_vidtreo.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class assign_submission_vidtreo extends assign_submission_plugin {

    private const DEFAULT_MAX_RECORDING_TIME = 300;

    public function get_name() {
        return get_string('vidtreo', 'assignsubmission_vidtreo');
    }

    public function get_settings(\MoodleQuickForm $mform) {
        $maxrecordingtime = $this->get_config('maxrecordingtime');
        if ($maxrecordingtime === false) {
            $maxrecordingtime = get_config('assignsubmission_vidtreo', 'maxrecordingtime');
        }
        $enablesourceswitching = $this->get_config('enablesourceswitching');
        if ($enablesourceswitching === false) {
            $enablesourceswitching = get_config('assignsubmission_vidtreo', 'enablesourceswitching');
        }
        $enablepause = $this->get_config('enablepause');
        if ($enablepause === false) {
            $enablepause = get_config('assignsubmission_vidtreo', 'enablepause');
        }

        $mform->addElement(
            'text',
            'assignsubmission_vidtreo_maxrecordingtime',
            get_string('maxrecordingtime', 'assignsubmission_vidtreo')
        );
        $mform->setType('assignsubmission_vidtreo_maxrecordingtime', PARAM_INT);
        $mform->setDefault('assignsubmission_vidtreo_maxrecordingtime',
            $maxrecordingtime !== false ? (int) $maxrecordingtime : self::DEFAULT_MAX_RECORDING_TIME);
        $mform->hideIf('assignsubmission_vidtreo_maxrecordingtime',
            'assignsubmission_vidtreo_enabled', 'notchecked');

        $mform->addElement(
            'checkbox',
            'assignsubmission_vidtreo_enablesourceswitching',
            get_string('enablesourceswitching', 'assignsubmission_vidtreo')
        );
        $mform->setDefault('assignsubmission_vidtreo_enablesourceswitching',
            $enablesourceswitching !== false ? (int) $enablesourceswitching : 1);
        $mform->hideIf('assignsubmission_vidtreo_enablesourceswitching',
            'assignsubmission_vidtreo_enabled', 'notchecked');

        $mform->addElement(
            'checkbox',
            'assignsubmission_vidtreo_enablepause',
            get_string('enablepause', 'assignsubmission_vidtreo')
        );
        $mform->setDefault('assignsubmission_vidtreo_enablepause',
            $enablepause !== false ? (int) $enablepause : 1);
        $mform->hideIf('assignsubmission_vidtreo_enablepause',
            'assignsubmission_vidtreo_enabled', 'notchecked');
    }

    public function save_settings(\stdClass $data) {
        $this->set_config('maxrecordingtime',
            isset($data->assignsubmission_vidtreo_maxrecordingtime)
                ? (int) $data->assignsubmission_vidtreo_maxrecordingtime
                : self::DEFAULT_MAX_RECORDING_TIME);
        $this->set_config('enablesourceswitching',
            !empty($data->assignsubmission_vidtreo_enablesourceswitching) ? 1 : 0);
        $this->set_config('enablepause',
            !empty($data->assignsubmission_vidtreo_enablepause) ? 1 : 0);

        return true;
    }

    public function get_form_elements($submission, \MoodleQuickForm $mform, \stdClass $data) {
        global $PAGE;

        $apikey = get_config('assignsubmission_vidtreo', 'apikey');
        $backendurl = get_config('assignsubmission_vidtreo', 'backendurl');
        $cdnurl = get_config('assignsubmission_vidtreo', 'cdnurl');
        $maxrecordingtime = get_config('assignsubmission_vidtreo', 'maxrecordingtime');
        $enablesourceswitching = get_config('assignsubmission_vidtreo', 'enablesourceswitching');
        $enablepause = get_config('assignsubmission_vidtreo', 'enablepause');

        if (empty($apikey)) {
            $mform->addElement('static', 'vidtreo_error', '',
                get_string('error:noapikey', 'assignsubmission_vidtreo'));
            return true;
        }

        if (empty($backendurl)) {
            $mform->addElement('static', 'vidtreo_error', '',
                get_string('error:nobackendurl', 'assignsubmission_vidtreo'));
            return true;
        }

        $submissionid = $submission ? $submission->id : 0;
        $existingrecording = $this->get_vidtreo_submission($submissionid);

        $currentlang = current_language();
        $langmap = [
            'en' => 'en',
            'es' => 'es',
        ];
        $widgetlang = isset($langmap[$currentlang]) ? $langmap[$currentlang] : 'en';

        $config = [
            'apiKey' => $apikey,
            'backendUrl' => $backendurl,
            'cdnUrl' => $cdnurl,
            'maxRecordingTime' => (int) $maxrecordingtime,
            'enableSourceSwitching' => (bool) $enablesourceswitching,
            'enablePause' => (bool) $enablepause,
            'lang' => $widgetlang,
            'submissionId' => $submissionid,
            'assignmentId' => $this->assignment->get_instance()->id,
        ];

        $existingdata = null;
        if ($existingrecording) {
            $existingdata = [
                'recordingId' => $existingrecording->recording_id,
                'publicId' => $existingrecording->public_id,
                'duration' => $existingrecording->duration,
                'status' => $existingrecording->status,
            ];
        }

        $templatecontext = [
            'submission_id' => $submissionid,
            'config_json' => json_encode($config),
            'existing_data' => $existingdata ? json_encode($existingdata) : 'null',
            'has_existing' => !empty($existingrecording),
            'existing_duration' => $existingrecording ? $existingrecording->duration : 0,
        ];

        $renderer = $PAGE->get_renderer('assignsubmission_vidtreo');
        $mform->addElement('html', $renderer->render_from_template(
            'assignsubmission_vidtreo/recorder',
            $templatecontext
        ));

        $mform->addElement('hidden', 'vidtreo_recording_id',
            $existingrecording ? $existingrecording->recording_id : '');
        $mform->setType('vidtreo_recording_id', PARAM_TEXT);

        $mform->addElement('hidden', 'vidtreo_public_id',
            $existingrecording ? $existingrecording->public_id : '');
        $mform->setType('vidtreo_public_id', PARAM_TEXT);

        $mform->addElement('hidden', 'vidtreo_duration',
            $existingrecording ? $existingrecording->duration : '');
        $mform->setType('vidtreo_duration', PARAM_INT);

        $mform->addElement('hidden', 'vidtreo_status',
            $existingrecording ? $existingrecording->status : '');
        $mform->setType('vidtreo_status', PARAM_TEXT);

        $mform->addElement('hidden', 'vidtreo_metadata',
            $existingrecording ? $existingrecording->metadata : '');
        $mform->setType('vidtreo_metadata', PARAM_RAW);

        $PAGE->requires->js_call_amd(
            'assignsubmission_vidtreo/recorder',
            'init',
            [$submissionid, $config]
        );

        return true;
    }

    public function save(\stdClass $submission, \stdClass $data) {
        global $DB;

        $recordingid = isset($data->vidtreo_recording_id) ? trim($data->vidtreo_recording_id) : '';

        if (empty($recordingid)) {
            return true;
        }

        $vidtreosubmission = $this->get_vidtreo_submission($submission->id);

        $record = new \stdClass();
        $record->recording_id = $recordingid;
        $record->public_id = isset($data->vidtreo_public_id) ? trim($data->vidtreo_public_id) : '';
        $record->duration = isset($data->vidtreo_duration) ? (int) $data->vidtreo_duration : null;
        $record->status = isset($data->vidtreo_status) ? trim($data->vidtreo_status) : 'complete';
        $record->metadata = isset($data->vidtreo_metadata) ? $data->vidtreo_metadata : null;

        $params = [
            'context' => $this->assignment->get_context(),
            'courseid' => $this->assignment->get_course()->id,
            'objectid' => 0,
            'other' => [
                'assignmentid' => $this->assignment->get_instance()->id,
                'submissionid' => $submission->id,
                'submissionattempt' => $submission->attemptnumber,
                'submissionstatus' => $submission->status,
            ],
        ];

        if ($vidtreosubmission) {
            $record->id = $vidtreosubmission->id;
            $DB->update_record('assignsubmission_vidtreo', $record);

            $params['objectid'] = $record->id;
            $event = \assignsubmission_vidtreo\event\submission_updated::create($params);
            $event->trigger();
        } else {
            $record->assignment = $this->assignment->get_instance()->id;
            $record->submission = $submission->id;
            $recordid = $DB->insert_record('assignsubmission_vidtreo', $record);

            $params['objectid'] = $recordid;
            $event = \assignsubmission_vidtreo\event\submission_created::create($params);
            $event->trigger();
        }

        return true;
    }

    public function get_config_defaults() {
        return [
            'maxrecordingtime' => self::DEFAULT_MAX_RECORDING_TIME,
            'enablesourceswitching' => 1,
            'enablepause' => 1,
        ];
    }

    public function get_files(\stdClass $submission, \stdClass $user) {
        return [];
    }

    public function view_summary(\stdClass $submission, &$showviewlink) {
        $vidtreosubmission = $this->get_vidtreo_submission($submission->id);

        // DEBUG: Agregar información visible en el resumen
        $debug = '<div style="background: #d1ecf1; border: 2px solid #0c5460; padding: 10px; margin: 5px 0; font-size: 12px;">';
        $debug .= '<strong>🔍 DEBUG view_summary()</strong><br>';
        $debug .= 'Submission ID: ' . $submission->id . '<br>';

        if (!$vidtreosubmission) {
            $debug .= 'Status: NO SUBMISSION<br>';
            $debug .= '</div>';
            return $debug . get_string('nosubmission', 'assignsubmission_vidtreo');
        }

        $debug .= 'Recording ID: ' . $vidtreosubmission->recording_id . '<br>';
        $debug .= 'Duration: ' . ($vidtreosubmission->duration ?: 0) . 's<br>';
        $debug .= 'Show view link: TRUE<br>';
        $debug .= '</div>';

        $showviewlink = true;
        $duration = $vidtreosubmission->duration ? $vidtreosubmission->duration : 0;
        return $debug . get_string('recording_submitted', 'assignsubmission_vidtreo', $duration);
    }

    public function view(\stdClass $submission) {
        global $PAGE;

        $vidtreosubmission = $this->get_vidtreo_submission($submission->id);

        if (!$vidtreosubmission) {
            return '<div class="alert alert-info">' . 
                   get_string('nosubmission', 'assignsubmission_vidtreo') . 
                   '</div>';
        }

        if (empty($vidtreosubmission->recording_id)) {
            return '<div class="alert alert-warning">' . 
                   get_string('nosubmission', 'assignsubmission_vidtreo') . 
                   '</div>';
        }

        $apikey = get_config('assignsubmission_vidtreo', 'apikey');
        $backendurl = get_config('assignsubmission_vidtreo', 'backendurl');
        $playercdnurl = get_config('assignsubmission_vidtreo', 'player_cdnurl');

        if (empty($playercdnurl)) {
            $playercdnurl = 'https://cdn.jsdelivr.net/npm/@vidtreo/player-wc@latest/dist/vidtreo-player.js';
        }

        if (empty($apikey)) {
            return '<div class="alert alert-danger">' . 
                   get_string('error:noapikey', 'assignsubmission_vidtreo') . 
                   '</div>';
        }

        if (empty($backendurl)) {
            return '<div class="alert alert-danger">' . 
                   get_string('error:nobackendurl', 'assignsubmission_vidtreo') . 
                   '</div>';
        }

        $templatecontext = [
            'submission_id' => $submission->id,
            'recording_id' => $vidtreosubmission->recording_id,
            'duration' => $vidtreosubmission->duration ? $vidtreosubmission->duration : 0,
            'status' => $vidtreosubmission->status,
            'apikey' => $apikey,
            'backendurl' => $backendurl,
            'player_cdnurl' => $playercdnurl,
        ];

        $renderer = $PAGE->get_renderer('assignsubmission_vidtreo');
        $output = $renderer->render_from_template(
            'assignsubmission_vidtreo/player',
            $templatecontext
        );

        $PAGE->requires->js_call_amd(
            'assignsubmission_vidtreo/player',
            'init',
            [$submission->id, $playercdnurl]
        );

        return $output;
    }

    public function is_empty(\stdClass $submission) {
        $vidtreosubmission = $this->get_vidtreo_submission($submission->id);

        if (!$vidtreosubmission) {
            return true;
        }

        return empty($vidtreosubmission->recording_id);
    }

    public function delete_instance() {
        global $DB;

        $DB->delete_records('assignsubmission_vidtreo',
            ['assignment' => $this->assignment->get_instance()->id]);

        return true;
    }

    public function copy_submission(\stdClass $sourcesubmission, \stdClass $destsubmission) {
        global $DB;

        $vidtreosubmission = $this->get_vidtreo_submission($sourcesubmission->id);

        if ($vidtreosubmission) {
            $newrecord = new \stdClass();
            $newrecord->assignment = $this->assignment->get_instance()->id;
            $newrecord->submission = $destsubmission->id;
            $newrecord->recording_id = $vidtreosubmission->recording_id;
            $newrecord->public_id = $vidtreosubmission->public_id;
            $newrecord->duration = $vidtreosubmission->duration;
            $newrecord->status = $vidtreosubmission->status;
            $newrecord->metadata = $vidtreosubmission->metadata;
            $DB->insert_record('assignsubmission_vidtreo', $newrecord);
        }

        return true;
    }

    private function get_vidtreo_submission($submissionid) {
        global $DB;

        if (!$submissionid) {
            return null;
        }

        return $DB->get_record('assignsubmission_vidtreo', ['submission' => $submissionid]);
    }
}

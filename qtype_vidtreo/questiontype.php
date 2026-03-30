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
 * Question type class for the Vidtreo video recording question type.
 *
 * @package    qtype_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class qtype_vidtreo extends question_type {

    public function save_question_options($question) {
        global $DB;

        $options = $DB->get_record('question_vidtreo', ['questionid' => $question->id]);

        $record = new stdClass();
        $record->questionid          = $question->id;
        $record->maxrecordingtime    = isset($question->maxrecordingtime) ? (int)$question->maxrecordingtime : 300;
        $record->instructions        = isset($question->instructions)
            ? (is_array($question->instructions) ? ($question->instructions['text'] ?? '') : (string)$question->instructions)
            : '';
        $record->enablesourceswitching = isset($question->enablesourceswitching) ? (int)$question->enablesourceswitching : 1;
        $record->enablepause         = isset($question->enablepause) ? (int)$question->enablepause : 1;

        if ($options) {
            $record->id = $options->id;
            $DB->update_record('question_vidtreo', $record);
        } else {
            $DB->insert_record('question_vidtreo', $record);
        }

        return true;
    }

    public function get_question_options($question) {
        global $DB;

        $options = $DB->get_record('question_vidtreo', ['questionid' => $question->id]);
        if ($options) {
            $question->options = $options;
        } else {
            $question->options = new stdClass();
            $question->options->maxrecordingtime     = 300;
            $question->options->instructions         = '';
            $question->options->enablesourceswitching = 1;
            $question->options->enablepause          = 1;
        }
        return true;
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('question_vidtreo', ['questionid' => $questionid]);
        parent::delete_question($questionid, $contextid);
    }

    public function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        if (isset($questiondata->options)) {
            $question->maxrecordingtime     = $questiondata->options->maxrecordingtime;
            $question->instructions         = $questiondata->options->instructions;
            $question->enablesourceswitching = $questiondata->options->enablesourceswitching;
            $question->enablepause          = $questiondata->options->enablepause;
        }
    }

    public function extra_question_fields() {
        return ['question_vidtreo', 'maxrecordingtime', 'instructions', 'enablesourceswitching', 'enablepause'];
    }
}

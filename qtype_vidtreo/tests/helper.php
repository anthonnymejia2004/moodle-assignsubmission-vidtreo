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
 * Test helper for qtype_vidtreo.
 *
 * @package    qtype_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/vidtreo/question.php');

/**
 * Test helper class for making vidtreo questions.
 */
class qtype_vidtreo_test_helper extends question_test_helper {

    public function get_test_questions() {
        return ['vidtreo'];
    }

    /**
     * Makes a vidtreo question.
     *
     * @return qtype_vidtreo_question
     */
    public function make_vidtreo_question() {
        question_bank::load_question_definition_classes('vidtreo');
        $q = new qtype_vidtreo_question();
        test_question_maker::initialise_a_question($q);
        $q->name = 'Vidtreo question';
        $q->questiontext = 'Please record your video response.';
        $q->generalfeedback = 'Thank you for your recording.';
        $q->qtype = question_bank::get_qtype('vidtreo');
        $q->maxrecordingtime = 120;
        $q->instructions = 'Record a video answering the question.';
        $q->enablesourceswitching = 1;
        $q->enablepause = 1;
        return $q;
    }
}

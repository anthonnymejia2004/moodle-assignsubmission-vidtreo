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
 * Edit form for the Vidtreo video recording question type.
 *
 * @package    qtype_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class qtype_vidtreo_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        $mform->addElement('text', 'maxrecordingtime',
            get_string('maxrecordingtime', 'local_vidtreo'), ['size' => 5]);
        $mform->setType('maxrecordingtime', PARAM_INT);
        $mform->setDefault('maxrecordingtime', 300);
        $mform->addHelpButton('maxrecordingtime', 'maxrecordingtime', 'local_vidtreo');

        $mform->addElement('textarea', 'instructions',
            get_string('instructions', 'qtype_vidtreo'), ['rows' => 5, 'cols' => 60]);
        $mform->setType('instructions', PARAM_TEXT);

        $mform->addElement('checkbox', 'enablesourceswitching',
            get_string('enablesourceswitching', 'local_vidtreo'));
        $mform->setDefault('enablesourceswitching', 1);

        $mform->addElement('checkbox', 'enablepause',
            get_string('enablepause', 'local_vidtreo'));
        $mform->setDefault('enablepause', 1);
    }

    public function qtype() {
        return 'vidtreo';
    }
}

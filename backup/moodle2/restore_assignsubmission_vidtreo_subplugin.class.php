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
 * Restore structure definition for assignsubmission_vidtreo.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/restore_subplugin.class.php');

class restore_assignsubmission_vidtreo_subplugin extends restore_subplugin {

    protected function define_submission_subplugin_structure() {
        $paths = [];

        $elename = $this->get_namefor('submission');
        $elepath = $this->get_pathfor('/vidtreo');

        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    public function process_assignsubmission_vidtreo_submission($data) {
        global $DB;

        $data = (object) $data;
        $data->assignment = $this->get_new_parentid('assign');

        $oldsubmissionid = $data->submission;
        $data->submission = $this->get_mappingid('submission', $oldsubmissionid);

        $newrecordid = $DB->insert_record('assignsubmission_vidtreo', $data);

        return $newrecordid;
    }
}

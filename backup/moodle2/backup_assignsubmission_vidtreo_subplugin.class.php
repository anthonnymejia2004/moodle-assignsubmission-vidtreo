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
 * Backup structure definition for assignsubmission_vidtreo.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/backup_subplugin.class.php');

class backup_assignsubmission_vidtreo_subplugin extends backup_subplugin {

    protected function define_submission_subplugin_structure() {
        $subpluginroot = $this->get_subplugin_element();

        $wrapper = new backup_nested_element($this->get_recommended_name());

        $vidtreo = new backup_nested_element(
            'vidtreo',
            null,
            [
                'recording_id',
                'public_id',
                'duration',
                'status',
                'metadata',
            ]
        );

        $subpluginroot->add_child($wrapper);
        $wrapper->add_child($vidtreo);

        $vidtreo->set_source_table(
            'assignsubmission_vidtreo',
            ['submission' => backup::VAR_PARENTID]
        );

        return $subpluginroot;
    }
}

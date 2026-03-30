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
 * Event class for VIDTREO submission updated (subplugin namespace).
 *
 * @package    vidtreomodule_vidtreosubmission
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace vidtreomodule_vidtreosubmission\event;

defined('MOODLE_INTERNAL') || die();

class submission_updated extends \core\event\base {

    protected function init() {
        $this->data['crud']        = 'u';
        $this->data['edulevel']    = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'assignsubmission_vidtreo';
    }

    public static function get_name() {
        return get_string('pluginname', 'assignsubmission_vidtreo');
    }

    public function get_description() {
        return "User {$this->userid} updated a VIDTREO submission for submission {$this->objectid}.";
    }

    public function get_url() {
        return new \moodle_url('/mod/assign/view.php', ['id' => $this->contextinstanceid]);
    }
}

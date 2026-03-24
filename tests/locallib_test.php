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
 * Structure tests for assignsubmission_vidtreo locallib.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_vidtreo;

defined('MOODLE_INTERNAL') || die();

final class locallib_test extends \advanced_testcase {

    private function get_locallib_source(): string {
        return file_get_contents(__DIR__ . '/../locallib.php');
    }

    public function test_get_name(): void {
        $source = $this->get_locallib_source();

        $this->assertStringContainsString('function get_name()', $source);
        $this->assertStringContainsString("get_string('vidtreo', 'assignsubmission_vidtreo')", $source);
    }

    public function test_get_config_defaults(): void {
        $source = $this->get_locallib_source();

        $this->assertStringContainsString('function get_config_defaults()', $source);
        $this->assertStringContainsString("'maxrecordingtime' => self::DEFAULT_MAX_RECORDING_TIME", $source);
        $this->assertStringContainsString("'enablesourceswitching' => 1", $source);
        $this->assertStringContainsString("'enablepause' => 1", $source);
    }

    public function test_get_files_returns_empty_array(): void {
        $source = $this->get_locallib_source();

        $this->assertStringContainsString('function get_files(\stdClass $submission, \stdClass $user)', $source);
        $this->assertStringContainsString('return [];', $source);
    }

    public function test_is_empty_with_no_submission(): void {
        $source = $this->get_locallib_source();

        $this->assertStringContainsString('function is_empty(\stdClass $submission)', $source);
        $this->assertStringContainsString('return true;', $source);
        $this->assertStringContainsString("return empty(", $source);
    }
}

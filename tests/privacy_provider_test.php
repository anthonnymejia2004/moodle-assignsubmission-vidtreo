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
 * Structure tests for assignsubmission_vidtreo privacy provider.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_vidtreo;

defined('MOODLE_INTERNAL') || die();

final class privacy_provider_test extends \advanced_testcase {

    private function get_provider_source(): string {
        return file_get_contents(__DIR__ . '/../classes/privacy/provider.php');
    }

    public function test_get_metadata_collection(): void {
        $source = $this->get_provider_source();

        $this->assertStringContainsString('add_database_table(', $source);
        $this->assertStringContainsString("'assignsubmission_vidtreo'", $source);
        $this->assertStringContainsString("'recording_id'", $source);
        $this->assertStringContainsString("'public_id'", $source);
        $this->assertStringContainsString("'duration'", $source);
        $this->assertStringContainsString("'status'", $source);
        $this->assertStringContainsString("'metadata'", $source);
    }

    public function test_get_metadata_declares_external_system(): void {
        $source = $this->get_provider_source();

        $this->assertStringContainsString('add_external_location_link(', $source);
        $this->assertStringContainsString("'vidtreo'", $source);
        $this->assertStringContainsString("'privacy:externalsystem'", $source);
    }
}

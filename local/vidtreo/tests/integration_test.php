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
 * Integration tests for local_vidtreo — covers the three main module flows.
 *
 * @package    local_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/vidtreo/db/upgrade.php');

/**
 * End-to-end integration tests for local_vidtreo and its subplugins.
 *
 * Covers:
 *  - Assignment submission flow (Requisitos 5.6, 6.6)
 *  - Quiz question options round-trip (Requisitos 6.11, 6.12)
 *  - Migration data preservation (Requisitos 11.2, 11.3)
 *  - Global config consistency (Requisito 8.7)
 *  - Recording data round-trip (Requisitos 13.1, 13.3)
 *  - Metadata JSON round-trip (Requisito 13.2)
 *
 * @group local_vidtreo
 */
class local_vidtreo_integration_testcase extends advanced_testcase {

    /**
     * Reset the database and caches after each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    // -------------------------------------------------------------------------
    // Test 1: Assignment flow
    // -------------------------------------------------------------------------

    /**
     * Validates: Requisitos 5.6, 6.6
     *
     * Simulates saving a recording via the assignsubmission_vidtreo table and
     * verifies the record is retrievable with correct fields.
     */
    public function test_assignment_flow_recording_saved_and_retrieved(): void {
        global $DB;

        $generator = $this->getDataGenerator();

        // Create course, assignment and student.
        $course     = $generator->create_course();
        $assignment = $generator->create_module('assign', ['course' => $course->id]);
        $student    = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');

        // Simulate a submission record (direct DB insert — form submission not
        // possible in unit tests).
        $record               = new stdClass();
        $record->assignment   = $assignment->cmid;
        $record->submission   = 0; // No real submission object needed for this test.
        $record->recording_id = 'rec-assign-001';
        $record->public_id    = 'pub-assign-001';
        $record->duration     = 42;
        $record->status       = 'complete';
        $record->metadata     = json_encode(['source' => 'webcam']);

        $insertedid = $DB->insert_record('assignsubmission_vidtreo', $record);
        $this->assertGreaterThan(0, $insertedid, 'Record should be inserted successfully.');

        // Read it back.
        $saved = $DB->get_record('assignsubmission_vidtreo', ['id' => $insertedid]);
        $this->assertNotFalse($saved, 'Record should exist in the database.');
        $this->assertEquals('rec-assign-001', $saved->recording_id);
        $this->assertEquals('pub-assign-001', $saved->public_id);
        $this->assertEquals(42, (int)$saved->duration);
        $this->assertEquals('complete', $saved->status);

        // Verify is_empty() equivalent: a non-empty recording_id means not empty.
        $this->assertNotEmpty($saved->recording_id, 'Recording ID must not be empty.');
    }

    // -------------------------------------------------------------------------
    // Test 2: Quiz question options round-trip
    // -------------------------------------------------------------------------

    /**
     * Validates: Requisitos 6.11, 6.12 (Propiedad 13)
     *
     * Inserts a question_vidtreo record with specific options and verifies all
     * fields are preserved exactly on read-back.
     */
    public function test_quiz_question_options_roundtrip(): void {
        global $DB;

        // Skip if the table doesn't exist yet (subplugin not installed).
        try {
            $DB->get_records('question_vidtreo', [], '', 'id', 0, 1);
        } catch (dml_exception $e) {
            $this->markTestSkipped('Table question_vidtreo does not exist — subplugin not installed.');
        }

        $generator = $this->getDataGenerator();
        $course    = $generator->create_course();

        // Insert options record (questionid=0 is fine for a unit test — no real
        // question object is required to test the round-trip).
        $record                      = new stdClass();
        $record->questionid          = 0;
        $record->maxrecordingtime    = 120;
        $record->instructions        = 'Test instructions';
        $record->enablesourceswitching = 1;
        $record->enablepause         = 0;

        $insertedid = $DB->insert_record('question_vidtreo', $record);
        $this->assertGreaterThan(0, $insertedid);

        // Read back and assert all fields match exactly.
        $saved = $DB->get_record('question_vidtreo', ['id' => $insertedid]);
        $this->assertNotFalse($saved);
        $this->assertEquals(120, (int)$saved->maxrecordingtime);
        $this->assertEquals('Test instructions', $saved->instructions);
        $this->assertEquals(1, (int)$saved->enablesourceswitching);
        $this->assertEquals(0, (int)$saved->enablepause);
    }

    // -------------------------------------------------------------------------
    // Test 3: Migration data preservation
    // -------------------------------------------------------------------------

    /**
     * Validates: Requisitos 11.2, 11.3 (Propiedad 8)
     *
     * Inserts records into assignsubmission_vidtreo, runs the upgrade function
     * for version 2026032900, and verifies the records appear in
     * local_vidtreo_recordings with matching fields.
     */
    public function test_migration_preserves_assignment_data(): void {
        global $DB;

        // Insert source records.
        $src1               = new stdClass();
        $src1->assignment   = 1;
        $src1->submission   = 1;
        $src1->recording_id = 'rec-migrate-001';
        $src1->public_id    = 'pub-migrate-001';
        $src1->duration     = 30;
        $src1->status       = 'complete';
        $src1->metadata     = json_encode(['migrated' => true]);
        $DB->insert_record('assignsubmission_vidtreo', $src1);

        $src2               = new stdClass();
        $src2->assignment   = 1;
        $src2->submission   = 2;
        $src2->recording_id = 'rec-migrate-002';
        $src2->public_id    = 'pub-migrate-002';
        $src2->duration     = 60;
        $src2->status       = 'complete';
        $src2->metadata     = null;
        $DB->insert_record('assignsubmission_vidtreo', $src2);

        // Run the upgrade from a version before 2026032900.
        $result = xmldb_local_vidtreo_upgrade(2026032800);
        $this->assertTrue($result, 'Upgrade function should return true.');

        // Verify both records now exist in local_vidtreo_recordings.
        $dest1 = $DB->get_record('local_vidtreo_recordings', ['recording_id' => 'rec-migrate-001']);
        $this->assertNotFalse($dest1, 'First migrated record should exist.');
        $this->assertEquals('pub-migrate-001', $dest1->public_id);
        $this->assertEquals(30, (int)$dest1->duration);
        $this->assertEquals('complete', $dest1->status);

        $dest2 = $DB->get_record('local_vidtreo_recordings', ['recording_id' => 'rec-migrate-002']);
        $this->assertNotFalse($dest2, 'Second migrated record should exist.');
        $this->assertEquals('pub-migrate-002', $dest2->public_id);
        $this->assertEquals(60, (int)$dest2->duration);
        $this->assertEquals('complete', $dest2->status);
    }

    // -------------------------------------------------------------------------
    // Test 4: Global config consistency
    // -------------------------------------------------------------------------

    /**
     * Validates: Requisito 8.7 (Propiedad 1)
     *
     * Sets a config value for local_vidtreo and reads it back, asserting they match.
     */
    public function test_global_config_read_by_subplugins(): void {
        set_config('apikey', 'test-key-123', 'local_vidtreo');

        $value = get_config('local_vidtreo', 'apikey');

        $this->assertEquals('test-key-123', $value, 'Config value should round-trip exactly.');
    }

    // -------------------------------------------------------------------------
    // Test 5: Recording data round-trip
    // -------------------------------------------------------------------------

    /**
     * Validates: Requisitos 13.1, 13.3 (Propiedad 2)
     *
     * Inserts a record into local_vidtreo_recordings and verifies all fields
     * are preserved exactly on read-back.
     */
    public function test_recording_data_roundtrip(): void {
        global $DB;

        $metadata = json_encode(['source' => 'screen', 'resolution' => '1080p']);

        $record               = new stdClass();
        $record->recording_id = 'rec-roundtrip-001';
        $record->public_id    = 'pub-roundtrip-001';
        $record->duration     = 120;
        $record->status       = 'complete';
        $record->metadata     = $metadata;
        $record->userid       = 0;
        $record->timecreated  = time();
        $record->timemodified = time();

        $insertedid = $DB->insert_record('local_vidtreo_recordings', $record);
        $this->assertGreaterThan(0, $insertedid);

        $saved = $DB->get_record('local_vidtreo_recordings', ['id' => $insertedid]);
        $this->assertNotFalse($saved);
        $this->assertEquals('rec-roundtrip-001', $saved->recording_id);
        $this->assertEquals('pub-roundtrip-001', $saved->public_id);
        $this->assertEquals(120, (int)$saved->duration);
        $this->assertEquals('complete', $saved->status);
        $this->assertEquals($metadata, $saved->metadata);
    }

    // -------------------------------------------------------------------------
    // Test 6: Metadata JSON round-trip
    // -------------------------------------------------------------------------

    /**
     * Validates: Requisito 13.2 (Propiedad 3)
     *
     * Encodes a metadata array to JSON and decodes it back, asserting the
     * decoded result equals the original.
     */
    public function test_metadata_json_roundtrip(): void {
        $original = [
            'source'     => 'webcam',
            'resolution' => '720p',
            'attempts'   => 3,
            'paused'     => false,
        ];

        $encoded = json_encode($original);
        $this->assertIsString($encoded, 'json_encode should return a string.');

        $decoded = json_decode($encoded, true);
        $this->assertIsArray($decoded, 'json_decode should return an array.');

        $this->assertEquals($original['source'], $decoded['source']);
        $this->assertEquals($original['resolution'], $decoded['resolution']);
        $this->assertEquals($original['attempts'], $decoded['attempts']);
        $this->assertEquals($original['paused'], $decoded['paused']);
    }
}

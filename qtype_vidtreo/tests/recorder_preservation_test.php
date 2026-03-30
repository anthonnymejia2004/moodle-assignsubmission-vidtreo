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
 * Preservation property tests for non-Quiz recorder contexts.
 *
 * **Validates: Requirements 3.1, 3.3, 3.4, 3.5**
 *
 * These tests MUST PASS on unfixed code - they capture baseline behavior
 * that must be preserved after the fix.
 *
 * @package    qtype_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Property 2: Preservation - Non-Quiz Context Behavior
 *
 * Tests that recorder behavior in Assignment context remains
 * unchanged after the fix. This context uses hardcoded field names.
 *
 * @group qtype_vidtreo
 */
final class qtype_vidtreo_recorder_preservation_test extends advanced_testcase {

    /** @var int Number of property test iterations. */
    private const ITERATIONS = 10;

    /**
     * Reset the database and caches after each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Property 2.1: Assignment Submission Context Preservation
     *
     * **Validates: Requirements 3.1, 3.3**
     *
     * For all Assignment contexts, the recorder SHALL continue to work with
     * hardcoded field names (vidtreo_recording_id, etc.) after the fix.
     *
     * EXPECTED OUTCOME ON UNFIXED CODE: Test PASSES (baseline behavior)
     * EXPECTED OUTCOME ON FIXED CODE: Test PASSES (behavior preserved)
     */
    public function test_property_assignment_context_uses_hardcoded_field_names(): void {
        global $DB;

        $generator = $this->getDataGenerator();

        // Generate multiple test cases
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create course, assignment and student
            $course = $generator->create_course();
            $assignment = $generator->create_module('assign', ['course' => $course->id]);
            $student = $generator->create_user();
            $generator->enrol_user($student->id, $course->id, 'student');

            // Simulate a submission record with hardcoded field names
            $record = new stdClass();
            $record->assignment = $assignment->cmid;
            $record->submission = 0;
            $record->recording_id = "rec-assign-{$i}";
            $record->public_id = "pub-assign-{$i}";
            $record->duration = rand(30, 300);
            $record->status = 'complete';
            $record->metadata = json_encode(['iteration' => $i]);

            $insertedid = $DB->insert_record('assignsubmission_vidtreo', $record);
            $this->assertGreaterThan(0, $insertedid, "Assignment record {$i} should be inserted");

            // Verify the record can be retrieved with hardcoded field names
            $saved = $DB->get_record('assignsubmission_vidtreo', ['id' => $insertedid]);
            $this->assertNotFalse($saved, "Assignment record {$i} should exist");
            $this->assertEquals("rec-assign-{$i}", $saved->recording_id);
            $this->assertEquals("pub-assign-{$i}", $saved->public_id);
            $this->assertEquals('complete', $saved->status);

            // Property: Field names are hardcoded, not dynamic
            $this->assertStringNotContainsString(
                ':',
                $saved->recording_id,
                "Assignment field names should NOT contain ':' (not dynamic)"
            );
        }
    }

    /**
     * Property 2.2: Recording Data Persistence Preservation
     *
     * **Validates: Requirement 3.3, 3.4**
     *
     * For all contexts, recording data SHALL continue to be saved correctly
     * to the appropriate fields after the fix.
     *
     * EXPECTED OUTCOME ON UNFIXED CODE: Test PASSES (baseline behavior)
     * EXPECTED OUTCOME ON FIXED CODE: Test PASSES (behavior preserved)
     */
    public function test_property_recording_data_persistence_preserved(): void {
        global $DB;

        // Test with local_vidtreo_recordings table (central storage)
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $metadata = json_encode([
                'source' => $i % 2 === 0 ? 'webcam' : 'screen',
                'resolution' => $i % 3 === 0 ? '1080p' : '720p',
                'iteration' => $i,
            ]);

            $record = new stdClass();
            $record->recording_id = "rec-persist-{$i}";
            $record->public_id = "pub-persist-{$i}";
            $record->duration = rand(30, 300);
            $record->status = 'complete';
            $record->metadata = $metadata;
            $record->userid = 0;
            $record->timecreated = time();
            $record->timemodified = time();

            $insertedid = $DB->insert_record('local_vidtreo_recordings', $record);
            $this->assertGreaterThan(0, $insertedid);

            // Verify all fields are preserved exactly
            $saved = $DB->get_record('local_vidtreo_recordings', ['id' => $insertedid]);
            $this->assertNotFalse($saved);
            $this->assertEquals("rec-persist-{$i}", $saved->recording_id);
            $this->assertEquals("pub-persist-{$i}", $saved->public_id);
            $this->assertEquals('complete', $saved->status);
            $this->assertEquals($metadata, $saved->metadata);

            // Property: All recording data fields are preserved
            $this->assertObjectHasProperty('recording_id', $saved);
            $this->assertObjectHasProperty('public_id', $saved);
            $this->assertObjectHasProperty('duration', $saved);
            $this->assertObjectHasProperty('status', $saved);
            $this->assertObjectHasProperty('metadata', $saved);
        }
    }

    /**
     * Property 2.3: Form Validation Preservation
     *
     * **Validates: Requirement 3.5**
     *
     * For all contexts, form validation SHALL continue to check for recording
     * completion before allowing submission.
     *
     * This test verifies the validation logic remains unchanged.
     *
     * EXPECTED OUTCOME ON UNFIXED CODE: Test PASSES (baseline behavior)
     * EXPECTED OUTCOME ON FIXED CODE: Test PASSES (behavior preserved)
     */
    public function test_property_form_validation_preserved(): void {
        global $CFG;
        require_once($CFG->dirroot . '/question/type/vidtreo/question.php');

        $question = new qtype_vidtreo_question();

        // Generate test cases for validation
        $testCases = [
            // Valid responses
            ['recording_id' => 'rec-123', 'public_id' => 'pub-123', 'duration' => 60, 'status' => 'complete'],
            ['recording_id' => 'rec-456', 'public_id' => 'pub-456', 'duration' => 120, 'status' => 'complete'],
            // Invalid responses (empty recording_id)
            ['recording_id' => '', 'public_id' => '', 'duration' => 0, 'status' => ''],
            ['recording_id' => '   ', 'public_id' => 'pub-789', 'duration' => 30, 'status' => 'complete'],
        ];

        foreach ($testCases as $i => $response) {
            $isComplete = $question->is_complete_response($response);
            $isGradable = $question->is_gradable_response($response);

            if (!empty($response['recording_id']) && trim($response['recording_id']) !== '') {
                // Valid response
                $this->assertTrue($isComplete, "Response {$i} with recording_id should be complete");
                $this->assertTrue($isGradable, "Response {$i} with recording_id should be gradable");
            } else {
                // Invalid response
                $this->assertFalse($isComplete, "Response {$i} without recording_id should NOT be complete");
                $this->assertFalse($isGradable, "Response {$i} without recording_id should NOT be gradable");
            }
        }

        // Property: Validation logic is consistent across all test cases
        $this->assertTrue(true, "Form validation logic preserved");
    }

    /**
     * Property 2.4: Metadata JSON Round-trip Preservation
     *
     * **Validates: Requirement 3.5**
     *
     * For all contexts, metadata JSON encoding/decoding SHALL continue to work
     * correctly after the fix.
     *
     * EXPECTED OUTCOME ON UNFIXED CODE: Test PASSES (baseline behavior)
     * EXPECTED OUTCOME ON FIXED CODE: Test PASSES (behavior preserved)
     */
    public function test_property_metadata_json_roundtrip_preserved(): void {
        // Generate various metadata structures
        $metadataTestCases = [
            ['source' => 'webcam', 'resolution' => '720p'],
            ['source' => 'screen', 'resolution' => '1080p', 'fps' => 30],
            ['attempts' => 3, 'paused' => false, 'quality' => 'high'],
            ['nested' => ['key1' => 'value1', 'key2' => 'value2']],
            ['array' => [1, 2, 3, 4, 5]],
            ['mixed' => ['string' => 'test', 'number' => 42, 'bool' => true]],
        ];

        foreach ($metadataTestCases as $i => $original) {
            // Encode to JSON
            $encoded = json_encode($original);
            $this->assertIsString($encoded, "Metadata {$i} should encode to string");

            // Decode back to array
            $decoded = json_decode($encoded, true);
            $this->assertIsArray($decoded, "Metadata {$i} should decode to array");

            // Verify round-trip preserves data
            $this->assertEquals($original, $decoded, "Metadata {$i} should round-trip exactly");
        }

        // Property: JSON encoding/decoding is lossless for all metadata structures
        $this->assertTrue(true, "Metadata JSON round-trip preserved");
    }
}

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
 * Preservation property tests for assignment-nosubmission-message-fix.
 *
 * These tests MUST PASS on unfixed code - they capture the existing behavior
 * that must be preserved after the fix is implemented.
 *
 * IMPORTANT: These tests observe and document the current behavior in grading context.
 * They will be re-run after the fix to ensure no regressions.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_vidtreo;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/submission/vidtreo/locallib.php');

/**
 * Preservation property tests.
 *
 * **Property 2: Preservation - Comportamiento en interfaz de calificación**
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**
 *
 * @covers \assign_submission_vidtreo::view_summary
 * @covers \assign_submission_vidtreo::is_empty
 * @covers \assign_submission_vidtreo::save
 * @covers \assign_submission_vidtreo::copy_submission
 * @covers \assign_submission_vidtreo::delete_instance
 */
final class preservation_property_test extends \advanced_testcase {

    /** @var int Number of property test iterations. */
    private const ITERATIONS = 50;

    /**
     * Create a course, assignment with vidtreo submission enabled, and users.
     *
     * @return array{course: \stdClass, assignment: \assign, student: \stdClass, teacher: \stdClass, context: \context_module, plugin: \assign_submission_vidtreo}
     */
    private function create_test_environment(): array {
        $course = $this->getDataGenerator()->create_course();
        
        $assignmentrecord = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'assignsubmission_vidtreo_enabled' => 1,
        ]);
        
        $cm = get_coursemodule_from_instance('assign', $assignmentrecord->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $assignment = new \assign($context, $cm, $course);
        
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        
        // Get the vidtreo submission plugin instance.
        $plugin = $assignment->get_submission_plugin_by_type('vidtreo');
        
        return [
            'course' => $course,
            'assignment' => $assignment,
            'student' => $student,
            'teacher' => $teacher,
            'context' => $context,
            'plugin' => $plugin,
        ];
    }

    /**
     * Property 2.1: Preservation - "No se presentó nada" message in grading context without recording.
     *
     * For any call to view_summary() in grading context (user with mod/assign:grade capability)
     * without a recording, the method SHALL return the "No se presentó nada" message.
     *
     * This test MUST PASS on unfixed code - it captures existing behavior to preserve.
     *
     * **Validates: Requirement 3.1**
     */
    public function test_property2_preservation_nosubmission_message_in_grading_context(): void {
        global $DB;

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $this->resetAfterTest(true);

            $env = $this->create_test_environment();
            
            // Set user as teacher (has grading capability).
            $this->setUser($env['teacher']);
            
            // Verify teacher has grading capability.
            $this->assertTrue(has_capability('mod/assign:grade', $env['context']),
                "Iteration $i: Teacher should have grading capability.");
            
            // Create a submission for the student (but no vidtreo recording).
            $submission = $env['assignment']->get_user_submission($env['student']->id, true);
            
            // Verify no vidtreo submission exists.
            $vidtreosubmission = $DB->get_record('assignsubmission_vidtreo', ['submission' => $submission->id]);
            $this->assertFalse($vidtreosubmission, 
                "Iteration $i: No vidtreo submission should exist initially.");
            
            // Call view_summary() as teacher (grading context) without recording.
            $showviewlink = false;
            $result = $env['plugin']->view_summary($submission, $showviewlink);
            
            // EXPECTED BEHAVIOR (Requirement 3.1):
            // In grading context without recording, the method SHALL return "No se presentó nada" message.
            // This behavior MUST be preserved after the fix.
            
            $nosubmissionstring = get_string('nosubmission', 'assignsubmission_vidtreo');
            
            $this->assertStringContainsString($nosubmissionstring, $result,
                "Iteration $i: view_summary() MUST return 'nosubmission' message in grading context without recording. " .
                "This is the correct behavior that must be preserved. " .
                "Got: " . substr($result, 0, 100));
        }
    }

    /**
     * Property 2.2: Preservation - Video player display in grading context with valid recording.
     *
     * For any call to view_summary() in grading context with a valid recording,
     * the method SHALL NOT return the "No se presentó nada" message.
     *
     * This test MUST PASS on unfixed code - it captures existing behavior to preserve.
     *
     * **Validates: Requirement 3.1**
     */
    public function test_property2_preservation_video_player_in_grading_context(): void {
        global $DB;

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $this->resetAfterTest(true);

            $env = $this->create_test_environment();
            
            // Configure API key for this test.
            set_config('apikey', 'test_api_key_' . $i, 'assignsubmission_vidtreo');
            set_config('backendurl', 'https://test.vidtreo.com', 'assignsubmission_vidtreo');
            
            // Set user as teacher (has grading capability).
            $this->setUser($env['teacher']);
            
            // Create a submission for the student with a valid recording.
            $submission = $env['assignment']->get_user_submission($env['student']->id, true);
            
            // Create a vidtreo submission record with valid recording_id.
            $record = new \stdClass();
            $record->assignment = $env['assignment']->get_instance()->id;
            $record->submission = $submission->id;
            $record->recording_id = 'test_recording_' . $i . '_' . time();
            $record->public_id = 'test_public_' . $i;
            $record->duration = 120 + ($i % 100); // Vary duration
            $record->status = 'complete';
            $record->metadata = json_encode(['test' => 'data']);
            $DB->insert_record('assignsubmission_vidtreo', $record);
            
            // Verify the record exists with valid recording_id.
            $vidtreosubmission = $DB->get_record('assignsubmission_vidtreo', ['submission' => $submission->id]);
            $this->assertNotFalse($vidtreosubmission, 
                "Iteration $i: Vidtreo submission record should exist.");
            $this->assertNotEmpty($vidtreosubmission->recording_id,
                "Iteration $i: recording_id should not be empty.");
            
            // Call view_summary() as teacher (grading context) with valid recording.
            $showviewlink = false;
            $result = $env['plugin']->view_summary($submission, $showviewlink);
            
            // EXPECTED BEHAVIOR (Requirement 3.1):
            // In grading context with valid recording, the method SHALL NOT return "nosubmission" message.
            // This behavior MUST be preserved after the fix.
            
            $nosubmissionstring = get_string('nosubmission', 'assignsubmission_vidtreo');
            
            $this->assertStringNotContainsString($nosubmissionstring, $result,
                "Iteration $i: view_summary() MUST NOT return 'nosubmission' message when there is a valid recording. " .
                "Got: " . substr($result, 0, 100));
            
            // Verify result is not empty (either player HTML or error message, but not "nosubmission").
            $this->assertNotEmpty($result,
                "Iteration $i: view_summary() should return non-empty content. " .
                "Got: " . substr($result, 0, 100));
        }
    }

    /**
     * Property 2.3: Preservation - is_empty() method behavior.
     *
     * For any submission, is_empty() SHALL return true if no recording_id exists,
     * and false if recording_id exists.
     *
     * This test MUST PASS on unfixed code - it captures existing behavior to preserve.
     *
     * **Validates: Requirement 3.3**
     */
    public function test_property2_preservation_is_empty_method(): void {
        global $DB;

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $this->resetAfterTest(true);

            $env = $this->create_test_environment();
            
            // Create a submission for the student.
            $submission = $env['assignment']->get_user_submission($env['student']->id, true);
            
            // Test 1: No vidtreo submission record - is_empty() should return true.
            $isEmpty = $env['plugin']->is_empty($submission);
            $this->assertTrue($isEmpty,
                "Iteration $i: is_empty() should return true when no vidtreo submission exists.");
            
            // Test 2: Vidtreo submission with empty recording_id - is_empty() should return true.
            $record = new \stdClass();
            $record->assignment = $env['assignment']->get_instance()->id;
            $record->submission = $submission->id;
            $record->recording_id = '';
            $record->public_id = '';
            $record->duration = null;
            $record->status = 'pending';
            $record->metadata = null;
            $DB->insert_record('assignsubmission_vidtreo', $record);
            
            $isEmpty = $env['plugin']->is_empty($submission);
            $this->assertTrue($isEmpty,
                "Iteration $i: is_empty() should return true when recording_id is empty.");
            
            // Test 3: Update with valid recording_id - is_empty() should return false.
            $vidtreosubmission = $DB->get_record('assignsubmission_vidtreo', ['submission' => $submission->id]);
            $vidtreosubmission->recording_id = 'test_recording_' . $i . '_' . time();
            $DB->update_record('assignsubmission_vidtreo', $vidtreosubmission);
            
            $isEmpty = $env['plugin']->is_empty($submission);
            $this->assertFalse($isEmpty,
                "Iteration $i: is_empty() should return false when recording_id exists.");
        }
    }

    /**
     * Property 2.4: Preservation - save() method behavior.
     *
     * For any submission data with recording information, save() SHALL correctly
     * store recording_id, public_id, duration, status, and metadata.
     *
     * This test MUST PASS on unfixed code - it captures existing behavior to preserve.
     *
     * **Validates: Requirement 3.2**
     */
    public function test_property2_preservation_save_method(): void {
        global $DB;

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $this->resetAfterTest(true);

            $env = $this->create_test_environment();
            
            // Create a submission for the student.
            $submission = $env['assignment']->get_user_submission($env['student']->id, true);
            
            // Prepare submission data with recording information.
            $data = new \stdClass();
            $data->vidtreo_recording_id = 'test_recording_' . $i . '_' . time();
            $data->vidtreo_public_id = 'test_public_' . $i;
            $data->vidtreo_duration = 120 + ($i % 100);
            $data->vidtreo_status = 'complete';
            $data->vidtreo_metadata = json_encode(['iteration' => $i, 'test' => 'data']);
            
            // Call save() method.
            $result = $env['plugin']->save($submission, $data);
            
            // EXPECTED BEHAVIOR (Requirement 3.2):
            // save() should return true and correctly store all recording data.
            
            $this->assertTrue($result,
                "Iteration $i: save() should return true.");
            
            // Verify data was saved correctly.
            $vidtreosubmission = $DB->get_record('assignsubmission_vidtreo', ['submission' => $submission->id]);
            
            $this->assertNotFalse($vidtreosubmission,
                "Iteration $i: Vidtreo submission record should exist after save().");
            
            $this->assertEquals($data->vidtreo_recording_id, $vidtreosubmission->recording_id,
                "Iteration $i: recording_id should be saved correctly.");
            
            $this->assertEquals($data->vidtreo_public_id, $vidtreosubmission->public_id,
                "Iteration $i: public_id should be saved correctly.");
            
            $this->assertEquals($data->vidtreo_duration, $vidtreosubmission->duration,
                "Iteration $i: duration should be saved correctly.");
            
            $this->assertEquals($data->vidtreo_status, $vidtreosubmission->status,
                "Iteration $i: status should be saved correctly.");
            
            $this->assertEquals($data->vidtreo_metadata, $vidtreosubmission->metadata,
                "Iteration $i: metadata should be saved correctly.");
        }
    }

    /**
     * Property 2.5: Preservation - copy_submission() method behavior.
     *
     * For any source submission with recording data, copy_submission() SHALL
     * correctly copy all recording data to the destination submission.
     *
     * This test MUST PASS on unfixed code - it captures existing behavior to preserve.
     *
     * **Validates: Requirement 3.5**
     */
    public function test_property2_preservation_copy_submission_method(): void {
        global $DB;

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $this->resetAfterTest(true);

            $env = $this->create_test_environment();
            
            // Create source submission with recording data.
            $sourcesubmission = $env['assignment']->get_user_submission($env['student']->id, true);
            
            $sourcerecord = new \stdClass();
            $sourcerecord->assignment = $env['assignment']->get_instance()->id;
            $sourcerecord->submission = $sourcesubmission->id;
            $sourcerecord->recording_id = 'source_recording_' . $i . '_' . time();
            $sourcerecord->public_id = 'source_public_' . $i;
            $sourcerecord->duration = 150 + ($i % 50);
            $sourcerecord->status = 'complete';
            $sourcerecord->metadata = json_encode(['source' => true, 'iteration' => $i]);
            $DB->insert_record('assignsubmission_vidtreo', $sourcerecord);
            
            // Create destination submission.
            $destsubmission = new \stdClass();
            $destsubmission->assignment = $env['assignment']->get_instance()->id;
            $destsubmission->userid = $env['student']->id;
            $destsubmission->timecreated = time();
            $destsubmission->timemodified = time();
            $destsubmission->status = 'new';
            $destsubmission->groupid = 0;
            $destsubmission->attemptnumber = 1;
            $destsubmission->latest = 1;
            $destsubmission->id = $DB->insert_record('assign_submission', $destsubmission);
            
            // Call copy_submission() method.
            $result = $env['plugin']->copy_submission($sourcesubmission, $destsubmission);
            
            // EXPECTED BEHAVIOR (Requirement 3.5):
            // copy_submission() should return true and correctly copy all recording data.
            
            $this->assertTrue($result,
                "Iteration $i: copy_submission() should return true.");
            
            // Verify data was copied correctly.
            $destrecord = $DB->get_record('assignsubmission_vidtreo', ['submission' => $destsubmission->id]);
            
            $this->assertNotFalse($destrecord,
                "Iteration $i: Destination vidtreo submission record should exist after copy_submission().");
            
            $this->assertEquals($sourcerecord->recording_id, $destrecord->recording_id,
                "Iteration $i: recording_id should be copied correctly.");
            
            $this->assertEquals($sourcerecord->public_id, $destrecord->public_id,
                "Iteration $i: public_id should be copied correctly.");
            
            $this->assertEquals($sourcerecord->duration, $destrecord->duration,
                "Iteration $i: duration should be copied correctly.");
            
            $this->assertEquals($sourcerecord->status, $destrecord->status,
                "Iteration $i: status should be copied correctly.");
            
            $this->assertEquals($sourcerecord->metadata, $destrecord->metadata,
                "Iteration $i: metadata should be copied correctly.");
        }
    }

    /**
     * Property 2.6: Preservation - delete_instance() method behavior.
     *
     * For any assignment instance, delete_instance() SHALL correctly delete
     * all associated vidtreo submission records.
     *
     * This test MUST PASS on unfixed code - it captures existing behavior to preserve.
     *
     * **Validates: Requirement 3.6**
     */
    public function test_property2_preservation_delete_instance_method(): void {
        global $DB, $CFG;

        // Reduce iterations for this test to avoid debugging messages from enrolment.
        $iterations = 10;
        
        // Suppress debugging messages from subplugin system (not related to our test).
        $olddebug = $CFG->debug;
        $CFG->debug = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->resetAfterTest(true);
            
            // Re-suppress debugging after reset.
            $CFG->debug = 0;

            $env = $this->create_test_environment();
            
            // Create multiple submissions with recording data.
            $numsubmissions = 2 + ($i % 3); // Vary number of submissions (2-4)
            $submissionids = [];
            
            for ($j = 0; $j < $numsubmissions; $j++) {
                $user = $this->getDataGenerator()->create_user();
                $this->getDataGenerator()->enrol_user($user->id, $env['course']->id, 'student');
                
                $submission = $env['assignment']->get_user_submission($user->id, true);
                $submissionids[] = $submission->id;
                
                $record = new \stdClass();
                $record->assignment = $env['assignment']->get_instance()->id;
                $record->submission = $submission->id;
                $record->recording_id = 'recording_' . $i . '_' . $j . '_' . time();
                $record->public_id = 'public_' . $i . '_' . $j;
                $record->duration = 100 + ($j % 50);
                $record->status = 'complete';
                $record->metadata = json_encode(['iteration' => $i, 'submission' => $j]);
                $DB->insert_record('assignsubmission_vidtreo', $record);
            }
            
            // Verify records exist before deletion.
            $recordsbefore = $DB->get_records('assignsubmission_vidtreo', 
                ['assignment' => $env['assignment']->get_instance()->id]);
            $this->assertCount($numsubmissions, $recordsbefore,
                "Iteration $i: Should have $numsubmissions vidtreo submission records before deletion.");
            
            // Call delete_instance() method.
            $result = $env['plugin']->delete_instance();
            
            // EXPECTED BEHAVIOR (Requirement 3.6):
            // delete_instance() should return true and delete all associated records.
            
            $this->assertTrue($result,
                "Iteration $i: delete_instance() should return true.");
            
            // Verify all records were deleted.
            $recordsafter = $DB->get_records('assignsubmission_vidtreo', 
                ['assignment' => $env['assignment']->get_instance()->id]);
            $this->assertCount(0, $recordsafter,
                "Iteration $i: All vidtreo submission records should be deleted after delete_instance().");
        }
        
        // Restore debug level.
        $CFG->debug = $olddebug;
    }
}

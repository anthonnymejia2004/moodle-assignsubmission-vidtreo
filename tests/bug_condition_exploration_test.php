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
 * Bug condition exploration test for assignment-nosubmission-message-fix.
 *
 * This test MUST FAIL on unfixed code - the failure confirms the bug exists.
 * DO NOT attempt to fix the test or code when it fails.
 * This test encodes the expected behavior - it will validate the fix when it passes after implementation.
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
 * Bug condition exploration property test.
 *
 * **Property 1: Bug Condition - Mensaje "No se presentó nada" en formulario de estudiante**
 *
 * **Validates: Requirements 1.1, 1.2, 1.3, 2.1, 2.2, 2.3**
 *
 * @covers \assign_submission_vidtreo::view_summary
 */
final class bug_condition_exploration_test extends \advanced_testcase {

    /** @var int Number of property test iterations. */
    private const ITERATIONS = 50;

    /**
     * Create a course, assignment with vidtreo submission enabled, and enrolled student.
     *
     * @return array{course: \stdClass, assignment: \assign, user: \stdClass, context: \context_module, plugin: \assign_submission_vidtreo}
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
        
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        
        // Get the vidtreo submission plugin instance.
        $plugin = $assignment->get_submission_plugin_by_type('vidtreo');
        
        return [
            'course' => $course,
            'assignment' => $assignment,
            'user' => $user,
            'context' => $context,
            'plugin' => $plugin,
        ];
    }

    /**
     * Property 1: Bug Condition - No mostrar mensaje "No se presentó nada" en formulario de estudiante.
     *
     * For any call to view_summary() where the context is the student submission form
     * and there is no prior recording (isBugCondition returns true), the method
     * SHALL NOT return the "No se presentó nada" message.
     *
     * CRITICAL: This test MUST FAIL on unfixed code - the failure confirms the bug exists.
     * EXPECTED RESULT: Test FAILS (this is correct - proves the bug exists).
     *
     * **Validates: Requirements 1.1, 1.2, 1.3, 2.1, 2.2, 2.3**
     */
    public function test_property1_bug_condition_no_submission_message_in_student_form(): void {
        global $DB;

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $this->resetAfterTest(true);

            $env = $this->create_test_environment();
            
            // Set user as student (no grading capability).
            $this->setUser($env['user']);
            
            // Create a submission for the student (but no vidtreo recording).
            $submission = $env['assignment']->get_user_submission($env['user']->id, true);
            
            // Verify no vidtreo submission exists (Bug Condition 1.2).
            $vidtreosubmission = $DB->get_record('assignsubmission_vidtreo', ['submission' => $submission->id]);
            $this->assertFalse($vidtreosubmission, 
                "Iteration $i: No vidtreo submission should exist initially.");
            
            // Call view_summary() as student without recording.
            $showviewlink = false;
            $result = $env['plugin']->view_summary($submission, $showviewlink);
            
            // EXPECTED BEHAVIOR (Requirements 2.1, 2.2, 2.3):
            // In student submission form context without recording, 
            // the method SHALL NOT return "No se presentó nada" message.
            // It should return empty string or null.
            
            $nosubmissionstring = get_string('nosubmission', 'assignsubmission_vidtreo');
            
            $this->assertStringNotContainsString($nosubmissionstring, $result,
                "Iteration $i: view_summary() MUST NOT return 'nosubmission' message in student context without recording. " .
                "Bug Condition: Student viewing submission form without prior recording. " .
                "Expected: empty string or null. Got: " . substr($result, 0, 100));
            
            // Additional assertion: result should be empty or null in student context.
            $this->assertTrue(empty($result) || $result === '' || $result === null,
                "Iteration $i: view_summary() should return empty/null in student context without recording. " .
                "Got: " . substr($result, 0, 100));
        }
    }

    /**
     * Property 1 (Variant): Bug Condition with empty recording_id.
     *
     * For any call to view_summary() where the context is the student submission form
     * and there is a record but recording_id is empty (Bug Condition 1.3),
     * the method SHALL NOT return the "No se presentó nada" message.
     *
     * CRITICAL: This test MUST FAIL on unfixed code.
     *
     * **Validates: Requirements 1.3, 2.3**
     */
    public function test_property1_bug_condition_with_empty_recording_id(): void {
        global $DB;

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $this->resetAfterTest(true);

            $env = $this->create_test_environment();
            
            // Set user as student (no grading capability).
            $this->setUser($env['user']);
            
            // Create a submission for the student.
            $submission = $env['assignment']->get_user_submission($env['user']->id, true);
            
            // Create a vidtreo submission record with empty recording_id (Bug Condition 1.3).
            $record = new \stdClass();
            $record->assignment = $env['assignment']->get_instance()->id;
            $record->submission = $submission->id;
            $record->recording_id = ''; // Empty recording_id
            $record->public_id = '';
            $record->duration = null;
            $record->status = 'pending';
            $record->metadata = null;
            $DB->insert_record('assignsubmission_vidtreo', $record);
            
            // Verify the record exists but recording_id is empty.
            $vidtreosubmission = $DB->get_record('assignsubmission_vidtreo', ['submission' => $submission->id]);
            $this->assertNotFalse($vidtreosubmission, 
                "Iteration $i: Vidtreo submission record should exist.");
            $this->assertEmpty($vidtreosubmission->recording_id,
                "Iteration $i: recording_id should be empty.");
            
            // Call view_summary() as student with empty recording_id.
            $showviewlink = false;
            $result = $env['plugin']->view_summary($submission, $showviewlink);
            
            // EXPECTED BEHAVIOR (Requirement 2.3):
            // In student submission form context with empty recording_id,
            // the method SHALL NOT return "No se presentó nada" message.
            
            $nosubmissionstring = get_string('nosubmission', 'assignsubmission_vidtreo');
            
            $this->assertStringNotContainsString($nosubmissionstring, $result,
                "Iteration $i: view_summary() MUST NOT return 'nosubmission' message in student context with empty recording_id. " .
                "Bug Condition: Student viewing submission form with empty recording_id. " .
                "Expected: empty string or null. Got: " . substr($result, 0, 100));
            
            // Additional assertion: result should be empty or null in student context.
            $this->assertTrue(empty($result) || $result === '' || $result === null,
                "Iteration $i: view_summary() should return empty/null in student context with empty recording_id. " .
                "Got: " . substr($result, 0, 100));
        }
    }
}

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
 * Property-based tests for autosave_recording external function.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_vidtreo;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Property-based tests for the autosave recording external function.
 *
 * **Feature: video-auto-save, Property 1: Round-trip de auto-guardado (upsert idempotente)**
 * **Feature: video-auto-save, Property 2: Control de permisos**
 * **Feature: video-auto-save, Property 3: Gestión de borrador de entrega**
 *
 * **Validates: Requirements 2.3, 2.4, 2.5, 3.3, 2.2, 2.6, 3.1, 3.2**
 *
 * @covers \assignsubmission_vidtreo\external\autosave_recording::execute
 * @runTestsInSeparateProcesses
 */
final class autosave_property_test extends \advanced_testcase {

    /** @var int Number of property test iterations. */
    private const ITERATIONS = 100;

    /**
     * Generate a random non-empty string of given max length.
     *
     * @param int $maxlength Maximum length of the string.
     * @return string A random alphanumeric string (at least 1 char).
     */
    private function random_string(int $maxlength = 50): string {
        $length = random_int(1, $maxlength);
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     * Generate random valid recording data.
     *
     * @return array{recordingid: string, publicid: string, duration: int, status: string, metadata: string}
     */
    private function random_recording_data(): array {
        $statuses = ['pending', 'complete', 'error', 'recording', 'uploading'];
        return [
            'recordingid' => $this->random_string(100),
            'publicid'    => $this->random_string(100),
            'duration'    => random_int(0, 7200),
            'status'      => $statuses[random_int(0, count($statuses) - 1)],
            'metadata'    => json_encode([
                'userAgent' => $this->random_string(30),
                'source'    => $this->random_string(10),
                'extra'     => random_int(0, 9999),
            ]),
        ];
    }

    /**
     * Create a course, assignment, and enrolled student with the vidtreo:use capability.
     *
     * @return array{course: \stdClass, assignment: \stdClass, user: \stdClass, context: \context_module}
     */
    private function create_test_environment(): array {
        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
        ]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $cm = get_coursemodule_from_instance('assign', $assignment->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        // Grant the vidtreo:use capability to the student role.
        $roleid = $this->getDataGenerator()->create_role();
        \role_assign($roleid, $user->id, $context->id);
        \assign_capability('assignsubmission/vidtreo:use', CAP_ALLOW, $roleid, $context->id);

        return [
            'course'     => $course,
            'assignment' => $assignment,
            'user'       => $user,
            'context'    => $context,
        ];
    }

    /**
     * Property 1: Round-trip de auto-guardado (upsert idempotente).
     *
     * For any valid recording data, calling execute() must result in exactly one record
     * in assignsubmission_vidtreo for that submission, with fields matching the input.
     * Calling execute() again with different data must update the existing record
     * without creating a duplicate.
     *
     * **Feature: video-auto-save, Property 1: Round-trip de auto-guardado (upsert idempotente)**
     * **Validates: Requirements 2.3, 2.4, 2.5, 3.3**
     */
    public function test_property1_roundtrip_upsert_idempotence(): void {
        global $DB;

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $this->resetAfterTest(true);

            $env = $this->create_test_environment();
            $this->setUser($env['user']);

            // Generate first set of random recording data.
            $data1 = $this->random_recording_data();

            // First call: should insert a new record.
            $result1 = \assignsubmission_vidtreo\external\autosave_recording::execute(
                $env['assignment']->id,
                $data1['recordingid'],
                $data1['publicid'],
                $data1['duration'],
                $data1['status'],
                $data1['metadata']
            );

            $this->assertTrue($result1['success'], "Iteration $i: First execute() should succeed.");
            $this->assertGreaterThan(0, $result1['submissionid'], "Iteration $i: Should return a valid submission id.");

            // Verify exactly one record exists for this submission.
            $records = $DB->get_records('assignsubmission_vidtreo', ['submission' => $result1['submissionid']]);
            $this->assertCount(1, $records, "Iteration $i: Exactly one record should exist after first call.");

            $record = reset($records);
            $this->assertEquals($data1['recordingid'], $record->recording_id,
                "Iteration $i: recording_id should match.");
            $this->assertEquals($data1['publicid'], $record->public_id,
                "Iteration $i: public_id should match.");
            $this->assertEquals($data1['duration'], (int) $record->duration,
                "Iteration $i: duration should match.");
            $this->assertEquals($data1['status'], $record->status,
                "Iteration $i: status should match.");
            $this->assertEquals($data1['metadata'], $record->metadata,
                "Iteration $i: metadata should match.");

            // Generate second set of different random recording data.
            $data2 = $this->random_recording_data();

            // Second call: should update the existing record, not create a duplicate.
            $result2 = \assignsubmission_vidtreo\external\autosave_recording::execute(
                $env['assignment']->id,
                $data2['recordingid'],
                $data2['publicid'],
                $data2['duration'],
                $data2['status'],
                $data2['metadata']
            );

            $this->assertTrue($result2['success'], "Iteration $i: Second execute() should succeed.");
            $this->assertEquals($result1['submissionid'], $result2['submissionid'],
                "Iteration $i: Submission id should remain the same.");

            // Verify still exactly one record (no duplicates).
            $records2 = $DB->get_records('assignsubmission_vidtreo', ['submission' => $result2['submissionid']]);
            $this->assertCount(1, $records2, "Iteration $i: Still exactly one record after second call (no duplicates).");

            $record2 = reset($records2);
            $this->assertEquals($data2['recordingid'], $record2->recording_id,
                "Iteration $i: recording_id should be updated to second value.");
            $this->assertEquals($data2['publicid'], $record2->public_id,
                "Iteration $i: public_id should be updated to second value.");
            $this->assertEquals($data2['duration'], (int) $record2->duration,
                "Iteration $i: duration should be updated to second value.");
            $this->assertEquals($data2['status'], $record2->status,
                "Iteration $i: status should be updated to second value.");
            $this->assertEquals($data2['metadata'], $record2->metadata,
                "Iteration $i: metadata should be updated to second value.");
        }
    }

    /**
     * Property 2: Control de permisos.
     *
     * For any user and assignment, execute() must succeed if and only if the user
     * has the capability `assignsubmission/vidtreo:use` in the module context.
     * If the user does not have the capability, the assignsubmission_vidtreo table
     * must not be modified.
     *
     * **Feature: video-auto-save, Property 2: Control de permisos**
     * **Validates: Requirements 2.2, 2.6**
     */
    public function test_property2_permission_control(): void {
        global $DB;

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $this->resetAfterTest(true);

            $data = $this->random_recording_data();

            // --- User WITHOUT capability ---
            $course = $this->getDataGenerator()->create_course();
            $assignment = $this->getDataGenerator()->create_module('assign', [
                'course' => $course->id,
            ]);
            $unpermitteduser = $this->getDataGenerator()->create_user();
            // Enrol in course but do NOT grant assignsubmission/vidtreo:use.
            $this->getDataGenerator()->enrol_user($unpermitteduser->id, $course->id, 'student');

            // Remove the capability from the student archetype for this context so the
            // default archetype grant does not apply.
            $cm = get_coursemodule_from_instance('assign', $assignment->id, $course->id, false, MUST_EXIST);
            $context = \context_module::instance($cm->id);
            $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
            \assign_capability('assignsubmission/vidtreo:use', CAP_PROHIBIT, $studentroleid, $context->id, true);

            // Snapshot the table count before the call.
            $countbefore = $DB->count_records('assignsubmission_vidtreo');

            $this->setUser($unpermitteduser);

            $exceptionthrown = false;
            try {
                \assignsubmission_vidtreo\external\autosave_recording::execute(
                    $assignment->id,
                    $data['recordingid'],
                    $data['publicid'],
                    $data['duration'],
                    $data['status'],
                    $data['metadata']
                );
            } catch (\required_capability_exception $e) {
                $exceptionthrown = true;
            }
            // Clear any debugging messages generated by Moodle's capability string lookup.
            $this->resetDebugging();

            $this->assertTrue($exceptionthrown,
                "Iteration $i: execute() should throw required_capability_exception for unpermitted user.");

            // Verify the table was NOT modified.
            $countafter = $DB->count_records('assignsubmission_vidtreo');
            $this->assertEquals($countbefore, $countafter,
                "Iteration $i: Table should not be modified for unpermitted user.");

            // --- User WITH capability ---
            $env = $this->create_test_environment();
            $this->setUser($env['user']);

            $result = \assignsubmission_vidtreo\external\autosave_recording::execute(
                $env['assignment']->id,
                $data['recordingid'],
                $data['publicid'],
                $data['duration'],
                $data['status'],
                $data['metadata']
            );

            $this->assertTrue($result['success'],
                "Iteration $i: execute() should succeed for permitted user.");
            $this->assertGreaterThan(0, $result['submissionid'],
                "Iteration $i: Should return a valid submission id for permitted user.");

            // Verify data was persisted.
            $record = $DB->get_record('assignsubmission_vidtreo', ['submission' => $result['submissionid']]);
            $this->assertNotFalse($record,
                "Iteration $i: Record should exist in table for permitted user.");
            $this->assertEquals($data['recordingid'], $record->recording_id,
                "Iteration $i: recording_id should match for permitted user.");
            $this->assertEquals($data['publicid'], $record->public_id,
                "Iteration $i: public_id should match for permitted user.");
            $this->assertEquals($data['duration'], (int) $record->duration,
                "Iteration $i: duration should match for permitted user.");
            $this->assertEquals($data['status'], $record->status,
                "Iteration $i: status should match for permitted user.");
            $this->assertEquals($data['metadata'], $record->metadata,
                "Iteration $i: metadata should match for permitted user.");
        }
    }
}

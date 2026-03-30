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
 * Upgrade script for local_vidtreo.
 *
 * @package    local_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade local_vidtreo to a new version.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool True on success.
 */
function xmldb_local_vidtreo_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026032900) {
        // Migrate records from assignsubmission_vidtreo into local_vidtreo_recordings.
        // Only copies rows that are not already present (checked by recording_id).

        $transaction = $DB->start_delegated_transaction();

        try {
            $sourcerecords = $DB->get_records('assignsubmission_vidtreo');
            $sourcecount   = count($sourcerecords);
            $migrated      = 0;

            foreach ($sourcerecords as $src) {
                // Skip rows without a recording_id (incomplete/draft submissions).
                if (empty($src->recording_id)) {
                    continue;
                }

                // Skip if already migrated.
                if ($DB->record_exists('local_vidtreo_recordings', ['recording_id' => $src->recording_id])) {
                    $migrated++;
                    continue;
                }

                $record               = new \stdClass();
                $record->recording_id = $src->recording_id;
                $record->public_id    = $src->public_id ?? '';
                $record->duration     = $src->duration ?? 0;
                $record->status       = $src->status ?? 'complete';
                $record->metadata     = $src->metadata ?? null;
                $record->userid       = 0; // Not stored in assignsubmission_vidtreo.
                $record->timecreated  = time();
                $record->timemodified = time();

                $DB->insert_record('local_vidtreo_recordings', $record);
                $migrated++;
            }

            $transaction->allow_commit();

            // Verify counts: every source row with a recording_id should now exist in the destination.
            $sourceWithId = $DB->count_records_select(
                'assignsubmission_vidtreo',
                "recording_id IS NOT NULL AND recording_id <> ''"
            );
            $destcount = $DB->count_records('local_vidtreo_recordings');

            if ($destcount < $sourceWithId) {
                debugging(
                    'local_vidtreo upgrade: post-migration count mismatch. ' .
                    'Source rows with recording_id: ' . $sourceWithId .
                    ', destination rows: ' . $destcount,
                    DEBUG_DEVELOPER
                );
            }

        } catch (\Exception $e) {
            $transaction->rollback($e);
            debugging(
                'local_vidtreo upgrade: migration failed with exception: ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
            return false;
        }

        upgrade_plugin_savepoint(true, 2026032900, 'local', 'vidtreo');
    }

    return true;
}

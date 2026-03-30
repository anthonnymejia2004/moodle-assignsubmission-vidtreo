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
 * Centralized GDPR privacy provider for local_vidtreo.
 *
 * This provider covers all Vidtreo subplugins (Assignment, Quiz, Lesson).
 * Each subplugin only needs a null_provider that delegates here.
 *
 * @package    local_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_vidtreo\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use local_vidtreo\api_client;

/**
 * Centralized privacy provider for all Vidtreo video recording data.
 *
 * Implements both the metadata provider (declares what data is stored) and
 * the plugin request provider (handles export and deletion requests).
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Declares all personal data stored by local_vidtreo.
     *
     * Covers the local_vidtreo_recordings table and the external Vidtreo cloud
     * service where recordings are stored.
     *
     * @param collection $collection The metadata collection to populate.
     * @return collection The populated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_vidtreo_recordings',
            [
                'recording_id' => 'privacy:metadata:recording_id',
                'public_id'    => 'privacy:metadata:public_id',
                'duration'     => 'privacy:metadata:duration',
                'status'       => 'privacy:metadata:status',
                'metadata'     => 'privacy:metadata:metadata',
            ],
            'privacy:metadata:local_vidtreo_recordings'
        );

        $collection->add_external_location_link(
            'vidtreo_cloud',
            [
                'recording_id' => 'privacy:metadata:recording_id',
                'public_id'    => 'privacy:metadata:public_id',
                'duration'     => 'privacy:metadata:duration',
                'metadata'     => 'privacy:metadata:metadata',
            ],
            'privacy:externalsystem'
        );

        return $collection;
    }

    /**
     * Returns the contexts that contain personal data for the given user.
     *
     * @param int $userid The user ID to look up.
     * @return contextlist The list of contexts containing user data.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        // Find all contexts associated with recordings for this user.
        // Recordings are stored at system context level in local_vidtreo_recordings.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_vidtreo_recordings} r ON r.userid = :userid
                 WHERE ctx.contextlevel = :contextlevel";

        $contextlist->add_from_sql($sql, [
            'userid'       => $userid,
            'contextlevel' => CONTEXT_SYSTEM,
        ]);

        return $contextlist;
    }

    /**
     * Exports all personal data for the given user within the given contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export data for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();
        $recordings = $DB->get_records('local_vidtreo_recordings', ['userid' => $user->id]);

        if (empty($recordings)) {
            return;
        }

        foreach ($contextlist->get_contexts() as $context) {
            $data = [];
            foreach ($recordings as $recording) {
                $data[] = (object) [
                    'recording_id' => $recording->recording_id,
                    'public_id'    => $recording->public_id,
                    'duration'     => $recording->duration,
                    'status'       => $recording->status,
                    'metadata'     => $recording->metadata,
                    'timecreated'  => transform::datetime($recording->timecreated),
                    'timemodified' => transform::datetime($recording->timemodified),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_vidtreo')],
                (object) ['recordings' => $data]
            );
        }
    }

    /**
     * Deletes all personal data for all users in the given context.
     *
     * @param \context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('local_vidtreo_recordings');
    }

    /**
     * Deletes all personal data for the given user.
     *
     * Attempts to delete each recording from the Vidtreo cloud backend before
     * removing the local database record. Exceptions from the API are caught
     * and logged so that the local deletion always proceeds.
     *
     * @param approved_contextlist $contextlist The approved contexts to delete data for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();
        $recordings = $DB->get_records('local_vidtreo_recordings', ['userid' => $user->id]);

        if (empty($recordings)) {
            return;
        }

        // Attempt to delete each recording from the external Vidtreo cloud.
        try {
            $apiclient = new api_client();
            foreach ($recordings as $recording) {
                if (!empty($recording->recording_id)) {
                    try {
                        $apiclient->delete_recording($recording->recording_id);
                    } catch (\Exception $e) {
                        debugging(
                            'local_vidtreo privacy provider: failed to delete recording ' .
                            $recording->recording_id . ' from Vidtreo cloud — ' . $e->getMessage(),
                            DEBUG_DEVELOPER
                        );
                        // Continue — local deletion must always proceed.
                    }
                }
            }
        } catch (\moodle_exception $e) {
            // API client could not be instantiated (e.g. no API key configured).
            // Log and continue with local deletion.
            debugging(
                'local_vidtreo privacy provider: could not instantiate api_client — ' .
                $e->getMessage() . '. Proceeding with local deletion only.',
                DEBUG_DEVELOPER
            );
        }

        // Delete all local records for this user.
        $DB->delete_records('local_vidtreo_recordings', ['userid' => $user->id]);
    }
}

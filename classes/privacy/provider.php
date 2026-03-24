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
 * Privacy subsystem implementation for assignsubmission_vidtreo.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_vidtreo\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\writer;
use mod_assign\privacy\assignsubmission_provider;
use mod_assign\privacy\assign_plugin_request_data;

class provider implements metadata_provider, assignsubmission_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'assignsubmission_vidtreo',
            [
                'recording_id' => 'privacy:metadata:recording_id',
                'public_id' => 'privacy:metadata:public_id',
                'duration' => 'privacy:metadata:duration',
                'status' => 'privacy:metadata:status',
                'metadata' => 'privacy:metadata:metadata',
            ],
            'privacy:metadata:assignsubmission_vidtreo'
        );

        $collection->add_external_location_link(
            'vidtreo',
            [
                'recording_id' => 'privacy:metadata:recording_id',
                'public_id' => 'privacy:metadata:public_id',
                'duration' => 'privacy:metadata:duration',
                'metadata' => 'privacy:metadata:metadata',
            ],
            'privacy:externalsystem'
        );

        return $collection;
    }

    public static function export_submission_user_data(assign_plugin_request_data $exportdata) {
        global $DB;

        $submission = $exportdata->get_pluginobject();
        $record = $DB->get_record('assignsubmission_vidtreo', ['submission' => $submission->id]);

        if (!$record) {
            return;
        }

        $context = $exportdata->get_context();
        $currentpath = $exportdata->get_subcontext();
        $subcontext = array_merge($currentpath, [get_string('pluginname', 'assignsubmission_vidtreo')]);

        $exporteddata = (object) [
            'recording_id' => $record->recording_id,
            'public_id' => $record->public_id,
            'duration' => $record->duration,
            'status' => $record->status,
            'metadata' => $record->metadata,
        ];

        writer::with_context($context)->export_data($subcontext, $exporteddata);
    }

    public static function delete_submission_for_context(assign_plugin_request_data $requestdata) {
        global $DB;

        $DB->delete_records(
            'assignsubmission_vidtreo',
            ['assignment' => $requestdata->get_assignid()]
        );
    }

    public static function delete_submission_for_userid(assign_plugin_request_data $requestdata) {
        global $DB;

        $submissionids = $DB->get_fieldset_select(
            'assign_submission',
            'id',
            'assignment = :assignid AND userid = :userid',
            [
                'assignid' => $requestdata->get_assignid(),
                'userid' => $requestdata->get_user()->id,
            ]
        );

        if (empty($submissionids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($submissionids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('assignsubmission_vidtreo', "submission {$insql}", $inparams);
    }
}

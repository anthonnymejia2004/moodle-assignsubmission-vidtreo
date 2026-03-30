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
 * English language strings for local_vidtreo.
 *
 * @package    local_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Vidtreo';

$string['apikey'] = 'API key';
$string['apikey_desc'] = 'Your Vidtreo API key. Get one from the Vidtreo dashboard at https://app.vidtreo.com.';
$string['backendurl'] = 'Backend URL';
$string['backendurl_desc'] = 'The Vidtreo backend API URL. Only change this if you are using a custom deployment.';
$string['cdnurl'] = 'Recorder CDN URL';
$string['cdnurl_desc'] = 'The CDN URL for loading the Vidtreo Recorder web component script. Only change this if you need to use a specific version.';
$string['player_cdnurl'] = 'Player CDN URL';
$string['player_cdnurl_desc'] = 'The CDN URL for loading the Vidtreo Player web component script. Only change this if you need to use a specific version.';
$string['maxrecordingtime'] = 'Maximum recording time';
$string['maxrecordingtime_desc'] = 'Maximum recording time in seconds. Set to 0 for unlimited recording time.';
$string['enablesourceswitching'] = 'Enable source switching';
$string['enablesourceswitching_desc'] = 'Allow users to switch between camera and screen recording sources.';
$string['enablepause'] = 'Enable pause';
$string['enablepause_desc'] = 'Allow users to pause and resume their recording.';

$string['recording_exists'] = 'Existing recording';
$string['uploading'] = 'Uploading...';
$string['recording_completed'] = 'Recording completed';
$string['duration'] = 'Duration';

$string['error:unauthorized'] = 'API key is invalid or expired. Please contact your site administrator.';
$string['error:notfound'] = 'Recording not found on the server.';
$string['error:servererror'] = 'Vidtreo server error. Please try again later.';
$string['error:timeout'] = 'Connection timed out. Please check your internet connection.';
$string['error:network'] = 'Network error. Please check your internet connection.';
$string['error:noapikey'] = 'The Vidtreo API key has not been configured. Please contact your site administrator.';
$string['error:nobackendurl'] = 'The Vidtreo backend URL has not been configured. Please contact your site administrator.';
$string['norecording'] = 'No video recording submitted.';
$string['error:no_recording'] = 'You must record a video before submitting.';
$string['error:recorder_load_failed'] = 'Failed to load the Vidtreo recorder. Please check your internet connection and try again.';

$string['autosave:saving'] = 'Saving...';
$string['autosave:saved'] = 'Auto-saved successfully';
$string['autosave:error'] = 'Auto-save failed: {$a}';

$string['privacy:metadata:local_vidtreo_recordings'] = 'Stores video recording data for all Vidtreo activities across Moodle modules.';
$string['privacy:metadata:recording_id'] = 'The unique identifier of the video recording stored on Vidtreo servers.';
$string['privacy:metadata:public_id'] = 'The public identifier of the video recording used for playback URL construction.';
$string['privacy:metadata:duration'] = 'The duration of the video recording in seconds.';
$string['privacy:metadata:status'] = 'The processing status of the video recording (complete or error).';
$string['privacy:metadata:metadata'] = 'Additional metadata about the recording such as file size, MIME type, and user agent.';
$string['privacy:externalsystem'] = 'Video recordings are stored on the Vidtreo cloud platform. The recording file, along with the recording identifier and associated metadata, is transmitted to Vidtreo for processing and storage.';

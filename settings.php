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
 * Admin settings for assignsubmission_vidtreo.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext(
        'assignsubmission_vidtreo/apikey',
        get_string('apikey', 'assignsubmission_vidtreo'),
        get_string('apikey_desc', 'assignsubmission_vidtreo'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'assignsubmission_vidtreo/backendurl',
        get_string('backendurl', 'assignsubmission_vidtreo'),
        get_string('backendurl_desc', 'assignsubmission_vidtreo'),
        'https://core.vidtreo.com',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'assignsubmission_vidtreo/cdnurl',
        get_string('cdnurl', 'assignsubmission_vidtreo'),
        get_string('cdnurl_desc', 'assignsubmission_vidtreo'),
        'https://cdn.jsdelivr.net/npm/@vidtreo/recorder-wc@latest/dist/vidtreo-recorder.js',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'assignsubmission_vidtreo/maxrecordingtime',
        get_string('maxrecordingtime', 'assignsubmission_vidtreo'),
        get_string('maxrecordingtime_desc', 'assignsubmission_vidtreo'),
        300,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'assignsubmission_vidtreo/enablesourceswitching',
        get_string('enablesourceswitching', 'assignsubmission_vidtreo'),
        get_string('enablesourceswitching_desc', 'assignsubmission_vidtreo'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'assignsubmission_vidtreo/enablepause',
        get_string('enablepause', 'assignsubmission_vidtreo'),
        get_string('enablepause_desc', 'assignsubmission_vidtreo'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'assignsubmission_vidtreo/player_cdnurl',
        get_string('player_cdnurl', 'assignsubmission_vidtreo'),
        get_string('player_cdnurl_desc', 'assignsubmission_vidtreo'),
        'https://cdn.jsdelivr.net/npm/@vidtreo/player-wc@latest/dist/vidtreo-player.js',
        PARAM_URL
    ));

}

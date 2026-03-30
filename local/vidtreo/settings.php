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
 * Admin settings for local_vidtreo.
 *
 * @package    local_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_vidtreo',
        get_string('pluginname', 'local_vidtreo')
    );

    $ADMIN->add('localplugins', $settings);
}

if ($settings && $ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext(
        'local_vidtreo/apikey',
        get_string('apikey', 'local_vidtreo'),
        get_string('apikey_desc', 'local_vidtreo'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_vidtreo/backendurl',
        get_string('backendurl', 'local_vidtreo'),
        get_string('backendurl_desc', 'local_vidtreo'),
        'https://core.vidtreo.com',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_vidtreo/cdnurl',
        get_string('cdnurl', 'local_vidtreo'),
        get_string('cdnurl_desc', 'local_vidtreo'),
        'https://cdn.jsdelivr.net/npm/@vidtreo/recorder-wc@latest/dist/vidtreo-recorder.js',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_vidtreo/player_cdnurl',
        get_string('player_cdnurl', 'local_vidtreo'),
        get_string('player_cdnurl_desc', 'local_vidtreo'),
        'https://cdn.jsdelivr.net/npm/@vidtreo/player-wc@latest/dist/vidtreo-player.js',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_vidtreo/maxrecordingtime',
        get_string('maxrecordingtime', 'local_vidtreo'),
        get_string('maxrecordingtime_desc', 'local_vidtreo'),
        300,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_vidtreo/enablesourceswitching',
        get_string('enablesourceswitching', 'local_vidtreo'),
        get_string('enablesourceswitching_desc', 'local_vidtreo'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_vidtreo/enablepause',
        get_string('enablepause', 'local_vidtreo'),
        get_string('enablepause_desc', 'local_vidtreo'),
        1
    ));

}

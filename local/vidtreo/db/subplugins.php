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
 * Subplugin declaration for local_vidtreo.
 *
 * The type prefix 'vidtreomodule' must match exactly the prefix of each
 * subplugin component name. For example:
 *   - vidtreomodule_vidtreosubmission -> local/vidtreo/subplugins/vidtreosubmission/
 *
 * Note: qtype_vidtreo is NOT a subplugin of local_vidtreo.
 * It lives at question/type/vidtreo/ as a standalone question type plugin.
 *
 * @package    local_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$subplugins = ['vidtreomodule' => 'local/vidtreo/subplugins'];

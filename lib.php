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
 * Library functions
 *
 * @package assignsubmission_onlyoffice
 * @copyright 2020 Alex Paphitis <alex@paphitis.net>, Synergy Learning
 *  based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_onlyoffice\onlyoffice;
use assignsubmission_onlyoffice\util\crypt;

defined('MOODLE_INTERNAL') || die();

/**
 * Handle file serving
 * @param stdClass $course Course record
 * @param cm_info $cm Course module
 * @param context $context Context of the file
 * @param array $filearea File area
 * @param array $args Additional arguments
 * @param bool $forcedownload Whether or not to force downloading of this file
 * @param array $options Additional options
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function assignsubmission_onlyoffice_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Must be in a course module context.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return; // Not in a course mdoule context.
    }

    // User must provide a token or must be logged in.
    if ($documentjson = optional_param('doc', '', PARAM_TEXT)) {
        $usingtoken = true;
        crypt::decode($documentjson);
    } else {
        $usingtoken = false;
        require_login($course, false, $cm); // Otherwise user must be logged in.
    }

    // Must be a file area.
    if (!in_array($filearea, onlyoffice::FILEAREAS)) {
        return; // Not a file area.
    }

    // Item ID is the group ID.
    $itemid = (int) array_shift($args);
    if ($itemid < 0) {
        return; // Invalid group ID.
    }

    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args);
    if ($filepath !== '/') {
        $filepath .= '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, onlyoffice::COMPONENT_NAME, $filearea, $itemid, $filepath, $filename);

    // Check file exists.
    if (!$file) {
        return; // File does not exist.
    }

    // We'll have to force downloading if using a token for OnlyOffice to be able to grab the file.
    if ($usingtoken) {
        $forcedownload = true;
    }

    // Send back the file.
    send_stored_file($file, null, 0, $forcedownload, $options);
}
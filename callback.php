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
 * Endpoint callback for OnlyOffice
 *
 * @package assignsubmission_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_onlyoffice\api;
use assignsubmission_onlyoffice\record\onlyoffice_document;
use assignsubmission_onlyoffice\util\crypt;

define('AJAX_SCRIPT', true);

require_once(__DIR__.'/../../../../config.php');

global $CFG;
require_once($CFG->libdir.'/filelib.php');

// Headers.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
header('Content-Encoding: UTF-8');
header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
header('Expires: ' . gmdate('D, d M Y H:i:s', 0) . 'GMT');
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache");

// Request.
$request = file_get_contents('php://input');

// Document.
$doc = crypt::decode(required_param('doc', PARAM_TEXT));
$document = new onlyoffice_document($doc->assignmentid, $doc->submissionid);

// Handle the request.
$api = new api($document);
$response = json_encode($api->handle_request($request));
die($response);

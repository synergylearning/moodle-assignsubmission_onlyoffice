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
 * Language strings (English)
 *
 * @package   assignsubmission_onlyoffice
 * @copyright Alex Paphitis <alex@paphitis.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cannotchange'] = 'OnlyOffice format settings cannot be changed once a submission has been started';
$string['defaultformat'] = 'Default format';
$string['defaultinitialtext'] = 'Default initial text';
$string['documentserversecret'] = 'Document Server Secret';
$string['documentserversecretdesc'] = 'The secret is used to generate the token (an encrypted signature) in the browser for the document editor opening and calling the methods and the requests to the document command service and document conversion service. The token prevents the substitution of important parameters in ONLYOFFICE Document Server requests.';
$string['documentserverurl'] = 'Document Editing Service Address';
$string['documentserverurldesc'] = 'The Document Editing Service Address specifies the address of the server with the document services installed. Please replace \'https://documentserver.url\' above with the correct server address';
$string['enabled'] = 'OnlyOffice submissions';
$string['enabled_help'] = 'If enabled, students are able to use OnlyOffice for their submission.';
$string['eventassessableuploaded'] = 'A file has been uploaded.';
$string['fileareadesc'] = '{$a} files Area';
$string['format'] = 'OnlyOffice format';
$string['height'] = 'OnlyOffice height';
$string['initialfile'] = 'OnlyOffice initial file';
$string['initialfilemissing'] = 'The teacher who created this assignment did not upload an initial file to start your submission from. You cannot start a submission until this file has been uploaded.';
$string['initialtext'] = 'OnlyOffice initial text';
$string['logmessage'] = 'A submission file exists.';
$string['missingfile'] = 'Missing file';
$string['nosubmission'] = 'Nothing has been submitted for this assignment';
$string['overridetemplatepresentation'] = 'Override default template for presentations';
$string['overridetemplatespreadsheet'] = 'Override default template for spreadsheets';
$string['overridetemplateworddocument'] = 'Override default template for word documents';
$string['pluginname'] = 'OnlyOffice submissions';
$string['presentation'] = 'Presentation';
$string['requiredfortext'] = 'Required when the format is \'Specified text\'';
$string['requiredforupload'] = 'Required when the format is \'File upload\'';
$string['returntodocument'] = 'Return to course page';
$string['serveroffline'] = 'Document server is offline, in offline mode you can download the file if we have a copy';
$string['spreadsheet'] = 'Spreadsheet';
$string['submissionsubmitted'] = 'Submission submitted';
$string['text'] = 'Specified text';
$string['unsupportedtype'] = 'Unsupported filetype {$a}';
$string['upload'] = 'File upload';
$string['width'] = 'OnlyOffice width';
$string['wordprocessor'] = 'Wordprocessor document';


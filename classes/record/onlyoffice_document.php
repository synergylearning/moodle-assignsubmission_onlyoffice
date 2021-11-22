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
 * OnlyOffice document
 *
 * @package assignsubmission_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_onlyoffice\record;

use assign;
use coding_exception;
use context;
use context_module;
use context_system;
use dml_exception;
use file_exception;
use assignsubmission_onlyoffice\onlyoffice;
use assignsubmission_onlyoffice\util\crypt;
use moodle_exception;
use moodle_url;
use stdClass;
use stored_file;
use stored_file_creation_exception;

defined('MOODLE_INTERNAL') || die();

class onlyoffice_document {
    /** @var string Table name for a document */
    private const TABLE_DOCUMENT = 'assignsubmission_onlyoffice';

    /** @var int Length of the document key */
    const DOCUMENT_KEY_LENGTH = 20;

    /** @var context $context Context */
    public $context;

    /** @var assign $assignment Assignment */
    public $assignment;

    /** @var stdClass $submission Submission */
    public $submission;

    /** @var stdClass $documentrecord Document record */
    private $documentrecord;

    /** @var stored_file $file File */
    public $file;

    /**
     * onlyoffice_document constructor.
     * @param stdClass|int $assignmentorid ID of the assignment or the assignment object
     * @param stdClass|int $submissionorid ID of the submission or the submission object
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function __construct($assignmentorid, $submissionorid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        // Assignment.
        if (is_numeric($assignmentorid)) {
            list($course, $cm) = get_course_and_cm_from_instance($assignmentorid, 'assign');
            $context = context_module::instance($cm->id);
            $this->assignment = new assign($context, $cm, $course);
        } else {
            $this->assignment = $assignmentorid;
        }

        $this->assignmentconfig = $this->get_assignment_config();

        // Submission.
        if (is_numeric($submissionorid)) {
            $params = ['assignment' => $this->assignment->get_instance()->id, 'id' => $submissionorid];
            $this->submission = $DB->get_record('assign_submission', $params, '*', MUST_EXIST);
        } else {
            $this->submission = $submissionorid;
        }

        // Context.
        $this->context = $this->assignment->get_context();

        // Document and file.
        $this->documentrecord = $this->load_document();
        $this->file = $this->load_file();
    }

    /**
     * Load the document record for this activity instance and group
     * @return stdClass Document record
     * @throws dml_exception
     */
    private function load_document(): stdClass {
        global $DB;

        // Try get the existing record, otherwise create it.
        $params = ['assignment' => $this->assignment->get_instance()->id, 'submission' => $this->submission->id];
        if (!$record = $DB->get_record(self::TABLE_DOCUMENT, $params)) {
            return $this->create_document_record(); // Doesn't exist, create it.
        }

        return $record;
    }

    /**
     * Create the individual document record
     * @return stdClass Individual document record
     * @throws dml_exception
     */
    private function create_document_record(): stdClass {
        global $DB;

        $record = (object)[
            'assignment' => $this->assignment->get_instance()->id,
            'submission' => $this->submission->id,
            'userid' => $this->submission->userid ?? 0,
            'groupid' => $this->submission->groupid ?? 0,
            'documentkey' => self::generate_document_key(),
        ];

        $record->id = $DB->insert_record(self::TABLE_DOCUMENT, $record);
        return $record;
    }

    /**
     * Update the current document key
     * @param string|null $key New key to set (Optional, will generate a key if not provided)
     * @throws dml_exception
     */
    private function update_document_key(string $key = null): void {
        global $DB;
        $key = $key ?? self::generate_document_key();
        $DB->set_field(self::TABLE_DOCUMENT, 'documentkey', $key, ['id' => $this->documentrecord->id]);
    }

    /**
     * Generate a document key
     * @return string Newly generated document key
     */
    public static function generate_document_key(): string {
        return random_string(self::DOCUMENT_KEY_LENGTH);
    }

    /**
     * Load the file for this document
     * @return stored_file Stored file tied to the document
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    private function load_file(): stored_file {
        $fs = get_file_storage();

        // Use the correct file area and item ID.
        $filearea = onlyoffice::FILEAREA_SUBMISSIONS;
        $itemid = (int) $this->submission->id;

        // Get the first file.
        $files = $fs->get_area_files($this->context->id, onlyoffice::COMPONENT_NAME, $filearea,
            $itemid, '', false, 0, 0, 1);

        // Check whether the file exists.
        if (!$file = reset($files)) {
            return $this->create_file(); // File doesn't exist, create it.
        }

        return $file;
    }

    /**
     * Update the current file
     * @param stored_file $newfiletemp File to use for overwriting the current one
     * @throws coding_exception|dml_exception
     */
    public function update_file(stored_file $newfiletemp): void {
        // Replace the current file with the new one and delete the temporary file.
        $this->file->replace_file_with($newfiletemp);
        $this->file->set_timemodified(time());
        $newfiletemp->delete();
        $this->update_document_key();
    }

    /**
     * Create the file depending on the format
     * @return stored_file Newly created file
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    private function create_file(): stored_file {
        $format = $this->assignmentconfig->format;

        switch ($format) {
            case onlyoffice::FORMAT_UPLOAD:
                return $this->create_file_initial_upload();
            case onlyoffice::FORMAT_TEXT:
                return $this->create_file_initial_text();
            case onlyoffice::FORMAT_PRESENTATION:
            case onlyoffice::FORMAT_WORDPROCESSOR:
            case onlyoffice::FORMAT_SPREADSHEET:
                return $this->create_file_from_template();
            default:
                throw new coding_exception("Unknown format: {$format}");
        }
    }

    /**
     * Get the file template we're using
     * @return stored_file File for the template
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    private function create_file_from_template(): stored_file {
        global $CFG;

        $templatesitemids = [
            onlyoffice::FORMAT_SPREADSHEET => ['itemid' => onlyoffice::FORMAT_SPREADSHEET_ITEM_ID, 'ext' => 'xlsx'],
            onlyoffice::FORMAT_WORDPROCESSOR => ['itemid' => onlyoffice::FORMAT_WORDPROCESSOR_ITEM_ID, 'ext' => 'docx'],
            onlyoffice::FORMAT_PRESENTATION => ['itemid' => onlyoffice::FORMAT_PRESENTATION_ITEM_ID, 'ext' => 'pptx'],
        ];

        // Template ID and extension for the given format.
        $format = $this->assignmentconfig->format;
        $itemid = $templatesitemids[$format]['itemid'];
        $name = $this->assignment->get_instance()->name;
        $ext = $templatesitemids[$format]['ext'];

        // Try get get the overridden template file.
        $fs = get_file_storage();
        $ctx = context_system::instance();
        $files = $fs->get_area_files($ctx->id, onlyoffice::COMPONENT_NAME, onlyoffice::FILEAREA_TEMPLATES,
            $itemid, '', false, 0, 0, 1);

        // Check whether we have an overridden template file we can provide instead of the blank file.
        if ($templatefile = reset($files)) {
            // We have a template file, we'll make a copy of it.
            $filerec = $this->build_file_record();
            $filerec->filename = clean_filename(format_string("{$name}.{$ext}"));
            $file = $fs->create_file_from_storedfile($filerec, $templatefile);
            return $file; // We have an overridden template file.
        }

        // No overridden template, we'll create a copy of the blank file for this format.
        $filerec = $this->build_file_record();
        $filerec->filename = clean_filename(format_string("{$name}.{$ext}"));
        $filepath = "{$CFG->dirroot}/mod/assign/submission/onlyoffice/blankfiles/blank{$format}.{$ext}";
        $file = $fs->create_file_from_pathname($filerec, $filepath);

        return $file;
    }

    /**
     * Build the initial file record
     * @return stdClass File record
     */
    private function build_file_record(): stdClass {
        $name = $this->assignment->get_instance()->name;

        // Use the correct file area and item ID.
        $filearea = onlyoffice::FILEAREA_SUBMISSIONS;
        $itemid = (int) $this->submission->id;

        // Build the record.
        $filerec = (object)[
            'contextid' => $this->context->id,
            'component' => onlyoffice::COMPONENT_NAME,
            'filearea' => $filearea,
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => clean_filename(format_string($name)),
        ];

        return $filerec;
    }

    /**
     * Create a file from initial text
     * @return stored_file File created from the initial text
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    private function create_file_initial_text(): stored_file {
        $fs = get_file_storage();

        $content = $this->assignmentconfig->initialtext;
        $filerec = $this->build_file_record();
        $filerec->filename = "{$filerec->filename}.txt";

        return $fs->create_file_from_string($filerec, $content);

    }

    /**
     * Create a file from an initial upload
     * @return stored_file File created from the initial upload
     * @throws file_exception
     * @throws stored_file_creation_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function create_file_initial_upload(): stored_file {
        $fs = get_file_storage();

        // Build the file.
        $filerec = $this->build_file_record();
        $initfile = $this->get_initial_file();
        $ext = pathinfo($initfile->get_filename(), PATHINFO_EXTENSION);
        $filerec->filename = "{$filerec->filename}.$ext";

        // Create the file from our stored file.
        $file = $fs->create_file_from_storedfile($filerec, $initfile);
        return $file;
    }

    /**
     * Get the initial file
     * @return stored_file Initial file that was uploaded for this document
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function get_initial_file() {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, onlyoffice::COMPONENT_NAME, onlyoffice::FILEAREA_INITIAL,
            false, '', false, 0, 0, 1);

        // Initial file must exist.
        if (!$file = reset($files)) {
            throw new moodle_exception('initialfilemissing', onlyoffice::COMPONENT_NAME);
        }

        // File exists.
        return $file;
    }

    /**
     * Get the permissions for this document for the user
     * @return bool[] Permissions for this document
     */
    public function get_permissions(): array {
        return [
            'edit' => !$this->is_read_only(),
            'print' => true,
            'download' => true,
        ];
    }

    /**
     * Is this document read only? (Whether or not it can be edited)
     * @return bool Whether or not the document is read only
     */
    private function is_read_only(): bool {
        global $USER;

        // Site admins && graders (teachers/managers) cannot edit the file - irrespective of submission status.
        if (is_siteadmin() || $this->assignment->can_grade()) {
            return true; // Either a site admin or someone that can grade (teachers/managers) => Is read only.
        }

        // Check whether submission are open, if not, it's read only.
        if (!$this->assignment->submissions_open($USER->id, null, $this->submission)) {
            return true; // Submission is not open => Read only.
        }

        // Not read only.
        return false;
    }

    /**
     * Get the key for this document
     * @return string Key for this document
     */
    public function get_key(): string {
        return $this->documentrecord->documentkey;
    }

    /**
     * Get the external URL to download the file
     * @return moodle_url URL for accessing this file externally
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_external_download_url(): moodle_url {
        // File metadata.
        $filename = $this->file->get_filename();
        $filepath = $this->file->get_filepath();

        // Use the correct file area and item ID.
        $filearea = onlyoffice::FILEAREA_SUBMISSIONS;
        $itemid = (int) $this->submission->id;

        // Document URL.
        $params = ['assignmentid' => $this->assignment->get_instance()->id, 'submissionid' => $this->submission->id];
        $params = crypt::encode_and_sign($params);
        $url = "/pluginfile.php/{$this->context->id}/assignsubmission_onlyoffice/{$filearea}/{$itemid}{$filepath}{$filename}";

        $documenturl = new moodle_url($url, ['doc' => $params]);
        return $documenturl;
    }

    /**
     * Get config for the assignment
     * @return stdClass Config for the assignment
     * @throws dml_exception
     */
    private function get_assignment_config(): stdClass {
        global $DB;

        $dbparams = [
            'assignment' => $this->assignment->get_instance()->id,
            'subtype' => 'assignsubmission',
            'plugin' => 'onlyoffice',
        ];
        $results = $DB->get_records('assign_plugin_config', $dbparams);

        $config = new stdClass();

        if (is_array($results)) {
            foreach ($results as $setting) {
                $name = $setting->name;
                $config->$name = $setting->value;
            }
        }

        return $config;
    }
}

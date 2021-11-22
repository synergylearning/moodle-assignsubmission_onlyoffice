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
 * OnlyOffice submission type
 *
 * @package assignsubmission_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_file\event\assessable_uploaded;
use assignsubmission_onlyoffice\editor;
use assignsubmission_onlyoffice\event\submission_created;
use assignsubmission_onlyoffice\event\submission_updated;
use assignsubmission_onlyoffice\onlyoffice;
use assignsubmission_onlyoffice\record\onlyoffice_document;

defined('MOODLE_INTERNAL') || die();

class assign_submission_onlyoffice extends assign_submission_plugin {
    /**
     * The default file options.
     * @return array of file options.
     */
    private function get_default_fileoptions(): array {
        return [
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => 1,
            'accepted_types' => onlyoffice::get_accepted_types(),
        ];
    }

    /**
     * @param stdClass $submission Submission object
     * @return object|StdClass File record
     */
    private function get_submission_file_record(stdClass $submission) {
        // Grab the file from the submissions file area.
        $filearea = onlyoffice::FILEAREA_SUBMISSIONS;
        $itemid = $submission->id;
        $filerec = $this->generate_file_record(null, $filearea, $itemid);

        return $filerec;
    }

    /**
     * Get the files for a submission
     * @param stdClass $submission Submission object
     * @return stored_file Files stored for this submission
     * @throws coding_exception
     */
    private function get_submission_file(stdClass $submission): ?stored_file {
        $fs = get_file_storage();
        $filerec = $this->get_submission_file_record($submission);

        $files = $fs->get_area_files(
            $filerec->contextid,
            $filerec->component,
            $filerec->filearea,
            $submission->id,
            '',
            false,
            0,
            0,
            1
        );

        // Check whether we have the submission file.
        if (!$file = reset($files)) {
            return null; // We don't have the submission file.
        }

        // We have the submission file.
        return $file;
    }

    /**
     * Function to return the file record object.
     *
     * @param string|null $filename - might be empty to be filled by caller.
     * @param string|null $filearea - the file area - default is the initial files area.
     * @param int|null $itemid - usually the user or group id except for initial files.
     * @param string|null $filepath - we don't use this for our plugin but might do in the future.
     * @return StdClass
     */
    private function generate_file_record($filename = null, $filearea = null, $itemid = null, $filepath = null) {
        $contextid = $this->assignment->get_context()->id;
        $itemid = $itemid ?? 0;
        $filepath = $filepath ?? '/';

        return (object)[
            'contextid' => $contextid,
            'component' => onlyoffice::COMPONENT_NAME,
            'filearea' => onlyoffice::FILEAREA_SUBMISSIONS,
            'itemid' => $itemid,
            'filepath' => $filepath,
            'filename' => $filename ? clean_filename($filename) : $filename
        ];
    }

    /**
     * Get the file link
     * @return string URL to the file
     * @throws coding_exception
     */
    private function get_initial_file_link(): string {
        $fs = get_file_storage();

        // Get all the files.
        $files = $fs->get_area_files(
            $this->assignment->get_context()->id,
            onlyoffice::COMPONENT_NAME,
            onlyoffice::FILEAREA_INITIAL,
            $this->assignment->get_instance()->id,
            '',
            false,
            0,
            0,
            1
        );

        // Try get the first file.
        if (!$file = reset($files)) {
            return get_string('missingfile', 'assignsubmission_onlyoffice'); // File doesn't exist.
        }

        // Build the URL.
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            true
        );

        return html_writer::link($url, $file->get_filename());
    }

    /**
     * Remove the submission file
     * @param $submission Submission object
     */
    private function remove_submission_file($submission): void {
        $fs = get_file_storage();
        $rec = $this->get_submission_file_record($submission);
        $fs->delete_area_files($rec->contextid, $rec->component, $rec->filearea, $rec->itemid);
    }

    /**
     * Get the HTML for the OnlyOffice container
     * @param onlyoffice_document $document Document object
     * @return string HTML for showing the document on the page
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function get_onlyoffice_container_html(onlyoffice_document $document): string {
        global $PAGE;

        // Config.
        $documentserverurl = onlyoffice::get_server_url();
        $config = $this->get_config();

        // Handle container width and height.
        $width = $config->width ?? null;
        $width = $width > 0 ? "{$width}px" : '100%';

        $height = $config->height ?? null;
        $height = $height > 0 ? "{$height}px" : '100vh'; // Whether to use fixed height given or height that takes up the viewport.

        // Document container.
        $html = '';
        $html .= html_writer::start_div('onlyoffice-container', ['style' => "width: $width; height: $height"]);
        $html .= html_writer::div('', '', ['id' => 'onlyoffice-editor-submission']); // This gets replaced with the iframe.
        $html .= html_writer::end_div();

        // Hidden input (Document config).
        $editor = new editor($document);
        $configstr = json_encode($editor->get_config());
        $html .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'config', 'value' => $configstr]);

        // Javascript required.
        $jsurl = "{$documentserverurl}/web-apps/apps/api/documents/api.js";
        $html .= html_writer::tag('script', '', ['type' => 'text/javascript', 'src' => $jsurl]);

        $PAGE->requires->js_call_amd('assignsubmission_onlyoffice/editor', 'init', ['scriptURL' => $jsurl]);

        return $html;
    }

    /**
     * Name of our plugin type
     * @return string - Name of our plugin
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('pluginname', 'assignsubmission_onlyoffice');
    }

    /**
     * Lock the format settings once there is a submission for the assignment.
     * @return bool
     */
    private function has_any_submissions(): bool {
        global $DB;
        if (!$this->assignment->has_instance()) {
            return false; // No assignment created yet => no submissions.
        }
        // Look to see if any submission files have been created.
        $context = $this->assignment->get_context();
        $cond = [
            'contextid' => $context->id,
            'component' => onlyoffice::COMPONENT_NAME,
            'filearea' => onlyoffice::FILEAREA_SUBMISSIONS,
        ];
        return $DB->record_exists('files', $cond);
    }

    /**
     * Get the default settings
     * @param MoodleQuickForm $mform Moodle form object
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_settings(MoodleQuickForm $mform) {
        $prefix = "assignsubmission_onlyoffice";

        $config = $this->assignment->has_instance() ? $this->get_config() : (object)[];
        $format = $config->format ?? null;
        $height = $config->height ?? 0;
        $width = $config->width ?? 0;

        $hassubmission = $this->has_any_submissions();

        // Format section.
        $mform->addElement('select', "{$prefix}_format",
            get_string('format', 'assignsubmission_onlyoffice'), onlyoffice::get_format_menu());
        $mform->setDefault("{$prefix}_format", onlyoffice::FORMAT_UPLOAD);
        $mform->hideIf("{$prefix}_format", "{$prefix}_enabled");

        // Format cannot be changed once a submission has been created.
        if ($hassubmission) {
            $mform->freeze("{$prefix}_format"); // Instance created, freeze format.
            $mform->addElement('static', "{$prefix}_cannotchange", '', get_string('cannotchange', 'assignsubmission_onlyoffice'));
        }

        // For new instances we'll show an empty file picker with no file.
        if (!$hassubmission) {
            $mform->addElement('filemanager', "{$prefix}_initialfile_filemanager",
                get_string('initialfile', 'assignsubmission_onlyoffice'), null,
                $this->get_default_fileoptions());
            $mform->hideIf("{$prefix}_initialfile_filemanager", "{$prefix}_format", 'neq', onlyoffice::FORMAT_UPLOAD);
            $mform->hideIf("{$prefix}_initialfile_filemanager", "{$prefix}_enabled");
        }

        // Get the file for an instance that already exists and is using the file upload format.
        // Once the file has been uploaded it cannot be changed.
        if ($hassubmission && $format === onlyoffice::FORMAT_UPLOAD) {
            $mform->addElement('static', "{$prefix}_initialfile",
                get_string('initialfile', 'assignsubmission_onlyoffice'),
                $this->get_initial_file_link());
        }

        // Add the initial text area when the instance does not already exist or if the format is text.
        if (!$hassubmission || $format === onlyoffice::FORMAT_TEXT) {
            $mform->addElement('textarea', "{$prefix}_initialtext", get_string('initialtext', 'assignsubmission_onlyoffice'));
            $mform->setDefault("{$prefix}_initialtext", onlyoffice::get_default_initial_text());
            $mform->hideIf("{$prefix}_initialtext", "{$prefix}_format", 'neq', onlyoffice::FORMAT_TEXT);
            $mform->hideIf("{$prefix}_initialtext", "{$prefix}_enabled");
        }

        // IFRAME Width.
        $mform->addElement('text', "{$prefix}_width", get_string('width', 'assignsubmission_onlyoffice'));
        $mform->setDefault("{$prefix}_width", $width);
        $mform->setType("{$prefix}_width", PARAM_INT);
        $mform->hideIf("{$prefix}_width", "{$prefix}_enabled");

        // IFRAME Height.
        $mform->addElement('text', "{$prefix}_height", get_string('height', 'assignsubmission_onlyoffice'));
        $mform->setDefault("{$prefix}_height", $height);
        $mform->setType("{$prefix}_height", PARAM_INT);
        $mform->hideIf("{$prefix}_height", "{$prefix}_enabled");
    }

    public function data_preprocessing(&$defaultvalues) {
        if (!$this->assignment->has_instance()) {
            return; // Assignment does not yet exist.
        }
        $assign = $this->assignment->get_instance();
        $context = $this->assignment->get_context();
        $fieldname = 'assignsubmission_onlyoffice_initialfile';
        $data = file_prepare_standard_filemanager(
            (object)$defaultvalues,
            $fieldname,
            $this->get_default_fileoptions(),
            $context,
            onlyoffice::COMPONENT_NAME,
            onlyoffice::FILEAREA_INITIAL,
            $assign->id
        );
        $defaultvalues[$fieldname.'_filemanager'] = $data->{$fieldname.'_filemanager'};
    }

    /**
     * Save the settings for submission plugin.
     *
     * @param stdClass $data - the form data.
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save_settings(stdClass $data) {
        // Width.
        $width = $data->assignsubmission_onlyoffice_width ?? 0;
        $this->set_config('width', $width);

        // Height.
        $height = $data->assignsubmission_onlyoffice_height ?? 0;
        $this->set_config('height', $height);

        // These settings can only be set when the instance doesn't exist.
        if ($this->has_any_submissions()) {
            return true; // Already exists, nothing left for us to do.
        }

        // We set these settings only when this instance is new.

        // Format.
        $format = $data->assignsubmission_onlyoffice_format;
        $this->set_config('format', $format);

        // Initial text.
        $initialtext = $data->assignsubmission_onlyoffice_initialtext ?? '';
        $initialtext = $format ?? onlyoffice::FORMAT_TEXT ? $initialtext : '';
        $this->set_config('initialtext', $initialtext);

        // Check whether our format is for a file.
        if ($format !== onlyoffice::FORMAT_UPLOAD) {
            return true;
        }

        // We're using the upload format, save the file.
        $context = $this->assignment->get_context();
        file_postupdate_standard_filemanager(
            $data,
            'assignsubmission_onlyoffice_initialfile',
            $this->get_default_fileoptions(),
            $context,
            onlyoffice::COMPONENT_NAME,
            onlyoffice::FILEAREA_INITIAL,
            $this->assignment->get_instance()->id
        );

        return true;
    }

    /**
     * View the submission summary and sets whether a view link be provided.
     *
     * @param stdClass $submission Submission object
     * @param bool $showviewlink - whether or not to have a link to view the submission file.
     * @return string view text.
     * @throws coding_exception
     */
    public function view_summary(stdClass $submission, &$showviewlink) {
        global $DB;
        $showviewlink = false; // Default do not show view link.

        if ($this->is_empty($submission)) {
            return get_string('nosubmission', 'assignsubmission_onlyoffice');
        }

        // Decide whether to show the view link or not.
        if ($this->assignment->can_grade()) {
            $showviewlink = true; // A grader can view the link.
        } else if (!$this->assignment->can_edit_submission($submission->userid)) {
            $showviewlink = true; // If we cannot see the submission any other way - i.e. it is not editable.
        }

        // Confirm status has been set.
        if (empty($submission->status)) {   // Grading table doesn't include the status ...
            $submission->status = $DB->get_field('assign_submission', 'status', ['id' => $submission->id]);
            if (!$submission->status) {
                return '**:'.get_string('submissionsubmitted', 'assignsubmission_onlyoffice');
            }
        }

        // Status.
        return ucfirst($submission->status);
    }

    /**
     * Save any custom data for this form submission.
     *
     * @param stdClass $submission Submission object
     * @param stdClass $data - the data submitted from the form
     * @return bool - on error then we set error and return false.
     * @throws coding_exception|dml_exception
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        // The assessable_uploaded event.
        $params = [
            'context' => context_module::instance($this->assignment->get_course_module()->id),
            'courseid' => $this->assignment->get_course()->id,
            'objectid' => $submission->id,
            'other' => [
                'content' => '',
                'pathnamehashes' => [
                    $data->submpathnamehash,
                ]
            ],
        ];

        if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
            $params['relateduserid'] = $submission->userid;
        } else {
            $params['userid'] = $submission->userid;
        }

        $event = assessable_uploaded::create($params);
        $event->trigger();

        $groupname = null;
        $groupid = 0;

        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submission->userid) && !empty($submission->groupid)) {
            $groupname = $DB->get_field('groups', 'name', ['id' => $submission->groupid], MUST_EXIST);
            $groupid = $submission->groupid;
        } else {
            $params['relateduserid'] = $submission->userid;
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']); // We do not use this as we do not have a separate table.
        unset($params['other']);

        $params['other'] = [
            'submissionid' => $submission->id,
            'submissionattempt' => $submission->attemptnumber,
            'submissionstatus' => $submission->status,
            'submissionfilename' => $data->submfilename,
            'submpathnamehash' => $data->submpathnamehash,
            'groupid' => $groupid,
            'groupname' => $groupname,
        ];

        // Create either a 'New submission' event or 'Updated submission' event.
        $event = $data->subnewsubmssn ? submission_created::create($params) : submission_updated::create($params);
        $event->set_assign($this->assignment);
        $event->trigger();

        return true;
    }

    /**
     * Produce a list of files suitable for export that represent this submission.
     * @param stdClass $submission Submission object
     * @param stdClass $user User object
     * @return array - return an array of files indexed by filename
     * @throws coding_exception
     */
    public function get_files(stdClass $submission, stdClass $user) {
        $result = [];

        // Check whether there is a file.
        if (!$file = $this->get_submission_file($submission)) {
            return $result; // No files, nothing left for us to do.
        }

        // Do we return the full folder path or just the file name?
        if (isset($submission->exportfullpath) && $submission->exportfullpath == false) {
            $result[$file->get_filename()] = $file;
        } else {
            $result[$file->get_filepath().$file->get_filename()] = $file;
        }

        return $result;
    }

    /**
     * Get form elements to display to user submitting
     * @param mixed $submission Submission object
     * @param MoodleQuickForm $mform Moodle form
     * @param stdClass $data Form data
     * @param null $userid User ID (Optional) In this case itś not used
     * @return bool
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data, $userid = null) {
        $isnewsubmission = true;
        $document = new onlyoffice_document($this->assignment, $submission);

        // OnlyOffice container.
        $html = $this->get_onlyoffice_container_html($document);
        $mform->addElement('html', $html);

        // Some hidden fields for the save event.

        // Submission file name - Hidden.
        $mform->addElement('hidden', 'submfilename', $document->file->get_filename());
        $mform->setType('submfilename', PARAM_TEXT);

        // Submission pathname hash - Hidden.
        $mform->addElement('hidden', 'submpathnamehash', $document->file->get_pathnamehash());
        $mform->setType('submpathnamehash', PARAM_RAW);

        // Whether this is a new submission - Hidden.
        $mform->addElement('hidden', 'subnewsubmssn', $isnewsubmission);
        $mform->setType('subnewsubmssn', PARAM_INT);

        return true;
    }

    /**
     * View submission - the submission file will always be read only.
     * @param stdClass $submission Submission object
     * @return string - html frame of the submitted file.
     * @throws coding_exception
     */
    public function view(stdClass $submission): string {
        // We must have a file.
        if (!$submissionfile = $this->get_submission_file($submission)) {
            throw new coding_exception('Missing submission file'); // Should never happen.
        }

        $document = new onlyoffice_document($this->assignment, $submission);
        return $this->get_onlyoffice_container_html($document);
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     * @param string $type The old assignment subtype
     * @param int $version The old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version): bool {
        return false;
    }

    /**
     * Formatting for log info.
     * @param stdClass $submission The submission
     * @return string Log message
     * @throws coding_exception
     */
    public function format_for_log(stdClass $submission) {
        return get_string('logmessage', 'assignsubmission_onlyoffice');
    }

    /**
     * Get file areas - returns a list of areas where this plugin stores files
     * @return array - An array of file areas (keys) and descriptions (values)
     * @throws coding_exception
     */
    public function get_file_areas(): array {
        return [
            onlyoffice::FILEAREA_INITIAL => ucfirst(get_string('fileareadesc',
                'assignsubmission_onlyoffice', onlyoffice::FILEAREA_INITIAL)),
            onlyoffice::FILEAREA_SUBMISSIONS => ucfirst(get_string('fileareadesc',
                'assignsubmission_onlyoffice', onlyoffice::FILEAREA_SUBMISSIONS)),
        ];
    }

    /**
     * Copy the plugin specific submission data to a new submission record.
     * @param stdClass $sourcesubmission - Old submission record
     * @param stdClass $destsubmission - New submission record
     * @return bool
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission): bool {
        return true; // File will always remain the same.
    }

    /**
     * The assignment has been deleted - remove the plugin specific data
     * @return bool Whether the assignment has been deleted
     * @throws dml_exception
     */
    public function delete_instance(): bool {
        global $DB;

        // Delete documents related to instance.
        $DB->delete_records('assignsubmission_onlyoffice', ['assignment' => $this->assignment->get_instance()->id]);

        return true;
    }

    /**
     * If true, the plugin will appear on the module settings page and can be enabled/disabled per assignment instance.
     * @return bool Always show on the module settings page
     */
    public function is_configurable(): bool {
        return true;
    }

    /**
     * Is this assignment plugin empty? (i.e no submission)
     * @param stdClass $submission assign_submission.
     * @return bool Whether or not the assignment is empty
     * @throws coding_exception
     */
    public function is_empty(stdClass $submission): bool {
        if (!$file = $this->get_submission_file($submission)) {
            return true; // No file returned.
        }

        // Got the file, submission is not empty.
        return false;
    }

    /**
     * Determine if a submission is empty
     * @param stdClass $data The submission data
     * @return bool Whether or not the submission is empty (Never empty)
     */
    public function submission_is_empty(stdClass $data): bool {
        return false;
    }

    /**
     * Remove any saved data from this submission.
     * @param stdClass $submission - assign_submission data
     * @return void
     * @since Moodle 3.6
     */
    public function remove(stdClass $submission): void {
        $this->remove_submission_file($submission);
    }
}

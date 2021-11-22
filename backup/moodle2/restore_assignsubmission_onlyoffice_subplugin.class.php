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
 * @author Alex Paphitis <alex@paphitis.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_onlyoffice\onlyoffice;

defined('MOODLE_INTERNAL') || die();

class restore_assignsubmission_onlyoffice_subplugin extends restore_subplugin {
    /**
     * Returns the paths to be handled by the subplugin at workshop level
     * @return array Paths to be handled
     */
    protected function define_submission_subplugin_structure() {
        $paths = [];

        $elename = $this->get_namefor('submission');
        $elepath = $this->get_pathfor('/submission_onlyoffice');

        // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Processes one submission_onlyoffice element
     * @param mixed $data Data for restoring
     * @return void
     * @throws dml_exception
     */
    public function process_assignsubmission_onlyoffice_submission($data) {
        global $DB;

        $data = (object) $data;

        // Keep old IDs for mapping files.
        $oldsubmissionid = $data->submission;
        $olduserid = $data->userid;
        $oldgroupid = $data->groupid;

        // Build the new record.
        $data->assignment = $this->get_new_parentid('assign');
        $data->submission = $this->get_mappingid('submission', $data->submission);
        $data->groupid = $data->groupid ? $this->get_mappingid('group', $data->groupid) : 0;
        $data->userid = $data->userid ? $this->get_mappingid('user', $data->userid) : 0;
        $data->documentkey = \assignsubmission_onlyoffice\record\onlyoffice_document::generate_document_key();

        // Add our record.
        $DB->insert_record('assignsubmission_onlyoffice', $data);

        // Set mappings.
        $this->set_mapping('onlyoffice_group', $oldgroupid, $data->groupid, true);
        $this->set_mapping('onlyoffice_user', $olduserid, $data->userid, true);

        // Add OnlyOffice related files.
        $this->add_related_files('assignsubmission_onlyoffice', onlyoffice::FILEAREA_INITIAL, null);
        $this->add_related_files('assignsubmission_onlyoffice', onlyoffice::FILEAREA_SUBMISSIONS,
            'submission', null, $oldsubmissionid);
    }
}

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
 * This file contains the class for backup of this submission plugin
 *
 * @package assignsubmission_onlyoffice
 * @copyright 2019 Synergy Learning {@link https://www.synergy-learning.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_onlyoffice\onlyoffice;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the information to backup submission files
 * This just adds its filearea to the annotations and records the number of files
 *
 * @package assignsubmission_onlyoffice
 * @copyright 2019 Synergy Learning {@link https://www.synergy-learning.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_assignsubmission_onlyoffice_subplugin extends backup_subplugin {
    /**
     * Returns the subplugin information to attach to submission element
     * @return backup_subplugin_element Backup element
     * @throws base_element_struct_exception
     */
    protected function define_submission_subplugin_structure() {
        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subpluginelement = new backup_nested_element(
            'submission_onlyoffice',
            null,
            ['submission', 'groupid', 'userid', 'documentkey']
        );

        // Define ID annotations.
        $subpluginelement->annotate_ids('group', 'groupid');
        $subpluginelement->annotate_ids('user', 'userid');

        // Connect XML elements into the tree.
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subpluginelement);

        // Set source to populate the data.
        $subpluginelement->set_source_table('assignsubmission_onlyoffice', ['submission' => backup::VAR_PARENTID]);

        // Define file annotations.
        $subpluginelement->annotate_files('assignsubmission_onlyoffice', onlyoffice::FILEAREA_SUBMISSIONS, 'submission');

        return $subplugin;
    }
}

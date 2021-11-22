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
 * Event for submission created
 *
 * @package assignsubmission_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_onlyoffice\event;

use coding_exception;

defined('MOODLE_INTERNAL') || die ();

class submission_updated extends \mod_assign\event\submission_updated {
    /**
     * Returns description of what happened
     * @return string Description of the event
     */
    public function get_description() {
        $a = (object)[
            'userid' => $this->userid,
            'submissionfilename' => $this->other['submissionfilename'],
            'contextinstanceid' => $this->contextinstanceid,
        ];

        // Description for group submission.
        if ($this->other['groupid']) {
            $a->groupname = $this->other['groupname'];
            $a->groupid = $this->other['groupid'];

            // Language string should not be used here.
            return "The user with ID ({$a->userid}) updated a OnlyOffice submission file named " /
                "({$a->submissionfilename}) in the assignment with course module id ({$a->contextinstanceid}) for the" /
                " group ({$a->groupname}) group ID ({$a->groupid})";
        }

        // Description for user submission.
        // Language string should not be used here.
        return "The user with ID ({$a->userid}) updated a OnlyOffice submission file named ({$a->submissionfilename})" /
            "in the assignment with course module id ({$a->contextinstanceid})";
    }

    /**
     * Custom validation.
     *
     * @return void
     * @throws coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['submpathnamehash'])) {
            throw new coding_exception('The \'submpathnamehash\' value must be set in other.');
        }

        if (!isset($this->other['submissionfilename'])) {
            throw new coding_exception('The \'submissionfilename\' value must be set in other.');
        }
    }

}

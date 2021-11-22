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
 * Event for assessable uploaded
 *
 * @package assignsubmission_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_onlyoffice\event;

use coding_exception;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class assessable_uploaded extends \core\event\assessable_uploaded {
    /**
     * Returns description of what happened.
     * @return string Description of the event
     */
    public function get_description() {
        $a = (object)[
            'userid' => $this->userid,
            'objectid' => $this->objectid,
            'contextinstanceid' => $this->contextinstanceid,
        ];

        // Language string shouldn't be used here.
        return "The user with id ({$a->userid}) has uploaded a file to the submission with id ({$a->objectid}) in the" /
            "assignment activity with course module id ({$a->contextinstanceid})";
    }

    /**
     * Return localised event name.
     * @return string Localised event name
     * @throws coding_exception
     */
    public static function get_name() {
        return get_string('eventassessableuploaded', 'assignsubmission_onlyoffice');
    }

    /**
     * Get URL related to the action.
     * @return moodle_url URL for the action to give further context
     * @throws moodle_exception
     */
    public function get_url(): moodle_url {
        return new moodle_url('/mod/assign/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Init method
     * @return void
     */
    protected function init(): void {
        parent::init();
        $this->data['objecttable'] = 'assign_submission';
    }

    /**
     * Object mappings for assessable uploaded event.
     * @return array Mapping for restoring
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'assign_submission',
            'restore' => 'submission',
        ];
    }
}

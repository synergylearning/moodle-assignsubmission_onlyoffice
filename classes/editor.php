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

namespace assignsubmission_onlyoffice;

use coding_exception;
use core_useragent;
use dml_exception;
use assignsubmission_onlyoffice\record\onlyoffice_document;
use assignsubmission_onlyoffice\util\crypt;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class editor {
    /** @var onlyoffice_document */
    private $document;

    /**
     * editor constructor.
     * @param onlyoffice_document $document
     */
    public function __construct(onlyoffice_document $document) {
        $this->document = $document;
    }

    /**
     * Get the config
     * @return array Config
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_config(): array {
        // Document server secret.
        $documentserversecret = onlyoffice::get_secret_key();

        // File and document server secret are required.
        if (!$this->document->file || !$documentserversecret) {
            return [];
        }

        // Build our config.
        $config = [
            'type' => $this->get_device_type(),
            'document' => $this->get_document_config(),
            'editorConfig' => $this->get_editor_config(),
        ];

        // Add our token.
        $config['token'] = crypt::encode_and_sign($config);

        return $config;
    }

    /**
     * Get config values for the document
     * @return array Part of config - Document key
     * @throws moodle_exception
     */
    private function get_document_config(): array {
        // File metadata.
        $filename = $this->document->file->get_filename();
        $fileext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return [
            'url' => (string) $this->document->get_external_download_url(),
            'fileType' => $fileext,
            'title' => $filename,
            'key' => $this->document->get_key(),
            'permissions' => $this->document->get_permissions(),
        ];
    }

    /**
     * Get the editor config values
     * @return array Part of config - Editor key
     * @throws moodle_exception
     */
    private function get_editor_config(): array {
        $editorconfig = [];

        $editorconfig['callbackUrl'] = (string) $this->get_callback_url();
        $editorconfig['user'] = $this->get_user_config();
        $editorconfig['customization'] = $this->get_customisation();

        return $editorconfig;
    }

    /**
     * Get the device type
     * @return string Type of device that is being used to view the page
     */
    private function get_device_type(): string {
        $devicetype = core_useragent::get_device_type();
        return $devicetype == 'tablet' || $devicetype == 'mobile' ? 'mobile' : 'desktop';
    }

    /**
     * Get the config values for the user
     * @return array Part of config - User key
     */
    private function get_user_config(): array {
        global $USER;
        return ['id' => $USER->id, 'name' => fullname($USER)];
    }

    /**
     * Get the customisations for our editor
     * @return array Part of config - Customisation key
     * @throws coding_exception|moodle_exception
     */
    private function get_customisation(): array {
        $cmid = $this->document->assignment->get_course_module()->id;

        $goback = [
            'blank' => false,
            'text' => get_string('returntodocument', 'assignsubmission_onlyoffice'),
            'url' => (string) new moodle_url('/mod/assign/view.php', ['id' => $cmid]),
        ];

        return [
            'goback' => $goback,
            'forcesave' => true,
            'commentAuthorOnly' => true,
        ];
    }

    /**
     * Get the callback URL
     * @return moodle_url URL given to Document server to call us back on
     * @throws moodle_exception
     */
    private function get_callback_url(): moodle_url {
        $assignmentid = $this->document->assignment->get_instance()->id;
        $submissionid = $this->document->submission->id;

        $params = ['assignmentid' => $assignmentid, 'submissionid' => $submissionid];
        $doc = crypt::encode_and_sign($params);
        return new moodle_url('/mod/assign/submission/onlyoffice/callback.php', ['doc' => $doc]);
    }
}
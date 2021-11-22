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
 * Our API for OnlyOffice to interact with us
 *
 * @package assignsubmission_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_onlyoffice;

use coding_exception;
use Exception;
use file_exception;
use assignsubmission_onlyoffice\record\onlyoffice_document;
use stored_file;

defined('MOODLE_INTERNAL') || die();

class api {
    /** @var int Status codes for OnlyOffice */
    const STATUS_NOTFOUND = 0;
    const STATUS_EDITING = 1;
    const STATUS_MUSTSAVE = 2;
    const STATUS_ERRORSAVING = 3;
    const STATUS_CLOSEDNOCHANGES = 4;
    const STATUS_FORCESAVE = 6;
    const STATUS_ERRORFORCESAVE = 7;

    /** @var onlyoffice_document */
    private $document;

    /**
     * api constructor.
     * @param onlyoffice_document $document Document object
     */
    public function __construct(onlyoffice_document $document) {
        $this->document = $document;
    }

    /**
     * Handle a request from the OnlyOffice server calling us
     * @param string $request Request from OnlyOffice server (JSON)
     * @return array Our response including the status and any errors
     * @throws coding_exception
     */
    public function handle_request($request) {
        // Start building our response.
        $response = ['status' => 'success', 'error' => 0];

        // Handle different status codes.
        $request = json_decode($request, true);
        $status = (int)($request['status'] ?? 0);

        switch ($status) {
            case self::STATUS_MUSTSAVE:
            case self::STATUS_FORCESAVE:
                $response['error'] = $this->handle_save_document($request) ? 0 : 1;
                break;
            case self::STATUS_EDITING:
            case self::STATUS_CLOSEDNOCHANGES:
                $response['error'] = 0;
                break;
            case self::STATUS_NOTFOUND:
            case self::STATUS_ERRORSAVING:
            case self::STATUS_ERRORFORCESAVE:
            default:
                $response['error'] = 1;
        }

        // Return our complete response.
        return $response;
    }

    /**
     * Handle saving the new document
     * @param string $request Request from the OnlyOffice document server
     * @return bool Whether or not the document was successfully saved
     */
    private function handle_save_document($request): bool {
        // We need to provided with the URL of the new file.
        if (!$newfileurl = $request['url'] ?? null) {
            return false;
        }

        // Try save the new document.
        try {
            $newfile = $this->get_new_file($newfileurl);
            $this->document->update_file($newfile);
        } catch (Exception $e) {
            return false; // Failed saving document.
        }

        // Successfully saved document.
        return true;
    }

    /**
     * Get the new file
     * @param string $newfileurl URL of the new file
     * @return stored_file Stored file in Moodle
     * @throws file_exception
     */
    private function get_new_file(string $newfileurl): stored_file {
        $fs = get_file_storage();
        $file = $this->document->file;

        // Build the file record.
        $rec = [
            'contextid' => $file->get_contextid(),
            'component' => $file->get_component(),
            'filearea' => 'draft',
            'itemid' => $file->get_itemid(),
            'filename' => $file->get_filename() . '_temp',
            'filepath' => '/',
            'userid' => $file->get_userid(),
            'timecreated' => $file->get_timecreated()
        ];

        // Download the new file.
        $newfile = $fs->create_file_from_url($rec, $newfileurl);
        return $newfile;
    }
}
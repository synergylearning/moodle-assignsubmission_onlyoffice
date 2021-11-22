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
 * OnlyOffice
 *
 * @package assignsubmission_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_onlyoffice;

use coding_exception;
use dml_exception;

defined('MOODLE_INTERNAL') || die();

class onlyoffice {
    /** @var string File format - File uploaded by user */
    const FORMAT_UPLOAD = 'upload';

    /** @var string File format - Text document created from initial text */
    const FORMAT_TEXT = 'text';

    /** @var string File format - Spreadsheet template */
    const FORMAT_SPREADSHEET = 'spreadsheet';

    /** @var string File format - Word processor template */
    const FORMAT_WORDPROCESSOR = 'wordprocessor';

    /** @var string File format - Presentation template */
    const FORMAT_PRESENTATION = 'presentation';

    /** @var int Item ID of the global template file - Spreadsheet */
    const FORMAT_SPREADSHEET_ITEM_ID = 1;

    /** @var int Item ID of the global template file - Word processor */
    const FORMAT_WORDPROCESSOR_ITEM_ID = 2;

    /** @var int Item ID of the global template file - Presentation */
    const FORMAT_PRESENTATION_ITEM_ID = 3;

    /** @var string Type of file area the file is stored in - Initial */
    const FILEAREA_INITIAL = 'initial';

    /** @var string Type of file area the file is stored in - Global templates */
    const FILEAREA_TEMPLATES = 'templates';

    /** @var string TYpe of file area the file is stored in - Submissions */
    const FILEAREA_SUBMISSIONS = 'submissions';

    /** @var string[] Valid file areas */
    const FILEAREAS = [
        self::FILEAREA_INITIAL,
        self::FILEAREA_TEMPLATES,
        self::FILEAREA_SUBMISSIONS,
    ];

    /** @var int Server timeout time in seconds */
    const SERVER_CONNECT_TIMEOUT = 5;

    /** @var string Component name */
    const COMPONENT_NAME = 'assignsubmission_onlyoffice';

    /** @var bool Default state of whether the document is locked or not */
    const LOCKED_DEFAULT = true;

    /**
     * Get the format menu options
     * @return array Format menu options
     * @throws coding_exception
     */
    public static function get_format_menu(): array {
        return [
            self::FORMAT_UPLOAD => get_string(self::FORMAT_UPLOAD, self::COMPONENT_NAME),
            self::FORMAT_TEXT => get_string(self::FORMAT_TEXT, self::COMPONENT_NAME),
            self::FORMAT_SPREADSHEET => get_string(self::FORMAT_SPREADSHEET, self::COMPONENT_NAME),
            self::FORMAT_WORDPROCESSOR => get_string(self::FORMAT_WORDPROCESSOR, self::COMPONENT_NAME),
            self::FORMAT_PRESENTATION => get_string(self::FORMAT_PRESENTATION, self::COMPONENT_NAME),
        ];
    }

    /**
     * Get all the accepted types
     * @return string[] Accepted file types
     */
    public static function get_accepted_types(): array {
        $alltypes = array_merge(
            self::get_accepted_types_spreadsheets(),
            self::get_accepted_types_presentations(),
            self::get_accepted_types_word_documents(),
            self::get_accepted_types_drawings()
        );

        return array_unique($alltypes);
    }

    /**
     * Get accepted types for spreadsheets
     * @return string[] Accepted types for spreadsheets
     */
    public static function get_accepted_types_spreadsheets(): array {
        return [
            '.xls',
            '.xlsx',
            '.ots',
            '.ods',
        ];
    }

    /**
     * Get accepted types for presentations
     * @return string[] Accepted types for presentations
     */
    public static function get_accepted_types_presentations(): array {
        return [
            '.ppt',
            '.pptx',
            '.otp',
            '.odp',
        ];
    }

    /**
     * Get accepted types for word documents
     * @return string[] Accepted types for word documents
     */
    public static function get_accepted_types_word_documents(): array {
        return [
            '.txt', '.rtf', // Text.
            '.html', '.htm', // HTML.
            '.doc', '.docx', '.odt', // Word documents.
        ];
    }

    /**
     * Get accepted types for drawings
     * @return string[] Accepted types for drawings
     */
    public static function get_accepted_types_drawings(): array {
        return [
            '.odg', // Drawing.
        ];
    }

    /**
     * Get the default initial text
     * @return string Default initial text to use
     * @throws dml_exception
     */
    public static function get_default_initial_text(): string {
        return get_config(self::COMPONENT_NAME, 'defaultinitialtext');
    }

    /**
     * Get the document server URL
     * @return string URL of the document server URL
     * @throws dml_exception
     */
    public static function get_server_url(): string {
        return get_config(self::COMPONENT_NAME, 'documentserverurl');
    }

    /**
     * Get the document server secret key
     * @return string Secret key used to access the document server
     * @throws dml_exception
     */
    public static function get_secret_key(): string {
        return get_config(self::COMPONENT_NAME, 'documentserversecret');
    }

    /**
     * Is the OnlyOffice document server online?
     * @return bool Whether or not the document server is online
     * @throws dml_exception
     */
    public static function is_server_online(): bool {
        $documentserverurl = self::get_server_url();

        // Try connect to the document server.
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $documentserverurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::SERVER_CONNECT_TIMEOUT);

        curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode != 200 && $httpcode != 302) {
            return false; // Not an OK status code.
        }

        return true;
    }
}
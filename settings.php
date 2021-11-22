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
 * Global settings
 *
 * @package assignsubmission_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_onlyoffice\onlyoffice;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig && $ADMIN->fulltree) {

    // Document server URL.
    $settings->add(new admin_setting_configtext(
        'assignsubmission_onlyoffice/documentserverurl',
        new lang_string('documentserverurl', 'assignsubmission_onlyoffice'),
        new lang_string('documentserverurldesc', 'assignsubmission_onlyoffice'),
        '',
        PARAM_URL
    ));

    // Document server secret.
    $settings->add(new admin_setting_configtext(
        'assignsubmission_onlyoffice/documentserversecret',
        new lang_string('documentserversecret', 'assignsubmission_onlyoffice'),
        new lang_string('documentserversecretdesc', 'assignsubmission_onlyoffice'),
        ''
    ));

    // Default format.
    $settings->add(new admin_setting_configselect(
        'assignsubmission_onlyoffice/defaultformat',
        new lang_string('defaultformat', 'assignsubmission_onlyoffice'),
        '',
        onlyoffice::FORMAT_UPLOAD,
        onlyoffice::get_format_menu()
    ));

    // Override default template file for spreadsheets.
    $settings->add(new admin_setting_configstoredfile(
        'assignsubmission_onlyoffice/overridetemplatespreadsheet',
        new lang_string('overridetemplatespreadsheet', 'assignsubmission_onlyoffice'),
        '',
        onlyoffice::FILEAREA_TEMPLATES,
        onlyoffice::FORMAT_SPREADSHEET_ITEM_ID,
        ['accepted_types' => onlyoffice::get_accepted_types_spreadsheets()]
    ));

    // Override default template file for presentations.
    $settings->add(new admin_setting_configstoredfile(
        'assignsubmission_onlyoffice/overridetemplatepresentation',
        new lang_string('overridetemplatepresentation', 'assignsubmission_onlyoffice'),
        '',
        onlyoffice::FILEAREA_TEMPLATES,
        onlyoffice::FORMAT_PRESENTATION_ITEM_ID,
        ['accepted_types' => onlyoffice::get_accepted_types_presentations()]
    ));

    // Override default template file for word documents.
    $settings->add(new admin_setting_configstoredfile(
        'assignsubmission_onlyoffice/overridetemplateworddocument',
        new lang_string('overridetemplateworddocument', 'assignsubmission_onlyoffice'),
        '',
        onlyoffice::FILEAREA_TEMPLATES,
        onlyoffice::FORMAT_WORDPROCESSOR_ITEM_ID,
        ['accepted_types' => onlyoffice::get_accepted_types_word_documents()]
    ));

    // Default initial text.
    $settings->add(new admin_setting_configtextarea(
        'assignsubmission_onlyoffice/defaultinitialtext',
        new lang_string('defaultinitialtext', 'assignsubmission_onlyoffice'),
        '',
        ''
    ));

}

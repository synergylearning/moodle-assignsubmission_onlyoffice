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
 * Cryptography functions
 *
 * @package assignsubmission_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_onlyoffice\util;

use dml_exception;
use Firebase\JWT\JWT;
use assignsubmission_onlyoffice\onlyoffice;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class crypt {
    /** @var string Algorithm to use for signing */
    private const SIGNING_ALGORITHM = 'HS256';

    /**
     * Encode a message and sign it for verification
     * @param stdClass|array $payload Payload for us to encode and sign
     * @return string Encoded and signed payload
     * @throws dml_exception
     */
    public static function encode_and_sign($payload): string {
        $key = onlyoffice::get_secret_key();
        return JWT::encode($payload, $key, self::SIGNING_ALGORITHM);
    }

    /**
     * Decode a signed message
     * @param string $jwt Encoded payload
     * @return object Decoded payload
     * @throws dml_exception
     */
    public static function decode(string $jwt) {
        $key = onlyoffice::get_secret_key();
        return JWT::decode($jwt, $key, [self::SIGNING_ALGORITHM]);
    }
}
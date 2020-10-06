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
 * This file receives a registration request along with the registration token and returns a client_id.
 *
 * @copyright  2020 Claude Vervoort (Cengage), Carlos Costa, Adrian Hutchinson (Macgraw Hill)
 * @package    mod_lti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/mod/lti/openidregistrationlib.php');

/**
 * Creates and returns an error message
 *
 * @param string $message Response message
 * @param int $code The response code
 */
function return_response($message, $code = 200) {
    $response = new \mod_lti\local\ltiservice\response();
    // Set code.
    $response->set_code($code);
    // Set body.
    $response->set_body($message);
    $response->send();
    die;
}

/**
 * Helper function to return errors
 *
 * @param string $message Error message
 * @param int $code Error code
 */
function return_error($message, $code = 500) {
    return_response($message, $code);
}

// Retrieve registration token from Bearer Authorization header.
$authheader = moodle\mod\lti\OAuthUtil::get_headers() ['Authorization'] ?? '';
if (!($authheader && substr($authheader, 0, 7) == 'Bearer ')) {
    return_error('missing_registration_token', 401);
}

// Retrieve registration parameters.
$registrationpayload = json_decode(file_get_contents('php://input') , true);

// Registers tool.
$type = new stdClass();
$type->state = LTI_TOOL_STATE_PENDING;
try {
    $clientid = validate_registration_token(trim(substr($authheader, 7)));
    $config = registration_to_config($registrationpayload, $clientid);
    $typeid = lti_add_type($type, clone $config);
    $responsemessage = json_encode(config_to_registration($config, $typeid));
    // Returning registration response.
    header('Content-Type: application/json; charset=utf-8');
    return_response($responsemessage);
} catch (LTIRegistrationException $e) {
    return_error($e->errormsg, $e->httperrorcode);
}



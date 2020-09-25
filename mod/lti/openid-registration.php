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
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

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

/**
 * Funcion used to validade paramteres.
 *
 * This function is needed because the payload contains nested
 * objects, and optional_param() does not support arrays of arrays.
 *
 * @param array $payload
 * @param string $key
 * @param boolean $required
 *
 * @return mixed
 */
function get_parameter($payload, $key, $required) {
    if (!isset($payload[$key]) || empty($payload[$key])) {
        if ($required) {
            return_error('missing_parameter_' . $key, 400);
        }
        return null;
    }
    $parameter = $payload[$key];
    // Cleans parameters to avoid XSS and other issues.
    if (is_array($parameter)) {
        return clean_param_array($parameter, PARAM_TEXT, true);
    }
    return clean_param($parameter, PARAM_TEXT);
}

// Retrieve registration token from Bearer Authorization header.
$authheader = moodle\mod\lti\OAuthUtil::get_headers() ['Authorization'] ?? '';
if (!($authheader && substr($authheader, 0, 7) == 'Bearer ')) {
    return_error('missing_registration_token', 401);
}

// Validate registrationtoken.
$registrationtokenjwt = trim(substr($authheader, 7));
$keys = JWK::parseKeySet(jwks());
$registrationtoken = JWT::decode($registrationtokenjwt, $keys, ['RS256']);

// Get clientid from registrationtoken.
$clientid = $registrationtoken->sub;

// Checks if clientid is already registered.
if (!empty($DB->get_record('lti_types', array(
    'clientid' => $clientid
)))) {
    return_error('token_already_consumed', 401);
}

// Retrieve registration parameters.
$registrationpayload = json_decode(file_get_contents('php://input') , true);

$responsetypes = get_parameter($registrationpayload, 'response_types', true);
$granttypes = get_parameter($registrationpayload, 'grant_types', true);
$initiateloginuri = get_parameter($registrationpayload, 'initiate_login_uri', true);
$redirecturis = get_parameter($registrationpayload, 'redirect_uris', true);
$clientname = get_parameter($registrationpayload, 'client_name', true);
$jwksuri = get_parameter($registrationpayload, 'jwks_uri', true);
$tokenendpointauthmethod = get_parameter($registrationpayload, 'token_endpoint_auth_method', true);

$applicationtype = get_parameter($registrationpayload, 'application_type', false);
$logouri = get_parameter($registrationpayload, 'logo_uri', false);

$ltitoolconfiguration = get_parameter($registrationpayload, 'https://purl.imsglobal.org/spec/lti-tool-configuration', true);

$domain = get_parameter($ltitoolconfiguration, 'domain', true);
$targetlinkuri = get_parameter($ltitoolconfiguration, 'target_link_uri', true);
$customparameters = get_parameter($ltitoolconfiguration, 'custom_parameters', false);
$scopes = explode(" ", get_parameter($registrationpayload, 'scope', false) ?? '');
$claims = get_parameter($ltitoolconfiguration, 'claims', false);
$messages = $ltitoolconfiguration['messages'] ?? [];
$description = get_parameter($ltitoolconfiguration, 'description', false);

// Create response objects.
$registrationresponse = new stdClass();
$lticonfigurationresponse = new stdClass();

// Validate response type.
// According to specification, for this scenario, id_token must be explicitly set.
if (!in_array('id_token', $responsetypes)) {
    return_error('invalid_response_types', 400);
}
$registrationresponse->response_types = $responsetypes;

// Validate granttypes.
// According to specification, for this scenario implicit and client_cresentials must be explicitly set.
if (!in_array('implicit', $granttypes) || !in_array('client_credentials', $granttypes)) {
    return_error('invalid_grant_types', 400);
}
$registrationresponse->grant_types = $granttypes;

// Validate redirect uris.
// According to specification, this parameter needs to be an array.
if (!is_array($redirecturis)) {
    return_error('invalid_redirect_uris', 400);
}
$registrationresponse->rediret_uris = $redirecturis;

// Validate token endpoint auth method.
// According to specification, for this scenario private_key_jwt must be explicitly set.
if ($tokenendpointauthmethod !== 'private_key_jwt') {
    return_error('invalid_token_endpoint_auth_method', 400);
}
$registrationresponse->token_endpoint_auth_method = ['private_key_jwt'];

// Validate application type.
if (!empty($applicationtype) && $applicationtype !== 'web') {
    return_error('invalid_application_type', 400);
}
$registrationresponse->application_type = ['web'];

// Creating tool configuration.
$type = new stdClass();
// Sets Pending state explicitly.
$type->state = LTI_TOOL_STATE_PENDING;

// Create LTI config.
$config = new stdClass();
// Sets Tool Url.
$config->lti_toolurl = $targetlinkuri;
$lticonfigurationresponse->target_link_uri = $targetlinkuri;
// Sets Tool Domain.
$config->lti_tooldomain = $domain;
$lticonfigurationresponse->domain = $domain;
// Sets Tool Name.
$config->lti_typename = $clientname;
$registrationresponse->client_name = $clientname;
// Sets Tool Description.
$config->lti_description = $description;
$lticonfigurationresponse->description = $description;
// Sets LTI version.
$config->lti_ltiversion = LTI_VERSION_1P3;
// Default is to use siteid as instance guid.
$config->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEID;
// Sets ClientID.
$config->lti_clientid = $clientid;
$registrationresponse->client_id = $clientid;
// Sets icon.
$config->lti_icon = $logouri;
$registrationresponse->logo_uri = $logouri;
// Sets Course Visible.
$config->lti_coursevisible = LTI_COURSEVISIBLE_PRECONFIGURED;
// Sets Content Item.
if (!empty($messages)) {
    $messagesresponse = [];
    foreach ($messages as $value) {
        if ($value['type'] === 'LtiDeepLinkingRequest') {
            $config->lti_contentitem = 1;
            $config->lti_toolurl_ContentItemSelectionRequest = $value['target_link_uri'];
            array_push($messagesresponse, $value);
        }
    }
    $lticonfigurationresponse->messages = $messagesresponse;
}

// Sets key type.
$config->lti_keytype = 'JWK_KEYSET';
// Sets public keyset.
$config->lti_publickeyset = $jwksuri;
$registrationresponse->jwks_uri = $jwksuri;
// Sets login uri.
$config->lti_initiatelogin = $initiateloginuri;
$registrationresponse->initiate_login_uri = $initiateloginuri;
// Sets redirection uris.
$config->lti_redirectionuris = implode(PHP_EOL, $redirecturis);
// Sets custom parameters.
if (isset($customparameters)) {
    $paramssarray = [];
    foreach ($customparameters as $key => $value) {
        array_push($paramssarray, $key . '=' . $value);
    }
    $config->lti_customparameters = implode(PHP_EOL, $paramssarray);
    $lticonfigurationresponse->custom_parameters = $customparameters;
}
// Sets launch container.
$config->lti_launchcontainer = LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;

// Sets Service info based on scopes.
if (isset($scopes)) {
    $scopesresponse = [];
    // Expected scopes.
    $scopescore = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';
    $scoperesult = 'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly';
    $scopelineitemread = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';
    $scopelineitem = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
    $scopenamesroles = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
    $scopetoolsettings = 'https://purl.imsglobal.org/spec/lti-ts/scope/toolsetting';

    // Sets Assignment and Grade Services info.
    $config->lti_acceptgrades = LTI_SETTING_NEVER;
    $config->ltiservice_gradesynchronization = 0;

    if (in_array($scopescore, $scopes)) {
        $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
        $config->ltiservice_gradesynchronization = 1;
        array_push($scopesresponse, $scopescore);
    }
    if (in_array($scoperesult, $scopes)) {
        $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
        $config->ltiservice_gradesynchronization = 1;
        array_push($scopesresponse, $scoperesult);
    }
    if (in_array($scopelineitemread, $scopes)) {
        $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
        $config->ltiservice_gradesynchronization = 1;
        array_push($scopesresponse, $scopelineitemread);
    }
    if (in_array($scopelineitem, $scopes)) {
        $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
        $config->ltiservice_gradesynchronization = 2;
        array_push($scopesresponse, $scopelineitem);
    }

    // Sets Names and Role Provisioning info.
    if (in_array($scopenamesroles, $scopes)) {
        $config->ltiservice_memberships = 1;
        array_push($scopesresponse, $scopenamesroles);
    } else {
        $config->ltiservice_memberships = 0;
    }

    // Sets Tool Settings info.
    if (in_array($scopetoolsettings, $scopes)) {
        $config->ltiservice_toolsettings = 1;
        array_push($scopesresponse, $scopetoolsettings);
    } else {
        $config->ltiservice_toolsettings = 0;
    }
    $registrationresponse->scope = implode(' ', $scopesresponse);
}

// Sets privacy settings.
if (isset($claims)) {
    $claimsresponse = [];
    // Sets name privacy settings.
    $config->lti_sendname = LTI_SETTING_NEVER;

    if (in_array('given_name', $claims)) {
        $config->lti_sendname = LTI_SETTING_ALWAYS;
        array_push($claimsresponse, 'given_name');
    }
    if (in_array('family_name', $claims)) {
        $config->lti_sendname = LTI_SETTING_ALWAYS;
        array_push($claimsresponse, 'family_name');
    }
    if (in_array('middle_name', $claims)) {
        $config->lti_sendname = LTI_SETTING_ALWAYS;
        array_push($claimsresponse, 'middle_name');
    }

    // Sets email privacy settings.
    if (in_array('email', $claims)) {
        $config->lti_sendemailaddr = LTI_SETTING_ALWAYS;
        array_push($claimsresponse, 'email');
    } else {
        $config->lti_sendemailaddr = LTI_SETTING_NEVER;
    }
    $lticonfigurationresponse->claims = $claimsresponse;
}

// Registers tool.
lti_add_type($type, $config);

// Assemble response message.
$responsemessage = json_encode($registrationresponse);
$responsemessage = substr($responsemessage, 0, -1);
$responsemessage .= ',"https://purl.imsglobal.org/spec/lti-tool-configuration":' . json_encode($lticonfigurationresponse);
$responsemessage .= '}';
// Returning registration response.
header('Content-Type: application/json; charset=utf-8');
return_response($responsemessage);


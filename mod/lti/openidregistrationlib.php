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
 * @package    mod_lti
 * @copyright  2020 Claude Vervoort (Cengage), Carlos Costa, Adrian Hutchinson (Macgraw Hill)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/lti/locallib.php');
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

/** score scope */
const SCOPE_SCORE = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';
/** result scope */
const SCOPE_RESULT = 'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly';
/** lineitem read-only scope */
const SCOPE_LINEITEM_RO = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';
/** lineitem full access scope */
const SCOPE_LINEITEM = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
/** Names and Roles (membership) scope */
const SCOPE_NRPS = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
/** Tool Settings scope */
const SCOPE_TOOL_SETTING = 'https://purl.imsglobal.org/spec/lti-ts/scope/toolsetting';


/**
 * Exception when transforming the registration to LTI config.
 *
 * Code is the HTTP Error code.
 */
class LTIRegistrationException extends Exception {
}

/**
 * Function used to validate parameters.
 *
 * This function is needed because the payload contains nested
 * objects, and optional_param() does not support arrays of arrays.
 *
 * @param array $payload that may contain the parameter key
 * @param string $key
 * @param bool $required
 *
 * @return mixed
 */
function get_parameter(array $payload, string $key, bool $required) {
    if (!isset($payload[$key]) || empty($payload[$key])) {
        if ($required) {
            throw new LTIRegistrationException('missing required attribute '.$key, 400);
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

/**
 * Transforms an LTI 1.3 Registration to a Moodle LTI Config.
 *
 * @param array $registrationpayload the registration data received from the tool.
 * @param string $clientid the clientid to be issued for that tool.
 *
 * @return object the Moodle LTI config.
 */
function registration_to_config(array $registrationpayload, string $clientid) : object {
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

    // Validate response type.
    // According to specification, for this scenario, id_token must be explicitly set.
    if (!in_array('id_token', $responsetypes)) {
        throw new LTIRegistrationException('invalid_response_types', 400);
    }

    // According to specification, this parameter needs to be an array.
    if (!is_array($redirecturis)) {
        throw new LTIRegistrationException('invalid_redirect_uris', 400);
    }

    // According to specification, for this scenario private_key_jwt must be explicitly set.
    if ($tokenendpointauthmethod !== 'private_key_jwt') {
        throw new LTIRegistrationException('invalid_token_endpoint_auth_method', 400);
    }

    if (!empty($applicationtype) && $applicationtype !== 'web') {
        throw new LTIRegistrationException('invalid_application_type', 400);
    }

    $config = new stdClass();
    $config->lti_clientid = $clientid;
    $config->lti_toolurl = $targetlinkuri;
    $config->lti_tooldomain = $domain;
    $config->lti_typename = $clientname;
    $config->lti_description = $description;
    $config->lti_ltiversion = LTI_VERSION_1P3;
    $config->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEID;
    $config->lti_icon = $logouri;
    $config->lti_coursevisible = LTI_COURSEVISIBLE_PRECONFIGURED;
    $config->lti_contentitem = 0;
    // Sets Content Item.
    if (!empty($messages)) {
        $messagesresponse = [];
        foreach ($messages as $value) {
            if ($value['type'] === 'LtiDeepLinkingRequest') {
                $config->lti_contentitem = 1;
                $config->lti_toolurl_ContentItemSelectionRequest = $value['target_link_uri'] ?? '';
                array_push($messagesresponse, $value);
            }
        }
    }

    $config->lti_keytype = 'JWK_KEYSET';
    $config->lti_publickeyset = $jwksuri;
    $config->lti_initiatelogin = $initiateloginuri;
    $config->lti_redirectionuris = implode(PHP_EOL, $redirecturis);
    $config->lti_customparameters = '';
    // Sets custom parameters.
    if (isset($customparameters)) {
        $paramssarray = [];
        foreach ($customparameters as $key => $value) {
            array_push($paramssarray, $key . '=' . $value);
        }
        $config->lti_customparameters = implode(PHP_EOL, $paramssarray);
    }
    // Sets launch container.
    $config->lti_launchcontainer = LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;

    // Sets Service info based on scopes.
    $config->lti_acceptgrades = LTI_SETTING_NEVER;
    $config->ltiservice_gradesynchronization = 0;
    $config->ltiservice_memberships = 0;
    $config->ltiservice_toolsettings = 0;
    if (isset($scopes)) {
        // Sets Assignment and Grade Services info.

        if (in_array(SCOPE_SCORE, $scopes)) {
            $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
            $config->ltiservice_gradesynchronization = 1;
        }
        if (in_array(SCOPE_RESULT, $scopes)) {
            $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
            $config->ltiservice_gradesynchronization = 1;
        }
        if (in_array(SCOPE_LINEITEM_RO, $scopes)) {
            $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
            $config->ltiservice_gradesynchronization = 1;
        }
        if (in_array(SCOPE_LINEITEM, $scopes)) {
            $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
            $config->ltiservice_gradesynchronization = 2;
        }

        // Sets Names and Role Provisioning info.
        if (in_array(SCOPE_NRPS, $scopes)) {
            $config->ltiservice_memberships = 1;
        }

        // Sets Tool Settings info.
        if (in_array(SCOPE_TOOL_SETTING, $scopes)) {
            $config->ltiservice_toolsettings = 1;
        }
    }

    // Sets privacy settings.
    $config->lti_sendname = LTI_SETTING_NEVER;
    $config->lti_sendemailaddr = LTI_SETTING_NEVER;
    if (isset($claims)) {
        // Sets name privacy settings.

        if (in_array('name', $claims)) {
            $config->lti_sendname = LTI_SETTING_ALWAYS;
        }
        if (in_array('given_name', $claims)) {
            $config->lti_sendname = LTI_SETTING_ALWAYS;
        }
        if (in_array('family_name', $claims)) {
            $config->lti_sendname = LTI_SETTING_ALWAYS;
        }

        // Sets email privacy settings.
        if (in_array('email', $claims)) {
            $config->lti_sendemailaddr = LTI_SETTING_ALWAYS;
        }
    }
    return $config;
}

/**
 * Transforms a moodle LTI 1.3 Config to an OAuth/LTI Client Registration.
 *
 * @param object $config Moodle LTI Config.
 * @param int $typeid which is the LTI deployment id.
 *
 * @return array the Client Registration as an associative array.
 */
function config_to_registration(object $config, int $typeid) : array {
    $registrationresponse = [];
    $registrationresponse['client_id'] = $config->lti_clientid;
    $registrationresponse['token_endpoint_auth_method'] = ['private_key_jwt'];
    $registrationresponse['response_types'] = ['id_token'];
    $registrationresponse['jwks_uri'] = $config->lti_publickeyset;
    $registrationresponse['initiate_login_uri'] = $config->lti_initiatelogin;
    $registrationresponse['grant_types'] = ['client_credentials', 'implicit'];
    $registrationresponse['redirect_uris'] = explode(PHP_EOL, $config->lti_redirectionuris);
    $registrationresponse['application_type'] = ['web'];
    $registrationresponse['token_endpoint_auth_method'] = 'private_key_jwt';
    $registrationresponse['client_name'] = $config->lti_typename;
    $registrationresponse['logo_uri'] = $config->lti_icon ?? '';
    $lticonfigurationresponse = [];
    $lticonfigurationresponse['deployment_id'] = strval($typeid);
    $lticonfigurationresponse['target_link_uri'] = $config->lti_toolurl;
    $lticonfigurationresponse['domain'] = $config->lti_tooldomain ?? '';
    $lticonfigurationresponse['description'] = $config->lti_description ?? '';
    if ($config->lti_contentitem == 1) {
        $contentitemmessage = [];
        $contentitemmessage['type'] = 'LtiDeepLinkingRequest';
        if (isset($config->lti_toolurl_ContentItemSelectionRequest)) {
            $contentitemmessage['target_link_uri'] = $config->lti_toolurl_ContentItemSelectionRequest;
        }
        $lticonfigurationresponse['messages'] = [$contentitemmessage];
    }
    if (isset($config->lti_customparameters) && !empty($config->lti_customparameters)) {
        $params = [];
        foreach (explode(PHP_EOL, $config->lti_customparameters) as $param) {
            $split = explode('=', $param);
            $params[$split[0]] = $split[1];
        }
        $lticonfigurationresponse['custom_parameters'] = $params;
    }
    $scopesresponse = [];
    if ($config->ltiservice_gradesynchronization > 0) {
        $scopesresponse[] = SCOPE_SCORE;
        $scopesresponse[] = SCOPE_RESULT;
        $scopesresponse[] = SCOPE_LINEITEM_RO;
    }
    if ($config->ltiservice_gradesynchronization == 2) {
        $scopesresponse[] = SCOPE_LINEITEM;
    }
    if ($config->ltiservice_memberships == 1) {
        $scopesresponse[] = SCOPE_NRPS;
    }
    if ($config->ltiservice_toolsettings == 1) {
        $scopesresponse[] = SCOPE_TOOL_SETTING;
    }
    $registrationresponse['scope'] = implode(' ', $scopesresponse);

    $claimsresponse = ['sub', 'iss'];
    if ($config->lti_sendname = LTI_SETTING_ALWAYS) {
        $claimsresponse[] = 'name';
        $claimsresponse[] = 'family_name';
        $claimsresponse[] = 'middle_name';
    }
    if ($config->lti_sendemailaddr = LTI_SETTING_ALWAYS) {
        $claimsresponse[] = 'email';
    }
    $lticonfigurationresponse['claims'] = $claimsresponse;
    $registrationresponse['https://purl.imsglobal.org/spec/lti-tool-configuration'] = $lticonfigurationresponse;
    return $registrationresponse;
}

/**
 * Validates the registration token is properly signed and not used yet.
 * Return the client id to use for this registration.
 *
 * @param string $registrationtokenjwt registration token
 *
 * @return string client id for the registration
 */
function validate_registration_token(string $registrationtokenjwt) : string {
    global $DB;
    $keys = JWK::parseKeySet(jwks());
    $registrationtoken = JWT::decode($registrationtokenjwt, $keys, ['RS256']);

    // Get clientid from registrationtoken.
    $clientid = $registrationtoken->sub;

    // Checks if clientid is already registered.
    if (!empty($DB->get_record('lti_types', array(
        'clientid' => $clientid
    )))) {
        debugging("client id already consumed: {$clientid}.", DEBUG_INFO);
        throw new LTIRegistrationException("token_already_used", 401);
    }
    return $clientid;
}

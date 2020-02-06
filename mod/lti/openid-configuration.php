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
 * This file returns an array of available public keys
 *
 * @package    mod_lti
 * @copyright  2019 Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');

$conf = [
    "issuer" => "https://server.example.com",
    "authorization_endpoint" =>  "https://server.example.com/connect/authorize",
    "token_endpoint" => "https://server.example.com/connect/token",
    "token_ endpoint_auth_methods_supported" => ["private_key_jwt"],
    "token_endpoint_auth_signing_alg_values_supported" => ["RS256"],
    "jwks_uri" => "https://server.example.com/jwks.json",
    "registration_endpoint" => "https://server.example.com/connect/register",
    "scopes_supported" => ["openid",
        "https://purl.imsglobal.org/spec/lti-gs/scope/contextgroup.readonly",
        "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
        "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly",
        "https://purl.imsglobal.org/spec/lti-ags/scope/score",
        "https://purl.imsglobal.org/spec/lti-reg/scope/registration"],
    "response_types_supported" => ["id_token"],
    "subject_types_supported" => ["public", "pairwise"],
    "id_token_signing_alg_values_supported" => ["RS256"],
    "claims_supported" => ["sub", "iss", "name", "given_name", "family_name", "email"],
    "https://purl.imsglobal.org/spec/lti-platform-configuration " => [
        "messages_supported" => ["LtiResourceLink", "LtiDeepLinkingRequest", "LtiDeploymentRequest"],
        "placements" => ["AddContentMenu", "AddToRichTextMenu", "CourseNavigation"],
        "variables" => ["CourseSection.timeFrame.end", "CourseSection.timeFrame.begin", "Context.id.history", "ResourceLink.id.history"]
    ]
];

@header('Content-Type: application/json; charset=utf-8');

echo json_encode($conf, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

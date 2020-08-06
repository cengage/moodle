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
 * Redirect the user to registration with token and openid config url as query params.
 *
 * @package mod_lti
 * @copyright  2020 Cengage
 * @author     Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
require_once($CFG->libdir.'/weblib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

if (isset($_GET['url'])) {
    $now = time();
    $token = [
        "sub" => random_string(15),
        "scope" => "reg",
        "iat" => $now,
        "exp" => $now + 3600 
    ];
    $privatekey = get_private_key();
    $reg_token = JWT::encode($token, $privatekey['key'], 'RS256', $privatekey['kid']);
    $conf_url = new moodle_url('/mod/lti/openid-configuration.php');
    $url = new moodle_url($_GET['url']);
    $url->param('openid_configuration', $conf_url->out(false));
    $url->param('registration_token', $reg_token);
    header("Location: ".$url->out(false));
}
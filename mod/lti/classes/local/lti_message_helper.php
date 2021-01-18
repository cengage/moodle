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
 * This files exposes functions for handling of LTI Messages.
 *
 * @package    mod_lti
 * @copyright  2021 Claude Vervoort (Cengage)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lti\local;

/**
 * This class exposes functions for LTI Messages.
 *
 * @package    mod_lti
 * @copyright  2021 Claude Vervoort (Cengage)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lti_message_helper {

    /**
     * Returns the private key to use to sign outgoing JWT.
     *
     * @return array keys are kid and key in PEM format.
     */
    public static function to_message(int $messageid, int $typeid, int $courseid, 
                                      string $url, string $customparams, string $messagetype): object {
        $lti = new \StdClass();
        $lti->message_type = $messagetype;
        $lti->typeid = $typeid;
        $lti->id = $messageid;
        $lti->toolurl = $url ?? '';
        $lti->instructorcustomparameters = $customparameters ?? '';
        $lti->debuglaunch = false;
        $lti->course = $courseid;
        return $lti;
    }
}

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
     * Runtime build of LTI message, used when executing an LTI
     * placement that is not an actual record in the LTI Table.
     *
     * @param object $type tool type
     * @param array $config type's config
     * @param int $messageid message id
     * @param string $name name of the link
     * @param int $courseid
     * @param string $url if not present the tool url will be used
     * @param string $customparameters this message custom parameteres, added to the tool's custom parameters
     * @param string $messagetype message type
     *
     * @return object LTI Message object.
     */
    public static function to_message(object $type, array $config, int $messageid, string $name, int $courseid,
                                      ?string $url, ?string $customparameters, string $messagetype): object {
        $lti = new \StdClass();
        $lti->message_type = $messagetype;
        $lti->typeid = $type->id;
        $lti->name = $name;
        $lti->id = $messageid;
        $lti->toolurl = $url ?? $type->baseurl;
        if (isset($config['customparameters']) && $config['customparameters']) {
            if (isset($customparameters) && $customparameters) {
                $lti->instructorcustomparameters = $config['customparameters']."\n".$customparameters;
            } else {
                $lti->instructorcustomparameters = $config['customparameters'];
            }
        } else {
            $lti->instructorcustomparameters = $customparameters ?? '';
        }
        $lti->debuglaunch = false;
        $lti->course = $courseid;
        $lti->showtitlelaunch = false;
        $lti->showdescriptionlaunch = false;
        return $lti;
    }
}

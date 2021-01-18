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
 * This files exposes the LTI message types.
 *
 * @package    mod_lti
 * @copyright  2021 Claude Vervoort (Cengage)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lti\local;

/**
 * This class exposes the lti message types.
 *
 * @package    mod_lti
 * @copyright  2021 Claude Vervoort (Cengage)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class lti_message_type {
    const BASIC_LAUNCH = 'basic-lti-launch-request';
    const RESOURCE_LAUNCH = 'LtiResourceLinkLaunchRequest';
    const COURSE_NAV_LAUNCH = 'ContextLaunchRequest';
    const CONTENT_ITEM_LAUNCH = 'ContentItemSelectionRequest';
    const DEEP_LINKING_LAUNCH = 'LtiDeepLinkingLaunchRequest'; 
};

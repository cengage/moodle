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

namespace mod_lti\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * External function for fetching all tool types and proxies.
 *
 * @package    mod_lti
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_deeplinking_for_richtexteditor extends external_api {

    /**
     * Get parameter definition for get_deeplinking_for_richtexteditor().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(
                PARAM_INT,
                'Context id',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Get the deep linking tools available for the context.
     *
     * @param int $context The current context id
     * @return array
     */
    public static function execute($contextid): array {
        return [
            'types' => [[
                'name' => 'Test',
                'id' => $contextid,
            ]
        ]];
    }

    /**
     * Get return definition for get_tool_types_and_proxies.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'types' => new external_multiple_structure(new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'Tool type id'),
                    'name' => new external_value(PARAM_NOTAGS, 'Tool type name'),
                ]
        ))]);
    }
}

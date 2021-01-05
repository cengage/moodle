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
//
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file contains unit tests for lti_coursenav_lib.
 *
 * @package    mod_lti
 * @copyright  2021 Claude Vervoort (Cengage)
 * @author     Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

    /**
     * Create an LTI Tool.
     *
     * @param object $config tool config.
     * @param object $course optional set if course tool.
     *
     * @return object tool.
     */
    function create_type(object $config, object $course = null): object {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');
        $type = new stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = "Test client ID";
        $type->baseurl = 'https://test.example.org/test.html';
        if (isset($course)) {
            $type->course = $course->id;
        }

        $configbase = new stdClass();
        $configbase->lti_acceptgrades = LTI_SETTING_NEVER;
        $configbase->lti_sendname = LTI_SETTING_NEVER;
        $configbase->lti_sendemailaddr = LTI_SETTING_NEVER;
        $mergedconfig = (object) array_merge( (array) $configbase, (array) $config);
        $typeid = lti_add_type($type, $mergedconfig);
        return lti_get_type($typeid);
    }

    /**
     * Create an LTI Tool.
     *
     * @param object $config tool config.
     *
     * @return object tool.
     */
    function update_type(object $type) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');
        lti_update_type($type);
    }
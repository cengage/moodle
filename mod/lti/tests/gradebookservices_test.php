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
 * Unit tests for mod_lti gradebookservices
 * @package    mod_lti
 * @category   external
 * @copyright  2019 Claude Vervoort <claude.vervoort@cengage.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.8
 */

use ltiservice_gradebookservices\local\service\gradebookservices;


class mod_lti_gradebookservices_testcase extends advanced_testcase {

    /**
     * Test saving a graded LTI with resource and tag info (as a result of
     * content item selection) creates a gradebookservices record
     * that can be retrieved using the gradebook service API.
     */
    public function test_lti_add_coupled_lineitem() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a tool type, associated with that proxy.

        $course = $this->getDataGenerator()->create_course();
        
        $type = new stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = "Test client ID";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new stdClass();

        $typeid = lti_add_type($type, $config);

        $lti = array('course' => $course->id,
                    'typeid' => $typeid,
                    'instructorchoiceacceptgrades' => LTI_SETTING_ALWAYS,
                    'grade' => 10,
                    'lineitemresourceid' => 'test-resource-id',
                    'lineitemtag' => 'test-tag');
    
        $ltiinstance = $this->getDataGenerator()->create_module('lti', $lti, array());

        $this->assertNotNull($ltiinstance);

        $gbs = gradebookservices::find_ltiservice_gradebookservice_for_lti($ltiinstance->id);

        $this->assertNotNull($gbs);
        $this->assertEquals($lti['lineitemresourceid'], $gbs->resourceid);
        $this->assertEquals($lti['lineitemtag'], $gbs->tag);

        $gbservice = new gradebookservices();
        //$courseid, $resourceid, $ltilinkid, $tag, $limitfrom, $limitnum, $typeid
        $gradeitems = $gbservice->get_lineitems($course->id, null, null, null, null, null, $typeid);

        // the 1st item in the array is the items count
        $this->assertEquals(1, $gradeitems[0]);

        $lineitem = gradebookservices::item_for_json($gradeitems[1][0], '', $typeid);
        $this->assertEquals(10, $lineitem->scoreMaximum);
        $this->assertEquals($lti['lineitemresourceid'], $lineitem->resourceId);
        $this->assertEquals($lti['lineitemtag'], $lineitem->tag);
        $this->assertEquals($ltiinstance->name, $lineitem->label);

    }


}
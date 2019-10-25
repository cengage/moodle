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
        
        $typeid = $this->create_type();
        $course = $this->getDataGenerator()->create_course();
        $resourceid = 'test-resource-id';
        $tag = 'tag';

        $ltiinstance = $this->create_graded_lti($typeid, $course, $resourceid, $tag);

        $this->assertNotNull($ltiinstance);

        $gbs = gradebookservices::find_ltiservice_gradebookservice_for_lti($ltiinstance->id);

        $this->assertNotNull($gbs);
        $this->assertEquals($resourceid, $gbs->resourceid);
        $this->assertEquals($tag, $gbs->tag);

        $this->assertLineItems($course, $typeid, $ltiinstance->name, $ltiinstance, $resourceid, $tag);
    }

    /**
     * Test saving a standalone LTI lineitem with resource and tag info
     * that can be retrieved using the gradebook service API.
     */
    public function test_lti_add_standalone_lineitem() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $resourceid = "test-resource-standalone";
        $tag = "test-tag-standalone";
        $typeid = $this->create_type();

        $this->create_standalone_lineitem($course->id, $typeid, $resourceid, $tag);
        
        $this->assertLineItems($course, $typeid, "manualtest", null, $resourceid, $tag);
    }
    
    /**
     * Test line item URL is populated for coupled line item only
     * if there is not another line item bound to the lti instance,
     * since in that case there would be no rule to define which of
     * the line items should be actually passed.
     */
    public function test_get_launch_parameters_coupled() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        
        $typeid = $this->create_type();
        $course = $this->getDataGenerator()->create_course();

        $ltiinstance = $this->create_graded_lti($typeid, $course, 'resource-id', 'tag');

        $this->assertNotNull($ltiinstance);

        $gbservice = new gradebookservices();
        $params = $gbservice->get_launch_parameters('basic-lti-launch-request', $course->id, 111, $typeid, $ltiinstance->id); 
        $this->assertEquals('$LineItem.url', $params['lineitem_url']);
        $this->assertEquals('$LineItem.url', $params['lineitem_url']);

        $this->create_standalone_lineitem($course->id, $typeid, 'resource-id', 'tag', $ltiinstance->id);
        $params = $gbservice->get_launch_parameters('basic-lti-launch-request', $course->id, 111, $typeid, $ltiinstance->id); 
        $this->assertEquals('$LineItems.url', $params['lineitems_url']);
        // 2 line items for a single link, we cannot return a single line item url
        $this->assertFalse(array_key_exists('$LineItem.url', $params));
    }
    
    /**
     * Test line item URL is populated for not coupled line item only
     * if there is a single line item attached to that lti instance.
     */
    public function test_get_launch_parameters_decoupled() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        
        $typeid = $this->create_type();
        $course = $this->getDataGenerator()->create_course();

        $ltiinstance = $this->create_notgraded_lti($typeid, $course);

        $this->assertNotNull($ltiinstance);

        $gbservice = new gradebookservices();
        $params = $gbservice->get_launch_parameters('basic-lti-launch-request', $course->id, 111, $typeid, $ltiinstance->id); 
        $this->assertEquals('$LineItems.url', $params['lineitems_url']);
        $this->assertFalse(array_key_exists('$LineItem.url', $params));

        $this->create_standalone_lineitem($course->id, $typeid, 'resource-id', 'tag', $ltiinstance->id);
        $params = $gbservice->get_launch_parameters('basic-lti-launch-request', $course->id, 111, $typeid, $ltiinstance->id); 
        $this->assertEquals('$LineItems.url', $params['lineitems_url']);
        $this->assertEquals('$LineItem.url', $params['lineitem_url']);

        // 2 line items for a single link, we cannot return a single line item url
        $this->create_standalone_lineitem($course->id, $typeid, 'resource-id', 'tag-2', $ltiinstance->id);
        $this->assertFalse(array_key_exists('$LineItem.url', $params));
    }

    private function assertLineItems($course, $typeid, $label, $ltiinstance, $resourceid, $tag) {
        $gbservice = new gradebookservices();
        $gradeitems = $gbservice->get_lineitems($course->id, null, null, null, null, null, $typeid);

        // the 1st item in the array is the items count
        $this->assertEquals(1, $gradeitems[0]);

        $lineitem = gradebookservices::item_for_json($gradeitems[1][0], '', $typeid);
        $this->assertEquals(10, $lineitem->scoreMaximum);
        $this->assertEquals($resourceid, $lineitem->resourceId);
        $this->assertEquals($tag, $lineitem->tag);
        $this->assertEquals($label, $lineitem->label);
        
        $gradeitems = $gbservice->get_lineitems($course->id, $resourceid, null, null, null, null, $typeid);
        $this->assertEquals(1, $gradeitems[0]);

        if (isset($ltiinstance)) {
            $gradeitems = $gbservice->get_lineitems($course->id, null, $ltiinstance->id, null, null, null, $typeid);
            $this->assertEquals(1, $gradeitems[0]);
            $gradeitems = $gbservice->get_lineitems($course->id, null, $ltiinstance->id + 1, null, null, null, $typeid);
            $this->assertEquals(0, $gradeitems[0]);
        }
        
        $gradeitems = $gbservice->get_lineitems($course->id, null, null, $tag, null, null, $typeid);
        $this->assertEquals(1, $gradeitems[0]);
        
        $gradeitems = $gbservice->get_lineitems($course->id, 'an unknown resource id', null, null, null, null, $typeid);
        $this->assertEquals(0, $gradeitems[0]);
        
        $gradeitems = $gbservice->get_lineitems($course->id, null, null, 'an unknown tag', null, null, $typeid);
        $this->assertEquals(0, $gradeitems[0]);

    }

    private function create_graded_lti($typeid, $course, $resourceid, $tag) {

        $lti = array('course' => $course->id,
                    'typeid' => $typeid,
                    'instructorchoiceacceptgrades' => LTI_SETTING_ALWAYS,
                    'grade' => 10,
                    'lineitemresourceid' => $resourceid,
                    'lineitemtag' => $tag);
    
        return $this->getDataGenerator()->create_module('lti', $lti, array());
    }

    private function create_notgraded_lti($typeid, $course) {

        $lti = array('course' => $course->id,
                    'typeid' => $typeid,
                    'instructorchoiceacceptgrades' => LTI_SETTING_NEVER);
    
        return $this->getDataGenerator()->create_module('lti', $lti, array());
    }

    private function create_standalone_lineitem($courseid, $typeid, $resourceid, $tag, $ltiinstanceid = null) {
        $gbservice = new gradebookservices();
        $gbservice->add_standalone_lineitem($courseid, 
            "manualtest", 
            10, 
            "https://test.phpunit", 
            $ltiinstanceid, 
            $resourceid, 
            $tag, 
            $typeid, 
            null /*toolproxyid*/);
    }

    private function create_type() {
        $type = new stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = "Test client ID";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new stdClass();
        $config->ltiservice_gradesynchronization = 2;
        return lti_add_type($type, $config);
    }
}
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
// You should have received t, see <http://www.gnu.org/licenses/>.

namespace ltiservice_deeplinkservice;

use ltiservice_deeplinkservice\local\service\deeplinkservice as dlservice;

/**
 * Unit tests for LTI Deep Linking Service.
 *
 * @package    ltiservice_deeplinkservice
 * @category   test
 * @copyright  2023 Cengage Group
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_lti\service\deeplinkservice\local\deeplinkservice
 */
class deeplinkservice_test extends \advanced_testcase {

   /**
     * @covers ::get_links
     *
     * Tests getting existing tool links in the expected format.
     */
    public function test_get_links() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $type1id = $this->create_type('tool1');
        $type2id = $this->create_type('tool2');
        
        $service = new dlservice();
        $service->set_type(lti_get_type($type1id));
        
        $course = $this->getDataGenerator()->create_course();

        $this->create_lti($type1id, $course, 'tool1_link1');
        $this->create_lti($type1id, $course, 'tool1_link2');
        $this->create_lti($type2id, $course, 'tool2_link1');

        $links = $service->get_links($course, $type1id, 0, 0);
        $this->assertEquals(2, sizeof($links));
        $this->assert_link($links[0]);
    }

     /**
      * Inserts an lti instance.
      *
      * @param int $typeid Type Id of the LTI Tool.
      * @param object $course course where to add the lti instance.
      *
      * @return object lti instance created
      */
      private function create_lti(int $typeid, object $course, string $name) : object {
        $lti = ['course' => $course->id,
            'typeid' => $typeid,
            'name' => $name,
            'toolurl' => 'https://test.toolurl/'.$name,
            'custom' => 'a=1,b=2',
            'instructorchoiceacceptgrades' => LTI_SETTING_NEVER];
        return $this->getDataGenerator()->create_module('lti', $lti, array());
    }

    private function assert_link(object $link) {
        $name = $link->name;
        $this->assertNotNull($name);
    }

    /**
     * Creates a new LTI Tool Type.
     */
    private function create_type(string $name) {
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = $name;
        $type->description = "Example description";
        $type->clientid = "Test client ID:".$name;
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new \stdClass();
        $config->ltiservice_gradesynchronization = 2;
        return lti_add_type($type, $config);
    }
}
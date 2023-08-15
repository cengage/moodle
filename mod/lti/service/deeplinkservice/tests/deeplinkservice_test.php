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
        $this->create_lti($type1id, $course, 'tool1_link2_graded', true);
        $this->create_lti($type2id, $course, 'tool2_link1');

        $links = $service->get_links($course, $type1id, 0, 0);
        $this->assertEquals(2, count($links));
        $this->assert_link((object)$links[0], '');
        $this->assert_link((object)$links[1], '');
    }

   /**
     * @covers ::get_link
     *
     * Tests getting existing tool link in the expected format.
     */
    public function test_get_link() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $type1id = $this->create_type('tool1');

        $service = new dlservice();
        $service->set_type(lti_get_type($type1id));

        $course = $this->getDataGenerator()->create_course();

        $lti = $this->create_lti($type1id, $course, 'tool1_link1');

        $link = $service->get_link($course, $type1id, $lti->id);
        $this->assert_link((object)$link, '');
    }

   /**
     * @covers ::update_link
     *
     * Tests updating a link url and parameters.
     */
    public function test_update_link() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $type1id = $this->create_type('tool1');

        $service = new dlservice();
        $service->set_type(lti_get_type($type1id));

        $course = $this->getDataGenerator()->create_course();

        $lti = $this->create_lti($type1id, $course, 'tool1_link1');

        $link = (object)$service->get_link($course, $type1id, $lti->id);
        $variant = 'updated';
        $link->url = $link->url.$variant;
        $link->custom['b'] = $link->custom['b'].$variant;
        $link->custom = (object)$link->custom;
        $service->update_link($course, $type1id, $lti->id, $link);
        $this->assert_link((object)$service->get_link($course, $type1id, $lti->id), $variant);
    }

     /**
      * Inserts an lti instance.
      *
      * @param int $typeid Type Id of the LTI Tool.
      * @param object $course course where to add the lti instance.
      * @param bool $graded if the link is a graded link.
      *
      * @return object lti instance created
      */
      private function create_lti(int $typeid, object $course, string $name, bool $graded = false) : object {
        $lti = ['course' => $course->id,
            'typeid' => $typeid,
            'name' => $name,
            'toolurl' => 'https://test.toolurl/'.$name,
            'instructorcustomparameters' => "a=1\nb=2!1",
            'instructorchoiceacceptgrades' => LTI_SETTING_NEVER];
        if ($graded) {
            $lti['instructorchoiceacceptgrades'] = LTI_SETTING_ALWAYS;
            $lti['grade'] = 10;
            $lti['lineitemresourceid'] = 'rid_'.$name;
        }
        return $this->getDataGenerator()->create_module('lti', $lti, array());
    }

    /**
     * Asserts a link is valid.
     *
     * @param object $link link to verify
     * @param string variant if the link is updated
     */
    private function assert_link(object $link, string $variant) {
        $name = $link->title;
        $this->assertNotNull($name);
        $this->assertEquals('ltiResourceLink', $link->type);
        $this->assertEquals('https://test.toolurl/'.$name.$variant, $link->url);
        $this->assertEquals('1', $link->custom['a']);
        $this->assertEquals('2!1'.$variant, $link->custom['b']);
        if (strpos($name, 'graded') !== false) {
            $this->assertNotEmpty($link->lineItemId);
        } else {
            $this->assertEmpty($link->lineItemId ?? '');
        }
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

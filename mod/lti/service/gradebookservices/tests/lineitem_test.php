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

namespace ltiservice_gradebookservices;

use ltiservice_gradebookservices\local\resources\lineitem;
use ltiservice_gradebookservices\local\service\gradebookservices;

/**
 * Unit tests for lti lineitem.
 *
 * @package    ltiservice_gradebookservices
 * @category   test
 * @copyright  2022 Claude Vervoort <claude.vervoort@cengage.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lineitem_test extends \advanced_testcase {

    /**
     * Test updating the line item.
     */
    public function test_lti_add_coupled_lineitem() {
        $gbservice = new gradebookservices();
        $lineitemresource = new lineitem($gbservice);
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a tool type, associated with that proxy.

        $typeid = $this->create_type();
        $gbservice->set_type(lti_get_type($typeid));
        $course = $this->getDataGenerator()->create_course();
        $resourceid = 'test-resource-id';
        $tag = 'tag';
        $subreviewurl = 'https://subreview.example.com';
        $subreviewparams = 'a=2';

        $this->create_graded_lti($typeid, $course, $resourceid, $tag, $subreviewurl, $subreviewparams);
        $gradeitems = $gbservice->get_lineitems($course->id, null, null, null, null, null, $typeid);
        // The 1st item in the array is the items count.
        $this->assertEquals(1, $gradeitems[0]);
        $lineitem = gradebookservices::item_for_json($gradeitems[1][0], '', $typeid);
        $lineitem->resourceId = $resourceid.'modified';
        $lineitem->submissionReview->url = $subreviewurl.'modified';
        $lineitem->submissionReview->custom = ['a'=>'3'];
        $lineitemresource->process_put_request(json_encode($lineitem), $gradeitems[1][0], $typeid);
        $lineitem = gradebookservices::item_for_json($gradeitems[1][0], '', $typeid);
        $this->assertEquals($resourceid.'modified', $lineitem->resourceId);
        $this->assertEquals($subreviewurl.'modified', $lineitem->submissionReview->url);
        $custom = $lineitem->submissionReview->custom;
        $this->assertEquals('a=3', join("\n", array_map(fn($k) => $k.'='.$custom[$k], array_keys($custom))));
    }

    /**
     * Inserts a graded lti instance, which should create a grade_item and gradebookservices record.
     *
     * @param int $typeid Type ID of the LTI Tool.
     * @param object $course course where to add the lti instance.
     * @param string|null $resourceid resource id
     * @param string|null $tag tag
     * @param string|null $tag submission review url
     * @param string|null $tag submission review custom params
     *
     * @return object lti instance created
     */
    private function create_graded_lti(int $typeid, object $course, ?string $resourceid, ?string $tag,
            ?string $subreviewurl = null, ?string $subreviewparams = null) : object {

        $lti = ['course' => $course->id,
            'typeid' => $typeid,
            'instructorchoiceacceptgrades' => LTI_SETTING_ALWAYS,
            'grade' => 10,
            'lineitemresourceid' => $resourceid,
            'lineitemtag' => $tag,
            'lineitemsubreviewurl' => $subreviewurl,
            'lineitemsubreviewparams' => $subreviewparams];

        return $this->getDataGenerator()->create_module('lti', $lti, array());
    }

    /**
     * Creates a new LTI Tool Type.
     */
    private function create_type() {
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = "Test client ID";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new \stdClass();
        $config->ltiservice_gradesynchronization = 2;
        return lti_add_type($type, $config);
    }

}
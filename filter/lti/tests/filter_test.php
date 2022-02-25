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
 * Unit tests.
 *
 * @package filter_lti
 * @category test
 * @copyright 2021 Cengage Group
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/lti/filter.php');

/**
 * Tests for filter_lti
 *
 * @copyright 2021 Cengage Group
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_lti_testcase extends advanced_testcase {

    /**
     * Tests the filtering of lti hyperlinks
     *
     */
    function test_filtering_no_lti() {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $filter = new filter_lti($context, array());
        $noltilinkhere = "bla bla <a href=\"https://nowhere\">test</a> bla bla";
        $this->assertEquals($noltilinkhere, $filter->filter($noltilinkhere));
    }
    
    function test_filtering_embed_link_transform() {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $filter = new filter_lti($context, array());
        $withltilink = "bla bla <a data-lti=\"embed\" href=\"https://nowhere?a=1\">test</a> bla bla";
        $this->assertEquals(strpos($filter->filter($withltilink), "<iframe class=\"ltiembed\" src=\"https://nowhere?a=1&course=$course->id\" style=\"width:90%;height:400px\">"), 8);
        $this->assertFalse(strpos($filter->filter($withltilink), "<a "));
    }

    function test_filtering_embed_link_transform_with_widthheight() {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $filter = new filter_lti($context, array());
        $withltilink = "bla bla <a data-lti=\"embed;width:300px;height:440px\" href=\"https://nowhere\">test</a> bla bla";
        $this->assertEquals(strpos($filter->filter($withltilink), "<iframe class=\"ltiembed\" src=\"https://nowhere?course=$course->id\" style=\"width:300px;height:440px\">"), 8);
        $this->assertFalse(strpos($filter->filter($withltilink), "<a "));
    }
    
    function test_filtering_notenmbed_addcourseid() {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $filter = new filter_lti($context, array());
        $withltilink = "bla bla <a data-lti=\"newwin\" href=\"https://nowhere\">test</a> bla bla";
        $this->assertEquals("bla bla <a data-lti=\"newwin\" href=\"https://nowhere?course=$course->id\">test</a> bla bla", $filter->filter($withltilink));
    }
}

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

use mod_lti\local\lti_coursenav_lib;


global $CFG;
require_once($CFG->dirroot . '/mod/lti/tests/lti_test_helper.php');

/**
 * LTI Course Navigation library tests
 */
class mod_lti_coursenav_lib_testcase extends advanced_testcase {

    /**
     * Test creation, update and deletion of nav messages (used in form post when editing tool).
     */
    public function test_update_coursenavs() {
        global $DB;
        $this->resetAfterTest();
        $config = new stdClass();
        $config->lti_organizationid = '';
        $type = create_type($config);
        $menulinks = [
            "link"=>["label"=> "menulink1"]
        ];
        lti_coursenav_lib::get()->update_type_coursenavs($type->id, $menulinks);
        $coursenavs = lti_coursenav_lib::get()->load_coursenav_messages($type->id);
        $this->assertEquals(1, count($coursenavs));
        $newlink = array_values($coursenavs)[0];
        $this->assertEquals("menulink1", $newlink->label);
        $newandupdated = [
            "link1"=>["label"=> "menulink1_updated", "id"=>$newlink->id],
            "link2"=>["label"=> "menulink2", "url"=>"https://somewhere", "allowlearners"=>"1"]
        ];
        lti_coursenav_lib::get()->update_type_coursenavs($type->id, $newandupdated);
        $coursenavs = lti_coursenav_lib::get()->load_coursenav_messages($type->id);
        $this->assertEquals(2, count($coursenavs));
        $this->assertEquals("menulink1_updated", $coursenavs[$newlink->id]->label);
        $link2check = function($n) {return $n->label=="menulink2";};
        $link2a = array_filter($coursenavs, $link2check); 
        $this->assertEquals(1, count($link2a));
        $link2 = array_values($link2a)[0];
        $this->assertEquals("https://somewhere", $link2->url);
        $this->assertEquals('1', $link2->allowlearners);
        $updatedanddeleted = [
            "link2"=>["label"=> "menulink2", "url"=>"https://somewhere2", "allowlearners"=>"0", "id"=>$link2->id]
        ];
        lti_coursenav_lib::get()->update_type_coursenavs($type->id, $updatedanddeleted);
        $coursenavs = lti_coursenav_lib::get()->load_coursenav_messages($type->id);
        $this->assertEquals(1, count($coursenavs));
        $link2updated = $coursenavs[$link2->id];
        $this->assertEquals("https://somewhere2", $link2updated->url);
        $this->assertEquals('0', $link2updated->allowlearners);
    }

    public function test_course_placements() {
        $this->resetAfterTest();
        $config = new stdClass();
        $config->lti_organizationid = '';
        $type = create_type($config);
        $menulinks = [
            "link1"=>["label"=> "menulink1"],
            "link2"=>["label"=> "menulink2", "url"=>"https://somewhere", "allowlearners"=>"1"]
        ];
        lti_coursenav_lib::get()->update_type_coursenavs($type->id, $menulinks);
        self::setUser($this->getDataGenerator()->create_user());
        $course = $this->getDataGenerator()->create_course();
        $coursenavs = lti_coursenav_lib::get()->load_coursenav_links($course->id, true);
        // New course has no actual placements of the tool's course navs.
        $this->assertTrue(empty($coursenavs));
        $coursenavsaddible = lti_coursenav_lib::get()->load_coursenav_links($course->id);
        $this->assertEquals(1, count($coursenavsaddible));
        $this->assertEquals(2, count($coursenavsaddible[$type->id]->menulinks));
        $coursenavsvals = array_values($coursenavsaddible[$type->id]->menulinks);
        // Now adding those 2 links to the course.
        $fakeform = [];
        $fakeform["menulink-{$type->id}-{$coursenavsvals[0]->id}"] = "1";
        lti_coursenav_lib::get()->set_coursenav_links_from_form_data($course->id, $fakeform);
        $coursenavs = lti_coursenav_lib::get()->load_coursenav_links($course->id, true);
        $this->assertEquals(1, count($coursenavs[$type->id]->menulinks));
        $fakeform["menulink-{$type->id}-{$coursenavsvals[1]->id}"] = "1";
        lti_coursenav_lib::get()->set_coursenav_links_from_form_data($course->id, $fakeform);
        $coursenavs = lti_coursenav_lib::get()->load_coursenav_links($course->id, true);
        $this->assertEquals(2, count($coursenavs[$type->id]->menulinks));
        $coursenavslearner = lti_coursenav_lib::get()->load_coursenav_links($course->id, true, true);
        $this->assertEquals(1, count($coursenavslearner[$type->id]->menulinks));
        $coursenavsvals = array_values($coursenavslearner[$type->id]->menulinks);
        var_dump($coursenavsvals);
        $this->assertEquals($type->name, $coursenavslearner[$type->id]->name);
        $this->assertEquals('menulink2', $coursenavsvals[0]->label);
        $this->assertTrue($coursenavsvals[0]->id > 0);
    }

}
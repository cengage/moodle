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
 * This file contains a class definition for the LTI Gradebook Services
 *
 * @package    ltiservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @author     Dirk Singels, Diego del Blanco, Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ltiservice_deeplinkservice\local\service;

use mod_lti\local\ltiservice\service_base;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * A service implementing LTI Gradebook Services.
 *
 * @package    ltiservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deeplinkservice extends service_base {

    /** Scope for reading membership data */
    const SCOPE_DEEPLINKING_READ = 'https://purl.imsglobal.org/spec/lti/scope/deeplinkingitem.readonly';
    const SCOPE_DEEPLINKING_UPDATE = 'https://purl.imsglobal.org/spec/lti/scope/deeplinkingitem.update';
    const SCOPE_DEEPLINKING_ALL = 'https://purl.imsglobal.org/spec/lti/scope/deeplinkingitem';

    /**
     * Class constructor.
     */
    public function __construct() {

        parent::__construct();
        $this->id = 'deeplinkservice';
        $this->name = get_string($this->get_component_id(), $this->get_component_id());

    }

    /**
     * Get the resources for this service.
     *
     * @return array
     */
    public function get_resources() {

        if (empty($this->resources)) {
            $this->resources = array();
            $this->resources[] = new \ltiservice_deeplinkservice\local\resources\contextlinks($this);
            $this->resources[] = new \ltiservice_deeplinkservice\local\resources\deeplink($this);
        }

        return $this->resources;

    }

    /**
     * Get the scope(s) permitted for the tool relevant to this service.
     *
     * @return array
     */
    public function get_permitted_scopes() {

        $scopes = array();
        $ok = !empty($this->get_type());
        if ($ok) {
            $scopes[] = self::SCOPE_DEEPLINKING_READ;
            $scopes[] = self::SCOPE_DEEPLINKING_UPDATE;
            $scopes[] = self::SCOPE_DEEPLINKING_ALL;
        }
        /*
        if ($ok && isset($this->get_typeconfig()[$this->get_component_id()]) &&
            ($this->get_typeconfig()[$this->get_component_id()] == parent::SERVICE_ENABLED)) {
            $scopes[] = self::SCOPE_MEMBERSHIPS_READ;
        }
        */
        return $scopes;

    }

    /**
     * Get the scope(s) defined by this service.
     *
     * @return array
     */
    public function get_scopes() {
        return [self::SCOPE_DEEPLINKING_ALL, self::SCOPE_DEEPLINKING_READ, self::SCOPE_DEEPLINKING_UPDATE];
    }

    /**
     * Get existing links.
     *
     * @param \mod_lti\local\ltiservice\resource_base $resource       Resource handling the request
     * @param \context_course   $context    Course context
     * @param \course           $course     Course
     * @param string            $role       User role requested (empty if none)
     * @param int               $limitfrom  Position of first record to be returned
     * @param int               $limitnum   Maximum number of records to be returned
     * @param object            $lti        LTI instance record
     * @param \core_availability\info_module $info Conditional availability information
     *      for LTI instance (null if context-level request)
     * @param \mod_lti\local\ltiservice\response $response       Response object for the request
     *
     * @return string
     */
    public function get_links($resource, $context, $course, $typeid, $limitfrom, $limitnum, $response) {
        global $DB;
        //$type = $DB->get_record('lti_types', array('id' => $typeid));
        //TODO: eventually need to check by URL too for instances without typeid.
        $links = array_values($DB->get_records('lti', array('course' => $course->id, 'typeid' => $typeid)));
        $func = function($l) {
            return $this->toLink($l);
        };
        return array_map($func, $links);
    }
    
    /**
     * Get existing link.
     *
     * @param \mod_lti\local\ltiservice\resource_base $resource       Resource handling the request
     * @param \context_course   $context    Course context
     * @param \course           $course     Course
     * @param string            $role       User role requested (empty if none)
     * @param int               $limitfrom  Position of first record to be returned
     * @param int               $limitnum   Maximum number of records to be returned
     * @param object            $lti        LTI instance record
     * @param \core_availability\info_module $info Conditional availability information
     *      for LTI instance (null if context-level request)
     * @param \mod_lti\local\ltiservice\response $response       Response object for the request
     *
     * @return string
     */
    public function get_link($resource, $context, $course, $typeid, $linkid, $response) {
        global $DB;
        //$type = $DB->get_record('lti_types', array('id' => $typeid));
        //TODO: eventually need to check by URL too for instances without typeid.
        $lti = $DB->get_record('lti', array('course' => $course->id, 'typeid' => $typeid, 'id' => $linkid));
        return $this->toLink($lti);
    }

    /**
     * Update link.
     *
     * @param \mod_lti\local\ltiservice\resource_base $resource       Resource handling the request
     * @param \context_course   $context    Course context
     * @param \course           $course     Course
     * @param string            $role       User role requested (empty if none)
     * @param int               $limitfrom  Position of first record to be returned
     * @param int               $limitnum   Maximum number of records to be returned
     * @param object            $lti        LTI instance record
     * @param \core_availability\info_module $info Conditional availability information
     *      for LTI instance (null if context-level request)
     * @param \mod_lti\local\ltiservice\response $response       Response object for the request
     *
     * @return string
     */
    public function update_link($resource, $context, $course, $typeid, $linkid, $link, $response) {
        global $DB;
        //$type = $DB->get_record('lti_types', array('id' => $typeid));
        //TODO: eventually need to check by URL too for instances without typeid.
        $lti = $DB->get_record('lti', array('course' => $course->id, 'typeid' => $typeid, 'id' => $linkid));
        if (empty($link->custom)) {
            $lti->instructorcustomparameters = '';
        } else {
            $lti->instructorcustomparameters = params_to_string( $link->custom );
        }
        $DB->update_record('lti', $lti);
        return $this->toLink($lti);
    }

    public function toLink($lti) {
        $link = [
            'title' => $lti->name
        ];
        if (!empty($lti->instructorcustomparameters)) {
            $link['custom'] = lti_split_parameters($lti->instructorcustomparameters);
        }
        return $link;
    }

    /**
     * Adds form elements for membership add/edit page.
     *
     * @param \MoodleQuickForm $mform
     */
    public function get_configuration_options(&$mform) {
        $elementname = $this->get_component_id();
        $options = [
            get_string('notallow', $this->get_component_id()),
            get_string('allow', $this->get_component_id())
        ];

        $mform->addElement('select', $elementname, get_string($elementname, $this->get_component_id()), $options);
        $mform->setType($elementname, 'int');
        $mform->setDefault($elementname, 0);
        $mform->addHelpButton($elementname, $elementname, $this->get_component_id());
    }

    /**
     * Return an array of key/values to add to the launch parameters.
     *
     * @param string $messagetype 'basic-lti-launch-request' or 'ContentItemSelectionRequest'.
     * @param string $courseid The course id.
     * @param string $user The user id.
     * @param string $typeid The tool lti type id.
     * @param string $modlti The id of the lti activity.
     *
     * The type is passed to check the configuration
     * and not return parameters for services not used.
     *
     * @return array of key/value pairs to add as launch parameters.
     */
    public function get_launch_parameters($messagetype, $courseid, $user, $typeid, $modlti = null) {
        $launchparameters = [];
        $tool = lti_get_type_type_config($typeid);
        if (isset($tool->{$this->get_component_id()})) {
            if ($tool->{$this->get_component_id()} == parent::SERVICE_ENABLED && $this->is_used_in_context($typeid, $courseid)) {
                $launchparameters['deeplink_context_url'] = '$DeepLinkService.contextUrl';
                if (!empty($modlti)) {
                    $launchparameters['deeplink_item_url'] = '$DeepLinkService.itemUrl';
                }
            }
        }
        return $launchparameters;
    }

}

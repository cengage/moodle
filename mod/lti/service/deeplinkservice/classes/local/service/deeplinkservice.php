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
use stdClass;

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
    const SCOPE_DEEPLINKING_READ = 'https://purl.imsglobal.org/spec/lti-dl/scope/contentitem.read';
    const SCOPE_DEEPLINKING_UPDATE = 'https://purl.imsglobal.org/spec/lti-dl/scope/contentitem.update';

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
    public function get_permitted_scopes():array {
        if (!empty($this->get_type()) && isset($this->get_typeconfig()[$this->get_component_id()]) &&
            ($this->get_typeconfig()[$this->get_component_id()] == parent::SERVICE_ENABLED)) {
            return $this->get_scopes();
        }
        return [];
    }

    /**
     * Get the scope(s) defined by this service.
     *
     * @return array
     */
    public function get_scopes():array {
        return [self::SCOPE_DEEPLINKING_READ, self::SCOPE_DEEPLINKING_UPDATE];
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
    public function get_links($course, $typeid, $limitfrom, $limitnum) {
        global $DB;
        //TODO: eventually need to check by URL too for instances without typeid.
        $links = array_values($DB->get_records('lti', array('course' => $course->id, 'typeid' => $typeid)));
        $func = function($l) use ($course, $typeid) {
            return $this->toLink($course->id, $typeid, $l);
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
    public function get_link($course, $typeid, $linkid) {
        global $DB;
        //$type = $DB->get_record('lti_types', array('id' => $typeid));
        //TODO: eventually need to check by URL too for instances without typeid.
        $lti = $DB->get_record('lti', array('course' => $course->id, 'typeid' => $typeid, 'id' => $linkid));
        return $this->toLink($course->id, $typeid, $lti);
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
     * @return array
     */
    public function update_link(stdClass $course, int $typeid, int $linkid, object $link):array {
        global $DB;
        $lti = $DB->get_record('lti', array('course' => $course->id, 'typeid' => $typeid, 'id' => $linkid));
        $lti->name = $link->title ?? $lti->name;
        if (empty($link->custom)) {
            $lti->instructorcustomparameters = '';
        } else {
            $lti->instructorcustomparameters = params_to_string( $link->custom );
        }
        $DB->update_record('lti', $lti);
        return $this->toLink($course->id, $typeid, $lti);
    }

    /**
     * Converts an LTI Link to the JSON representation.
     * 
     * @param int           $courseid   id of the course
     * @param int           $typeid     id of the LTI Tool Type
     * @param object        $lti        link definition
     * 
     * @return array
     */

    private function toLink(int $courseid, int $typeid, object $lti):array {
        $dlresource = $this->resources[] = new \ltiservice_deeplinkservice\local\resources\deeplink($this);
        $link = [
            'id' => $dlresource->get_link_endpoint($courseid, $typeid, $lti->id),
            'title' => $lti->name,
            'resourceLinkId' => $lti->id
        ];
        if (!empty($lti->instructorcustomparameters)) {
            $link['custom'] = lti_split_parameters($lti->instructorcustomparameters);
        }
        return $link;
    }

    /**
     * Adds form elements for enabling the service.
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
            if ($tool->{$this->get_component_id()} == parent::SERVICE_ENABLED) {
                $launchparameters['deeplinking_contentitems_url'] = '$DeepLinkService.contextUrl';
                if (!empty($modlti)) {
                    $launchparameters['deeplinking_contentitem_url'] = '$DeepLinkService.itemUrl';
                }
                $launchparameters['deeplinking_scopes'] = implode(',', $this->get_scopes());
            }
        }
        return $launchparameters;
    }

    /**
     * Return an array of key/claim mapping allowing LTI 1.1 custom parameters
     * to be transformed to LTI 1.3 claims.
     *
     * @return array Key/value pairs of params to claim mapping.
     */
    public function get_jwt_claim_mappings(): array {
        return [
            'custom_deeplinking_scopes' => [
                'suffix' => 'lti-dl',
                'group' => 'deeplinkingservice',
                'claim' => 'scope',
                'isarray' => true
            ],
            'custom_deeplinking_contentitems_url' => [
                'suffix' => 'dl',
                'group' => 'endpoint',
                'claim' => 'contentitems',
                'isarray' => false
            ],
            'custom_deeplinking_contentitem_url' => [
                'suffix' => 'dl',
                'group' => 'endpoint',
                'claim' => 'contentitem',
                'isarray' => false
            ],
        ];
    }

}

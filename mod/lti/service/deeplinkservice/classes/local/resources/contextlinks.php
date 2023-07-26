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
 * This file contains a class definition for the Context Memberships resource
 *
 * @package    ltiservice_memberships
 * @copyright  2015 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace ltiservice_deeplinkservice\local\resources;

use mod_lti\local\ltiservice\resource_base;

defined('MOODLE_INTERNAL') || die();

/**
 * A resource implementing Context Memberships.
 *
 * @package    ltiservice_memberships
 * @since      Moodle 3.0
 * @copyright  2015 Vital Source Technologies http://vitalsource.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contextlinks extends resource_base {

 /**
     * Class constructor.
     *
     * @param \ltiservice_memberships\local\service\memberships $service Service instance
     */
    public function __construct($service) {

        parent::__construct($service);
        $this->id = 'DeepLinkContextResource';
        $this->template = '/{context_id}/bindings/{tool_code}/contextlinks';
        //$this->variables[] = 'ToolProxyBinding.memberships.url';
        $this->formats[] = 'application/vnd.1edtech.lti.contentitems+json';
        $this->methods[] = self::HTTP_GET;
    }

    /**
     * Execute the request for this resource.
     *
     * @param \mod_lti\local\ltiservice\response $response  Response object for this request.
     */
    public function execute($response) {
        global $DB;

        $params = $this->parse_template();
        $limitnum = optional_param('limit', 0, PARAM_INT);
        $limitfrom = optional_param('from', 0, PARAM_INT);

        if ($limitnum <= 0) {
            $limitfrom = 0;
        }

        try {
            /*
            if (!$this->check_tool($params['tool_code'], $response->get_request_data(),
                array(memberships::SCOPE_MEMBERSHIPS_READ))) {
                throw new \Exception(null, 401);
            }
            */
            if (!($course = $DB->get_record('course', array('id' => $params['context_id']), 'id,shortname,fullname',
                IGNORE_MISSING))) {
                throw new \Exception("Not Found: Course {$params['context_id']} doesn't exist", 404);
            }
            if (!$this->get_service()->is_allowed_in_context($params['tool_code'], $course->id)) {
                throw new \Exception(null, 404);
            }
            if (!($context = \context_course::instance($course->id))) {
                throw new \Exception("Not Found: Course instance {$course->id} doesn't exist", 404);
            }

            $links = $this->get_service()->get_links($course, $params['tool_code'], $limitfrom, $limitnum);

            $response->set_body(json_encode(['items' => $links]));

        } catch (\Exception $e) {
            $response->set_code($e->getCode());
            $response->set_reason($e->getMessage());
        }
    }

    /**
     * Parse a value for custom parameter substitution variables.
     *
     * @param string $value String to be parsed
     *
     * @return string
     */
    public function parse_value($value) {
        global $COURSE, $DB;

        if (strpos($value, '$DeepLinkService.contextUrl') !== false) {
            $this->params['context_id'] = $COURSE->id;
            if ($tool = $this->get_service()->get_type()) {
                $this->params['tool_code'] = $tool->id;
            }
            $value = str_replace('$DeepLinkService.contextUrl', parent::get_endpoint(), $value);
        }
        return $value;

    }

}
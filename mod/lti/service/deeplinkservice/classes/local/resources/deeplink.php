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
 * This file contains a class definition for Deep Linking Item resource.
 *
 * @package    ltiservice_deeplinkservice
 * @author     Claude Vervoort
 * @copyright  2023 Cengage Group
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace ltiservice_deeplinkservice\local\resources;

use mod_lti\local\ltiservice\resource_base;

/**
 * A resource implementing Deep Linking Item Service.
 *
 * @package    ltiservice_deeplinkservice
 * @copyright  2023 Cengage Group
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deeplink extends resource_base {

    /**
     * Class constructor.
     *
     * @param \ltiservice_memberships\local\service\memberships $service Service instance
     */
    public function __construct($service) {

        parent::__construct($service);
        $this->id = 'DeepLinkResource';
        $this->template = '/{context_id}/bindings/{tool_code}/contextlinks/{link_id}';
        $this->formats[] = 'application/vnd.1edtech.lti.contentitem+json';
        $this->methods[] = self::HTTP_GET;
        $this->methods[] = self::HTTP_PUT;
    }

    /**
     * Execute the request for this resource.
     *
     * @param \mod_lti\local\ltiservice\response $response  Response object for this request.
     */
    public function execute($response) {
        global $DB;

        $params = $this->parse_template();

        try {
            $scopes = [];
            if ($response->get_request_method() === self::HTTP_GET) {
                $scopes[] = deeplinkservice::SCOPE_DEEPLINKING_READ;
            } else if ($response->get_request_method() === self::HTTP_PUT) {
                $scopes[] = deeplinkservice::SCOPE_DEEPLINKING_UPDATE;
            } else {
                throw new \Exception("Operation not supported", 400);
            }
            // For LTI 1.1, should we even bother?
            $typeid = optional_param('type_id', null, PARAM_INT);

            if (!$this->check_tool($typeid, $response->get_request_data(), $scopes)) {
                    throw new \Exception(null, 401);
            }

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
            switch ($response->get_request_method()) {
                case self::HTTP_GET:
                    $link = $this->get_service()->get_link($course, $params['tool_code'], $params['link_id']);
                    $response->set_body(json_encode($link));
                    break;
                case self::HTTP_PUT:
                    $link = json_decode($response->get_request_data());
                    $updatedlink = $this->get_service()->update_link($course, $params['tool_code'], $params['link_id'], $link);
                    $response->set_body(json_encode($updatedlink));
                    $response->set_code(200);
                    break;
            }
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

        if (strpos($value, '$DeepLinkService.itemUrl') !== false) {
            $id = optional_param('id', 0, PARAM_INT); // Course Module ID.
            if (empty($id)) {
                $hint = optional_param('lti_message_hint', "", PARAM_TEXT);
                if ($hint) {
                    $hintdec = json_decode($hint);
                    if (isset($hintdec->cmid)) {
                        $id = $hintdec->cmid;
                    }
                }
            }
            if (!empty($id)) {
                $cm = get_coursemodule_from_id('lti', $id, 0, false, MUST_EXIST);
                $this->params['context_id'] = $COURSE->id;
                $this->params['link_id'] = $cm->instance;
                if ($tool = $this->get_service()->get_type()) {
                    $this->params['tool_code'] = $tool->id;
                }
                $value = str_replace('$DeepLinkService.itemUrl', parent::get_endpoint(), $value);
            }
        }
        return $value;

    }

    /**
     * Get the link endpoint.
     *
     * @param int $courseid course id;
     * @param int $typeid type id;
     * @param int $linkid link id
     *
     * @return string
     */
    public function get_link_endpoint(int $courseid, int $typeid, int $linkid):string {
        $this->params['context_id'] = $courseid;
        $this->params['link_id'] = $linkid;
        $this->params['tool_code'] = $typeid;
        return parent::get_endpoint();
    }

}

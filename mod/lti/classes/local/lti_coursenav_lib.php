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
 * A Helper for LTI Dynamic Registration.
 *
 * @package    mod_lti
 * @copyright  2020 Claude Vervoort (Cengage), Carlos Costa, Adrian Hutchinson (Macgraw Hill)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lti\local;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/lti/locallib.php');
use stdClass;

/**
 * This class exposes functions for LTI Dynamic Registration.
 *
 * @package    mod_lti
 * @copyright  2021 Claude Vervoort (Cengage), Lilian Hawasli (UCLA)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lti_coursenav_lib
{

    /**
     * Get an instance of this helper
     *
     * @return object
     */
    public static function get()
    {
        return new lti_coursenav_lib();
    }

    public function load_coursenav_messages(int $typeid) {
        global $DB;
        return $DB->get_records('lti_course_nav_messages', array('typeid' => $typeid));
    }

    /**
     * Returns LTI tools that can be placed in the course menu.
     *
     * @param int $courseid
     * @param boolean $activeonly
     * @return array
     */
    public function load_coursenav_links(int $courseid, $activeonly=false) {
        global $DB;

        $join = '';
        if (!$activeonly) {
            $join = ' LEFT ';
        }
        $records = $DB->get_recordset_sql(
            "SELECT l.id as typeid,
                    l.name as typename,
                    l.description as typedesc,
                    nav.id,
                    nav.label,
                    nav.allowlearners,
                    lc.course,
                    lc.coursenavid
            FROM {lti_course_nav_messages} AS nav 
            JOIN {lti_types} AS l ON nav.typeid=l.id
        $join JOIN {lti_course_menu_placements} AS lc ON (lc.coursenavid=nav.id AND lc.course=?)
        ORDER BY l.name, nav.label", [$courseid]
        );

        $types = [];
        foreach ($records as $record) {
            if (!array_key_exists($record->typeid, $types)) {
                $type = new stdClass();
                $type->id = $record->typeid;
                $type->name = $record->typename;
                $type->description = trim($record->typedesc);
                $type->selected = $record->course == $courseid;
                $type->menulinks = [];
                $types[$type->id] = $type;
            }
            $type = $types[$record->typeid];
            $type->selected = $record->course == $courseid || $type->selected;
            $menulink = new stdClass();
            $menulink->id = $record->id;
            $menulink->typeid = $record->typeid;
            $menulink->label = $record->label;
            $menulink->selected = $record->course == $courseid;
            $menulink->allowlearners = $record->allowlearners;
            $type->menulinks[$menulink->id] = $menulink;
        }

        return $types;
    }

    /**
     * For given course, set course menu links.
     *
     * @param int $courseid
     * @param array $menulinkspertype
     */
    public function set_coursenav_links(int $courseid, array $menulinkspertype) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        try {
            $DB->delete_records('lti_course_menu_placements', ['course' => $courseid]);

            foreach ($menulinkspertype as $typeid => $links) {
                foreach ($links as $coursenavid) {
                    $DB->insert_record('lti_course_menu_placements', (object)[
                        'typeid' => $typeid,
                        'course' => $courseid,
                        'coursenavid' => $coursenavid
                    ]);
                } 
            }
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Organize the output data from menuplacement form.
     *
     * @param array $menuitems
     */
    public function set_coursenav_links_from_form_data(int $courseid, array $menuitems) {
        $linkspertool = [];
        foreach ($menuitems as $keyset => $menuitemid) {
            $key = explode('-', $keyset);
            if ($key[0] === 'menulink' && $menuitemid === '1') {
                $linkspertool[$key[1]][]= $key[2];
            }
        }
        $this->set_coursenav_links($courseid, $linkspertool);
        return $linkspertool;
    }

    public function update_type_coursenavs($typeid, $menulinks) {
        global $DB;
        $navids = [];
        if (isset($menulinks)) {
            $onlyid = function($n) {return $n['id']??null;};
            $navids = array_filter(array_map($onlyid, $menulinks));
        }
        if (empty($navids)) {
            // No ids this means no update, so we can delete all the nav items for this tool.
            $DB->delete_records('lti_course_nav_messages', array('typeid'=> $typeid));
        } else {
            // Let's only remove the ones we are not updating.
            list($notinsql, $notinparams) = $DB->get_in_or_equal($navids, SQL_PARAMS_NAMED, 'param', false);
            $sql = "typeid = :typeid AND id {$notinsql}";
            $params = [
                'typeid' => $typeid
            ];
            $params += $notinparams;
            $DB->delete_records_select('lti_course_nav_messages', $sql, $params);
        }

        if (isset($menulinks)) {
            foreach ($menulinks as $key => $value) {
                $value["typeid"] = $typeid;
                if (empty($value["id"])) {
                    $DB->insert_record('lti_course_nav_messages', $value);
                } else {
                    $DB->update_record('lti_course_nav_messages', $value);
                }
            }
        }
    }
}
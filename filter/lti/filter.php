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
 * This filter transforms LTI href to IFrame embed
 * and execute the LTI Launch.
 *
 * @package    filter
 * @subpackage lti
 * @copyright  2022 onwards Claude Vervoort Cengage Group
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * This filter transforms LTI href to IFrame embed
 * and execute the LTI Launch.
 *
 * @package    filter
 * @subpackage lti
 * @copyright  2022 onwards Claude Vervoort Cengage Group
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_lti extends moodle_text_filter {

    /**
     * Looks for LTI href and transforms them based on current context.
     * @param string $text text
     * @param array options
     * @return string possibly modified text
     */
    public function filter($text, array $options = array()) {

        $coursecontext = $this->context->get_course_context(false);
        // LTI launches for now only execute in course contexts.
        if ($coursecontext && !is_string($text) || empty($text) || stripos($text, 'data-lti') === false ) {
            return $text;
        }

        $matches = preg_split('/(<a.*?<\/a>)/i', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if (!$matches) {
            return $text;
        }
        $newtext = '';

        foreach ($matches as $idx => $val) {
            if (stripos($val, "<a ") === 0
                && preg_match('/data-lti=\"([^\"]*)/', $val, $ltidatamatches) === 1
                && preg_match('/href=\"([^\"]*)/', $val, $hrefmatches) === 1) {
                $href = $hrefmatches[1];
                $sep = strpos($href, '?') === false ? '?' : '&';
                $href = $href.$sep."course=".$coursecontext->instanceid;
                if (strpos($ltidatamatches[1], 'embed') === 0) {
                    $width = '90%';
                    $height = '400px';
                    if (preg_match('/width:([^;]*)/', $ltidatamatches[1], $widthmatches) === 1) {
                        $width = $widthmatches[1];
                    }
                    if (preg_match('/height:([^;1]*)/', $ltidatamatches[1], $heightmatches) === 1) {
                        $height = $heightmatches[1];
                    }
                    $newtext .= "<iframe class=\"ltiembed\" src=\"$href\" style=\"width:$width;height:$height\"></iframe>";
                } else {
                    $newtext .= str_replace($hrefmatches[1], $href, $val);
                }
            } else {
                $newtext .= $val;
            }
        }
        return $newtext;
    }

}

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
 * This file launches LTI-enabled tools that do not have a course module.
 *
 * If a tool instance is added to the rich text editor, it should not also show
 * up in the list of course activities. This file passes through the
 * resource_link_id without checking the database for it.
 *
 * @package    atto_lti
 * @copyright  2021 Cengage/Claude Vervoort
 * @author     Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../../../config.php');

$resourcelinkid = required_param('resourcelinkid', PARAM_ALPHANUMEXT);
$ltitypeid = required_param('ltitypeid', PARAM_INT);
$contenturl = required_param('contenturl', PARAM_URL);
$custom = optional_param('custom', '', PARAM_RAW_TRIMMED);

echo "
<!DOCTYPE html>
<html>
    <body>
        <form action='/lib/editor/atto/plugins/lti/view.php' method='POST' id='form'>
            <input type='hidden' name='course' id='course'>
            <input type='hidden' name='resourcelinkid' value='$resourcelinkid'>
            <input type='hidden' name='contenturl' value='$contenturl'>
            <input type='hidden' name='ltitypeid' value='$ltitypeid'>
            <input type='hidden' name='custom' value='$custom'>
        </form>
        <script type='text/javascript'>
            if (window.parent.lti && window.parent.lti.course) {
                document.getElementById('course').value = window.parent.lti.course;
            }
            document.getElementById('form').submit();
        </script>
    </body>
</html>
";

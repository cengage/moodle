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

namespace tiny_lti;

use context;
use editor_tiny\plugin;
use editor_tiny\plugin_with_buttons;
use editor_tiny\plugin_with_menuitems;
use editor_tiny\plugin_with_configuration;

/**
 * Tiny LTI plugin for Moodle.
 *
 * @package    tiny_lti
 * @copyright  2023 Claude Vervoort Cengage Group <claude.vervoort@cengage.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends plugin implements
    plugin_with_menuitems,
    plugin_with_configuration {

    public static function is_enabled(
        context $context,
        array $options,
        array $fpoptions,
        ?\editor_tiny\editor $editor = null
    ): bool {
        return true;
        // Users must have permission to embed content.
        //return has_capability('tiny/lti:addembed', $context);
    }
    
    public static function get_available_menuitems(): array {
        return [
            'tiny_lti/lti',
        ];
    }

    public static function get_plugin_configuration_for_context(
        context $context,
        array $options,
        array $fpoptions,
        ?\editor_tiny\editor $editor = null
    ): array {
        list($context, $course, $cm) = get_context_info_array($context->id);
        $starturl = "";
        if ($course) {
            $url = new \moodle_url('/mod/lti/contentitem_embed_start.php', ['course'=>$course->id, 'placement'=>'richtexteditor']);
            $starturl = $url->out(false);
        } 
        return [
            'startUrl' => $starturl
        ];
    }
}

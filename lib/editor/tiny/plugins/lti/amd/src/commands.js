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
 * Tiny LTI commands.
 *
 * @module      tiny_lticommands
 * @copyright   2023 Claude Vervoort Cengage Group <claude.vervoort@cengage.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import {getButtonImage} from 'editor_tiny/utils';
import {getDeeplinkingOptions} from './options';

import {
    component,
    ltiButtonName,
    icon,
} from './common';
import DeepLinking from './deeplinking';

export const getSetup = async() => {
    const [
        buttonTooltip,
        iconImage
    ] = await Promise.all([
        getString('pluginname', component),
        getButtonImage('icon', component),
    ]);

    return async (editor) => {
        /*
        const getMenuItems = async() => {
            const request = {
                methodname: 'mod_lti_get_deeplinking_for_richtexteditor',
                args: {'contextid': getContextId(editor)}
            };
            const [tools] = await Promise.all(ajax.call([request])); 
            const items = tools.types.map(t=>{ return {
                type: 'menuitem',
                text: t.name,
                onAction: () => alert(t.id)
            };});
            console.log("items async", items)
            return items;
        }*/
        editor.ui.registry.addIcon(icon, iconImage.html);
        /*editor.ui.registry.addMenuItem(ltiButtonName, {
            icon,
            text: buttonTooltip,
            onAction: () => {
                const dl = new DeepLinking(editor);
                dl.displayDialogue();
            }
        });*/
        const items = getDeeplinkingOptions(editor).map(dl => {
            return {
                type: 'menuitem',
                text: dl.name,
                onAction: () => alert(dl.url)
            }; 
        });
        editor.ui.registry.addNestedMenuItem(ltiButtonName, {
            icon,
            text: buttonTooltip,
            getSubmenuItems: () => items
        });
    };
};
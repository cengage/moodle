import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from './common';

// Helper variables for the option names.
const startUrlProperty = getPluginOptionName(pluginName, 'startUrl');

/**
 * Options registration function.
 *
 * @param {tinyMCE} editor
 */
export const register = (editor) => {
    const registerOption = editor.options.register;

    // For each option, register it with the editor.
    // Valid type are defined in https://www.tiny.cloud/docs/tinymce/6/apis/tinymce.editoroptions/
    registerOption(startUrlProperty, {
        processor: 'string',
    });
};

/**
 * Fetch the start picker URL value for this editor instance.
 *
 * @param {tinyMCE} editor The editor instance to fetch the value for
 * @returns {object} The Id of the current course or null if not in a course
 */
export const getStartUrl = (editor) => editor.options.get(startUrlProperty);
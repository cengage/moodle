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

/*
 * @package    tiny_lti
 * @copyright  Claude Vervoort Cengage Group <claude.vervoort@cengage.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import {get_string as getString} from 'core/str';
import {component} from './common';
import * as Modal from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';

/**
 * @typedef ProblemDetail
 * @type {object}
 * @param {string} description The description of the problem
 * @param {ProblemNode[]} problemNodes The list of affected nodes
 */

/**
 * @typedef ProblemNode
 * @type {object}
 * @param {string} nodeName The node name for the affected node
 * @param {string} nodeIndex The indexd of the node
 * @param {string} text A description of the issue
 * @param {string} src The source of the image
 */

export default class {

    constructor(editor) {
        this.editor = editor;
        this.modal = null;
    }

    destroy() {
        delete this.editor;
        delete this.colorBase;

        this.modal.destroy();
        delete this.modal;
    }

    async displayDialogue() {
        this.modal = await Modal.create({
            type: Modal.types.DEFAULT,
            large: true,
            title: getString('pluginname', component),
            body: this.getDialogueContent()
        });

        // Destroy the class when hiding the modal.
        this.modal.getRoot().on(ModalEvents.hidden, () => this.destroy());

        this.modal.getRoot()[0].addEventListener('click', (event) => {
            const faultLink = event.target.closest('[data-action="highlightfault"]');
            if (!faultLink) {
                return;
            }

            event.preventDefault();

            const nodeName = faultLink.dataset.nodeName;
            let selectedNode = null;
            if (nodeName) {
                if (nodeName.includes(',') || nodeName === 'body') {
                    selectedNode = this.editor.dom.select('body')[0];
                } else {
                    const nodeIndex = faultLink.dataset.nodeIndex ?? 0;
                    selectedNode = this.editor.dom.select(nodeName)[nodeIndex];
                }
            }

            if (selectedNode && selectedNode.nodeName.toUpperCase() !== 'BODY') {
                this.selectAndScroll(selectedNode);
            }

            this.modal.hide();
        });

        this.modal.show();
    }


    /**
     * Return the dialogue content.
     *
     * @return {Promise<Array>} A template promise containing the rendered dialogue content.
     */
     async getDialogueContent() {

        let startdeeplinkingurl = 'https://int-gateway.cengage.com';
        return Templates.render('tiny_ltiwarning_content', {
            startdeeplinkingurl
        });
    }

}
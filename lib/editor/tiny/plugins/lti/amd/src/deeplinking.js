// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty omf
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
import {getStartUrl} from './options';

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
        this.callback = 'dlr'+Math.floor(Math.random() * 9999999);
        document.CALLBACKS = document.CALLBACKS || {};
    }

    destroy() {
        delete this.editor;
        delete document.CALLBACKS[this.callback];
        this.modal.destroy();
        delete this.modal;
    }

    async displayDialogue() {
        const startUrl = getStartUrl(this.editor) + `&callback=${this.callback}`;
        this.modal = await Modal.create({
            type: Modal.types.DEFAULT,
            large: true,
            title: getString('pluginname', component),
            body: this.getDialogueContent(startUrl)
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

        document.CALLBACKS[this.callback] = (response) => {
            var items = response.items || [];
            items.forEach(i=>this.addToEditor(i));
            this.modal.hide();
        };
        this.modal.show();
    }


    /**
     * Return the dialogue content.
     * @param {string} startUrl URL to start the deeplinking flow
     *
     * @return {Promise<Array>} A template promise containing the rendered dialogue content.
     */
     async getDialogueContent(startUrl) {

        return Templates.render('tiny_lti/deeplinking', {
            startUrl
        });
    }

    /**
     * Adds the LTI link to the editor
     * @param {object} item content item to add
     */
    addToEditor(item) {
        if (item.mediaType == 'application/vnd.ims.lti.v1.ltilink') {
            var mode = "embed";
            var windowTarget = "_blank";
            if (item.placementAdvice) {
                switch (item.placementAdvice.presentationDocumentTarget) {
                    case 'window':
                    case 'popup':
                    case 'overlay':
                        mode = "window";
                        windowTarget = item.placementAdvice.windowTarget || windowTarget;
                        break;
                }
            }
            var width = (item.placementAdvice && item.placementAdvice.displayWidth) ?
                parseInt(item.placementAdvice.displayWidth) : 800;
            var height = (item.placementAdvice && item.placementAdvice.displayWidth) ?
                parseInt(item.placementAdvice.displayHeight) : Math.floor(width / 4 * 3);
            // sanitize target, url, text
            this.editor.insertContent(`<a href="${item.ltiurl}"
                data-lti="${mode};width:${width}px;height:${height}px"
                target="${windowTarget}">${item.text}</a>`);
        }
    }

}
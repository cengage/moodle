YUI.add('moodle-atto_lti-button', function (Y, NAME) {

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
 * @package     atto_lti
 * @copyright   2020 The Regents of the University of California, 2021 Cengage
 * @author      David Shepard, Claude Vervoort
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * @module moodle-atto_lti-button
 */


/**
 * Atto text editor LTI activities plugin
 *
 * @namespace M.atto_lti
 * @class     Button
 * @extends    M.editor_atto.EditorPlugin
 */


require(['jquery', 'core/str'], function($, Str) {

    var errorMessage = null,
        stringPromise = Str.get_string('erroroccurred', 'atto_lti');

    $.when(stringPromise).done(function(invalid) {
        errorMessage = invalid;
    });

    document.CALLBACKS = {};

});

Y.namespace('M.atto_lti').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    _CREATEACTIVITYURL: M.cfg.wwwroot + '/lib/editor/atto/plugins/lti/view.php',
    _CONTENT_ITEM_SELECTION_URL: M.cfg.wwwroot + '/mod/lti/contentitem.php',

    _panel: null,

    _addTool: function(event, tool) {
        event.preventDefault();
        var resourceLinkId = this._createResourceLinkId(),
            host = this.get('host'),
            panel,
            courseid = this._course;

        document.CALLBACKS['f' + resourceLinkId] = function(contentItemData) {
            var items = (contentItemData && contentItemData['@graph']) || [];

            panel.hide();
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var strategyFactory = new Y.M.atto_lti.PlacementStrategyFactory();
                var strategy = strategyFactory.strategyFor(item, courseid, resourceLinkId, tool);
                var render = strategy.toHtml;
                host.insertContentAtFocusPoint(render(item));
            }
            host.saveSelection();
            host.updateOriginal();
            panel.destroy();
        };

        this._panel = new M.core.dialogue({
            bodyContent: '<iframe src="' + this._CONTENT_ITEM_SELECTION_URL +
                '?placement=richtexteditor&course=' + this._course.id +
                '&id=' + tool.id +
                '&callback=f' + resourceLinkId +
                '" width="100%" height="100%"></iframe>',
            headerContent: tool.name,
            width: '67%',
            height: '66%',
            draggable: false,
            visible: true,
            zindex: 100,
            modal: true,
            focusAfterHide: host.editor,
            focusOnPreviousTargetAfterHide: true,
            render: true
        });

        this._panel.after('visibleChange', function() {
            this._panel.destroy();
        }, this);

        panel = this._panel;

    },

    initializer: function(arg) {
        if (arg.toolTypes.length > 0) {
            this._course = arg.course;
            this._createResourceLinkId = (function(base) {
                return function() {
                    return base + '_' + (new Date()).getTime();
                };
            }(arg.resourcebase));
            this.addToolbarMenu({
                icon: M.util.image_url('icon', 'mod_lti'),
                globalItemConfig: {
                    callback: this._addTool
                },
                items: arg.toolTypes.map(function(arg) {
                    return {
                        text: arg.name,
                        callbackArgs: arg
                    };
                })
            });
        }

    }

});// This file is part of Moodle - http://moodle.org/
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
 * @package     atto_lti
 * @copyright   2020 The Regents of the University of California, 2021 Cengage
 * @author      David Shepard, Lillian Hawasli, Claude Vervoort
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_lti-button
 */

/**
 * Atto text editor LTI activities plugin
 *
 * @namespace M.atto_lti
 * @class     Button
 * @extends   M.editor_atto.EditorPlugin
 */


Y.namespace('M.atto_lti').PlacementStrategyFactory = function() {

    this.strategyFor = function(item, course, resourceLinkId, tool) {

        var StrategyClass = Y.M.atto_lti.EmbeddedContentRenderingStrategy;
        if (item.mediaType == 'application/vnd.ims.lti.v1.ltilink') {
            StrategyClass = Y.M.atto_lti.LTIIframeRenderingStrategy;
        } else if (item.placementAdvice) {
            switch (item.placementAdvice.presentationDocumentTarget) {
                case 'window':
                case 'popup':
                case 'overlay':
                    StrategyClass = Y.M.atto_lti.PlaceholderRenderingStrategy;
                    break;
            }
        }

        var strategy = new StrategyClass(item, course, resourceLinkId, tool);

        return strategy;
    };
};

Y.namespace('M.atto_lti').EmbeddedContentRenderingStrategy = function(item,
        course, resourceLinkId, tool) {

    var mimeTypePieces = item.mediaType.split('/'),
            mimeTypeType = mimeTypePieces[0];

    var TEMPLATES = {
        image: Y.Handlebars.compile('<img src="{{url}}" alt="{{alt}}" '
                + '{{#if width}}width="{{width}}" {{/if}}'
                + '{{#if height}}height="{{height}}" {{/if}}'
                + '{{#if presentation}}role="presentation" {{/if}}'
                + '{{#if customstyle}}style="{{customstyle}}" {{/if}}'
                + '{{#if classlist}}class="{{classlist}}" {{/if}}'
                + '{{#if id}}id="{{id}}" {{/if}} />'
                ),
        audio: Y.Handlebars.compile('<audio src="{{url}}" controls="controls">'
                + 'Your computer does not support audio playback'
                + '</audio>'
                ),
        video: Y.Handlebars.compile('<video src="{{url}}"'
                + '{{#if width}}width="{{width}}" {{/if}}'
                + '{{#if height}}height="{{height}}" {{/if}}'
                + '{{#if presentationAdvice.width}}width="{{width}}" {{/if}}'
                + '{{#if presentationAdvice.height}}height="{{height}}" {{/if}}'
                + '></video>'
                )
    };

    var content;

    switch (mimeTypeType) {
        case 'application':
            if (mimeTypePieces[1] == 'vnd.ims.lti.v1.ltilink') {
                content = TEMPLATES.ltiLink({
                    item: item,
                    toolid: tool.id,
                    resourcelinkid: resourceLinkId,
                    course: course
                });
            }
            break;
        case 'text':
            content = item.text;
            break;
        case 'image':
            content = TEMPLATES.image({
                url: item.url,
                alt: item.title,
                width: item.width,
                height: item.height,
                presentation: true
            });
            break;
        case 'audio':
            content = TEMPLATES.audio(item);
            break;
        case 'video':
            content = TEMPLATES.video(item);
            break;
    }

    this.toHtml = function() {
        return content;
    };

};

Y.namespace('M.atto_lti').ImageRenderingStrategy = function(item) {

    var template = Y.Handlebars.compile('<img src="{{url}}" alt="{{title}}" '
            + '{{#if width}}width="{{width}}" {{/if}}'
            + '{{#if height}}height="{{height}}" {{/if}}'
            + '{{#if presentation}}role="presentation" {{/if}}'
            + '{{#if customstyle}}style="{{customstyle}}" {{/if}}'
            + '{{#if classlist}}class="{{classlist}}" {{/if}}'
            + '{{#if id}}id="{{id}}" {{/if}}' + '/>'
            );

    this.toHtml = function() {
        return template({
            item: item
        });
    };

};

Y.namespace('M.atto_lti').LTIIframeRenderingStrategy = function(item, course,
        resourceLinkId, tool) {

    var template;

    // If the item URL is the same as the LTI Launch URL (or Content-Item request), we assume we need
    // to make an LTI Launch request.
    if (item.url !== tool.baseurl && item.url !== tool.config.
            toolurl_ContentItemSelectionRequest) {
        item.useCustomUrl = true;
    }

    var width = (item.placementAdvice && item.placementAdvice.displayWidth) ? item.placementAdvice.displayWidth : 800;
    var height = (item.placementAdvice && item.placementAdvice.displayWidth) ?
        item.placementAdvice.displayHeight : Math.floor(width / 4 * 3);

    template = Y.Handlebars.compile('<a href="/lib/editor/atto/plugins/lti/launch.php?orig_course={{courseId}}'
            + '&ltitypeid={{ltiTypeId}}&custom={{custom}}'
            + '{{#if item.useCustomUrl}}&contenturl={{item.url}}{{/if}}'
            + '&resourcelinkid={{resourcelinkid}}" '
            + ' width="{{width}}" '
            + ' height="{{height}}" '
            + 'target="_blank">{{item.text}}</a>'
            );

    this.toHtml = function() {
        return template({
            item: item,
            custom: JSON.stringify(item.custom),
            courseId: course.id,
            resourcelinkid: resourceLinkId,
            ltiTypeId: tool.id,
            width: width,
            height: height
        });
    };

};

Y.namespace('M.atto_lti').PlaceholderRenderingStrategy = function(item) {

    Y.M.atto_lti.PlaceholderRenderingStrategy.superclass.constructor.apply(
            this, arguments
            );

    var placeholder, PlaceholderClass, action;

    if (typeof item.thumbnail !== 'undefined') {
        PlaceholderClass = Y.M.atto_lti.PreviewImagePlaceholderStrategy;
    } else if (typeof item.icon !== 'undefined') {
        PlaceholderClass = Y.M.atto_lti.IconPlaceholderStrategy;
    } else {
        PlaceholderClass = Y.M.atto_lti.TextPlaceholderStrategy;
    }

    placeholder = new PlaceholderClass(item);

    switch (item.placementAdvice.presentationDocumentTarget) {
        // NOTE: this may seem redundant for now, but it is likely we will want to
        // extend this in the future.
        case 'window':
        case 'popup':
        case 'overlay':
            action = new Y.M.atto_lti.NewWindowTargetAction(item, placeholder);
            break;
    }

    this.toHtml = function() {
        return action.toHtml();
    };

};

Y.namespace('M.atto_lti').PreviewImagePlaceholderStrategy = function(item) {

    Y.M.atto_lti.PreviewImagePlaceholderStrategy.superclass.constructor.call(
            this, item
            );

    var template = Y.Handlebars
            .compile('<img src="{{thumbnail[@id}}" width="{{thumbnail.width}}" '
                    + ' height="{{thumbnail.height}}" '
                    + ' alt="{{title}}" />'
                    );

    this.toHtml = function() {
        return template({
            thumnbail: item.thumbnail,
            title: item.title
        });
    };

};

Y.namespace('M.atto_lti').IconPlaceholderStrategy = function(item) {

    Y.M.atto_lti.IconPlaceholderStrategy.superclass.constructor
            .call(this, item);

    var template = Y.Handlebars
            .compile('<img src="{{icon[@id]}}" width="{{icon.width}}" '
                    + ' height="{{icon.height}}" '
                    + ' alt="{{title}}" />'
                    );

    this.toHtml = function() {
        return template({
            icon: item.icon,
            title: item.title
        });
    };

};

Y.namespace('M.atto_lti').TextPlaceholderStrategy = function(item) {
    Y.M.atto_lti.TextPlaceholderStrategy.superclass.constructor
            .call(this, item);

    this.toHtml = function() {
        if (item.text) {
            return item.text;
        } else {
            return item.title;
        }
    };
};

Y.namespace('M.atto_lti').NewWindowTargetAction = function(item, placeholder) {

    var template = Y.Handlebars
            .compile('<a href="{{item.url}}" target="_blank">{{placeholder.toHtml()}}</a>');

    this.toHtml = function() {
        return template({
            item: item,
            placeholder: placeholder
        });
    };

};

}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});
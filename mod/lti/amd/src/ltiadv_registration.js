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
 * Encapsules the behavior for creating a tool type and tool proxy from a
 * registration url in Moodle.
 *
 * Manages the UI while operations are occuring, including rendering external
 * registration page within the iframe.
 *
 * See template: mod_lti/external_registration
 *
 * @module     mod_lti/external_registration
 * @class      external_registration
 * @package    mod_lti
 * @copyright  2015 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'mod_lti/events'],
        function($, ltiEvents) {

    var SELECTORS = {
        LTIADV_REGISTRATION_CONTAINER: '#ltiadv-registration-container'
    };

    /**
     * Return the container that holds all elements for the external registration, including
     * the cancel button and the iframe.
     *
     * @method getExternalRegistrationContainer
     * @private
     * @return {JQuery} jQuery object
     */
    var getLTIAdvRegistrationContainer = function() {
        return $(SELECTORS.LTIADV_REGISTRATION_CONTAINER);
    };


    /**
     * Stops displaying the external registration content.
     *
     * @method hideExternalRegistrationContent
     * @private
    var hideLTIAdvRegistrationContainer = function() {
        getLTIAdvRegistrationContainer().addClass('hidden');
    };
*/
    /**
     * Displays the external registration content.
     *
     * @method showExternalRegistrationContent
     * @private
     */
    var showLTIAdvRegistrationContainer = function() {
        getLTIAdvRegistrationContainer().removeClass('hidden');
    };

    /**
     * Load the external registration template and render it in the DOM and display it.
     *
     * @method renderExternalRegistrationWindow
     * @private
     * @param {Object} registrationRequest
     * @return {Promise} jQuery Deferred object
     */
    var initiateRegistration = function(url) {

        // Show the external registration page in an iframe.
        var container = getLTIAdvRegistrationContainer();
        var iframe = container.append("<iframe src='/mod/lti/startltiadvregistration.php?url="
            + encodeURIComponent( url ) + "'></iframe>");
        showLTIAdvRegistrationContainer();

        window.addEventListener("message", (e=>{
            if (e.data && 'org.ims.global.close' === e.data.subject) {
                iframe.remove();
            }
        }), false);
    };

    /**
     * Sets up the listeners for user interaction on the page.
     *
     * @method registerEventListeners
     * @private
     */
    var registerEventListeners = function() {

        $(document).on(ltiEvents.START_LTIADV_REGISTRATION, function(event, data) {
                if (!data) {
                    return;
                }
                if (data.url) {
                    initiateRegistration(data.url);
                }
            });

        // This is gross but necessary due to isolated jQuery scopes between
        // child iframe and parent windows. There is no other way to communicate.
        //
        // This function gets called by the moodle page that received the redirect
        // from the external registration page and handles the external page's returned
        // parameters.
        //
        // See AMD module mod_lti/external_registration_return.
    };

    return /** @alias module:mod_lti/external_registration */ {

        /**
         * Initialise this module.
         */
        init: function() {
            registerEventListeners();
        }
    };
});

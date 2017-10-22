/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../progresswait.ts"/>

namespace Citadel
{
    
    /**
     * 
     * 
     * @class ApplyToGroupOverlay
     */
    export class ApplyToGroupOverlay
    {

        /**
         * The div container that represents the entirety of our HTML UI. 
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf ApplyToGroupOverlay
         */
        private m_overlay: HTMLDivElement;

        /**
         * The close button. Should be available at any time, and shut down the overlay.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf ApplyToGroupOverlay
         */
        private m_closeButton: HTMLButtonElement;

        /**
         * Used to display full page, overlay progress event information to the
         * user.
         * 
         * @private
         * @type {ProgressWait}
         * @memberOf Dashboard
         */
        private m_progressWait: ProgressWait;

        constructor() 
        {
            // Build button/input handlers and references.
            this.ConstructUIElements();
        }

        private ConstructUIElements(): void
        {
            this.m_overlay = document.querySelector('#overlay_apply_togroup') as HTMLDivElement;


            this.m_closeButton = document.querySelector('#apply_togroup_close') as HTMLButtonElement;           


            this.m_closeButton.onclick = ((e:MouseEvent) =>
            {
                this.Hide();
            });

            this.Reset();
        }

        private Reset(): void
        {

            // Ensure cancel button is always available.
            this.m_closeButton.disabled = false;
        }

        /**
         * Shows the HTML UI. Takes the datatables data from the lists table and tries
         * to extract existing filter groups/namespaces to show them as suggestions
         * inside the input field.
         * 
         * @param {DataTables.DataTable} allLists
         * @param {number} [fadeInTimeMsec=200]
         * 
         * @memberOf ListUploadOverlay
         */
        public Show(fadeInTimeMsec: number = 200): void
        {
            this.Reset();
            $(this.m_overlay).fadeIn(fadeInTimeMsec);
        }

        /**
         * Hides the HTML UI.
         * 
         * @param {number} [fadeOutTimeMsec=200]
         * 
         * @memberOf ListUploadOverlay
         */
        public Hide(fadeOutTimeMsec: number = 200): void
        {
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        }
    }
}
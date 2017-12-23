/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Citadel
{
    /**
     * 
     * 
     * @export
     * @class ProgressWait
     */
    export class ProgressWait
    {

        /**
         * 
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf ProgressWait
         */
        private m_overlay: HTMLDivElement;

        /**
         * 
         * 
         * @private
         * @type {HTMLHeadingElement}
         * @memberOf ProgressWait
         */
        private m_title: HTMLHeadingElement;

        /**
         * 
         * 
         * @private
         * @type {HTMLParagraphElement}
         * @memberOf ProgressWait
         */
        private m_message: HTMLParagraphElement;

        /**
         * Creates an instance of ProgressWait.
         * 
         * 
         * @memberOf ProgressWait
         */
        constructor()
        {
            this.m_overlay = document.getElementById('overlay_loading') as HTMLDivElement;
            this.m_title = document.getElementById('overlay_loading_title') as HTMLHeadingElement;
            this.m_message = document.getElementById('overlay_loading_message') as HTMLParagraphElement;
        }

        /**
         * 
         * 
         * @param {string} title
         * @param {string} [message=""]
         * @param {number} [fadeInTimeMsec=1000]
         * 
         * @memberOf ProgressWait
         */
        public Show(title: string, message: string = "", fadeInTimeMsec: number = 200): void
        {
            this.m_title.innerText = title;
            this.m_message.innerHTML = '<div>' + message + '</div>';
            //this.m_message.innerText = message;
            $(this.m_overlay).fadeIn(fadeInTimeMsec);
        }

        /**
         * 
         * 
         * @param {number} [fadeOutTimeMsec=1000]
         * 
         * @memberOf ProgressWait
         */
        public Hide(fadeOutTimeMsec: number = 200): void
        {
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        }

    }
}

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel
{

    export class BlacklistRecord extends BaseRecord
    {
        //
        // ────────────────────────────────────────────────────────────────────────── I ──────────
        //   :::::: U S E R   D A T A   M E M B E R S : :  :   :    :     :        :          :
        // ────────────────────────────────────────────────────────────────────────────────────
        //        

        private m_blacklistId: number;

        private m_applicationName: string;
        
        private m_isActive: number;

        private m_dateRegistered: string;

        //
        // ──────────────────────────────────────────────────────────────────────────────── II ──────────
        //   :::::: E D I T O R   H T M L   E L E M E N T S : :  :   :    :     :        :          :
        // ──────────────────────────────────────────────────────────────────────────────────────────
        //

        private m_mainForm: HTMLFormElement;

        /**
         * The Div of the editing overlay. This houses all of the editor
         * contents and overlays everything else with a super high z-index.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf BlacklistRecord
         */
        private m_editorOverlay: HTMLDivElement;
        private m_editorTitle: HTMLHeadingElement;
        private m_applicationNameInput: HTMLInputElement;
        private m_isActiveInput: HTMLInputElement;
        private m_submitBtn: HTMLButtonElement;
        private m_cancelBtn: HTMLButtonElement;

        /**
         * Gets the base API route from this record type.
         * 
         * @readonly
         * @type {string}
         * @memberOf BlacklistRecord
         */
        public get RecordRoute(): string
        {
            return 'api/admin/blacklists';
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions
        {
            let validationRules: JQueryValidation.RulesDictionary = {};

            validationRules[this.m_applicationNameInput.id] = {
                required: true
            };

            let validationErrorMessages = {};
            validationErrorMessages[this.m_applicationNameInput.id] = 'Application name is required.';
            
            let validationOptions: JQueryValidation.ValidationOptions =
                {
                    rules: validationRules,
                    errorPlacement: ((error: JQuery, element: JQuery): void =>
                    {
                        error.appendTo('#blacklist_form_errors');
                        $('#blacklist_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };

            return validationOptions;
        }

        /**
         * Creates an instance of BlacklistRecord.
         * 
         * 
         * @memberOf BlacklistRecord
         */
        constructor() 
        {
            super();
            this.ConstructFormReferences();
        }

        private ConstructFormReferences(): void
        {
            this.m_mainForm = document.querySelector('#editor_blacklist_form') as HTMLFormElement;
            this.m_editorTitle = document.querySelector('#blacklist_editing_title') as HTMLHeadingElement;
            this.m_editorOverlay = document.querySelector('#overlay_blacklist_editor') as HTMLDivElement;
        
            this.m_applicationNameInput = document.querySelector('#editor_blacklist_name') as HTMLInputElement;
            this.m_isActiveInput = document.querySelector('#editor_blacklist_input_isactive') as HTMLInputElement;

            this.m_submitBtn = document.querySelector('#blacklist_editor_submit') as HTMLButtonElement;
            this.m_cancelBtn = document.querySelector('#blacklist_editor_cancel') as HTMLButtonElement;

            this.InitButtonHandlers();
        }

        private InitButtonHandlers(): void
        {
            this.m_cancelBtn.onclick = ((e: MouseEvent): any =>
            {
                this.StopEditing();
            });
        }

        protected LoadFromObject(data: Object): void
        {

            this.m_blacklistId = data['id'] as number;
            this.m_applicationName = data['name'] as string;
            this.m_isActive = data['isactive'];
            this.m_dateRegistered = data['dt'] as string;
        }

        protected LoadFromForm(): void
        {
            this.m_applicationName = this.m_applicationNameInput.value;
            this.m_isActive = this.m_isActiveInput.checked == true ? 1 : 0;
        }

        public StartEditing(userData: Object = null): void
        {
            switch (userData == null)
            {

                case true:
                    {
                        // Creating a new object here.

                        this.m_editorTitle.innerText = "Add Blacklist Application";
                        this.m_submitBtn.innerText = "Add";

                        this.m_mainForm.reset();
                    }
                    break;

                case false:
                    {
                        // Editing an existing object here.
                        this.LoadFromObject(userData);

                        this.m_editorTitle.innerText = "Edit Blacklist Application";
                        this.m_submitBtn.innerText = "Save";
                        this.m_applicationNameInput.value = this.m_applicationName;
                        this.m_isActiveInput.checked = this.m_isActive != 0;
                    }
                    break;
            }

            this.m_mainForm.onsubmit = ((e: Event): any =>
            {

                let validateOpts = this.ValidationOptions;
                let validresult = $(this.m_mainForm).validate(validateOpts).form();

                if ($(this.m_mainForm).validate(validateOpts).valid())
                {
                    return this.OnFormSubmitClicked(e, userData == null);
                }

                return false;
            });

            // Show the editor.
            $(this.m_editorOverlay).fadeIn(250);
        }

        public StopEditing(): void
        {
            $(this.m_editorOverlay).fadeOut(200);
        }

        public ToObject(): Object
        {
            let obj =
                {
                    'id': this.m_blacklistId,
                    'name': this.m_applicationName,
                    'isactive': this.m_isActive,
                    'dt': this.m_dateRegistered
                };

            return obj;
        }
    }

}
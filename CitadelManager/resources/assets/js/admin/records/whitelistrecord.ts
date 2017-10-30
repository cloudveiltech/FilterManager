/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel
{

    export class WhitelistRecord extends BaseRecord
    {
        //
        // ────────────────────────────────────────────────────────────────────────── I ──────────
        //   :::::: U S E R   D A T A   M E M B E R S : :  :   :    :     :        :          :
        // ────────────────────────────────────────────────────────────────────────────────────
        //        

        private m_whitelistId: number;

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
         * @memberOf WhitelistRecord
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
         * @memberOf WhitelistRecord
         */
        public get RecordRoute(): string
        {
            return 'api/admin/whitelists';
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
                        error.appendTo('#whitelist_form_errors');
                        $('#whitelist_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };

            return validationOptions;
        }

        /**
         * Creates an instance of WhitelistRecord.
         * 
         * 
         * @memberOf WhitelistRecord
         */
        constructor() 
        {
            super();
            this.ConstructFormReferences();
        }

        private ConstructFormReferences(): void
        {
            this.m_mainForm = document.querySelector('#editor_whitelist_form') as HTMLFormElement;
            this.m_editorTitle = document.querySelector('#whitelist_editing_title') as HTMLHeadingElement;
            this.m_editorOverlay = document.querySelector('#overlay_whitelist_editor') as HTMLDivElement;
        
            this.m_applicationNameInput = document.querySelector('#editor_whitelist_name') as HTMLInputElement;
            this.m_isActiveInput = document.querySelector('#editor_whitelist_input_isactive') as HTMLInputElement;

            this.m_submitBtn = document.querySelector('#whitelist_editor_submit') as HTMLButtonElement;
            this.m_cancelBtn = document.querySelector('#whitelist_editor_cancel') as HTMLButtonElement;

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

            this.m_whitelistId = data['id'] as number;
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

                        this.m_editorTitle.innerText = "Add Whitelist Application";
                        this.m_submitBtn.innerText = "Add";

                        this.m_mainForm.reset();
                    }
                    break;

                case false:
                    {
                        // Editing an existing object here.
                        this.LoadFromObject(userData);

                        this.m_editorTitle.innerText = "Edit Whitelist Application";
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
                    'id': this.m_whitelistId,
                    'name': this.m_applicationName,
                    'isactive': this.m_isActive,
                    'dt': this.m_dateRegistered
                };

            return obj;
        }
    }

}
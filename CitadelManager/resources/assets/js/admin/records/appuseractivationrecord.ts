/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel
{

    export class AppUserActivationRecord extends BaseRecord
    {
        //
        // ────────────────────────────────────────────────────────────────────────── I ──────────
        //   :::::: U S E R   D A T A   M E M B E R S : :  :   :    :     :        :          :
        // ────────────────────────────────────────────────────────────────────────────────────
        //        

        private m_activationId: number;
        private m_userName: string;
        private m_identifier: string;
        private m_deviceId: string;
        private m_ipAddress: string;
        private m_bypassQuantity: number;
        private m_bypassPeriod: number;
        private m_bypassUsed: number;

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
         * @memberOf GroupRecord
         */
        private m_editorOverlay: HTMLDivElement;
        /**
         * The Div of the editing overlay. This houses all of the editor
         * contents and overlays everything else with a super high z-index.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf WhitelistRecord
         */     
        private m_userNameInput: HTMLInputElement;
        private m_identifierInput: HTMLInputElement;
        private m_deviceIdInput: HTMLInputElement;
        private m_ipAddressInput: HTMLInputElement;
        private m_bypassQuantityInput: HTMLInputElement;
        private m_bypassPeriodInput: HTMLInputElement;
        private m_bypassUsedInput: HTMLInputElement;
        private m_submitBtn: HTMLButtonElement;
        private m_cancelBtn: HTMLButtonElement;
        private m_applicationNameInput: HTMLInputElement;

        /**
         * Gets the base API route from this record type.
         * 
         * @readonly
         * @type {string}
         * @memberOf WhitelistRecord
         */
        public get RecordRoute(): string
        {
            return 'api/admin/user_activations';
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions
        {
            let validationRules: JQueryValidation.RulesDictionary = {};

           
            let validationErrorMessages = {};
            validationErrorMessages[this.m_applicationNameInput.id] = 'Application name is required.';
            
            let validationOptions: JQueryValidation.ValidationOptions =
                {
                    rules: validationRules,
                    errorPlacement: ((error: JQuery, element: JQuery): void =>
                    {
                        error.appendTo('#activation_form_errors');
                        $('#activation_form_errors').append('<br/>');
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
            this.m_mainForm = document.querySelector('#editor_activation_form') as HTMLFormElement;
            this.m_editorOverlay = document.querySelector('#overlay_activation_editor') as HTMLDivElement;
            this.m_userNameInput = document.querySelector('#editor_activation_input_user_full_name') as HTMLInputElement;
            this.m_identifierInput = document.querySelector('#editor_activation_input_identifier') as HTMLInputElement;
            this.m_deviceIdInput = document.querySelector('#editor_activation_input_device_id') as HTMLInputElement;
            this.m_ipAddressInput = document.querySelector('#editor_activation_input_ip_address') as HTMLInputElement;
            
            this.m_bypassQuantityInput = document.querySelector('#editor_activation_input_bypass_quantity') as HTMLInputElement;
            this.m_bypassPeriodInput = document.querySelector('#editor_activation_input_bypass_period') as HTMLInputElement;
            this.m_bypassUsedInput = document.querySelector('#editor_activation_input_bypass_used') as HTMLInputElement;
            
            this.m_submitBtn = document.querySelector('#activation_editor_submit') as HTMLButtonElement;
            this.m_cancelBtn = document.querySelector('#activation_editor_cancel') as HTMLButtonElement;

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
            this.m_activationId = data['id'] as number;
            this.m_userName = data['user']['name'] as string;
            this.m_identifier = data['identifier'] as string;
            this.m_ipAddress = data['ip_address'] as string;
            this.m_deviceId = data['device_id'] as string;
            if(data['bypass_quantity'] != null)
                this.m_bypassQuantity = data['bypass_quantity'] as number;
            else 
                this.m_bypassQuantity = null;

            if(data['bypass_period'] == null)                 
                this.m_bypassPeriod = data['bypass_period'] as number;
            else
                this.m_bypassPeriod = null;
            this.m_bypassUsed = data['bypass_used'] as number;
            
        }

        protected LoadFromForm(): void
        {
            this.m_bypassQuantity = this.m_bypassQuantityInput.value == "" ? null:parseInt(this.m_bypassQuantityInput.value);
            this.m_bypassPeriod = this.m_bypassPeriodInput.value == "" ? null:parseInt(this.m_bypassPeriodInput.value);            
        }

        public StartEditing(userData: Object = null): void
        {
            // Editing an existing object here.
            this.LoadFromObject(userData);

            this.m_userNameInput.value = this.m_userName;
            this.m_identifierInput.value = this.m_identifier;
            this.m_deviceIdInput.value = this.m_deviceId;
            this.m_ipAddressInput.value = this.m_ipAddress;
            if(this.m_bypassQuantity != null)
                this.m_bypassQuantityInput.value = this.m_bypassQuantity.toString();
            else
                this.m_bypassQuantityInput.value = "";

            if(this.m_bypassPeriod != null)
                this.m_bypassPeriodInput.value = this.m_bypassPeriod.toString();
            else
                this.m_bypassPeriodInput.value = "";
            
            this.m_bypassUsedInput.value = this.m_bypassUsed.toString();

            this.m_mainForm.onsubmit = ((e: Event): any =>
            {
                return this.OnFormSubmitClicked(e, userData == null);
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
                    'id': this.m_activationId,
                    'bypass_quantity': this.m_bypassQuantity,
                    'bypass_period': this.m_bypassPeriod
                };

            return obj;
        }

        public Block(): void
        {
            //this.LoadFromForm();
            
            // Get this record's data as an object we can serialize.
            let dataObject = {};

            this.m_progressWait.Show('Block Record', 'Blocking record to server.');

            let ajaxSettings: JQueryAjaxSettings =
                {
                    method: "POST",
                    timeout: 60000,
                    contents: { _token: $('meta[name="csrf-token"]').attr('content') },
                    url: this.RecordRoute + '/block/' + this.m_activationId,
                    data: dataObject,
                    success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                    {
                        this.m_progressWait.Hide();
                        if (this.m_actionCompleteCallback != null)
                        {
                            this.m_actionCompleteCallback("Blocked");
                        }

                        return false;
                    },
                    error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                    {
                        this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                         setTimeout(() => 
                            {
                                this.m_progressWait.Hide();
                            }, 5000);

                        if (jqXHR.status > 399 && jqXHR.status < 500)
                        {
                            // Almost certainly auth related error. Redirect to login
                            // by signalling for logout.
                            //window.location.href = 'login.php?logout';
                        }
                        else
                        {
                            
                        }
                    }
                }

            // POST the auth request.
            $.ajax(ajaxSettings);
        }
    }

}
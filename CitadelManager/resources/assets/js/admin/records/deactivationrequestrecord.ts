/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel {
    export class DeactivationRequestRecord extends BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        FADE_IN_DELAY_TIME = 200;

        TITLE_EDIT_DEACTIVATION = 'Edit Deactivation Request';

        BTN_LABEL_SAVE = 'Save';

        URL_ROUTE = 'api/admin/deactivationreq';
        //
        // ──────────────────────────────────────────────────────────────────────────────── I ──────────
        //   :::::: R E Q U E S T   D A T A   M E M B E R S : :  :   :    :     :        :          :
        // ──────────────────────────────────────────────────────────────────────────────────────────
        //

        private m_requestId         : number;
        private m_userName          : string;
        private m_requestGranted    : number;
        private m_dateRequested     : string;

        //
        // ──────────────────────────────────────────────────────────────────────────────── II ──────────
        //   :::::: E D I T O R   H T M L   E L E M E N T S : :  :   :    :     :        :          :
        // ──────────────────────────────────────────────────────────────────────────────────────────
        //

        private m_mainForm              : HTMLFormElement;

        private m_editorOverlay         : HTMLDivElement;
        private m_editorTitle           : HTMLHeadingElement;

        private m_inputUserName         : HTMLInputElement;
        private m_inputRequestGranted   : HTMLInputElement;

        private m_btnSubmit             : HTMLButtonElement;
        private m_btnCancel             : HTMLButtonElement;

        public get RecordRoute(): string {
            return this.URL_ROUTE;
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions {
            let validationRules: JQueryValidation.RulesDictionary = {};

            let validationErrorMessages = {};

            let validationOptions: JQueryValidation.ValidationOptions = {
                rules: validationRules,
                errorPlacement: ((error: JQuery, element: JQuery): void => {
                    error.appendTo('#deactivation_request_form_errors');
                    $('#deactivation_request_form_errors').append('<br/>');
                }),
                messages: validationErrorMessages
            };

            return validationOptions;
        }

        constructor() {
            super();
            this.ConstructFormReferences();
        }

        private ConstructFormReferences(): void {
            this.m_mainForm = document.querySelector('#editor_deactivation_request_form') as HTMLFormElement;
            this.m_editorTitle = document.querySelector('#deactivation_request_editing_title') as HTMLHeadingElement;
            this.m_editorOverlay = document.querySelector('#overlay_deactivation_request_editor') as HTMLDivElement;

            this.m_inputUserName = document.querySelector('#editor_deactivation_request_input_username') as HTMLInputElement;
            this.m_inputUserName.disabled = true;

            this.m_inputRequestGranted = document.querySelector('#editor_deactivation_request_input_isgranted') as HTMLInputElement;

            this.m_btnSubmit = document.querySelector('#deactivation_request_editor_submit') as HTMLButtonElement;
            this.m_btnCancel = document.querySelector('#deactivation_request_editor_cancel') as HTMLButtonElement;

            this.InitButtonHandlers();
        }

        private InitButtonHandlers(): void {
            this.m_btnCancel.onclick = ((e: MouseEvent): any => {
                this.StopEditing();
            });
        }

        protected LoadFromObject(data: Object): void {
            this.m_requestId            = data['id'] as number;
            this.m_userName             = data['user']['email'] as string;
            this.m_requestGranted       = data['granted'];
            this.m_dateRequested        = data['time_first_requested'] as string;
        }

        protected LoadFromForm(): void {
            this.m_requestGranted       = this.m_inputRequestGranted.checked == true ? 1 : 0;
        }

        public ToObject(): Object {
            let obj = {
                'id'                    : this.m_requestId,
                'user_name'             : this.m_userName,
                'granted'               : this.m_requestGranted,
                'time_first_requested'  : this.m_dateRequested
            };

            return obj;
        }

        public StartEditing(data: Object = null): void {
            switch (data == null) {
                case true:
                    {
                        // Only app users can create deactivation requests.
                    }
                    break;

                case false:
                    {
                        this.LoadFromObject(data);
                        this.m_editorTitle.innerText = this.TITLE_EDIT_DEACTIVATION;
                        this.m_btnSubmit.innerText = this.BTN_LABEL_SAVE;

                        this.m_inputUserName.value = this.m_userName;
                        this.m_inputRequestGranted.checked = this.m_requestGranted != 0;
                    }
                    break;
            }

            this.m_mainForm.onsubmit = ((e: Event): any => {
                let validateOpts = this.ValidationOptions;
                let validresult = $(this.m_mainForm).validate(validateOpts).form();

                if ($(this.m_mainForm).validate(validateOpts).valid()) {
                    return this.OnFormSubmitClicked(e, data == null);
                }

                return false;
            });

            $(this.m_editorOverlay).fadeIn(this.FADE_IN_DELAY_TIME);
        }

        public StopEditing(): void {
            $(this.m_editorOverlay).fadeOut(this.FADE_IN_DELAY_TIME);
        }

    }
}
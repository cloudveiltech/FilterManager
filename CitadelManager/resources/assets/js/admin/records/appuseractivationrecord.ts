/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel {

    export class AppUserActivationRecord extends BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        ERROR_MESSAGE_APP_NAME      = 'Application name is required.';

        MESSAGE_REPORT_LABEL        = 'Report blocked sites back to server';
        MESSAGE_NO_REPORT_LABEL     = 'No reporting back to server';

        MESSAGE_ACTION_FAILED       = 'Error reported by the server during action.\n %ERROR_MSG% \nCheck console for more information.';
        MESSAGE_ACTION_BLOCK        = 'Blocking record to server.';

        TITLE_ACTION_FAILED         = 'Action Failed';
        TITLE_ACTION_BLOCK          = 'Block Record';

        ERROR_MESSAGE_DELAY_TIME    = 5000;
        FADE_IN_DELAY_TIME          = 200;

        URL_ROUTE                   = 'api/admin/user_activations';

        // ──────────────────────────────────────────────────────
        //   :::::: APP USER ACTIVATION   M E M B E R S ::::::
        // ──────────────────────────────────────────────────────
        private m_activationId              : number;
        private m_groupId                   : number;
        private m_userName                  : string;
        private m_identifier                : string;
        private m_deviceId                  : string;
        private m_ipAddress                 : string;
        private m_bypassQuantity            : number;
        private m_bypassPeriod              : number;
        private m_bypassUsed                : number;
        private m_reportLevel               : number;
        private m_os                        : string;

        private configOverride : any;
        private selfModeration : any;
        private activationWhitelist : any;
        private triggerBlacklist : any;

        // ──────────────────────────────────────────────────────────
        //   :::::: E D I T O R   H T M L   E L E M E N T S ::::::
        // ──────────────────────────────────────────────────────────
        private m_mainForm                  : HTMLFormElement;

        private m_editorOverlay             : HTMLDivElement;

        private m_inputUserName             : HTMLInputElement;
        private m_inputIdentifier           : HTMLInputElement;
        private m_inputDeviceId             : HTMLInputElement;
        private m_inputIPAddress            : HTMLInputElement;
        private m_inputBPQuantity           : HTMLInputElement;    // Bypass Quantity
        private m_inputBPPeriod             : HTMLInputElement;    // Bypass Period
        private m_inputBPUsed               : HTMLInputElement;    // Bypass Used
        private m_inputAppName              : HTMLInputElement;
        private m_inputReportLevel          : HTMLInputElement;
        private m_labelReportLevel          : HTMLLabelElement;
        private m_selectGroup               : HTMLSelectElement;

        private m_blacklistTable            : SelfModerationTable;
        private m_whitelistTable            : SelfModerationTable;
        private m_triggerBlacklistTable     : SelfModerationTable;

        private m_btnSubmit                 : HTMLButtonElement;
        private m_btnCancel                 : HTMLButtonElement;

        private m_bindings                  : BindingInstance;

        constructor() {
            super();
            this.ConstructFormReferences();
        }

        public get RecordRoute(): string {
            return this.URL_ROUTE;
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions {
            let validationRules: JQueryValidation.RulesDictionary = {};

            let validationErrorMessages = {};
            validationErrorMessages[this.m_inputAppName.id] = this.ERROR_MESSAGE_APP_NAME;

            let validationOptions: JQueryValidation.ValidationOptions = {
                rules: validationRules,
                errorPlacement: ((error: JQuery, element: JQuery): void => {
                    error.appendTo('#activation_form_errors');
                    $('#activation_form_errors').append('<br/>');
                }),
                messages: validationErrorMessages
            };

            return validationOptions;
        }

        private ConstructFormReferences(): void {
            this.m_mainForm             = document.querySelector('#editor_activation_form') as HTMLFormElement;
            this.m_editorOverlay        = document.querySelector('#overlay_activation_editor') as HTMLDivElement;

            this.m_bindings = new BindingInstance(this.m_editorOverlay, this);
            this.m_bindings.Bind();
            this.m_bindings.Refresh();

            this.m_inputUserName        = document.querySelector('#editor_activation_input_user_full_name') as HTMLInputElement;
            this.m_inputIdentifier      = document.querySelector('#editor_activation_input_identifier') as HTMLInputElement;
            this.m_inputDeviceId        = document.querySelector('#editor_activation_input_device_id') as HTMLInputElement;
            this.m_inputIPAddress       = document.querySelector('#editor_activation_input_ip_address') as HTMLInputElement;
            this.m_inputReportLevel     = document.querySelector('#editor_activation_report_level') as HTMLInputElement;
            this.m_labelReportLevel     = document.querySelector('#editor_activation_report_level_text') as HTMLLabelElement;

            this.m_inputBPQuantity      = document.querySelector('#editor_activation_input_bypass_quantity') as HTMLInputElement;
            this.m_inputBPPeriod        = document.querySelector('#editor_activation_input_bypass_period') as HTMLInputElement;
            this.m_inputBPUsed          = document.querySelector('#editor_activation_input_bypass_used') as HTMLInputElement;

            this.m_btnSubmit            = document.querySelector('#activation_editor_submit') as HTMLButtonElement;
            this.m_btnCancel            = document.querySelector('#activation_editor_cancel') as HTMLButtonElement;
            this.m_selectGroup          = document.querySelector('#editor_ctivation_input_group_id') as HTMLSelectElement;
            this.InitButtonHandlers();
        }

        private InitSelfModerationTables(): void {
            this.m_blacklistTable = new SelfModerationTable(document.querySelector('#activation_self_moderation_table'), this.selfModeration);
            this.m_whitelistTable = new SelfModerationTable(document.querySelector('#activation_whitelist_table'), this.activationWhitelist);
            this.m_triggerBlacklistTable = new SelfModerationTable(document.querySelector('#activation_trigger_table'), this.triggerBlacklist);

            this.m_blacklistTable.render();
            this.m_whitelistTable.render();
            this.m_triggerBlacklistTable.render();
        }

        private InitButtonHandlers(): void {
            let that = this;

            this.m_inputReportLevel.onchange = ((e: MouseEvent): any => {
                if (that.m_inputReportLevel.checked)
                    that.m_labelReportLevel.innerHTML = this.MESSAGE_REPORT_LABEL;
                else
                    that.m_labelReportLevel.innerHTML = this.MESSAGE_NO_REPORT_LABEL;
            });

            this.m_btnCancel.onclick = ((e: MouseEvent): any => {
                this.StopEditing();
            });
        }

        protected LoadFromObject(data: Object): void {
            console.log(data)

            this.m_activationId     = data['id'] as number;
            this.m_userName         = data['name'] as string;
            this.m_identifier       = data['identifier'] as string;
            this.m_ipAddress        = data['ip_address'] as string;
            this.m_deviceId         = data['device_id'] as string;
            this.m_bypassQuantity   = (data['bypass_quantity'] != null) ? data['bypass_quantity'] as number: null;
            this.m_bypassPeriod     = (data['bypass_period'] != null) ? data['bypass_period'] as number : null;
            this.m_bypassUsed       = data['bypass_used'] as number;
            this.m_reportLevel      = data['report_level'] as number;
            this.m_os               = data['platform_name'] as string;
            this.m_groupId         = data['group_id'] as number;

            if('config_override' in data && data['config_override'] != null) {
                try {
                    this.configOverride = JSON.parse(data['config_override']);
                } catch(e) {
                    this.configOverride = null;
                }
            } else {
                this.configOverride = null;
            }

            if(this.configOverride) {
                this.selfModeration = this.configOverride.SelfModeration;
                this.activationWhitelist = this.configOverride.CustomWhitelist;
                this.triggerBlacklist = this.configOverride.CustomTriggerBlacklist;
            } else {
                this.selfModeration = null;
                this.activationWhitelist = null;
                this.triggerBlacklist = null;
            }
        }

        private getValueFromSelect(selectBox: HTMLSelectElement): number {
            let val = -1;
            if (selectBox.selectedIndex != -1) {
                let option = selectBox.options[selectBox.selectedIndex] as HTMLOptionElement;
                val = parseInt(option.value);
            }

            return val;
        }

        protected LoadFromForm(): void {
            this.m_bypassQuantity   = this.m_inputBPQuantity.value == "" ? null : parseInt(this.m_inputBPQuantity.value);
            this.m_bypassPeriod     = this.m_inputBPPeriod.value == "" ? null : parseInt(this.m_inputBPPeriod.value);
            this.m_reportLevel      = this.m_inputReportLevel.checked ? 1 : 0;

            this.m_groupId          = this.getValueFromSelect(this.m_selectGroup);

            this.selfModeration = this.m_blacklistTable.getData();
            this.activationWhitelist = this.m_whitelistTable.getData();
            this.triggerBlacklist = this.m_triggerBlacklistTable.getData();

            this.configOverride = JSON.stringify({
                SelfModeration: this.selfModeration,
                CustomWhitelist: this.activationWhitelist,
                CustomTriggerBlacklist: this.triggerBlacklist
            });
        }

        public StartEditing(allGroups, userData: Object = null): void {
            this.LoadFromObject(userData);

            if (this.m_selectGroup.options != null) {
                this.m_selectGroup.options.length = 0;
            }

            let option = document.createElement('option') as HTMLOptionElement;
            option.text = " ";
            option.value = "-1";
            this.m_selectGroup.options.add(option);

            for (var elm of allGroups) {
                let option = document.createElement('option') as HTMLOptionElement;
                option.text = elm['name'];
                option.value = elm['id'];
                this.m_selectGroup.options.add(option);
            }

            this.m_inputUserName.value      = this.m_userName;
            this.m_inputIdentifier.value    = this.m_identifier;
            this.m_inputDeviceId.value      = this.m_deviceId;
            this.m_inputIPAddress.value     = this.m_ipAddress;
            this.m_inputBPQuantity.value    = (this.m_bypassQuantity != null) ? this.m_bypassQuantity.toString() : '';
            this.m_inputBPPeriod.value      = (this.m_bypassPeriod != null) ? this.m_bypassPeriod.toString() : '';
            this.m_inputBPUsed.value        = this.m_bypassUsed.toString();
            this.m_inputReportLevel.checked = (this.m_reportLevel === 1);

            let optionInList = this.m_selectGroup.querySelector('option[value="' + this.m_groupId.toString() + '"]') as HTMLOptionElement;
            if (optionInList != null) {
                this.m_selectGroup.selectedIndex = optionInList.index;
            } else {
                this.m_selectGroup.selectedIndex  = -1;
            }

            if (this.m_reportLevel === 1) {
                this.m_labelReportLevel.innerHTML = this.MESSAGE_REPORT_LABEL;
            } else {
                this.m_labelReportLevel.innerHTML = this.MESSAGE_NO_REPORT_LABEL;
            }

            this.InitSelfModerationTables();

            this.m_mainForm.onsubmit = ((e: Event): any => {
                return this.OnFormSubmitClicked(e, userData == null);
            });

            $(this.m_editorOverlay).fadeIn(this.FADE_IN_DELAY_TIME);
        }

        public StopEditing(): void {
            $(this.m_editorOverlay).fadeOut(this.FADE_IN_DELAY_TIME);
        }

        public ToObject(): Object {
            let obj = {
                'id'                : this.m_activationId,
                'group_id'          : this.m_groupId,
                'bypass_quantity'   : this.m_bypassQuantity,
                'bypass_period'     : this.m_bypassPeriod,
                'bypass_used'       : this.m_bypassUsed,
                'report_level'      : this.m_reportLevel,
                'config_override'   : this.configOverride
            };

            return obj;
        }

        public Block(): void {
            let dataObject = {};

            this.m_progressWait.Show(this.TITLE_ACTION_BLOCK, this.MESSAGE_ACTION_BLOCK);

            let ajaxSettings: JQueryAjaxSettings = {
                method: "POST",
                timeout: 60000,
                url: this.RecordRoute + '/block/' + this.m_activationId,
                data: dataObject,
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    this.m_progressWait.Hide();
                    if (this.m_actionCompleteCallback != null) {
                        this.m_actionCompleteCallback("Blocked");
                    }

                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    this.m_progressWait.Show(this.TITLE_ACTION_FAILED, this.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));

                    setTimeout(() => {
                        this.m_progressWait.Hide();
                    }, this.ERROR_MESSAGE_DELAY_TIME);
                }
            }

            $.ajax(ajaxSettings);
        }

        public addNewSelfModerationSite(): void {
            this.m_blacklistTable.add();
        }

        public addNewWhitelistSite(): void {
            this.m_whitelistTable.add();
        }

        public addNewCustomTextTrigger(): void {
            this.m_triggerBlacklistTable.add();
        }

        public resetBypassUsed(): void {
            this.m_bypassUsed = 0;
            this.m_inputBPUsed.value = this.m_bypassUsed.toString();
        }
    }
}

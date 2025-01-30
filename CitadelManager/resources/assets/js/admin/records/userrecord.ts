/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel {
    export class SelfModerationTable {
        private table: HTMLTableElement;
        private data: any;
        private __nextId: number;

        private editInfo: any;
        private autoCompleteSource: string;

        public constructor(table: HTMLTableElement, data: any, autoCompleteSource?: any) {
            if(data != null && typeof data === 'object') {
                data = Object.values(data);
            }
            data = data || [];

            this.__nextId = 1;

            this.table = table;
            this.data = this.fromStrings(data);

            this.autoCompleteSource = autoCompleteSource;

        }

        private toStrings(): string[] {
            var sites = [];
            for (var i = 0; i < this.data.length; i++) {
                sites.push(this.data[i].site);
            }

            return sites;
        }

        private fromStrings(data: string[]): object[] {
            var processed = [];

            for (var i = 0; i < data.length; i++) {
                processed.push({
                    id: this.nextId(),
                    site: data[i]
                });
            }

            return processed;
        }

        private findSiteObj(id: number): object {
            for (var i = 0; i < this.data.length; i++) {
                if (this.data[i].id === id) {
                    return this.data[i];
                }
            }

            return null;
        }

        private nextId(): number {
            var n = this.__nextId;
            this.__nextId += 1;
            return n;
        }

        /* Taken from https://stackoverflow.com/questions/7616461/generate-a-hash-from-string-in-javascript */

        /* Not currently used. Might want this? */
        private siteIdHash(s: string): number {
            let hash = 0;
            if (s.length === 0) return hash;

            let i = 0;
            for (i = 0; i < s.length; i++) {
                const chr = s.charCodeAt(i);
                hash = ((hash << 5) - hash) + chr;
                hash |= 0;
            }

            return hash;
        }

        public setData(data: any): void {
            this.data = this.fromStrings(data);
        }

        public getData(): any {
            return this.toStrings();
        }

        public add(): void {
            var that = this;

            var id = this.nextId();

            this.data.push({
                id: id,
                site: ""
            });

            this.render();

            setTimeout(function () {
                that.edit(id);
            });
        }

        public remove(id: number): void {
            for (var i = 0; i < this.data.length; i++) {
                if (this.data[i].id == id) {
                    break;
                }
            }

            if (i < this.data.length) {
                this.data.splice(i, 1);
            }

            this.render();
        }

        public edit(id: number): void {
            if (this.editInfo && this.editInfo.inProgress) {
                return;
            }

            var siteObj = this.findSiteObj(id);

            var toEdit = this.table.querySelector("[data-id='" + id + "']");

            this.editInfo = {
                oldHtml: toEdit.innerHTML,
                inProgress: true,
                editor: this.editorRow(siteObj),
                siteObj: siteObj
            };

            toEdit.innerHTML = "";
            toEdit.parentNode.replaceChild(this.editInfo.editor, toEdit);

            this.editInfo.editor.querySelector('input').focus();
        }

        public endEdit(): void {
            if (!this.editInfo || !this.editInfo.inProgress) {
                return;
            }

            var elem = this.editInfo.editor;

            var siteObj = this.editInfo.siteObj;
            elem.parentNode.replaceChild(this.renderRow(siteObj), elem);

            this.editInfo = {inProgress: false};
        }

        private renderRow(siteObj): any {
            var that = this;

            var tr = document.createElement("tr");
            tr.setAttribute("data-id", siteObj.id);

            var siteTd = document.createElement("td");
            var editTd = document.createElement("td");
            var deleteTd = document.createElement("td");

            siteTd.innerHTML = siteObj.site;

            editTd.appendChild(this.button("Edit", function () {
                that.edit(siteObj.id);
            }));

            deleteTd.appendChild(this.button("Delete", function () {
                that.remove(siteObj.id);
            }));

            tr.appendChild(siteTd);
            tr.appendChild(editTd);
            tr.appendChild(deleteTd);

            return tr;
        }

        private button(text: string, onclick: any): any {
            var button = document.createElement("button");
            button.setAttribute("type", "button");

            button.innerHTML = text;
            button.addEventListener("click", onclick);

            return button;
        }

        public render(): void {
            var table = this.table;
            var tbody = this.table.querySelector("tbody");

            tbody.innerHTML = "";

            for (var i = 0; i < this.data.length; i++) {
                var obj = this.data[i];

                var tr = this.renderRow(obj);
                tbody.appendChild(tr);
            }
        }

        public editorRow(siteObj: any): any {
            var that = this;

            var site = siteObj.site;

            var tr = document.createElement("tr");
            tr.setAttribute("data-id", siteObj.id);
            var inputTd = document.createElement("td");
            var doneTd = document.createElement("td");
            var deleteTd = document.createElement("td");

            var input = document.createElement("input");
            input.setAttribute("type", "text");
            input.value = site;

            input.addEventListener("keypress", function (e) {
                if (e.which == 13) {
                    that.editInfo.siteObj.site = input.value;
                    that.endEdit();
                    e.preventDefault();
                    return false;
                }
            });

            if(this.autoCompleteSource != "") {
                $(input).autocomplete({
                    source: this.autoCompleteSource
                })
            }
            var button = document.createElement("button");
            button.innerHTML = "Done";

            button.addEventListener("click", function () {
                that.editInfo.siteObj.site = input.value;
                that.endEdit();
            });

            inputTd.appendChild(input);
            doneTd.appendChild(this.button("Done", function () {
                that.editInfo.siteObj.site = input.value;
                that.endEdit();
            }));

            tr.appendChild(inputTd);
            tr.appendChild(doneTd);
            tr.appendChild(deleteTd);

            return tr;
        }
    }

    export class UserRecord extends BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        ERROR_MESSAGE_EMAIL = 'A valid email address is required.';
        ERROR_MESSAGE_PASSWORD = 'Password must be specified and match the password confirmation field.';
        ERROR_MESSAGE_CONFIRM_PASSWORD = 'Password confirmation must be specified and match the password field.';
        ERROR_MESSAGE_ACTIVATION = 'Total number of permitted activations must be specified.';

        ERROR_MESSAGE_DELAY_TIME = 5000;
        FADE_IN_DELAY_TIME = 200;

        MESSAGE_BLOCK_ACTIVATION_CONFIRM = 'Are you sure you want to delete this activation and block the token?  The user will need to sign in again.';
        MESSAGE_DELETE_ACTIVATION_CONFIRM = 'Are you sure you want to delete this activation?';
        MESSAGE_ACTION_FAILED = 'Error reported by the server during action.\n %ERROR_MSG% \nCheck console for more information.';
        MESSAGE_INVALID_CHECKED_IN_DAYS = 'Checked-In should be greater equal than 0.';

        TITLE_NEW_USER = 'Create New User';
        TITLE_EDIT_USER = 'Edit User';
        TITLE_ACTION_FAILED = 'Action Failed';

        BTN_NEW_USER = 'Create';
        BTN_EDIT_USER = 'Save';

        URL_ROUTE = 'api/admin/users';
        URL_UPDATE_ALERT = 'api/admin/activations/update_alert';
        URL_UPDATE_CHECK_IN_DAYS = 'api/admin/activations/update_check_in_days';
        URL_DELETE_ACTIVATION = 'api/admin/user_activations/delete';
        URL_BLOCK_ACTIVATION = 'api/admin/user_activations/block';
        URL_REFRESH_ACTIVATIONS = 'api/admin/user/{user_id}/activations';
        URL_GET_APP_AUTOSUGGEST = 'api/admin/app_suggest';


        // ───────────────────────────────────────────────────
        //   :::::: U S E R   D A T A   M E M B E R S ::::::
        // ───────────────────────────────────────────────────
        private m_id: number;
        private m_fullName: string;
        private m_email: string;
        private m_password: string;
        private m_groupId: number;
        private m_roleId: number;
        private m_numActivations: number;
        private m_customerId: number;
        private m_isActive: number;
        private m_registeredAt: string;
        private m_numBypassesPermitted: number;
        private m_bypassDuration: number;
        private m_relaxedPolicyPasscode: string;
        private m_relaxedPolicyPasscodeEnabled: number;

        // ─────────────────────────────────────────────────────────
        //   :::::: E D I T O R   H T M L   E L E M E N T S ::::::
        // ─────────────────────────────────────────────────────────
        private m_mainForm: HTMLFormElement;

        private m_editorOverlay: HTMLDivElement;

        private m_editorTitleValue: string;

        // ─────────────────────────────────────────────
        //   ::::: I N P U T    E L E M E N T S ::::::
        // ─────────────────────────────────────────────
        private m_emailInputId: string;
        private m_inputPasswordId: string;
        private m_inputPasswordConfirm: HTMLInputElement;
        private m_activationCountInputId: string;
        private m_inputCustomerId: HTMLInputElement;
        private m_inputIsActive: HTMLInputElement;
        private m_inputRelaxedPolicyPasscodeEnabled: HTMLInputElement;
        private m_disableDns: HTMLInputElement;

        // ───────────────────────────────────────────────
        //   ::::: S E L E C T    E L E M E N T S ::::::
        // ───────────────────────────────────────────────
        private m_selectGroup: HTMLSelectElement;
        private m_selectRole: HTMLSelectElement;

        // ───────────────────────────────────────────────
        //   ::::: B U T T O N    E L E M E N T S ::::::
        // ───────────────────────────────────────────────
        private m_btnSubmit: HTMLButtonElement;
        private m_btnCancel: HTMLButtonElement;

        private activationData: any[];
        private myConfigData: any;
        private selfModeration: any;
        private customWhitelist: any;
        private customBypasslist: any;
        private customTriggers: any;
        private appBlocklist: any;
        private allGroups: any;
        private isDnsDisabled: any;

        // ─────────────────────────────────────────────────
        //   ::::: A C T I V A T I O N    T A B L E ::::::
        // ─────────────────────────────────────────────────
        private m_tableSettings: DataTables.Settings;
        private m_tableColumns: DataTables.ColumnSettings[];
        private m_ActivationTables: DataTables.Api;

        /**
         * SELF MODERATION TABLE
         */
        private m_selfModerationTable: SelfModerationTable;
        private m_customWhitelistTable: SelfModerationTable;
        private m_customBypasslistTable: SelfModerationTable;
        private m_textTriggerTable: SelfModerationTable;
        private m_appBlocklistTable: SelfModerationTable;

        private m_timeRestrictionsUI: TimeRestrictionUI

        private userData: any;

        // ─────────────────────────────────────────────────
        //   ::::: M E M B E R     F U N C T I O N S ::::::
        // ─────────────────────────────────────────────────


        constructor() {
            super();

            this.ConstructFormReferences();
        }

        private m_bindings: BindingInstance;

        private ConstructFormReferences(): void {
            this.m_editorOverlay = document.querySelector('#overlay_user_editor') as HTMLDivElement;

            this.m_bindings = new BindingInstance(this.m_editorOverlay, this);
            this.m_bindings.Bind();
            this.m_bindings.Refresh();
            this.m_timeRestrictionsUI = new TimeRestrictionUI(this.m_bindings, "#time_restrictions_tab");

            this.m_mainForm = document.querySelector('#editor_user_form') as HTMLFormElement;

            this.m_emailInputId = "editor_user_input_username";
            this.m_inputPasswordId = "editor_user_input_password";
            this.m_activationCountInputId = "editor_user_input_num_activations";

            this.m_selectGroup = document.querySelector('#editor_user_input_group_id') as HTMLSelectElement;
            this.m_selectRole = document.querySelector('#editor_user_input_role_id') as HTMLSelectElement;
            this.m_inputIsActive = document.querySelector('#editor_user_input_isactive') as HTMLInputElement;
            this.m_inputCustomerId = document.querySelector('#editor_user_input_customer_id') as HTMLInputElement;
            this.m_disableDns = document.querySelector('#editor_user_disable_dns') as HTMLInputElement;
            this.m_inputRelaxedPolicyPasscodeEnabled = document.querySelector('#editor_user_input_passcode_enabled') as HTMLInputElement;
            this.m_btnSubmit = document.querySelector('#user_editor_submit') as HTMLButtonElement;
            this.m_btnCancel = document.querySelector('#user_editor_cancel') as HTMLButtonElement;
        }

        public get RecordRoute(): string {
            return this.URL_ROUTE;
        }

        // ─────────────────────────────────────────────────────────
        //   ::::: G E T / R E M O V E     F U N C T I O N S ::::::
        // ─────────────────────────────────────────────────────────
        private getIdFromElementId(element_id: string): number {
            // styled <ID>_<NUMBER>
            var arr_id = element_id.split('_');

            return arr_id.length > 1 ? parseInt(arr_id[1]) : -1;
        }

        private getValueFromSelect(selectBox: HTMLSelectElement): number {
            let val = -1;
            if (selectBox.selectedIndex != -1) {
                let option = selectBox.options[selectBox.selectedIndex] as HTMLOptionElement;
                val = parseInt(option.value);
            }

            return val;
        }

        private refreshActivations(): void {
            var that = this;
            var url = that.URL_REFRESH_ACTIVATIONS.replace("{user_id}", that.m_id.toString());
            $.get(url)
                .done(function (data) {
                    that.activationData = data;
                    that.InitUserActivationTables();
                })
                .fail(function (xhr) {

                });
        }

        private removeActivationById(id: number): void {
            var index = -1;
            for (var i = 0; i < this.activationData.length; i++) {
                if (this.activationData[i].id == id) {
                    index = i;
                    break;
                }
            }
            if (index >= 0) {
                this.activationData.splice(index, 1);
            }
        }

        private getActivationById(id: number): any {
            for (var i = 0; i < this.activationData.length; i++) {
                if (this.activationData[i].id == id) {
                    return this.activationData[i];
                }
            }
        }

        public addNewSelfModerationSite(): void {
            this.m_selfModerationTable.add();
        }

        public addNewWhitelistSite(): void {
            this.m_customWhitelistTable.add();
        }

        public addNewBypassableSite(): void {
            this.m_customBypasslistTable.add();
        }

        public addNewCustomTextTrigger(): void {
            this.m_textTriggerTable.add();
        }

        public addNewBlockedApp(): void {
            this.m_appBlocklistTable.add();
        }

        // ────────────────────────────────────────────────────
        //   ::::: C O N V E R T     F U N C T I O N S ::::::
        // ────────────────────────────────────────────────────
        protected LoadFromObject(data: Object): void {
            this.m_id = data['id'] as number;
            this.m_fullName = data['name'] as string;
            this.m_email = data['email'] as string;
            this.m_password = data['password'] as string;
            this.m_groupId = data['group_id'] as number;
            this.m_customerId = data['customer_id'] as number;

            if (data['roles'].length == 0) {
                this.m_roleId = null;
            } else {
                this.m_roleId = data['roles'][0]['id'];
            }

            this.m_numActivations = data['activations_allowed'];
            this.m_isActive = data['isactive'];
            this.m_registeredAt = data['dt'] as string;
            this.m_relaxedPolicyPasscode = data['relaxed_policy_passcode'] as string;
            this.m_relaxedPolicyPasscodeEnabled = data['enable_relaxed_policy_passcode'] as number;
            this.activationData = data['activations'];
            this.myConfigData = data['config_override'] == null ? null : JSON.parse(data['config_override']);

            if (this.myConfigData) {
                this.m_numBypassesPermitted = this.myConfigData.BypassesPermitted;
                this.m_bypassDuration = this.myConfigData.BypassDuration;
            }

            if (this.myConfigData && this.myConfigData.SelfModeration) {
                this.selfModeration = this.myConfigData.SelfModeration;
            } else {
                this.selfModeration = [];
            }

            if (this.myConfigData && this.myConfigData.CustomWhitelist) {
                this.customWhitelist = this.myConfigData.CustomWhitelist;
            } else {
                this.customWhitelist = [];
            }

            if (this.myConfigData && this.myConfigData.CustomBypasslist) {
                this.customBypasslist = this.myConfigData.CustomBypasslist;
            } else {
                this.customBypasslist = [];
            }

            if (this.myConfigData && this.myConfigData.CustomTriggerBlacklist) {
                this.customTriggers = this.myConfigData.CustomTriggerBlacklist;
            } else {
                this.customTriggers = [];
            }

            if (this.myConfigData && this.myConfigData.CustomBlockedApps) {
                this.appBlocklist = this.myConfigData.CustomBlockedApps;
            } else {
                this.appBlocklist = [];
            }

            if (this.myConfigData && (this.myConfigData.PrimaryDns != null || this.myConfigData.SecondaryDns != null)) {
                this.isDnsDisabled = true;
            } else {
                this.isDnsDisabled = false;
            }

            if (this.myConfigData && this.myConfigData.TimeRestrictions) {
                this.m_timeRestrictionsUI.timeRestrictions = {};

                for (var day in this.myConfigData.TimeRestrictions) {
                    this.m_timeRestrictionsUI.timeRestrictions[day] = this.myConfigData.TimeRestrictions[day];
                }
            } else {
                this.m_timeRestrictionsUI.InitEmptyTimeRestrictionsObject();
            }
        }

        private eveningRestrictionsPreset(): void {
            this.m_timeRestrictionsUI.EveningRestrictionsPreset();
        }

        private officeRestrictionsPreset(): void {
            this.m_timeRestrictionsUI.OfficeRestrictionsPreset();
        }

        private noneRestrictionsPreset(): void {
            this.m_timeRestrictionsUI.NoneRestrictionsPreset();
        }

        protected LoadFromForm(): void {
            console.log(this.m_fullName);
            this.m_groupId = this.getValueFromSelect(this.m_selectGroup);
            this.m_roleId = this.getValueFromSelect(this.m_selectRole);
            this.m_customerId = this.m_inputCustomerId.value == "" ? null : this.m_inputCustomerId.valueAsNumber;
            this.m_isActive = this.m_inputIsActive.checked == true ? 1 : 0;
            this.isDnsDisabled = this.m_disableDns.checked == true ? 1 : 0;
            this.m_relaxedPolicyPasscodeEnabled = this.m_inputRelaxedPolicyPasscodeEnabled.checked == true ? 1 : 0;

            this.selfModeration = this.m_selfModerationTable.getData();
            this.customWhitelist = this.m_customWhitelistTable.getData();
            this.customBypasslist = this.m_customBypasslistTable.getData();
            this.customTriggers = this.m_textTriggerTable.getData();
            this.appBlocklist = this.m_appBlocklistTable.getData();

            this.myConfigData = this.myConfigData || {};

            this.myConfigData.SelfModeration = this.selfModeration;
            this.myConfigData.CustomWhitelist = this.customWhitelist;
            this.myConfigData.CustomBypasslist = this.customBypasslist;
            this.myConfigData.CustomTriggerBlacklist = this.customTriggers;
            this.myConfigData.CustomBlockedApps = this.appBlocklist;

            this.myConfigData.TimeRestrictions = {};
            for (var day in this.m_timeRestrictionsUI.timeRestrictions) {
                var slider = this.m_timeRestrictionsUI.timeRestrictionSliders[day];
                this.myConfigData.TimeRestrictions[day] = {
                    EnabledThrough: slider.noUiSlider.get(),
                    RestrictionsEnabled: this.m_timeRestrictionsUI.timeRestrictions[day].RestrictionsEnabled
                };
            }

            this.myConfigData.BypassesPermitted = this.m_numBypassesPermitted;
            this.myConfigData.BypassDuration = this.m_bypassDuration;

            if (!this.m_disableDns) {
                delete this.myConfigData.PrimaryDns;
                delete this.myConfigData.SecondaryDns;
            } else {
                this.myConfigData.PrimaryDns = "";
                this.myConfigData.SecondaryDns = "";
            }
        }


        public ToObject(): Object {
            if (this.myConfigData) {
                this.myConfigData.BypassesPermitted = this.m_numBypassesPermitted;
                this.myConfigData.BypassDuration = this.m_bypassDuration;

                if (!this.myConfigData.BypassesPermitted) {
                    delete this.myConfigData.BypassesPermitted;
                }

                if (!this.myConfigData.BypassDuration) {
                    delete this.myConfigData.BypassDuration;
                }

                if (!this.isDnsDisabled) {
                    delete this.myConfigData.PrimaryDns;
                    delete this.myConfigData.SecondaryDns;
                } else {
                    this.myConfigData.PrimaryDns = "";
                    this.myConfigData.SecondaryDns = "";
                }
            }

            let obj = {
                'id': this.m_id,
                'name': this.m_fullName,
                'email': this.m_email,
                'group_id': this.m_groupId,
                'role_id': this.m_roleId,
                'customer_id': this.m_customerId,
                'activations_allowed': this.m_numActivations,
                'isactive': this.m_isActive,
                'dt': this.m_registeredAt,
                'relaxed_policy_passcode': this.m_relaxedPolicyPasscode,
                'enable_relaxed_policy_passcode': this.m_relaxedPolicyPasscodeEnabled,
                'bypasses_permitted': this.m_numBypassesPermitted,
                'bypass_duration': this.m_bypassDuration,
                'config_override': JSON.stringify(this.myConfigData)
            };

            if (this.m_password != null && this.m_password.length > 0 && (this.m_password != Array(30).join("x"))) {
                obj['password'] = this.m_password;
                obj['password_verify'] = this.m_inputPasswordConfirm.value;
            }

            return obj;
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions {
            let validationRules: JQueryValidation.RulesDictionary = {};

            validationRules[this.m_emailInputId] = {
                required: true,
                email: true
            };

            validationRules[this.m_inputPasswordId] = {
                required: true,
                equalTo: '#' + this.m_inputPasswordConfirm.id
            };

            validationRules[this.m_inputPasswordConfirm.id] = {
                required: true,
                equalTo: '#' + this.m_inputPasswordId
            };

            validationRules[this.m_activationCountInputId] = {
                required: true,
                number: true
            };

            let validationErrorMessages = {};
            validationErrorMessages[this.m_emailInputId] = this.ERROR_MESSAGE_EMAIL;
            validationErrorMessages[this.m_inputPasswordId] = this.ERROR_MESSAGE_PASSWORD;
            validationErrorMessages[this.m_inputPasswordConfirm.id] = this.ERROR_MESSAGE_CONFIRM_PASSWORD;
            validationErrorMessages[this.m_activationCountInputId] = this.ERROR_MESSAGE_ACTIVATION;

            let validationOptions: JQueryValidation.ValidationOptions = {
                rules: validationRules,
                errorPlacement: ((error: JQuery, element: JQuery): void => {
                    error.appendTo('#user_form_errors');
                    $('#user_form_errors').append('<br/>');
                }),
                messages: validationErrorMessages
            };

            return validationOptions;
        }

        // ───────────────────────────────────────────────
        //   ::::: C L A S S      H A N D L E R S ::::::
        // ───────────────────────────────────────────────
        private generateAjaxSettings(url, data, otherOptions = null): JQueryAjaxSettings {
            var options = {
                method: "POST",
                timeout: 60000,
                url: url,
                data: data,

                success: (data: any): any => {
                    return false;
                },

                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(errorThrown);
                }
            };

            if (otherOptions) {
                for (var i in otherOptions) {
                    options[i] = otherOptions[i];
                }
            }

            return options;
        }

        public static timeOfDay(n): string {
            var minutes: any = Math.round((n % 1) * 60);
            var hours = Math.floor(n);

            var ampm = (hours % 24) >= 12 ? "PM" : "AM";

            hours %= 12;
            if (hours == 0) {
                hours = 12;
            }

            if (minutes < 10) {
                minutes = "0" + minutes;
            }

            return hours + ":" + minutes + ampm;
        }


        private InitSelfModerationTable() {
            let that = this;

            this.m_selfModerationTable = new SelfModerationTable(document.querySelector("#self_moderation_table"), this.selfModeration);
            this.m_customWhitelistTable = new SelfModerationTable(document.querySelector("#custom_whitelist_table"), this.customWhitelist);
            this.m_customBypasslistTable = new SelfModerationTable(document.querySelector("#custom_bypassable_table"), this.customBypasslist);
            this.m_textTriggerTable = new SelfModerationTable(document.querySelector("#custom_trigger_table"), this.customTriggers);
            this.m_appBlocklistTable = new SelfModerationTable(document.querySelector('#user_app_list_table'), this.appBlocklist, this.URL_GET_APP_AUTOSUGGEST);

            this.m_selfModerationTable.render();
            this.m_customWhitelistTable.render();
            this.m_customBypasslistTable.render();
            this.m_textTriggerTable.render();
            this.m_appBlocklistTable.render();
        }

        private InitUserActivationTables() {
            let that = this;
            let id = (this.m_id === undefined) ? 0 : this.m_id;

            this.m_tableColumns = [{
                title: 'Action Id',
                data: 'id',
                visible: false
            },
                {
                    title: 'Identifier',
                    data: 'identifier',
                    visible: true,
                },
                {
                    title: 'Device Id',
                    data: 'device_id',
                    visible: true,
                    width: '300px'
                },
                {
                    title: 'App Version',
                    data: 'app_version',
                    visible: true,
                    width: '180px'
                },
                {
                    title: 'Platform',
                    data: 'platform_name',
                    visible: true,
                    width: '100px'
                },
                {
                    title: 'OS Version',
                    data: 'os_version',
                    visible: true,
                    width: '180px'
                },
                {
                    title: 'IP Address',
                    data: 'ip_address',
                    visible: true,
                    width: '200px'
                },
                {
                    title: 'Last contact date',
                    data: 'last_sync_time',
                    visible: true,
                    width: "250px"
                },
                {
                    title: 'Last update requested date',
                    data: 'last_update_requested_time',
                    visible: true,
                    width: "250px"
                },
                {
                    title: 'Check-in Days',
                    data: 'check_in_days',
                    visible: true,
                    width: "100px",
                    render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                        return "<input type='number' min='0' data-id='" + row.id + "' value='" + data + "' class='check-in-days' />";
                    })
                },
                {
                    title: 'Alert Partner',
                    data: 'alert_partner',
                    visible: true,
                    width: "90px",
                    render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                        var chk_report = (data === 1) ? 'checked' : '';

                        var str = '';
                        str += '<label class=\'switch-original\'>';
                        str += '    <input type=\'checkbox\' data-id=\'' + row.id + '\'' + chk_report + ' />';
                        str += '    <span class=\'check\'></span>';
                        str += '</label>';

                        return str;
                    })
                },
                {
                    width: "330px",
                    render: function (data, type, row) {
                        var strButtons = "";
                        strButtons += "<button title='Edit' type='button' id='edit_" + row.id + "' class='btn-edit button primary'>\
                            <span class='mif mif-pencil'></span>\
                        </button>";

                        strButtons += "&nbsp;<button title='Delete' type='button' id='delete_" + row.id + "' class='btn-delete button alert'>\
                            <span class='mif mif-bin'></span></button>";
                        strButtons += "&nbsp;<button title='Block' type='button' id='block_" + row.id + "' class='btn-block button alert'>\
                            <span class='mif mif-blocked'></span></button>";

                        return strButtons;
                    }
                }
            ];

            this.m_tableSettings = {
                autoWidth: true,
                stateSave: true,
                responsive: true,
                columns: this.m_tableColumns,
                data: this.activationData,
                destroy: true,
                rowCallback: ((row: Node, data: any[] | Object): void => {

                }),
                drawCallback: ((settings): void => {
                    $("#user_activation_table").off("change", "input[type='checkbox']");
                    $("#user_activation_table").on("change", "input[type='checkbox']", function () {
                        let id = $(this).attr("data-id");
                        let val = 0;
                        if (this['checked']) val = 1;

                        let checkAjaxSettings: JQueryAjaxSettings = that.generateAjaxSettings(this.URL_UPDATE_ALERT, {
                            id: id,
                            value: val
                        });

                        $.ajax(checkAjaxSettings);
                    });

                    $("#user_activation_table").off("blur", "input[type='number']");
                    $("#user_activation_table").on("blur", "input[type='number']", function () {
                        let id = $(this).attr("data-id");
                        let val = <number>$(this).val();
                        if (val < 0) {
                            alert(that.MESSAGE_INVALID_CHECKED_IN_DAYS);
                            $(this).val(0);
                            return;
                        }

                        let checkAjaxSettings: JQueryAjaxSettings = that.generateAjaxSettings(this.URL_UPDATE_CHECK_IN_DAYS, {
                            id: id,
                            value: val
                        });

                        $.ajax(checkAjaxSettings);
                    });

                    $("#user_activation_table").off("click", "button.btn-edit");
                    $("#user_activation_table").on("click", "button.btn-edit", function (e) {
                        e.preventDefault();

                        var id = that.getIdFromElementId(e.target['id']);
                        if (id == -1) {
                            id = that.getIdFromElementId(e.target.parentNode['id']);
                        }

                        let appUserActivationRecord = new AppUserActivationRecord();

                        appUserActivationRecord.ActionCompleteCallback = ((action: string): void => {
                            appUserActivationRecord.StopEditing();

                            that.refreshActivations();
                        });
                        console.log(id)

                        var data = that.getActivationById(id);

                        appUserActivationRecord.StartEditing(that.allGroups, data);
                    });

                    $("#user_activation_table").off("click", "button.btn-delete");
                    $("#user_activation_table").on('click', 'button.btn-delete', function (e) {
                        e.preventDefault();
                        if (confirm(that.MESSAGE_DELETE_ACTIVATION_CONFIRM)) {
                            let dataObject = {};
                            let id = that.getIdFromElementId(e.target['id']);

                            let ajaxSettings: JQueryAjaxSettings = that.generateAjaxSettings(that.URL_DELETE_ACTIVATION + '/' + id, dataObject, {
                                success: (data: any): any => {
                                    that.removeActivationById(id);
                                    that.InitUserActivationTables();

                                    return false;
                                },

                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    that.m_progressWait.Show(that.TITLE_ACTION_FAILED, that.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));

                                    setTimeout(() => {
                                        that.m_progressWait.Hide();
                                    }, that.ERROR_MESSAGE_DELAY_TIME);
                                }
                            });

                            $.ajax(ajaxSettings);
                        }
                    });

                    $("#user_activation_table").off("click", "button.btn-block");
                    $("#user_activation_table").on('click', 'button.btn-block', function (e) {
                        e.preventDefault();
                        if (confirm(that.MESSAGE_BLOCK_ACTIVATION_CONFIRM)) {
                            let dataObject = {};
                            let id = that.getIdFromElementId(e.target['id']);

                            let ajaxSettings: JQueryAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: that.URL_BLOCK_ACTIVATION + '/' + id,
                                data: dataObject,
                                success: (data: any): any => {
                                    that.removeActivationById(id);
                                    that.InitUserActivationTables();
                                    return false;
                                },
                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    that.m_progressWait.Show(that.TITLE_ACTION_FAILED, that.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));
                                    setTimeout(() => {
                                        that.m_progressWait.Hide();
                                    }, that.ERROR_MESSAGE_DELAY_TIME);
                                }
                            }

                            $.ajax(ajaxSettings);
                        }
                    });
                })
            };

            this.m_ActivationTables = $('#user_activation_table').DataTable(this.m_tableSettings);
        }

        public cancelClick(e: MouseEvent): any {
            if (this.m_actionCompleteCallback != null) {
                this.m_actionCompleteCallback("Cancel");
            } else {
                this.StopEditing();
            }

            return false;
        }

        public StartEditing(allGroups, userData: Object = null): void {
            this.allGroups = allGroups;

            if (this.m_selectGroup.options != null) {
                this.m_selectGroup.options.length = 0;
            }

            let groupsSorted = allGroups.sort((g1, g2) => (g1.name.toLowerCase() < g2.name.toLowerCase() ? -1 : 1));
            for (var elm of groupsSorted) {
                let option = document.createElement('option') as HTMLOptionElement;
                option.text = elm['name'];
                option.value = elm['id'];
                this.m_selectGroup.options.add(option);
            }

            this.userData = userData;

            if (userData == null) {
                this.m_editorTitleValue = this.TITLE_NEW_USER;
                this.m_btnSubmit.innerText = this.BTN_NEW_USER;

                this.m_mainForm.reset();
                if (this.m_selectGroup.options != null && this.m_selectGroup.options.length > 0) {
                    this.m_selectGroup.selectedIndex = 0;
                } else {
                    this.m_selectGroup.selectedIndex = -1;
                }

                if (this.m_selectRole.options != null && this.m_selectRole.options.length > 0) {
                    this.m_selectRole.selectedIndex = 0;
                } else {
                    this.m_selectRole.selectedIndex = -1;
                }
            } else {
                this.LoadFromObject(userData);

                this.m_editorTitleValue = this.TITLE_EDIT_USER;
                this.m_btnSubmit.innerText = this.BTN_EDIT_USER;

                this.m_inputPasswordConfirm.value = new Array(30).join("x");
                this.m_password = new Array(30).join("x");

                if (this.m_groupId != -1) {
                    let optionInList = this.m_selectGroup.querySelector('option[value="' + this.m_groupId.toString() + '"]') as HTMLOptionElement;
                    if (optionInList != null) {
                        this.m_selectGroup.selectedIndex = optionInList.index;
                    }
                } else {
                    if (this.m_selectGroup.options != null && this.m_selectGroup.options.length > 0) {
                        this.m_selectGroup.selectedIndex = 0;
                    } else {
                        this.m_selectGroup.selectedIndex = -1;
                    }
                }

                if (this.m_roleId != -1) {
                    let optionInList = this.m_selectRole.querySelector('option[value="' + (this.m_roleId || "").toString() + '"]') as HTMLOptionElement;
                    if (optionInList != null) {
                        this.m_selectRole.selectedIndex = optionInList.index;
                    }
                } else {
                    if (this.m_selectRole.options != null && this.m_selectRole.options.length > 0) {
                        this.m_selectRole.selectedIndex = 0;
                    } else {
                        this.m_selectRole.selectedIndex = -1;
                    }
                }

                this.m_inputIsActive.checked = this.m_isActive != 0;
                this.m_disableDns.checked = this.isDnsDisabled;
                this.m_inputRelaxedPolicyPasscodeEnabled.checked = this.m_relaxedPolicyPasscodeEnabled != 0;
            }

            this.m_bindings.Refresh();

            if (userData != null) {
                this.m_id = userData['id'];
            } else {
                this.m_id = 0;
                this.activationData = [];
            }

            // Covers creation of new users, because this doesn't get assigned to in that circumstance.
            if (!this.m_timeRestrictionsUI.timeRestrictions || JSON.stringify(this.m_timeRestrictionsUI.timeRestrictions) == "{}") {
                this.m_timeRestrictionsUI.InitEmptyTimeRestrictionsObject();
            }

            this.InitUserActivationTables();
            this.InitSelfModerationTable();
            this.m_timeRestrictionsUI.InitTimeRestrictions();

            $(this.m_editorOverlay).fadeIn(this.FADE_IN_DELAY_TIME);
        }


        private onSubmit(e: Event): any {
            let validateOpts = this.ValidationOptions;
            let validresult = $(this.m_mainForm).validate(validateOpts).form();

            if ($(this.m_mainForm).validate(validateOpts).valid()) {
                return this.OnFormSubmitClicked(e, this.userData == null);
            }

            return false;
        }

        public StopEditing(): void {
            this.m_bindings.Unbind();
            $(this.m_editorOverlay).fadeOut(this.FADE_IN_DELAY_TIME);
        }
    }
}
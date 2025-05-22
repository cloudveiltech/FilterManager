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
        ERROR_MESSAGE_APP_NAME = 'Application name is required.';

        MESSAGE_REPORT_LABEL = 'Debug mode enabled';
        MESSAGE_NO_REPORT_LABEL = 'Debug mode disabled';

        MESSAGE_ACTION_FAILED = 'Error reported by the server during action.\n %ERROR_MSG% \nCheck console for more information.';
        MESSAGE_ACTION_BLOCK = 'Blocking record to server.';

        TITLE_ACTION_FAILED = 'Action Failed';
        TITLE_ACTION_BLOCK = 'Block Record';

        ERROR_MESSAGE_DELAY_TIME = 5000;
        FADE_IN_DELAY_TIME = 200;

        GET_APP_AUTOSUGGEST_URL = 'api/admin/app_suggest';
        URL_ROUTE = 'api/admin/user_activations';

        CATEGORY_OVERRIDES_OPTIONS = [
            '',
            'Whitelist',
            'Blacklist',
            'BypassList',
            'Ignored',
        ]



        // ──────────────────────────────────────────────────────
        //   :::::: APP USER ACTIVATION   M E M B E R S ::::::
        // ──────────────────────────────────────────────────────
        private m_activationId: number;
        private m_groupId: number;
        private m_userName: string;
        private m_identifier: string;
        private m_deviceId: string;
        private m_ipAddress: string;
        private m_bypassQuantity: number;
        private m_bypassPeriod: number;
        private m_bypassUsed: number;
        private m_reportLevel: number;
        private m_os: string;
        private m_friendlyName: string;

        private configOverride: any;
        private categoryOverrides: any;
        private selfModeration: any;
        private activationWhitelist: any;
        private triggerBlacklist: any;
        private appBlocklist: any;
        private allCategories: any;

        // ──────────────────────────────────────────────────────────
        //   :::::: E D I T O R   H T M L   E L E M E N T S ::::::
        // ──────────────────────────────────────────────────────────
        private m_mainForm: HTMLFormElement;

        private m_editorOverlay: HTMLDivElement;

        private m_inputUserName: HTMLInputElement;
        private m_inputIdentifier: HTMLInputElement;
        private m_inputDeviceId: HTMLInputElement;
        private m_inputIPAddress: HTMLInputElement;
        private m_inputBPQuantity: HTMLInputElement;    // Bypass Quantity
        private m_inputBPPeriod: HTMLInputElement;    // Bypass Period
        private m_inputBPUsed: HTMLInputElement;    // Bypass Used
        private m_inputAppName: HTMLInputElement;
        private m_inputDebugEnabled: HTMLInputElement;
        private m_labelDebugEnabled: HTMLLabelElement;
        private m_selectGroup: HTMLSelectElement;
        private m_inputFriendlyName: HTMLInputElement;
        private m_selectUpdateChannel: HTMLSelectElement;

        private m_blacklistTable: SelfModerationTable;
        private m_whitelistTable: SelfModerationTable;
        private m_triggerBlacklistTable: SelfModerationTable;
        private m_appBlocklistTable: SelfModerationTable;

        private m_categoryOverridesTable: any;

        private m_btnSubmit: HTMLButtonElement;
        private m_btnCancel: HTMLButtonElement;

        private m_timeRestrictionsUI: TimeRestrictionUI
        private m_bindings: BindingInstance;

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
            this.m_mainForm = document.querySelector('#editor_activation_form') as HTMLFormElement;
            this.m_editorOverlay = document.querySelector('#overlay_activation_editor') as HTMLDivElement;

            this.m_bindings = new BindingInstance(this.m_editorOverlay, this);
            this.m_bindings.Bind();
            this.m_bindings.Refresh();
            this.m_timeRestrictionsUI = new TimeRestrictionUI(this.m_bindings, "#time_restrictions_tab_activations");

            this.m_inputUserName = document.querySelector('#editor_activation_input_user_full_name') as HTMLInputElement;
            this.m_inputIdentifier = document.querySelector('#editor_activation_input_identifier') as HTMLInputElement;
            this.m_inputDeviceId = document.querySelector('#editor_activation_input_device_id') as HTMLInputElement;
            this.m_inputIPAddress = document.querySelector('#editor_activation_input_ip_address') as HTMLInputElement;
            this.m_inputDebugEnabled = document.querySelector('#editor_activation_debug_enabled') as HTMLInputElement;
            this.m_labelDebugEnabled = document.querySelector('#editor_activation_debug_enabled_text') as HTMLLabelElement;
            this.m_inputFriendlyName = document.querySelector('#editor_activation_input_friendly_name') as HTMLInputElement;

            this.m_inputBPQuantity = document.querySelector('#editor_activation_input_bypass_quantity') as HTMLInputElement;
            this.m_inputBPPeriod = document.querySelector('#editor_activation_input_bypass_period') as HTMLInputElement;
            this.m_inputBPUsed = document.querySelector('#editor_activation_input_bypass_used') as HTMLInputElement;

            this.m_btnSubmit = document.querySelector('#activation_editor_submit') as HTMLButtonElement;
            this.m_btnCancel = document.querySelector('#activation_editor_cancel') as HTMLButtonElement;
            this.m_selectGroup = document.querySelector('#editor_ctivation_input_group_id') as HTMLSelectElement;
            this.m_selectUpdateChannel = document.querySelector('#editor_activation_input_update_channel') as HTMLSelectElement;

            this.InitButtonHandlers();
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

        private InitSelfModerationTables(): void {
            this.m_blacklistTable = new SelfModerationTable(document.querySelector('#activation_self_moderation_table'), this.selfModeration);
            this.m_whitelistTable = new SelfModerationTable(document.querySelector('#activation_whitelist_table'), this.activationWhitelist);
            this.m_triggerBlacklistTable = new SelfModerationTable(document.querySelector('#activation_trigger_table'), this.triggerBlacklist);
            this.m_appBlocklistTable = new SelfModerationTable(document.querySelector('#activation_app_list_table'), this.appBlocklist, this.GET_APP_AUTOSUGGEST_URL + "?os=" + this.m_os);

            this.m_blacklistTable.render();
            this.m_whitelistTable.render();
            this.m_triggerBlacklistTable.render();
            this.m_appBlocklistTable.render();
        }

        private InitCategoryOverridesTable() {
            let that = this;
            const options = this.CATEGORY_OVERRIDES_OPTIONS;

            let columns = [
                {
                    title: 'Category',
                    data: 'categoryName',
                    visible: true,
                },
                {
                    title: 'Override',
                    data: 'override',
                    visible: true,
                    render: function (data, type, row) {
                        const optionsHtml = options.map((option) => {
                            const selected = data === option ? 'selected' : '';
                            return `<option ${selected}>${option}</option>`;
                        }).join('');
                        const html = `<select class="category-override-select" data-category-id="${row.categoryId}">
                            ${optionsHtml}
                        </select>`;


                        return html;
                    }
        }
        ];

            let settings = {
                autoWidth: true,
                stateSave: true,
                responsive: true,
                columns: columns,
                data: this.categoryOverrides,
                destroy: true,
                createdRow: function (row, data, dataIndex) {
                    const select = $(row).find('.category-override-select')[0] as HTMLSelectElement;
                    select.addEventListener('change', function () {
                        data.override = select.value;
                    });
                },
            };

            this.m_categoryOverridesTable = $('#activation_category_table').DataTable(settings);
        }


        private InitButtonHandlers(): void {
            let that = this;

            this.m_inputDebugEnabled.onchange = ((e: MouseEvent): any => {
                if (that.m_inputDebugEnabled.checked)
                    that.m_labelDebugEnabled.innerHTML = this.MESSAGE_REPORT_LABEL;
                else
                    that.m_labelDebugEnabled.innerHTML = this.MESSAGE_NO_REPORT_LABEL;
            });

            this.m_btnCancel.onclick = ((e: MouseEvent): any => {
                this.StopEditing();
            });
        }

        protected LoadFromObject(data: Object): void {
            console.log(data)

            this.m_activationId = data['id'] as number;
            this.m_userName = data['name'] as string;
            this.m_identifier = data['identifier'] as string;
            this.m_ipAddress = data['ip_address'] as string;
            this.m_deviceId = data['device_id'] as string;
            this.m_bypassQuantity = (data['bypass_quantity'] != null) ? data['bypass_quantity'] as number : null;
            this.m_bypassPeriod = (data['bypass_period'] != null) ? data['bypass_period'] as number : null;
            this.m_bypassUsed = data['bypass_used'] as number;
            this.m_reportLevel = data['debug_enabled'] as number;
            this.m_os = data['platform_name'] as string;
            this.m_groupId = data['group_id'] as number;
            this.m_friendlyName = data['friendly_name'] as string;


            if ('config_override' in data && data['config_override'] != null) {
                try {
                    this.configOverride = JSON.parse(<string>data['config_override']);
                } catch (e) {
                    this.configOverride = null;
                }
            } else {
                this.configOverride = null;
            }

            if (this.configOverride) {
                this.selfModeration = this.configOverride.SelfModeration;
                this.activationWhitelist = this.configOverride.CustomWhitelist;
                this.triggerBlacklist = this.configOverride.CustomTriggerBlacklist;
                this.appBlocklist = this.configOverride.CustomBlockedApps;
                if (this.configOverride.TimeRestrictions) {
                    this.m_timeRestrictionsUI.timeRestrictions = {};

                    for (var day in this.configOverride.TimeRestrictions) {
                        this.m_timeRestrictionsUI.timeRestrictions[day] = this.configOverride.TimeRestrictions[day];
                    }
                } else {
                    this.m_timeRestrictionsUI.InitEmptyTimeRestrictionsObject();
                }
                try {
                    for (let i = 0; i < this.m_selectUpdateChannel.options.length; ++i) {
                        if (this.m_selectUpdateChannel.options[i].value.toLowerCase() == < string > this.configOverride['UpdateChannel'].toLowerCase()) {
                            this.m_selectUpdateChannel.selectedIndex = this.m_selectUpdateChannel.options[i].index;
                            break;
                        }
                    }
                } catch (ex) {
                    console.warn(ex);
                    console.warn("Either the update channel is null or it's an invalid value. Defaulting...");
                    this.m_selectUpdateChannel.selectedIndex = 0;
                }
            } else {
                this.selfModeration = null;
                this.activationWhitelist = null;
                this.triggerBlacklist = null;
                this.appBlocklist = null;
                this.m_timeRestrictionsUI.InitEmptyTimeRestrictionsObject();
                this.m_selectUpdateChannel.selectedIndex = 0;
            }

            if (this.configOverride && this.configOverride.CategoryOverrides) {
                // console.log(this.myConfigData.CategoryOverrides)
                this.categoryOverrides = this.categoryOverrides.map((category) => {
                    const categoryOverride = this.configOverride.CategoryOverrides.find((override) => override.categoryId === category.categoryId);
                    if (categoryOverride) {
                        category.override = categoryOverride.override;
                    }
                    return category;
                });
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
            this.m_bypassQuantity = this.m_inputBPQuantity.value == "" ? null : parseInt(this.m_inputBPQuantity.value);
            this.m_bypassPeriod = this.m_inputBPPeriod.value == "" ? null : parseInt(this.m_inputBPPeriod.value);
            this.m_reportLevel = this.m_inputDebugEnabled.checked ? 1 : 0;
            this.m_friendlyName = this.m_inputFriendlyName.value.trim();

            this.m_groupId = this.getValueFromSelect(this.m_selectGroup);

            this.selfModeration = this.m_blacklistTable.getData();
            this.activationWhitelist = this.m_whitelistTable.getData();
            this.triggerBlacklist = this.m_triggerBlacklistTable.getData();
            this.appBlocklist = this.m_appBlocklistTable.getData();

            this.categoryOverrides = this.m_categoryOverridesTable.data().toArray();

            this.configOverride = {
                SelfModeration: this.selfModeration,
                CustomWhitelist: this.activationWhitelist,
                CustomTriggerBlacklist: this.triggerBlacklist,
                CustomBlockedApps: this.appBlocklist,
                CategoryOverrides: this.categoryOverrides.filter((category) => category.override !== '')
            };


            if(this.m_selectUpdateChannel.selectedIndex != 0) {
                this.configOverride.UpdateChannel = this.m_selectUpdateChannel.options[this.m_selectUpdateChannel.selectedIndex].value;
            }

            if(this.m_timeRestrictionsUI.HasRestrictions()) {
                this.configOverride.TimeRestrictions = {};
                for (var day in this.m_timeRestrictionsUI.timeRestrictions) {
                    var slider = this.m_timeRestrictionsUI.timeRestrictionSliders[day];
                    this.configOverride.TimeRestrictions[day] = {
                        EnabledThrough: slider.noUiSlider.get(),
                        RestrictionsEnabled: this.m_timeRestrictionsUI.timeRestrictions[day].RestrictionsEnabled
                    };
                }
            }
        }

        public StartEditing(allGroups, allCategories: Array, userData: Object = null): void {
            this.categoryOverrides = allCategories.map((category) => {
                return {
                    categoryId: category.id,
                    categoryName: category.category,
                    override: '',
                }
            });

            this.LoadFromObject(userData);

            if (this.m_selectGroup.options != null) {
                this.m_selectGroup.options.length = 0;
            }

            let option = document.createElement('option') as HTMLOptionElement;
            option.text = " ";
            option.value = "-1";
            this.m_selectGroup.options.add(option);

            let groupsSorted = allGroups.sort((g1, g2) => (g1.name.toLowerCase() < g2.name.toLowerCase() ? -1 : 1));

            for (var elm of groupsSorted) {
                let option = document.createElement('option') as HTMLOptionElement;
                option.text = elm['name'];
                option.value = elm['id'];
                this.m_selectGroup.options.add(option);
            }

            this.m_inputUserName.value = this.m_userName;
            this.m_inputIdentifier.value = this.m_identifier;
            this.m_inputDeviceId.value = this.m_deviceId;
            this.m_inputIPAddress.value = this.m_ipAddress;
            this.m_inputBPQuantity.value = (this.m_bypassQuantity != null) ? this.m_bypassQuantity.toString() : '';
            this.m_inputBPPeriod.value = (this.m_bypassPeriod != null) ? this.m_bypassPeriod.toString() : '';
            this.m_inputBPUsed.value = this.m_bypassUsed.toString();
            this.m_inputDebugEnabled.checked = (this.m_reportLevel === 1);
            this.m_inputFriendlyName.value = this.m_friendlyName;

            let optionInList = this.m_selectGroup.querySelector('option[value="' + this.m_groupId.toString() + '"]') as HTMLOptionElement;
            if (optionInList != null) {
                this.m_selectGroup.selectedIndex = optionInList.index;
            } else {
                this.m_selectGroup.selectedIndex = -1;
            }

            if (this.m_reportLevel === 1) {
                this.m_labelDebugEnabled.innerHTML = this.MESSAGE_REPORT_LABEL;
            } else {
                this.m_labelDebugEnabled.innerHTML = this.MESSAGE_NO_REPORT_LABEL;
            }

            this.InitSelfModerationTables();
            this.InitCategoryOverridesTable();
            // Covers creation of new users, because this doesn't get assigned to in that circumstance.
            if (!this.m_timeRestrictionsUI.timeRestrictions) {
                this.m_timeRestrictionsUI.InitEmptyTimeRestrictionsObject();
            }
            this.m_timeRestrictionsUI.InitTimeRestrictions();

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
                'id': this.m_activationId,
                'group_id': this.m_groupId,
                'bypass_quantity': this.m_bypassQuantity,
                'bypass_period': this.m_bypassPeriod,
                'bypass_used': this.m_bypassUsed,
                'debug_enabled': this.m_reportLevel,
                'config_override': JSON.stringify(this.configOverride),
                'friendly_name': this.m_friendlyName
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

        public addNewBlockedApp(): void {
            this.m_appBlocklistTable.add();
        }

        public resetBypassUsed(): void {
            this.m_bypassUsed = 0;
            this.m_inputBPUsed.value = this.m_bypassUsed.toString();
        }
    }
}

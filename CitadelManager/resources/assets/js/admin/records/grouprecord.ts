/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel {
    export class GroupRecord extends BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        ERROR_MESSAGE_EMAIL         = 'A valid group name is required.';

        MESSAGE_PLACE_HOLDER        = 'Type To Filter Selection';
        MESSAGE_REPORT_LABEL        = 'Report blocked sites back to server';
        MESSAGE_NO_REPORT_LABEL     = 'No reporting back to server';
        MESSAGE_ACTION_FAILED       = 'Error reported by the server during action.\n %ERROR_MSG% \nCheck console for more information.';

        TITLE_ACTION_FAILED         = 'Action Failed';
        TITLE_NEW_GROUP             = 'Create New Group';
        TITLE_CLONE_GROUP           = 'Clone Group';
        TITLE_EDIT_GROUP            = 'Edit Group';

        BTN_LABEL_CREATE_GROUP      = 'Create';
        BTN_LABEL_CLONE_GROUP       = 'Clone';
        BTN_LABEL_EDIT_GROUP        = 'Save';

        ERROR_MESSAGE_DELAY_TIME    = 5000;
        FADE_IN_DELAY_TIME          = 200;

        URL_ROUTE                   = 'api/admin/groups';
        URL_GET_APP_DATA            = 'api/admin/get_app_data';

        // ─────────────────────────────────────────────────────
        //   :::::: G R O U P   D A T A   M E M B E R S ::::::
        // ─────────────────────────────────────────────────────

        private m_groupId               : number;
        private m_groupName             : string;
        private m_isActive              : number;
        private m_assignedFilterIds     : Object[];
        private m_appConfig             : Object;

        private m_apps                  : any[];
        private m_group_to_apps         : any[];
        private m_app_groups            : any[]; // all Groups
        private m_left_groups           : any[]; // unselected groups
        private m_right_groups          : any[]; // selected groups
        private m_selected_apps         : any[]; // selected applications

        // ─────────────────────────────────────────────────────────
        //   :::::: E D I T O R   H T M L   E L E M E N T S ::::::
        // ─────────────────────────────────────────────────────────
        private m_mainForm              : HTMLFormElement;

        private m_editorOverlay         : HTMLDivElement;
        private m_editorTitle           : HTMLHeadingElement;

        // ────────────────────────────────────────────────────
        //   :::::: G E N E R A L      E L E M E N T S ::::::
        // ────────────────────────────────────────────────────
        private m_inputGroupName        : HTMLInputElement;
        private m_inputFrequency        : HTMLInputElement;
        private m_inputIsActive         : HTMLInputElement;     // check box
        private m_inputPrimaryDNS       : HTMLInputElement;
        private m_inputSecondaryDNS     : HTMLInputElement;
        private m_inputNlpThreshold     : HTMLInputElement;
        private m_inputTrigerMaxsize    : HTMLInputElement;
        private m_inputReportLevel      : HTMLInputElement;     // check box
        private m_labelReportLevel      : HTMLLabelElement;
        private m_selectChannel         : HTMLSelectElement;

        // ──────────────────────────────────────────────
        //   :::::: AntiTemper   E L E M E N T S ::::::
        // ──────────────────────────────────────────────
        private m_input_AT_NoTerminate          : HTMLInputElement;     // check box
        private m_input_AT_DisableInternet      : HTMLInputElement;     // check box
        private m_input_AT_UseThreshold         : HTMLInputElement;     // check box
        private m_input_AT_ThresholdCount       : HTMLInputElement;
        private m_input_AT_ThresholdTriggerPeriod: HTMLInputElement;
        private m_input_AT_ThresholdTimeout     : HTMLInputElement;
        private m_input_AT_BypassesPerDay       : HTMLInputElement;
        private m_input_AT_BypassDuration       : HTMLInputElement;

        // ──────────────────────────────────────────────
        //   :::::: App Group   E L E M E N T S ::::::
        // ──────────────────────────────────────────────
        private m_select_AG_Source              : HTMLSelectElement;
        private m_select_AG_Target              : HTMLSelectElement;

        private m_btn_AG_S_To_T_All             : HTMLButtonElement;
        private m_btn_AG_S_To_T_One             : HTMLButtonElement;
        private m_btn_AG_T_To_S_One             : HTMLButtonElement;
        private m_btn_AG_T_To_S_All             : HTMLButtonElement;

        private m_inputBlacklist                : HTMLInputElement;     // Radio Button
        private m_inputWhitelist                : HTMLInputElement;     // Radio Button

        private m_btnSubmit                     : HTMLButtonElement;
        private m_btnCancel                     : HTMLButtonElement;

        // ─────────────────────────────────────────────────────────────────────
        //   :::::: F I L T E R   L I S T   A S S I G M E N T   A R E A ::::::
        // ─────────────────────────────────────────────────────────────────────

        private m_input_BL_FiltersSearch                : HTMLInputElement; // Blacklist Search Filters
        private m_input_BL_FiltersSearchPlaceholder     : HTMLSpanElement;  // Blacklist Search Filters Placeholder

        private m_input_WL_FiltersSearch                : HTMLInputElement; // Whitelist Search Filters
        private m_input_WL_FiltersSearchPlaceholder     : HTMLSpanElement;  // Whitelist Search Filters Placeholder

        private m_input_BP_FiltersSearch                : HTMLInputElement; // Bypass Search Filters
        private m_input_BP_FiltersSearchPlaceholder     : HTMLSpanElement;  // Bypass Search Filters Placeholder

        private m_input_UA_FiltersSearch                : HTMLInputElement; // Unassigned Search Filters
        private m_input_UA_FiltersSearchPlaceholder     : HTMLSpanElement;  // Unassigned Search Filters Placeholder

        private m_container_Blacklist                   : HTMLDivElement;
        private m_container_Whitelist                   : HTMLDivElement;
        private m_container_ByPass                      : HTMLDivElement;
        private m_container_Unassigned                  : HTMLDivElement;

        /**
         * Creates an instance of GroupRecord.
         *
         * @memberOf GroupRecord
         */
        constructor() {
            super();
            this.ConstructFormReferences();
            this.ConstructFilterAssignmentArea();
        }

        public get RecordRoute(): string {
            return this.URL_ROUTE;
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions {
            let validationRules: JQueryValidation.RulesDictionary = {};
            validationRules[this.m_inputGroupName.id] = {
                required: true,
                minlength: 1
            };

            let validationErrorMessages = {};
            validationErrorMessages[this.m_inputGroupName.id] = this.ERROR_MESSAGE_EMAIL;

            let validationOptions: JQueryValidation.ValidationOptions = {
                rules: validationRules,
                errorPlacement: ((error: JQuery, element: JQuery): void => {
                    error.appendTo('#group_form_errors');
                    $('#group_form_errors').append('<br/>');
                }),
                messages: validationErrorMessages,
                ignore: ''
            };

            return validationOptions;
        }

        private ConstructFormReferences(): void {
            this.m_mainForm             = document.querySelector('#editor_group_form') as HTMLFormElement;
            this.m_editorTitle          = document.querySelector('#group_editing_title') as HTMLHeadingElement;
            this.m_editorOverlay        = document.querySelector('#overlay_group_editor') as HTMLDivElement;

            this.m_inputGroupName       = document.querySelector('#editor_group_input_groupname') as HTMLInputElement;
            this.m_inputFrequency       = document.querySelector('#editor_cfg_update_frequency_input') as HTMLInputElement;

            this.m_inputPrimaryDNS      = document.querySelector('#editor_cfg_primary_dns_input') as HTMLInputElement;
            this.m_inputSecondaryDNS    = document.querySelector('#editor_cfg_secondary_dns_input') as HTMLInputElement;
            this.m_inputReportLevel     = document.querySelector('#editor_group_report_level') as HTMLInputElement;
            this.m_labelReportLevel     = document.querySelector('#report_level_text') as HTMLLabelElement;

            let ipv4andv6OnlyFilter = (e: KeyboardEvent) => {
                let inputBox = e.target as HTMLInputElement;
                inputBox.value = inputBox.value.replace(/[^0-9\\.:]/g, '');
            };

            this.m_inputPrimaryDNS.onkeyup = ipv4andv6OnlyFilter;
            this.m_inputSecondaryDNS.onkeyup = ipv4andv6OnlyFilter;

            this.m_inputIsActive                    = document.querySelector('#editor_group_input_isactive') as HTMLInputElement;

            this.m_input_AT_NoTerminate             = document.querySelector('#editor_cfg_no_terminate_input') as HTMLInputElement;
            this.m_input_AT_DisableInternet         = document.querySelector('#editor_cfg_disable_internet_input') as HTMLInputElement;
            this.m_input_AT_UseThreshold            = document.querySelector('#editor_cfg_use_threshold_input') as HTMLInputElement;
            this.m_input_AT_ThresholdCount          = document.querySelector('#editor_cfg_threshold_count_input') as HTMLInputElement;
            this.m_input_AT_ThresholdTimeout        = document.querySelector('#editor_cfg_threshold_period_input') as HTMLInputElement;
            this.m_input_AT_ThresholdTriggerPeriod  = document.querySelector('#editor_cfg_threshold_timeout_input') as HTMLInputElement;
            this.m_input_AT_BypassesPerDay          = document.querySelector('#editor_cfg_bypasses_allowed_input') as HTMLInputElement;
            this.m_input_AT_BypassDuration          = document.querySelector('#editor_cfg_bypass_duration_input') as HTMLInputElement;

            this.m_inputNlpThreshold                = document.querySelector('#editor_cfg_nlp_threshold_input') as HTMLInputElement;
            this.m_inputTrigerMaxsize               = document.querySelector('#editor_cfg_trigger_max_size_input') as HTMLInputElement;
            this.m_selectChannel                    = document.querySelector('#editor_cfg_update_channel_input') as HTMLSelectElement;

            this.m_inputNlpThreshold.onkeyup = (e: KeyboardEvent) => {
                let inputBox = e.target as HTMLInputElement;
                let value = inputBox.valueAsNumber;

                if (value > 1.0) {
                    inputBox.valueAsNumber = 1.0;
                }

                if (value < 0) {
                    inputBox.valueAsNumber = 0;
                }
            };

            this.m_inputBlacklist               = document.querySelector('#group_filteredapps_radio_blacklist') as HTMLInputElement;
            this.m_inputWhitelist               = document.querySelector('#group_filteredapps_radio_whitelist') as HTMLInputElement;

            this.m_select_AG_Source             = document.querySelector('#appgroup_source_list') as HTMLSelectElement;
            this.m_select_AG_Target             = document.querySelector('#appgroup_target_list') as HTMLSelectElement;
            this.m_btn_AG_S_To_T_All            = document.querySelector('#appgroups_source_to_target') as HTMLButtonElement;
            this.m_btn_AG_S_To_T_One            = document.querySelector('#appgroup_source_to_target') as HTMLButtonElement;
            this.m_btn_AG_T_To_S_One            = document.querySelector('#appgroup_target_to_source') as HTMLButtonElement;
            this.m_btn_AG_T_To_S_All            = document.querySelector('#appgroups_target_to_source') as HTMLButtonElement;

            this.m_btnSubmit                    = document.querySelector('#group_editor_submit') as HTMLButtonElement;
            this.m_btnCancel                    = document.querySelector('#group_editor_cancel') as HTMLButtonElement;

            this.InitButtonHandlers();
        }

        private ConstructFilterAssignmentArea(): void {
            this.m_input_BL_FiltersSearch               = document.querySelector('#group_blacklist_filters_search_input') as HTMLInputElement;
            this.m_input_BL_FiltersSearchPlaceholder    = document.querySelector('#group_blacklist_filters_search_input_placeholder') as HTMLInputElement;

            this.m_input_WL_FiltersSearch               = document.querySelector('#group_whitelist_filters_search_input') as HTMLInputElement;
            this.m_input_WL_FiltersSearchPlaceholder    = document.querySelector('#group_whitelist_filters_search_input_placeholder') as HTMLInputElement;

            this.m_input_BP_FiltersSearch               = document.querySelector('#group_bypass_filters_search_input') as HTMLInputElement;
            this.m_input_BP_FiltersSearchPlaceholder    = document.querySelector('#group_bypass_filters_search_input_placeholder') as HTMLInputElement;

            this.m_input_UA_FiltersSearchPlaceholder    = document.querySelector('#group_unassigned_filters_search_input_placeholder') as HTMLInputElement;
            this.m_input_UA_FiltersSearch               = document.querySelector('#group_unassigned_filters_search_input') as HTMLInputElement;

            this.m_input_BL_FiltersSearch.onkeyup = ((e: KeyboardEvent): any => {
                this.OnSearchFieldInput(this.m_input_BL_FiltersSearch, this.m_container_Blacklist, e);
            });

            this.m_input_WL_FiltersSearch.onkeyup = ((e: KeyboardEvent): any => {
                this.OnSearchFieldInput(this.m_input_WL_FiltersSearch, this.m_container_Whitelist, e);
            });

            this.m_input_BP_FiltersSearch.onkeyup = ((e: KeyboardEvent): any => {
                this.OnSearchFieldInput(this.m_input_BP_FiltersSearch, this.m_container_ByPass, e);
            });

            this.m_input_UA_FiltersSearch.onkeyup = ((e: KeyboardEvent): any => {
                this.OnSearchFieldInput(this.m_input_UA_FiltersSearch, this.m_container_Unassigned, e);
            });

            let that = this;
            let setupSearchBoxFocus = ((searchBox: HTMLInputElement, placeholder: HTMLSpanElement): void => {
                searchBox.onfocus = ((e: FocusEvent): any => {
                    searchBox.style.color = "white";

                    if (searchBox.value.length > 0) {
                        placeholder.textContent = searchBox.value;
                    } else {
                        placeholder.textContent = that.MESSAGE_PLACE_HOLDER;
                    }
                });

                searchBox.onblur = ((e: FocusEvent): any => {
                    searchBox.style.color = "transparent";

                    if (searchBox.value.length > 0) {
                        placeholder.textContent = searchBox.value;
                    } else {
                        placeholder.textContent = that.MESSAGE_PLACE_HOLDER;
                    }
                });
            });

            setupSearchBoxFocus(this.m_input_BL_FiltersSearch, this.m_input_BL_FiltersSearchPlaceholder);
            setupSearchBoxFocus(this.m_input_WL_FiltersSearch, this.m_input_WL_FiltersSearchPlaceholder);
            setupSearchBoxFocus(this.m_input_BP_FiltersSearch, this.m_input_BP_FiltersSearchPlaceholder);
            setupSearchBoxFocus(this.m_input_UA_FiltersSearch, this.m_input_UA_FiltersSearchPlaceholder);

            this.m_container_Blacklist      = document.querySelector('#group_blacklist_filters') as HTMLDivElement;
            this.m_container_Whitelist      = document.querySelector('#group_whitelist_filters') as HTMLDivElement;
            this.m_container_ByPass         = document.querySelector('#group_bypass_filters') as HTMLDivElement;
            this.m_container_Unassigned     = document.querySelector('#group_unassigned_filters') as HTMLDivElement;
        }

        private ResetSearchBoxes(): void {
            this.m_input_BL_FiltersSearch.value = '';
            this.m_input_WL_FiltersSearch.value = '';
            this.m_input_BP_FiltersSearch.value = '';
            this.m_input_UA_FiltersSearch.value = '';

            this.m_input_BL_FiltersSearchPlaceholder.textContent = this.MESSAGE_PLACE_HOLDER;
            this.m_input_WL_FiltersSearchPlaceholder.textContent = this.MESSAGE_PLACE_HOLDER;
            this.m_input_BP_FiltersSearchPlaceholder.textContent = this.MESSAGE_PLACE_HOLDER;
            this.m_input_UA_FiltersSearchPlaceholder.textContent = this.MESSAGE_PLACE_HOLDER;
        }

        private OnSearchFieldInput(input: HTMLInputElement, searchTarget: HTMLDivElement, e: KeyboardEvent): any {
            let currentTextValue = input.value.toLowerCase();

            let childrenToSearch = searchTarget.querySelectorAll('div[citadel-filter-list-id]');

            for (let i = 0; i < childrenToSearch.length; ++i) {
                if (currentTextValue.length <= 0) {
                    $(childrenToSearch.item(i)).show();
                    continue;
                }

                let childText = childrenToSearch.item(i).textContent.toLowerCase();

                if (!~childText.indexOf(currentTextValue)) {
                    $(childrenToSearch.item(i)).hide();
                } else {
                    $(childrenToSearch.item(i)).show();
                }
            }
        }

        private InitButtonHandlers(): void {
            let that = this;

            this.m_inputReportLevel.onchange = ((e: MouseEvent): any => {
                if (that.m_inputReportLevel.checked)
                    that.m_labelReportLevel.innerHTML = that.MESSAGE_REPORT_LABEL;
                else
                    that.m_labelReportLevel.innerHTML = that.MESSAGE_NO_REPORT_LABEL;
            });

            this.m_btnCancel.onclick = ((e: MouseEvent): any => {
                this.StopEditing();
            });

            this.m_btn_AG_S_To_T_All.onclick = ((e: MouseEvent): any => {
                this.onMoveRightAllClicked(e);
            });
            this.m_btn_AG_S_To_T_One.onclick = ((e: MouseEvent): any => {
                this.onMoveRightClicked(e);
            });
            this.m_btn_AG_T_To_S_One.onclick = ((e: MouseEvent): any => {
                this.onMoveLeftClicked(e);
            });
            this.m_btn_AG_T_To_S_All.onclick = ((e: MouseEvent): any => {
                this.onMoveLeftAllClicked(e);
            });

            this.m_btnSubmit.onclick = ((e: MouseEvent): any => {

                if (this.m_mainForm.onsubmit != null) {
                    this.m_mainForm.onsubmit(new Event("submit"));
                }
            });
        }

        public ToObject(): Object {
            let obj = {
                'id'                    : this.m_groupId,
                'name'                  : this.m_groupName,
                'isactive'              : this.m_isActive,
                'assigned_filter_ids'   : this.m_assignedFilterIds,
                'app_cfg'               : JSON.stringify(this.m_appConfig),
                'assigned_app_groups'   : this.m_right_groups
            };

            return obj;
        }

        protected LoadFromObject(data: Object): void {
            this.m_groupId           = data['id'] as number;
            this.m_groupName         = data['name'] as string;
            this.m_isActive          = data['isactive'];
            this.m_assignedFilterIds = data['assigned_filter_ids'] as Object[];
            this.m_appConfig         = JSON.parse(data['app_cfg']);
        }

        protected LoadFromForm(): void {
            this.m_groupName        = this.m_inputGroupName.value;
            this.m_isActive         = this.m_inputIsActive.checked == true ? 1 : 0;

            let allAssignedFilters  = new Array < Object > ();

            let collectSelected = ((container: HTMLDivElement, blacklist: boolean, whitelist: boolean, bypass: boolean): void => {
                let assignedLists = container.querySelectorAll('div[citadel-filter-list-id]');
                for (let i = 0; i < assignedLists.length; ++i) {
                    let selectedBlacklist = {
                        'filter_list_id': parseInt(assignedLists
                            .item(i)
                            .getAttribute('citadel-filter-list-id')),
                        'as_blacklist': blacklist === true ? 1 : 0,
                        'as_whitelist': whitelist === true ? 1 : 0,
                        'as_bypass': bypass === true ? 1 : 0
                    };

                    allAssignedFilters.push(selectedBlacklist);
                }
            });

            collectSelected(this.m_container_Blacklist, true, false, false);
            collectSelected(this.m_container_Whitelist, false, true, false);
            collectSelected(this.m_container_ByPass, false, false, true);

            this.m_assignedFilterIds = allAssignedFilters;

            let filterAppsKey = 'Blacklist';
            if (!this.m_inputBlacklist.checked) {
                filterAppsKey = 'Whitelist';
            }

            let appConfig = {
                'UpdateFrequency': this.m_inputFrequency.valueAsNumber,
                'PrimaryDns': this.m_inputPrimaryDNS.value,
                'SecondaryDns': this.m_inputSecondaryDNS.value,
                'CannotTerminate': this.m_input_AT_NoTerminate.checked,
                'BlockInternet': this.m_input_AT_DisableInternet.checked,
                'UseThreshold': this.m_input_AT_UseThreshold.checked,
                'ThresholdLimit': this.m_input_AT_ThresholdCount.valueAsNumber,
                'ThresholdTriggerPeriod': this.m_input_AT_ThresholdTriggerPeriod.valueAsNumber,
                'ThresholdTimeoutPeriod': this.m_input_AT_ThresholdTimeout.valueAsNumber,
                'BypassesPermitted': this.m_input_AT_BypassesPerDay.valueAsNumber,
                'BypassDuration': this.m_input_AT_BypassDuration.valueAsNumber,
                'NlpThreshold': this.m_inputNlpThreshold.valueAsNumber,
                'MaxTextTriggerScanningSize': this.m_inputTrigerMaxsize.valueAsNumber,
                'UpdateChannel': this.m_selectChannel.options[this.m_selectChannel.selectedIndex].value,
                'ReportLevel': this.m_inputReportLevel.checked ? 1 : 0,
            };

            appConfig[filterAppsKey] = "checked";
            this.m_appConfig = appConfig;
        }

        private getGroupItem(group_id) {
            var group_item = null;
            this.m_app_groups.forEach((item: any): void => {
                if (item.id == group_id) {
                    group_item = item;
                    return;
                }
            });

            return group_item;
        }

        private get_appications_by_groupid(group_id) {
            let arr = [];
            this.m_group_to_apps.forEach((group_item): void => {
                if (group_item.app_group_id == group_id) {
                    arr.push(group_item.app_id);
                }
            });
            return arr;
        }

        private get_application(app_id) {
            var item = {
                name: 'none'
            };
            this.m_apps.forEach((app): void => {
                if (app.id == app_id) {
                    item = app;
                    return;
                }
            });
            return item;
        }

        private _drawGroups(groupData: any[], groupCtrl: HTMLSelectElement): void {
            $(groupCtrl).empty();

            let that = this;
            if (groupData.length == 0) return;
            groupData.forEach((group_id): void => {
                var newOption = document.createElement("option");
                var item = that.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;
                groupCtrl.add(newOption);
            });
        }

        private _drawSelectedApps() {
            var str_html = '';
            this.m_selected_apps = [];
            if (this.m_right_groups.length > 0) {
                this.m_right_groups.forEach((group_id): void => {
                    var app_ids = this.get_appications_by_groupid(group_id);
                    for (var i = 0; i < app_ids.length; i++) {
                        if (this.m_selected_apps.indexOf(app_ids[i]) < 0) {
                            this.m_selected_apps.push(app_ids[i]);
                            let app_item = this.get_application(app_ids[i]);
                            str_html += '<div>' + app_item.name + '</div>';
                        }
                    }
                });
            }
            $('#selected_applications').html(str_html);
        }

        private ClearFormErrorMessages(): void {
            var errElms = document.querySelectorAll('#group_form_errors > *');
            for (let i = 0; i < errElms.length; ++i) {
                let elm = errElms.item(i);
                elm.parentNode.removeChild(elm);
            }
        }

        private loadAppGroupDatas(flag: boolean): void {
            let that = this;

            this.m_apps = [];
            this.m_app_groups = [];
            this.m_btnSubmit.disabled = true;

            $("#spiner_4").show();
            var url = this.URL_GET_APP_DATA;
            if (flag) {
                url += '/' + this.m_groupId;
            }
            let ajaxSettings: JQueryAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: url,
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    this.m_apps = data.apps;
                    this.m_app_groups = data.app_groups;
                    this.m_group_to_apps = data.group_to_apps;
                    if (flag) {
                        let selected_app_groups = data.selected_app_groups;
                        this.m_left_groups = [];
                        this.m_right_groups = [];
                        if (selected_app_groups.length > 0) {
                            this.m_app_groups.forEach((app_group: any): void => {
                                this.m_left_groups.push(app_group.id);
                            });
                            selected_app_groups.forEach((app_group: any): void => {
                                this.m_right_groups.push(app_group.app_group_id);
                                let pos = this.m_left_groups.indexOf(app_group.app_group_id);
                                this.m_left_groups.splice(pos, 1);
                            });
                        } else {
                            this.m_app_groups.forEach((app_group: any): void => {
                                this.m_left_groups.push(app_group.id);
                            });
                        }
                    } else {
                        this.m_left_groups = [];
                        this.m_right_groups = [];
                        this.m_app_groups.forEach((app_group: any): void => {
                            this.m_left_groups.push(app_group.id);
                        });
                    }

                    this._drawGroups(this.m_left_groups, this.m_select_AG_Source);
                    this._drawGroups(this.m_right_groups, this.m_select_AG_Target);
                    this._drawSelectedApps();

                    $("#spiner_4").hide();
                    this.m_btnSubmit.disabled = false;

                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    $("#spiner_4").hide();
                    that.m_progressWait.Show(that.TITLE_ACTION_FAILED, that.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));
                    setTimeout(() => {
                        this.m_progressWait.Hide();
                    }, that.ERROR_MESSAGE_DELAY_TIME);
                }
            }

            $.ajax(ajaxSettings);
        }

        public StartEditing(allFilters: DataTables.Api, data: Object = null, cloneData: Object = null): void {
            let clearListContainer = ((container: HTMLDivElement): void => {
                let assignedChildren = container.querySelectorAll('div[citadel-filter-list-id]');
                for (let i = 0; i < assignedChildren.length; ++i) {
                    container.removeChild(assignedChildren.item(i));
                }
            });

            // Remove all existing draggable items.
            clearListContainer(this.m_container_Blacklist);
            clearListContainer(this.m_container_Whitelist);
            clearListContainer(this.m_container_ByPass);
            clearListContainer(this.m_container_Unassigned);

            this.ResetSearchBoxes();

            this.ClearFormErrorMessages();

            this.m_mainForm.reset();

            // Reset config controls.
            this.m_input_AT_NoTerminate.checked = false;
            this.m_input_AT_DisableInternet.checked = false;
            this.m_input_AT_UseThreshold.checked = false;
            this.m_input_AT_ThresholdCount.valueAsNumber = 0;
            this.m_input_AT_ThresholdTriggerPeriod.valueAsNumber = 0;
            this.m_input_AT_ThresholdTimeout.valueAsNumber = 0;
            this.m_input_AT_BypassesPerDay.valueAsNumber = 0;
            this.m_input_AT_BypassDuration.valueAsNumber = 0;

            this.m_inputNlpThreshold.valueAsNumber = 0;
            this.m_inputTrigerMaxsize.valueAsNumber = -1;
            this.m_selectChannel.selectedIndex = 0;

            this.m_inputFrequency.valueAsNumber = 5;
            this.m_inputPrimaryDNS.value = '';
            this.m_inputSecondaryDNS.value = '';

            $('#overlay_group_editor ul.tabs > li').removeClass('active');
            $('#overlay_group_editor ul.tabs > li').first().addClass('active');
            $('#overlay_group_editor div.frames > div.frame').hide();
            $('#overlay_group_editor div.frames > div.frame').first().show();

            // Try to load existing assigned filters, if we're editing.
            let allAssignedFilters = new Array < Object > ();
            if (data != null && data.hasOwnProperty('assigned_filter_ids')) {
                allAssignedFilters = data['assigned_filter_ids'] as Object[];
            } else {
                if (cloneData != null && cloneData.hasOwnProperty('assigned_filter_ids')) {
                    allAssignedFilters = cloneData['assigned_filter_ids'] as Object[];
                }
            }

            for (var a = 0; a < allFilters.length; a++) {
                var elm = allFilters[a];
                let imageClassName = "";
                let draggableFilterOption = document.createElement('div') as HTMLDivElement;
                draggableFilterOption.setAttribute('citadel-filter-list-id', elm['id']);
                draggableFilterOption.style.setProperty('font-size', '10');
                draggableFilterOption.style.setProperty('display', 'inline-block');
                draggableFilterOption.style.setProperty('text-align', 'center');

                if (( < string > (elm['type'])).toUpperCase().indexOf("TRIG") != -1) {
                    // This is a trigger list.
                    draggableFilterOption.style.setProperty('background', 'crimson');
                    draggableFilterOption.setAttribute('citadel-filter-list-type', 'trigger');
                    imageClassName = "mif-warning";
                } else if (( < string > (elm['type'])).toUpperCase().indexOf("NLP") != -1) {
                    // This is a NLP filtering category.
                    draggableFilterOption.style.setProperty('background', '#f0a30a');
                    draggableFilterOption.style.setProperty('color', 'white');
                    draggableFilterOption.setAttribute('citadel-filter-list-type', 'nlp');
                    imageClassName = "mif-language";
                } else {
                    // This is a filter rule list.
                    draggableFilterOption.style.setProperty('background', 'darkCyan');
                    draggableFilterOption.setAttribute('citadel-filter-list-type', 'filter');
                    imageClassName = "mif-filter";
                }

                draggableFilterOption.style.setProperty('margin-top', '10px');
                draggableFilterOption.style.setProperty('margin-bottom', '10px');
                draggableFilterOption.style.setProperty('padding-top', '10px');
                draggableFilterOption.style.setProperty('padding-bottom', '10px');
                draggableFilterOption.style.setProperty('vertical-align', 'middle');
                draggableFilterOption.style.setProperty('line-height', 'normal');
                draggableFilterOption.style.setProperty('width', '100%');

                draggableFilterOption.innerHTML = '<span style="float:left; margin-top: 4px; margin-left: 10px;" class="' + imageClassName + '"></span><div><span style="font-size: 14px;">' + elm['namespace'] + ' - ' + elm['category'] + '</span></div>';

                // Lastly, find out which column this needs to be added to.
                let alreadyHasThisFilterList = false;
                let existingItem: Object = null;
                for (let j = 0; j < allAssignedFilters.length; ++j) {
                    if (allAssignedFilters[j]['filter_list_id'] == < number > (elm['id'])) {
                        alreadyHasThisFilterList = true;
                        existingItem = allAssignedFilters[j];
                        break;
                    }
                }

                if (alreadyHasThisFilterList && existingItem != null) {
                    if (existingItem['as_blacklist'] == true) {
                        this.m_container_Blacklist.appendChild(draggableFilterOption);
                    } else if (existingItem['as_whitelist'] == true) {
                        this.m_container_Whitelist.appendChild(draggableFilterOption);
                    } else if (existingItem['as_bypass'] == true) {
                        this.m_container_ByPass.appendChild(draggableFilterOption);
                    }
                } else {
                    this.m_container_Unassigned.appendChild(draggableFilterOption);
                }
            }

            switch (data == null) {
                case true:
                    {
                        if (cloneData != null) {
                            // Set data with clone data
                            this.m_editorTitle.innerText = this.TITLE_CLONE_GROUP;
                            this.m_btnSubmit.innerText = this.BTN_LABEL_CLONE_GROUP;
                            this.LoadFromObject(cloneData);
                            this.m_inputGroupName.value = this.m_groupName + "-cloned";
                            this.m_inputIsActive.checked = this.m_isActive != 0;

                            this.m_input_AT_NoTerminate.checked = this.m_appConfig['CannotTerminate'];
                            this.m_input_AT_DisableInternet.checked = this.m_appConfig['BlockInternet'];
                            this.m_input_AT_UseThreshold.checked = this.m_appConfig['UseThreshold'];
                            this.m_input_AT_ThresholdCount.valueAsNumber = parseInt(this.m_appConfig['ThresholdLimit']);
                            this.m_input_AT_ThresholdTriggerPeriod.valueAsNumber = parseInt(this.m_appConfig['ThresholdTriggerPeriod']);
                            this.m_input_AT_ThresholdTimeout.valueAsNumber = parseInt(this.m_appConfig['ThresholdTimeoutPeriod']);
                            this.m_input_AT_BypassesPerDay.valueAsNumber = parseInt(this.m_appConfig['BypassesPermitted']);
                            this.m_input_AT_BypassDuration.valueAsNumber = parseInt(this.m_appConfig['BypassDuration']);
                            this.m_inputNlpThreshold.valueAsNumber = parseFloat(this.m_appConfig['NlpThreshold']);
                            this.m_inputTrigerMaxsize.valueAsNumber = parseInt(this.m_appConfig['MaxTextTriggerScanningSize']);

                            try {
                                for (let i = 0; i < this.m_selectChannel.options.length; ++i) {
                                    if (this.m_selectChannel.options[i].value.toLowerCase() == < string > this.m_appConfig['UpdateChannel'].toLowerCase()) {
                                        this.m_selectChannel.selectedIndex = this.m_selectChannel.options[i].index;
                                        break;
                                    }
                                }
                            } catch (ex) {
                                console.warn(ex);
                                console.warn("Either the update channel is null or it's an invalid value. Defaulting...");
                                this.m_selectChannel.selectedIndex = 0;
                            }

                            this.m_inputFrequency.valueAsNumber = parseInt(this.m_appConfig['UpdateFrequency']);
                            this.m_inputPrimaryDNS.value = this.m_appConfig['PrimaryDns'];
                            this.m_inputSecondaryDNS.value = this.m_appConfig['SecondaryDns'];

                            if (this.m_inputPrimaryDNS.value == 'undefined') {
                                this.m_inputPrimaryDNS.value = '';
                            }

                            if (this.m_inputSecondaryDNS.value == 'undefined') {
                                this.m_inputSecondaryDNS.value = '';
                            }

                            this.loadAppGroupDatas(true);

                            if ('Blacklist' in this.m_appConfig) {
                                this.m_inputBlacklist.checked = true;
                                this.m_inputWhitelist.checked = false;
                            } else if ('Whitelist' in this.m_appConfig) {
                                this.m_inputBlacklist.checked = false;
                                this.m_inputWhitelist.checked = true;
                            } else {
                                // Default
                                this.m_inputBlacklist.checked = true;
                                this.m_inputWhitelist.checked = false;
                            }
                            this.m_groupId = undefined;
                        } else {

                            this.m_editorTitle.innerText = this.TITLE_NEW_GROUP;
                            this.m_btnSubmit.innerText = this.BTN_LABEL_CREATE_GROUP;

                            this.loadAppGroupDatas(false);
                            this.m_inputIsActive.checked = true;
                        }
                    }
                    break;

                case false:
                    {

                        // Loading and editing existing item here.
                        this.LoadFromObject(data);
                        this.m_editorTitle.innerText = this.TITLE_EDIT_GROUP;
                        this.m_btnSubmit.innerText = this.BTN_LABEL_EDIT_GROUP;

                        this.m_inputGroupName.value = this.m_groupName;
                        this.m_inputIsActive.checked = this.m_isActive != 0;

                        if (this.m_appConfig['ReportLevel'] === undefined)
                            this.m_inputReportLevel.checked = false;
                        else
                            this.m_inputReportLevel.checked = this.m_appConfig['ReportLevel'];

                        if (this.m_inputReportLevel.checked)
                            this.m_labelReportLevel.innerHTML = this.MESSAGE_REPORT_LABEL;
                        else
                            this.m_labelReportLevel.innerHTML = this.MESSAGE_NO_REPORT_LABEL;

                        this.m_input_AT_NoTerminate.checked = this.m_appConfig['CannotTerminate'];
                        this.m_input_AT_DisableInternet.checked = this.m_appConfig['BlockInternet'];
                        this.m_input_AT_UseThreshold.checked = this.m_appConfig['UseThreshold'];
                        this.m_input_AT_ThresholdCount.valueAsNumber = parseInt(this.m_appConfig['ThresholdLimit']);
                        this.m_input_AT_ThresholdTriggerPeriod.valueAsNumber = parseInt(this.m_appConfig['ThresholdTriggerPeriod']);
                        this.m_input_AT_ThresholdTimeout.valueAsNumber = parseInt(this.m_appConfig['ThresholdTimeoutPeriod']);
                        this.m_input_AT_BypassesPerDay.valueAsNumber = parseInt(this.m_appConfig['BypassesPermitted']);
                        this.m_input_AT_BypassDuration.valueAsNumber = parseInt(this.m_appConfig['BypassDuration']);
                        this.m_inputNlpThreshold.valueAsNumber = parseFloat(this.m_appConfig['NlpThreshold']);
                        this.m_inputTrigerMaxsize.valueAsNumber = parseInt(this.m_appConfig['MaxTextTriggerScanningSize']);

                        try {
                            for (let i = 0; i < this.m_selectChannel.options.length; ++i) {
                                if (this.m_selectChannel.options[i].value.toLowerCase() == < string > this.m_appConfig['UpdateChannel'].toLowerCase()) {
                                    this.m_selectChannel.selectedIndex = this.m_selectChannel.options[i].index;
                                    break;
                                }
                            }
                        } catch (ex) {
                            console.warn(ex);
                            console.warn("Either the update channel is null or it's an invalid value. Defaulting...");
                            this.m_selectChannel.selectedIndex = 0;
                        }

                        this.m_inputFrequency.valueAsNumber = parseInt(this.m_appConfig['UpdateFrequency']);
                        this.m_inputPrimaryDNS.value = this.m_appConfig['PrimaryDns'];
                        this.m_inputSecondaryDNS.value = this.m_appConfig['SecondaryDns'];

                        if (this.m_inputPrimaryDNS.value == 'undefined') {
                            this.m_inputPrimaryDNS.value = '';
                        }

                        if (this.m_inputSecondaryDNS.value == 'undefined') {
                            this.m_inputSecondaryDNS.value = '';
                        }

                        this.loadAppGroupDatas(true);

                        if ('Blacklist' in this.m_appConfig) {
                            this.m_inputBlacklist.checked = true;
                            this.m_inputWhitelist.checked = false;
                        } else if ('Whitelist' in this.m_appConfig) {
                            this.m_inputBlacklist.checked = false;
                            this.m_inputWhitelist.checked = true;
                        } else {
                            // Default
                            this.m_inputBlacklist.checked = true;
                            this.m_inputWhitelist.checked = false;
                        }
                    }
                    break;
            }



            this.m_mainForm.onsubmit = ((e: Event): any => {
                let validateOpts = this.ValidationOptions;
                let validresult = $(this.m_mainForm).validate(validateOpts).form();

                let validator = $(this.m_mainForm).validate(validateOpts);

                if (validator.valid()) {
                    validator.resetForm();

                    this.ClearFormErrorMessages();

                    return this.OnFormSubmitClicked(e, data == null);
                }

                return false;
            });

            // Show the editor.
            $(this.m_editorOverlay).fadeIn(250);
        }

        public StopEditing(): void {
            $(this.m_editorOverlay).fadeOut(this.FADE_IN_DELAY_TIME);
        }

        public onMoveRightAllClicked(e: MouseEvent): void {
            this.m_left_groups.forEach((group_id): void => {
                this.m_right_groups.push(group_id);
            });
            this.m_left_groups = [];

            this._drawGroups(this.m_left_groups, this.m_select_AG_Source);
            this._drawGroups(this.m_right_groups, this.m_select_AG_Target);
            this._drawSelectedApps();
        }

        public onMoveLeftAllClicked(e: MouseEvent): void {
            this.m_right_groups.forEach((group_id): void => {
                this.m_left_groups.push(group_id);
            });
            this.m_right_groups = [];

            this._drawGroups(this.m_left_groups, this.m_select_AG_Source);
            this._drawGroups(this.m_right_groups, this.m_select_AG_Target);
            this._drawSelectedApps();
        }

        public onMoveRightClicked(e: MouseEvent): void {
            if (this.m_select_AG_Source.selectedIndex == -1) return;

            for (var i = 0; i < this.m_select_AG_Source.selectedOptions.length; i++) {
                let sel_id = parseInt(this.m_select_AG_Source.selectedOptions[i].value);
                let sel_seq_idx = this.m_left_groups.indexOf(sel_id);
                this.m_left_groups.splice(sel_seq_idx, 1);
                this.m_right_groups.push(sel_id);
            }

            this._drawGroups(this.m_left_groups, this.m_select_AG_Source);
            this._drawGroups(this.m_right_groups, this.m_select_AG_Target);
            this._drawSelectedApps();
        }

        public onMoveLeftClicked(e: MouseEvent): void {
            if (this.m_select_AG_Target.selectedIndex == -1) return;

            for (var i = 0; i < this.m_select_AG_Target.selectedOptions.length; i++) {
                let sel_id = parseInt(this.m_select_AG_Target.selectedOptions[i].value);
                let sel_seq_idx = this.m_right_groups.indexOf(sel_id);
                this.m_right_groups.splice(sel_seq_idx, 1);
                this.m_left_groups.push(sel_id);
            }

            this._drawGroups(this.m_left_groups, this.m_select_AG_Source);
            this._drawGroups(this.m_right_groups, this.m_select_AG_Target);
            this._drawSelectedApps();
        }
    }
}
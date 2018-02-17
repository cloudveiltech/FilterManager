/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel
{
    export class GroupRecord extends BaseRecord
    {
        //
        // ────────────────────────────────────────────────────────────────────────── I ──────────
        //   :::::: G R O U P   D A T A   M E M B E R S : :  :   :    :     :        :          :
        // ────────────────────────────────────────────────────────────────────────────────────
        //        

        private m_groupId: number;

        private m_groupName: string;

        private m_isActive: number;

        private m_assignedFilterIds: Object[];

        private m_appConfig: Object;
        private m_apps: any[];              // all application list
        private m_group_to_apps: any[];     // all application_id regarding group_id
        private m_app_groups: any[];        // all Groups 
        private m_left_groups: any[];       // unselected groups
        private m_right_groups: any[];      // selected groups
        private m_selected_apps: any[];     // selected applications

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
         * Heading that displays if we're creating or editing a group.
         * 
         * @private
         * @type {HTMLHeadingElement}
         * @memberOf GroupRecord
         */
        private m_editorTitle: HTMLHeadingElement;

        /**
         * The input where the user can specify the name of the group.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_groupNameInput: HTMLInputElement;

        /**
         * The input where the user can specify how often, in minutes, the client must check
         * for updates.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_groupUpdateCheckFrequencyInput: HTMLInputElement;

        /**
         * Toggle switch where the user can disable/enable the group.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_isActiveInput: HTMLInputElement;
        
        private m_groupPrimaryDnsInput: HTMLInputElement;

        private m_groupSecondaryDnsInput: HTMLInputElement;

        /**
         * Toggle switch to enable or disable use of termination prevention in app.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_antiTamperNoTerminateInput: HTMLInputElement;

        /**
         * Toggle switch to enable or disable disabling internet when app closes.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_antiTamperDisableInternetInput: HTMLInputElement;

        /**
         * Toggle switch to enable or disable using a block threshold in app.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_antiTamperUseThresholdInput: HTMLInputElement;

        /**
         * Number input indicating how many block actions it takes to hit the threshold.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_antiTamperThresholdCountInput: HTMLInputElement;

        /**
         * Number input indicating how long, in minutes, before the threshold counter resets.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_antiTamperThresholdTriggerPeriodInput: HTMLInputElement;

        /**
         * Number input indicating how long, in minutes, to disable the internet if the threshold is hit.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_antiTamperThresholdTimeoutInput: HTMLInputElement;

        /**
         * Number input indicating how many bypasses can be requested in a day.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_antiTamperBypassesPerDayInput: HTMLInputElement;

        /**
         * Number input indicating how much time, in minutes, a bypass is allowed to run for.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_antiTamperBypassDurationInput: HTMLInputElement;

        private m_groupNlpThresholdInput : HTMLInputElement;

        private m_textTriggerMaxSizeInput : HTMLInputElement;

        private m_updateChannelSelectInput : HTMLSelectElement;

        /**
         * Text area where users can list applications, one per line, that should be treated as a 
         * whitelist or blacklist by the filtering app for which applications to filter.
         * 
         * @private
         * @type {HTMLTextAreaElement}
         * @memberOf GroupRecord
         */
        //private m_filteredApplicationsList: HTMLTextAreaElement;

        private m_appGroupSourceList:HTMLSelectElement;
        private m_appGroupTargetList:HTMLSelectElement;
        private m_appGroupsSourceToTargetBtn: HTMLButtonElement;
        private m_appGroupSourceToTargetBtn: HTMLButtonElement;
        private m_appGroupTargetToSourceBtn: HTMLButtonElement;
        private m_appGroupsTargetToSourceBtn: HTMLButtonElement;
        /**
         * Radio button for choosing what type of list the filtered apps list, if
         * anything is defined, is supposed to be. Either a whitelist where the 
         * listed apps will bypass the filter and all others are filtered, or a blacklist
         * where only the listed apps will be filtered.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_filteredApplicationsAsBlacklistInput: HTMLInputElement;

        /**
        * Radio button for choosing what type of list the filtered apps list, if
        * anything is defined, is supposed to be. Either a whitelist where the 
        * listed apps will bypass the filter and all others are filtered, or a blacklist
        * where only the listed apps will be filtered.
        * 
        * @private
        * @type {HTMLInputElement}
        * @memberOf GroupRecord
        */
        private m_filteredApplicationsAsWhitelistInput: HTMLInputElement;

        /**
         * The submit button. Not a true form submit type, but rather manually
         * "submits" all data in this form.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf GroupRecord
         */
        private m_submitBtn: HTMLButtonElement;

        /**
         * Cancel button that hides the UI.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf GroupRecord
         */
        private m_cancelBtn: HTMLButtonElement;

        //
        // ──────────────────────────────────────────────────────────────────────────────────────────── III ──────────
        //   :::::: F I L T E R   L I S T   A S S I G M E N T   A R E A : :  :   :    :     :        :          :
        // ──────────────────────────────────────────────────────────────────────────────────────────────────────
        //

        /**
         * Search input for filtering draggable items in the blacklist container.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_blacklistFiltersSearchInput: HTMLInputElement;

        /**
         * Placeholder that saves the last search, or instructs how to search.
         * 
         * @private
         * @type {HTMLSpanElement}
         * @memberOf GroupRecord
         */
        private m_blacklistFiltersSearchPlaceholder: HTMLSpanElement;

        /**
         * Search input for filtering draggable items in the whitelist container.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_whitelistFiltersSearchInput: HTMLInputElement;

        /**
         * Placeholder that saves the last search, or instructs how to search.
         * 
         * @private
         * @type {HTMLSpanElement}
         * @memberOf GroupRecord
         */
        private m_whitelistFiltersSearchPlaceholder: HTMLSpanElement;

        /**
         * Search input for filtering draggable items in the bypass container. 
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_bypassFiltersSearchInput: HTMLInputElement;

        /**
         * Placeholder that saves the last search, or instructs how to search.
         * 
         * @private
         * @type {HTMLSpanElement}
         * @memberOf GroupRecord
         */
        private m_bypassFiltersSearchPlaceholder: HTMLSpanElement;

        /**
         * Search input for filtering draggable items in the unassigned container.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf GroupRecord
         */
        private m_unassignedFiltersSearchInput: HTMLInputElement;

        /**
         * Placeholder that saves the last search, or instructs how to search.
         * 
         * @private
         * @type {HTMLSpanElement}
         * @memberOf GroupRecord
         */
        private m_unassignedFiltersSearchPlaceholder: HTMLSpanElement;

        /**
         * Dragula container for holding selected blacklists.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf GroupRecord
         */
        private m_blacklistFiltersContainer: HTMLDivElement;

        /**
         * Dragula container for holding selected whitelists.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf GroupRecord
         */
        private m_whitelistFiltersContainer: HTMLDivElement;

        /**
         * Dragula container for holding selected bypassable lists.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf GroupRecord
         */
        private m_bypassFiltersContainer: HTMLDivElement;

        /**
         * Dragula container for holding unselected filter lists.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf GroupRecord
         */
        private m_unassignedFiltersContainer: HTMLDivElement;

        /**
         * Gets the base API route from this record type.
         * 
         * @readonly
         * @type {string}
         * @memberOf GroupRecord
         */
        public get RecordRoute(): string
        {
            return 'api/admin/groups';
        }

        /**
         * Gets the form validation options required for proper submission.
         * 
         * @readonly
         * @protected
         * @type {JQueryValidation.ValidationOptions}
         * @memberOf GroupRecord
         */
        protected get ValidationOptions(): JQueryValidation.ValidationOptions
        {
            let validationRules: JQueryValidation.RulesDictionary = {};
            validationRules[this.m_groupNameInput.id] = {
                required: true,
                minlength: 1
            };

            let validationErrorMessages = {};
            validationErrorMessages[this.m_groupNameInput.id] = 'A valid group name is required.';

            let validationOptions: JQueryValidation.ValidationOptions =
                {
                    rules: validationRules,
                    errorPlacement: ((error: JQuery, element: JQuery): void =>
                    {
                        error.appendTo('#group_form_errors');
                        $('#group_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages,

                    // This is critical. Without this, hidden/non-visible forms and
                    // form elements will not be validated at all. 
                    ignore: ''
                };

            return validationOptions;
        }

        /**
         * Creates an instance of GroupRecord.
         * 
         * 
         * @memberOf GroupRecord
         */
        constructor()
        {
            super();
            this.ConstructFormReferences();
            this.ConstructFilterAssignmentArea();
        }
        private ConstructFormReferences(): void
        {
            this.m_mainForm = document.querySelector('#editor_group_form') as HTMLFormElement;
            this.m_editorTitle = document.querySelector('#group_editing_title') as HTMLHeadingElement;
            this.m_editorOverlay = document.querySelector('#overlay_group_editor') as HTMLDivElement;

            this.m_groupNameInput = document.querySelector('#editor_group_input_groupname') as HTMLInputElement;
            this.m_groupUpdateCheckFrequencyInput = document.querySelector('#editor_cfg_update_frequency_input') as HTMLInputElement;

            this.m_groupPrimaryDnsInput = document.querySelector('#editor_cfg_primary_dns_input') as HTMLInputElement;
            this.m_groupSecondaryDnsInput = document.querySelector('#editor_cfg_secondary_dns_input') as HTMLInputElement;
            
            let ipv4andv6OnlyFilter = (e:KeyboardEvent) =>
            {
                let inputBox = e.target as HTMLInputElement;
                inputBox.value = inputBox.value.replace(/[^0-9\\.:]/g, '');
            };

            this.m_groupPrimaryDnsInput.onkeyup = ipv4andv6OnlyFilter;

            this.m_groupSecondaryDnsInput.onkeyup = ipv4andv6OnlyFilter;

            this.m_isActiveInput = document.querySelector('#editor_group_input_isactive') as HTMLInputElement;

            this.m_antiTamperNoTerminateInput = document.querySelector('#editor_cfg_no_terminate_input') as HTMLInputElement;
            this.m_antiTamperDisableInternetInput = document.querySelector('#editor_cfg_disable_internet_input') as HTMLInputElement;
            this.m_antiTamperUseThresholdInput = document.querySelector('#editor_cfg_use_threshold_input') as HTMLInputElement;
            this.m_antiTamperThresholdCountInput = document.querySelector('#editor_cfg_threshold_count_input') as HTMLInputElement;
            this.m_antiTamperThresholdTimeoutInput = document.querySelector('#editor_cfg_threshold_period_input') as HTMLInputElement;
            this.m_antiTamperThresholdTriggerPeriodInput = document.querySelector('#editor_cfg_threshold_timeout_input') as HTMLInputElement;
            this.m_antiTamperBypassesPerDayInput = document.querySelector('#editor_cfg_bypasses_allowed_input') as HTMLInputElement;
            this.m_antiTamperBypassDurationInput = document.querySelector('#editor_cfg_bypass_duration_input') as HTMLInputElement;

            this.m_groupNlpThresholdInput = document.querySelector('#editor_cfg_nlp_threshold_input') as HTMLInputElement;
            this.m_textTriggerMaxSizeInput = document.querySelector('#editor_cfg_trigger_max_size_input') as HTMLInputElement;
            this.m_updateChannelSelectInput = document.querySelector('#editor_cfg_update_channel_input') as HTMLSelectElement;
            

            // Enforce input range when typing. Since this represents a percent, should
            // always be between 0 and 1.0.
            this.m_groupNlpThresholdInput.onkeyup = (e:KeyboardEvent) =>
            {
                let inputBox = e.target as HTMLInputElement;
                let value = inputBox.valueAsNumber;

                if(value > 1.0)
                {
                    inputBox.valueAsNumber = 1.0;
                }

                if(value < 0)
                {
                    inputBox.valueAsNumber = 0;
                }
            };

            //this.m_filteredApplicationsList = document.querySelector('#group_filtered_applications') as HTMLTextAreaElement;
            this.m_filteredApplicationsAsBlacklistInput = document.querySelector('#group_filteredapps_radio_blacklist') as HTMLInputElement;
            this.m_filteredApplicationsAsWhitelistInput = document.querySelector('#group_filteredapps_radio_whitelist') as HTMLInputElement;

            this.m_appGroupSourceList = document.querySelector('#appgroup_source_list') as HTMLSelectElement;
            this.m_appGroupTargetList = document.querySelector('#appgroup_target_list') as HTMLSelectElement;
            this.m_appGroupsSourceToTargetBtn = document.querySelector('#appgroups_source_to_target') as HTMLButtonElement;
            this.m_appGroupSourceToTargetBtn = document.querySelector('#appgroup_source_to_target') as HTMLButtonElement;
            this.m_appGroupTargetToSourceBtn = document.querySelector('#appgroup_target_to_source') as HTMLButtonElement;
            this.m_appGroupsTargetToSourceBtn = document.querySelector('#appgroups_target_to_source') as HTMLButtonElement;
            
            this.m_submitBtn = document.querySelector('#group_editor_submit') as HTMLButtonElement;
            this.m_cancelBtn = document.querySelector('#group_editor_cancel') as HTMLButtonElement;

            this.InitButtonHandlers();
        }

        /**
         * Builds out the dragula areas where the user can configure blacklists
         * by drag and dropping them into various containers. This function also
         * sets up all of the search functionality for each dragula container.
         * 
         * @private
         * 
         * @memberOf GroupRecord
         */
        private ConstructFilterAssignmentArea(): void
        {
            this.m_blacklistFiltersSearchInput = document.querySelector('#group_blacklist_filters_search_input') as HTMLInputElement;
            this.m_blacklistFiltersSearchPlaceholder = document.querySelector('#group_blacklist_filters_search_input_placeholder') as HTMLInputElement;

            this.m_whitelistFiltersSearchInput = document.querySelector('#group_whitelist_filters_search_input') as HTMLInputElement;
            this.m_whitelistFiltersSearchPlaceholder = document.querySelector('#group_whitelist_filters_search_input_placeholder') as HTMLInputElement;

            this.m_bypassFiltersSearchInput = document.querySelector('#group_bypass_filters_search_input') as HTMLInputElement;
            this.m_bypassFiltersSearchPlaceholder = document.querySelector('#group_bypass_filters_search_input_placeholder') as HTMLInputElement;

            this.m_unassignedFiltersSearchPlaceholder = document.querySelector('#group_unassigned_filters_search_input_placeholder') as HTMLInputElement;
            this.m_unassignedFiltersSearchInput = document.querySelector('#group_unassigned_filters_search_input') as HTMLInputElement;

            // Add search capability for this input when typing.
            this.m_blacklistFiltersSearchInput.onkeyup = ((e: KeyboardEvent): any =>
            {
                this.OnSearchFieldInput(this.m_blacklistFiltersSearchInput, this.m_blacklistFiltersContainer, e);
            });

            // Add search capability for this input when typing.
            this.m_whitelistFiltersSearchInput.onkeyup = ((e: KeyboardEvent): any =>
            {
                this.OnSearchFieldInput(this.m_whitelistFiltersSearchInput, this.m_whitelistFiltersContainer, e);
            });

            // Add search capability for this input when typing.
            this.m_bypassFiltersSearchInput.onkeyup = ((e: KeyboardEvent): any =>
            {
                this.OnSearchFieldInput(this.m_bypassFiltersSearchInput, this.m_bypassFiltersContainer, e);
            });

            // Add search capability for this input when typing.
            this.m_unassignedFiltersSearchInput.onkeyup = ((e: KeyboardEvent): any =>
            {
                this.OnSearchFieldInput(this.m_unassignedFiltersSearchInput, this.m_unassignedFiltersContainer, e);
            });

            let setupSearchBoxFocus = ((searchBox: HTMLInputElement, placeholder: HTMLSpanElement): void =>
            {
                // Whenever the input has focus, we want to change the font color to white. When not
                // in focus, we want it transparent so it doesn't muck up our visuals.
                searchBox.onfocus = ((e: FocusEvent): any =>
                {
                    searchBox.style.color = "white";

                    if (searchBox.value.length > 0)
                    {
                        placeholder.textContent = searchBox.value;
                    }
                    else
                    {
                        placeholder.textContent = "Type To Filter Selection";
                    }
                });

                searchBox.onblur = ((e: FocusEvent): any =>
                {
                    searchBox.style.color = "transparent";

                    if (searchBox.value.length > 0)
                    {
                        placeholder.textContent = searchBox.value;
                    }
                    else
                    {
                        placeholder.textContent = "Type To Filter Selection";
                    }
                });
            });

            setupSearchBoxFocus(this.m_blacklistFiltersSearchInput, this.m_blacklistFiltersSearchPlaceholder);
            setupSearchBoxFocus(this.m_whitelistFiltersSearchInput, this.m_whitelistFiltersSearchPlaceholder);
            setupSearchBoxFocus(this.m_bypassFiltersSearchInput, this.m_bypassFiltersSearchPlaceholder);
            setupSearchBoxFocus(this.m_unassignedFiltersSearchInput, this.m_unassignedFiltersSearchPlaceholder);

            // Build out references to the storage containers that will hold our
            // dragable categories.
            this.m_blacklistFiltersContainer = document.querySelector('#group_blacklist_filters') as HTMLDivElement;
            this.m_whitelistFiltersContainer = document.querySelector('#group_whitelist_filters') as HTMLDivElement;
            this.m_bypassFiltersContainer = document.querySelector('#group_bypass_filters') as HTMLDivElement;
            this.m_unassignedFiltersContainer = document.querySelector('#group_unassigned_filters') as HTMLDivElement;
        }

        private ResetSearchBoxes(): void
        {
            this.m_blacklistFiltersSearchInput.value = '';
            this.m_whitelistFiltersSearchInput.value = '';
            this.m_bypassFiltersSearchInput.value = '';
            this.m_unassignedFiltersSearchInput.value = '';

            this.m_blacklistFiltersSearchPlaceholder.textContent = 'Type To Filter Selection';
            this.m_whitelistFiltersSearchPlaceholder.textContent = 'Type To Filter Selection';
            this.m_bypassFiltersSearchPlaceholder.textContent = 'Type To Filter Selection';
            this.m_unassignedFiltersSearchPlaceholder.textContent = 'Type To Filter Selection';
        }

        /**
         * Called by the two inputs used to "search" in our draggable areas. Hides elements
         * that do not match the search criteria. Called on key up.
         * 
         * @private
         * @param {HTMLInputElement} input  The html input element being typed on.
         * @param {HTMLDivElement} searchTarget The html element we're going to search within.
         * @param {KeyboardEvent} e The input event args.
         * @returns {*}
         * 
         * @memberOf GroupRecord
         */
        private OnSearchFieldInput(input: HTMLInputElement, searchTarget: HTMLDivElement, e: KeyboardEvent): any
        {
            let currentTextValue = input.value.toLowerCase();

            let childrenToSearch = searchTarget.querySelectorAll('div[citadel-filter-list-id]');

            for (let i = 0; i < childrenToSearch.length; ++i)
            {
                if (currentTextValue.length <= 0)
                {
                    $(childrenToSearch.item(i)).show();
                    continue;
                }

                let childText = childrenToSearch.item(i).textContent.toLowerCase();

                if (!~childText.indexOf(currentTextValue))
                {
                    $(childrenToSearch.item(i)).hide();
                }
                else
                {
                    $(childrenToSearch.item(i)).show();
                }
            }
        }

        private InitButtonHandlers(): void
        {
            this.m_cancelBtn.onclick = ((e: MouseEvent): any =>
            {
                this.StopEditing();
            });

            this.m_appGroupsSourceToTargetBtn.onclick = ((e: MouseEvent): any =>
            {
                this.onMoveRightAllClicked(e);
            });
            this.m_appGroupSourceToTargetBtn.onclick = ((e: MouseEvent): any =>
            {
                this.onMoveRightClicked(e);
            });
            this.m_appGroupTargetToSourceBtn.onclick = ((e: MouseEvent): any =>
            {
                this.onMoveLeftClicked(e);
            });
            this.m_appGroupsTargetToSourceBtn.onclick = ((e: MouseEvent): any =>
            {
                this.onMoveLeftAllClicked(e);
            });

            this.m_submitBtn.onclick = ((e: MouseEvent): any =>
            {
                console.log("submitting");

                if (this.m_mainForm.onsubmit != null)
                {
                    this.m_mainForm.onsubmit(new Event("submit"));
                }
            });
        }

        protected LoadFromObject(data: Object): void
        {
            this.m_groupId = data['id'] as number;
            this.m_groupName = data['name'] as string;
            this.m_isActive = data['isactive'];
            this.m_assignedFilterIds = data['assigned_filter_ids'] as Object[];
            this.m_appConfig = JSON.parse(data['app_cfg']);
            console.log(this.m_appConfig);
        }

        protected LoadFromForm(): void
        {
            this.m_groupName = this.m_groupNameInput.value;
            this.m_isActive = this.m_isActiveInput.checked == true ? 1 : 0;

            let allAssignedFilters = new Array<Object>();

            let collectSelected = ((container: HTMLDivElement, blacklist: boolean, whitelist: boolean, bypass: boolean): void =>
            {
                let assignedLists = container.querySelectorAll('div[citadel-filter-list-id]');
                for (let i = 0; i < assignedLists.length; ++i)
                {
                    let selectedBlacklist = {
                        'filter_list_id': parseInt(assignedLists.item(i).getAttribute('citadel-filter-list-id')),
                        'as_blacklist': blacklist === true ? 1 : 0,
                        'as_whitelist': whitelist === true ? 1 : 0,
                        'as_bypass': bypass === true ? 1 : 0
                    };

                    allAssignedFilters.push(selectedBlacklist);
                }
            });

            collectSelected(this.m_blacklistFiltersContainer, true, false, false);
            collectSelected(this.m_whitelistFiltersContainer, false, true, false);
            collectSelected(this.m_bypassFiltersContainer, false, false, true);

            this.m_assignedFilterIds = allAssignedFilters;

            // Get all listed apps to filter, keep only unique entries, and
            // then based on the radio selected, set to either whitelist or 
            // blacklist.
            //let whitelistedApplications = new Array<String>();
            //let blacklistedApplications = new Array<String>();

            /*
            var allFilteredAppLines = <Array<String>>this.m_filteredApplicationsList.value.trim().split('\n');
            if (allFilteredAppLines != null)
            {
                let uniqueFilteredApps = {};
                let distinctFilteredApps = [];
                allFilteredAppLines.forEach((value: string): void =>
                {
                    if (typeof (uniqueFilteredApps[value]) == "undefined")
                    {
                        distinctFilteredApps.push(value);
                    }

                    uniqueFilteredApps[value] = 0;
                });

                allFilteredAppLines = distinctFilteredApps;
            }
            */
            let filterAppsKey = 'Blacklist';
            if (!this.m_filteredApplicationsAsBlacklistInput.checked)
            {
                filterAppsKey = 'Whitelist';
            }
            
            let appConfig =
                {
                    'UpdateFrequency' : this.m_groupUpdateCheckFrequencyInput.valueAsNumber,
                    'PrimaryDns' : this.m_groupPrimaryDnsInput.value,
                    'SecondaryDns' : this.m_groupSecondaryDnsInput.value,
                    'CannotTerminate': this.m_antiTamperNoTerminateInput.checked,
                    'BlockInternet': this.m_antiTamperDisableInternetInput.checked,
                    'UseThreshold': this.m_antiTamperUseThresholdInput.checked,
                    'ThresholdLimit': this.m_antiTamperThresholdCountInput.valueAsNumber,
                    'ThresholdTriggerPeriod': this.m_antiTamperThresholdTriggerPeriodInput.valueAsNumber,
                    'ThresholdTimeoutPeriod': this.m_antiTamperThresholdTimeoutInput.valueAsNumber,
                    'BypassesPermitted': this.m_antiTamperBypassesPerDayInput.valueAsNumber,
                    'BypassDuration': this.m_antiTamperBypassDurationInput.valueAsNumber,
                    'NlpThreshold' : this.m_groupNlpThresholdInput.valueAsNumber,
                    'MaxTextTriggerScanningSize': this.m_textTriggerMaxSizeInput.valueAsNumber,
                    'UpdateChannel' : this.m_updateChannelSelectInput.options[this.m_updateChannelSelectInput.selectedIndex].value,
                };

            appConfig[filterAppsKey] = "checked";

            this.m_appConfig = appConfig;
        }

        private getGroupItem(group_id) {
            var group_item = null;
            this.m_app_groups.forEach((item: any): void =>
            {
                if(item.id == group_id) {
                    group_item = item;
                    return;
                }
            });
            return group_item;
        }
        private draw_left_groups()
        {
            $(this.m_appGroupSourceList).empty(); 
            if(this.m_left_groups.length == 0) return;
            this.m_left_groups.forEach((group_id): void =>
            {
                var newOption = document.createElement("option");
                var item = this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;                
                this.m_appGroupSourceList.add(newOption);
            });
        }

        private draw_right_groups()
        {
            $(this.m_appGroupTargetList).empty();
            if(this.m_right_groups.length == 0) return;
            this.m_right_groups.forEach((group_id): void =>
            {
                var newOption = document.createElement("option");
                var item = this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;                
                this.m_appGroupTargetList.add(newOption);
            });
        }

        private get_appications_by_groupid(group_id) {
            let arr = [];
            this.m_group_to_apps.forEach((group_item): void =>
            {
                if(group_item.app_group_id == group_id) {
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
                if(app.id == app_id) {
                    item = app;
                    return;
                }
            });
            return item;
        }
        private draw_selected_applications()
        {
            var str_html = '';
            this.m_selected_apps = [];
            if(this.m_right_groups.length >0) {
                this.m_right_groups.forEach((group_id): void =>
                {
                    var app_ids = this.get_appications_by_groupid(group_id); 
                    for(var i = 0; i < app_ids.length; i++) {
                        if(this.m_selected_apps.indexOf(app_ids[i]) < 0) {
                            this.m_selected_apps.push(app_ids[i]);
                            let app_item = this.get_application(app_ids[i]);
                            str_html += '<div>' + app_item.name + '</div>';
                        }
                    }
                });
            }
            $('#selected_applications').html(str_html);
        }
        /**
         * Removes all form validation error messages from the UI.
         * 
         * @private
         * 
         * @memberOf GroupRecord
         */
        private ClearFormErrorMessages(): void
        {
            // Force remove all validation error messages.
            var errElms = document.querySelectorAll('#group_form_errors > *');
            for (let i = 0; i < errElms.length; ++i)
            {
                let elm = errElms.item(i);
                elm.parentNode.removeChild(elm);
            }
        }

        private loadAppGroupDatas(flag: boolean): void {
            this.m_apps = [];
            this.m_app_groups = [];
            this.m_submitBtn.disabled = true;
            $("#spiner_4").show();
            var url = 'api/admin/get_app_data';
            if (flag) {
                url += '/' + this.m_groupId; 
            }   
            let ajaxSettings: JQueryAjaxSettings =
            {
                method: "GET",
                timeout: 60000,
                url: url,
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                {
                    this.m_apps = data.apps;
                    this.m_app_groups = data.app_groups;
                    this.m_group_to_apps = data.group_to_apps;
                    if(flag) {
                        let selected_app_groups = data.selected_app_groups;
                        this.m_left_groups = [];
                        this.m_right_groups = [];
                        if(selected_app_groups.length > 0) {
                            this.m_app_groups.forEach((app_group: any): void =>
                            {                                
                                this.m_left_groups.push(app_group.id);
                            });
                            selected_app_groups.forEach((app_group: any): void =>
                            {
                                this.m_right_groups.push(app_group.app_group_id);
                                let pos = this.m_left_groups.indexOf(app_group.app_group_id);
                                this.m_left_groups.splice(pos, 1);
                            });   
                        } else {
                            this.m_app_groups.forEach((app_group: any): void =>
                            {
                                this.m_left_groups.push(app_group.id);
                            });    
                        }
                    } else {
                        this.m_left_groups = [];
                        this.m_right_groups = [];
                        this.m_app_groups.forEach((app_group: any): void =>
                        {
                            this.m_left_groups.push(app_group.id);
                        });
                    }
                    this.draw_left_groups();
                    this.draw_right_groups();
                    this.draw_selected_applications();
                    $("#spiner_4").hide();
                    this.m_submitBtn.disabled = false;
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                {
                    $("#spiner_4").hide();
                    this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(() => { this.m_progressWait.Hide(); }, 5000);

                    if (jqXHR.status > 399 && jqXHR.status < 500){
                    } else {
                    }
                }
            }
            $.get(ajaxSettings);
        }
        public StartEditing(allFilters: DataTables.DataTable, data: Object = null, cloneData: Object = null): void
        {
            let clearListContainer = ((container: HTMLDivElement): void =>
            {
                let assignedChildren = container.querySelectorAll('div[citadel-filter-list-id]');
                for (let i = 0; i < assignedChildren.length; ++i)
                {
                    container.removeChild(assignedChildren.item(i));
                }
            });

            // Remove all existing draggable items.
            clearListContainer(this.m_blacklistFiltersContainer);
            clearListContainer(this.m_whitelistFiltersContainer);
            clearListContainer(this.m_bypassFiltersContainer);
            clearListContainer(this.m_unassignedFiltersContainer);
            //

            // Remove all existing search box content
            this.ResetSearchBoxes();

            // Remove any old form error messages
            this.ClearFormErrorMessages();

            // Reset form.
            this.m_mainForm.reset();

            // Clear out the filtered apps list.
            //this.m_filteredApplicationsList.value = "";

            // Reset config controls.
            this.m_antiTamperNoTerminateInput.checked = false;
            this.m_antiTamperDisableInternetInput.checked = false;
            this.m_antiTamperUseThresholdInput.checked = false;
            this.m_antiTamperThresholdCountInput.valueAsNumber = 0;
            this.m_antiTamperThresholdTriggerPeriodInput.valueAsNumber = 0;
            this.m_antiTamperThresholdTimeoutInput.valueAsNumber = 0;
            this.m_antiTamperBypassesPerDayInput.valueAsNumber = 0;
            this.m_antiTamperBypassDurationInput.valueAsNumber = 0;

            this.m_groupNlpThresholdInput.valueAsNumber = 0;
            this.m_textTriggerMaxSizeInput.valueAsNumber = -1;
            this.m_updateChannelSelectInput.selectedIndex = 0;

            this.m_groupUpdateCheckFrequencyInput.valueAsNumber = 5;
            this.m_groupPrimaryDnsInput.value = '';
            this.m_groupSecondaryDnsInput.value = '';

            // Set the active tab to the settings tab.
            // Using Jquery here because we're ultra lazy.
            $('ul.tabs > li').removeClass('active');
            $('ul.tabs > li').first().addClass('active');
            $('div.frames > div.frame').hide();
            $('div.frames > div.frame').first().show();

            // Try to load existing assigned filters, if we're editing.
            let allAssignedFilters = new Array<Object>();
            if (data != null && data.hasOwnProperty('assigned_filter_ids'))
            {
                allAssignedFilters = data['assigned_filter_ids'] as Object[];
            } else 
            {
                if (cloneData != null && cloneData.hasOwnProperty('assigned_filter_ids'))
                {
                    allAssignedFilters = cloneData['assigned_filter_ids'] as Object[];
                }
            }
            // Iterate over all available filter lists and put them in the
            // correct column for editing/assignment.
            allFilters.each((elm: any): void =>
            {
                let imageClassName = "";

                let draggableFilterOption = document.createElement('div') as HTMLDivElement;
                draggableFilterOption.setAttribute('citadel-filter-list-id', elm['id']);
                draggableFilterOption.style.setProperty('font-size', '10');
                draggableFilterOption.style.setProperty('display', 'inline-block');
                draggableFilterOption.style.setProperty('text-align', 'center');

                if ((<string>(elm['type'])).toUpperCase().indexOf("TRIG") != -1)
                {
                    // This is a trigger list.
                    draggableFilterOption.style.setProperty('background', 'crimson');
                    draggableFilterOption.setAttribute('citadel-filter-list-type', 'trigger');
                    imageClassName = "mif-warning";
                }
                else if ((<string>(elm['type'])).toUpperCase().indexOf("NLP") != -1)
                {
                    // This is a NLP filtering category.
                    draggableFilterOption.style.setProperty('background', '#f0a30a');
                    draggableFilterOption.style.setProperty('color', 'white');
                    draggableFilterOption.setAttribute('citadel-filter-list-type', 'nlp');
                    imageClassName = "mif-language";
                }
                else
                {
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
                for (let j = 0; j < allAssignedFilters.length; ++j)
                {
                    if (allAssignedFilters[j]['filter_list_id'] == <number>(elm['id']))
                    {
                        alreadyHasThisFilterList = true;
                        existingItem = allAssignedFilters[j];
                        break;
                    }
                }

                if (alreadyHasThisFilterList && existingItem != null)
                {
                    if (existingItem['as_blacklist'] == true)
                    {
                        this.m_blacklistFiltersContainer.appendChild(draggableFilterOption);
                    }
                    else if (existingItem['as_whitelist'] == true)
                    {
                        this.m_whitelistFiltersContainer.appendChild(draggableFilterOption);
                    }
                    else if (existingItem['as_bypass'] == true)
                    {
                        this.m_bypassFiltersContainer.appendChild(draggableFilterOption);
                    }
                }
                else
                {
                    this.m_unassignedFiltersContainer.appendChild(draggableFilterOption);
                }
            });
            switch (data == null)
            {
                case true:
                    {                        
                        if(cloneData != null) {
                            // Set data with clone data
                            this.m_editorTitle.innerText = "Clone Group";
                            this.m_submitBtn.innerText = "Clone Group";
                            this.LoadFromObject(cloneData);
                            this.m_groupNameInput.value = this.m_groupName + "-cloned";
                            this.m_isActiveInput.checked = this.m_isActive != 0;
            
                            this.m_antiTamperNoTerminateInput.checked = this.m_appConfig['CannotTerminate'];
                            this.m_antiTamperDisableInternetInput.checked = this.m_appConfig['BlockInternet'];
                            this.m_antiTamperUseThresholdInput.checked = this.m_appConfig['UseThreshold'];
                            this.m_antiTamperThresholdCountInput.valueAsNumber = parseInt(this.m_appConfig['ThresholdLimit']);
                            this.m_antiTamperThresholdTriggerPeriodInput.valueAsNumber = parseInt(this.m_appConfig['ThresholdTriggerPeriod']);
                            this.m_antiTamperThresholdTimeoutInput.valueAsNumber = parseInt(this.m_appConfig['ThresholdTimeoutPeriod']);
                            this.m_antiTamperBypassesPerDayInput.valueAsNumber = parseInt(this.m_appConfig['BypassesPermitted']);
                            this.m_antiTamperBypassDurationInput.valueAsNumber = parseInt(this.m_appConfig['BypassDuration']);
                            this.m_groupNlpThresholdInput.valueAsNumber = parseFloat(this.m_appConfig['NlpThreshold']);
                            this.m_textTriggerMaxSizeInput.valueAsNumber = parseInt(this.m_appConfig['MaxTextTriggerScanningSize']);
                            
                            try
                            {
                                for(let i = 0; i < this.m_updateChannelSelectInput.options.length; ++i)
                                {
                                    if(this.m_updateChannelSelectInput.options[i].value.toLowerCase() == <string>this.m_appConfig['UpdateChannel'].toLowerCase())
                                    {
                                        this.m_updateChannelSelectInput.selectedIndex = this.m_updateChannelSelectInput.options[i].index;
                                        break;
                                    }
                                }
                            }
                            catch(ex)
                            {
                                console.warn(ex);
                                console.warn("Either the update channel is null or it's an invalid value. Defaulting...");
                                this.m_updateChannelSelectInput.selectedIndex = 0;
                            }
                            
                            this.m_groupUpdateCheckFrequencyInput.valueAsNumber = parseInt(this.m_appConfig['UpdateFrequency']);
                            this.m_groupPrimaryDnsInput.value = this.m_appConfig['PrimaryDns'];
                            this.m_groupSecondaryDnsInput.value = this.m_appConfig['SecondaryDns'];
            
                            if(this.m_groupPrimaryDnsInput.value == 'undefined')
                            {
                                this.m_groupPrimaryDnsInput.value = '';
                            }
            
                            if(this.m_groupSecondaryDnsInput.value == 'undefined')
                            {
                                this.m_groupSecondaryDnsInput.value = '';
                            }
            
                            this.loadAppGroupDatas(true);
                            
                            if ('Blacklist' in this.m_appConfig)
                            {
                                this.m_filteredApplicationsAsBlacklistInput.checked = true;
                                this.m_filteredApplicationsAsWhitelistInput.checked = false;
                            }
                            else if ('Whitelist' in this.m_appConfig)
                            {
                                this.m_filteredApplicationsAsBlacklistInput.checked = false;
                                this.m_filteredApplicationsAsWhitelistInput.checked = true;
                            }
                            else
                            {
                                // Default
                                this.m_filteredApplicationsAsBlacklistInput.checked = true;
                                this.m_filteredApplicationsAsWhitelistInput.checked = false;
                            }
                            this.m_groupId = undefined;
            /*
                            let savedFilteredAppsList: Array<String>;
            
                            if (savedFilteredAppsList != null)
                            {
                                savedFilteredAppsList.forEach((line: String): void =>
                                {
                                    line = line.trim();
                                    if(line.length > 0)
                                    {
                                        this.m_filteredApplicationsList.value += line + "\n";
                                    }
                                });
                            } */
                        } else {
                            this.m_editorTitle.innerText = "Create New Group";
                            this.m_submitBtn.innerText = "Create Group";
                            this.loadAppGroupDatas(false);
                            // Group is enabled by default.
                            this.m_isActiveInput.checked = true;

                        }
                    }
                    break;

                case false:
                    {

                        // Loading and editing existing item here.
                        this.LoadFromObject(data);
                        this.m_editorTitle.innerText = "Edit Group";
                        this.m_submitBtn.innerText = "Save";

                        this.m_groupNameInput.value = this.m_groupName;
                        this.m_isActiveInput.checked = this.m_isActive != 0;

                        this.m_antiTamperNoTerminateInput.checked = this.m_appConfig['CannotTerminate'];
                        this.m_antiTamperDisableInternetInput.checked = this.m_appConfig['BlockInternet'];
                        this.m_antiTamperUseThresholdInput.checked = this.m_appConfig['UseThreshold'];
                        this.m_antiTamperThresholdCountInput.valueAsNumber = parseInt(this.m_appConfig['ThresholdLimit']);
                        this.m_antiTamperThresholdTriggerPeriodInput.valueAsNumber = parseInt(this.m_appConfig['ThresholdTriggerPeriod']);
                        this.m_antiTamperThresholdTimeoutInput.valueAsNumber = parseInt(this.m_appConfig['ThresholdTimeoutPeriod']);
                        this.m_antiTamperBypassesPerDayInput.valueAsNumber = parseInt(this.m_appConfig['BypassesPermitted']);
                        this.m_antiTamperBypassDurationInput.valueAsNumber = parseInt(this.m_appConfig['BypassDuration']);
                        this.m_groupNlpThresholdInput.valueAsNumber = parseFloat(this.m_appConfig['NlpThreshold']);
                        this.m_textTriggerMaxSizeInput.valueAsNumber = parseInt(this.m_appConfig['MaxTextTriggerScanningSize']);
                        
                        try
                        {
                            for(let i = 0; i < this.m_updateChannelSelectInput.options.length; ++i)
                            {
                                if(this.m_updateChannelSelectInput.options[i].value.toLowerCase() == <string>this.m_appConfig['UpdateChannel'].toLowerCase())
                                {
                                    this.m_updateChannelSelectInput.selectedIndex = this.m_updateChannelSelectInput.options[i].index;
                                    break;
                                }
                            }
                        }
                        catch(ex)
                        {
                            console.warn(ex);
                            console.warn("Either the update channel is null or it's an invalid value. Defaulting...");
                            this.m_updateChannelSelectInput.selectedIndex = 0;
                        }
                        
                        this.m_groupUpdateCheckFrequencyInput.valueAsNumber = parseInt(this.m_appConfig['UpdateFrequency']);
                        this.m_groupPrimaryDnsInput.value = this.m_appConfig['PrimaryDns'];
                        this.m_groupSecondaryDnsInput.value = this.m_appConfig['SecondaryDns'];

                        if(this.m_groupPrimaryDnsInput.value == 'undefined')
                        {
                            this.m_groupPrimaryDnsInput.value = '';
                        }

                        if(this.m_groupSecondaryDnsInput.value == 'undefined')
                        {
                            this.m_groupSecondaryDnsInput.value = '';
                        }
                        this.loadAppGroupDatas(true);
                        
                        if ('Blacklist' in this.m_appConfig)
                        {
                            this.m_filteredApplicationsAsBlacklistInput.checked = true;
                            this.m_filteredApplicationsAsWhitelistInput.checked = false;

                            //savedFilteredAppsList = this.m_appConfig['BlacklistedApplications'];
                        }
                        else if ('Whitelist' in this.m_appConfig)
                        {
                            this.m_filteredApplicationsAsBlacklistInput.checked = false;
                            this.m_filteredApplicationsAsWhitelistInput.checked = true;
                            //savedFilteredAppsList = this.m_appConfig['WhitelistedApplications'];
                        }
                        else
                        {
                            // Default
                            this.m_filteredApplicationsAsBlacklistInput.checked = true;
                            this.m_filteredApplicationsAsWhitelistInput.checked = false;
                        }
/*
                        let savedFilteredAppsList: Array<String>;

                        if (savedFilteredAppsList != null)
                        {
                            savedFilteredAppsList.forEach((line: String): void =>
                            {
                                line = line.trim();
                                if(line.length > 0)
                                {
                                    this.m_filteredApplicationsList.value += line + "\n";
                                }
                            });
                        }*/
                    }
                    break;
            }

            

            this.m_mainForm.onsubmit = ((e: Event): any =>
            {
                let validateOpts = this.ValidationOptions;
                let validresult = $(this.m_mainForm).validate(validateOpts).form();

                let validator = $(this.m_mainForm).validate(validateOpts);

                if (validator.valid())
                {
                    validator.resetForm();

                    this.ClearFormErrorMessages();

                    return this.OnFormSubmitClicked(e, data == null);
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
                    'id': this.m_groupId,
                    'name': this.m_groupName,
                    'isactive': this.m_isActive,
                    'assigned_filter_ids': this.m_assignedFilterIds,
                    'app_cfg': JSON.stringify(this.m_appConfig),
                    'assigned_app_groups': this.m_right_groups
                };
            return obj;
        }

        public onMoveRightAllClicked(e: MouseEvent): void {
            this.m_left_groups.forEach((group_id): void =>
            {
                this.m_right_groups.push(group_id);
            });
            this.m_left_groups = [];
            this.draw_left_groups();
            this.draw_right_groups();
            this.draw_selected_applications();
        }

        public onMoveLeftAllClicked(e: MouseEvent): void {
            this.m_right_groups.forEach((group_id): void =>
            {
                this.m_left_groups.push(group_id);
            });
            this.m_right_groups = [];
            this.draw_left_groups();
            this.draw_right_groups();
            this.draw_selected_applications();
        }

        public onMoveRightClicked(e: MouseEvent): void {
            if(this.m_appGroupSourceList.selectedIndex == -1) return;
            for (var i = 0; i < this.m_appGroupSourceList.selectedOptions.length; i++) {
                let sel_id = parseInt(this.m_appGroupSourceList.selectedOptions[i].value);                
                let sel_seq_idx = this.m_left_groups.indexOf(sel_id);
                this.m_left_groups.splice(sel_seq_idx,1);
                this.m_right_groups.push(sel_id);
            }
            this.draw_left_groups();
            this.draw_right_groups();
            this.draw_selected_applications();
        }

        public onMoveLeftClicked(e: MouseEvent): void {
            if(this.m_appGroupTargetList.selectedIndex == -1) return;
            for (var i = 0; i < this.m_appGroupTargetList.selectedOptions.length; i++) {                
                let sel_id = parseInt(this.m_appGroupTargetList.selectedOptions[i].value);
                let sel_seq_idx = this.m_right_groups.indexOf(sel_id);
                this.m_right_groups.splice(sel_seq_idx,1);
                this.m_left_groups.push(sel_id);
            }
            this.draw_left_groups();
            this.draw_right_groups();
            this.draw_selected_applications();
        }
    }

}
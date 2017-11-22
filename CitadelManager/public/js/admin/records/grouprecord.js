var __extends = (this && this.__extends) || (function () {
    var extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var Citadel;
(function (Citadel) {
    var GroupRecord = (function (_super) {
        __extends(GroupRecord, _super);
        function GroupRecord() {
            var _this = _super.call(this) || this;
            _this.ConstructFormReferences();
            _this.ConstructFilterAssignmentArea();
            return _this;
        }
        Object.defineProperty(GroupRecord.prototype, "RecordRoute", {
            get: function () {
                return 'api/admin/groups';
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(GroupRecord.prototype, "ValidationOptions", {
            get: function () {
                var validationRules = {};
                validationRules[this.m_groupNameInput.id] = {
                    required: true,
                    minlength: 1
                };
                var validationErrorMessages = {};
                validationErrorMessages[this.m_groupNameInput.id] = 'A valid group name is required.';
                var validationOptions = {
                    rules: validationRules,
                    errorPlacement: (function (error, element) {
                        error.appendTo('#group_form_errors');
                        $('#group_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages,
                    ignore: ''
                };
                return validationOptions;
            },
            enumerable: true,
            configurable: true
        });
        GroupRecord.prototype.ConstructFormReferences = function () {
            this.m_mainForm = document.querySelector('#editor_group_form');
            this.m_editorTitle = document.querySelector('#group_editing_title');
            this.m_editorOverlay = document.querySelector('#overlay_group_editor');
            this.m_groupNameInput = document.querySelector('#editor_group_input_groupname');
            this.m_groupUpdateCheckFrequencyInput = document.querySelector('#editor_cfg_update_frequency_input');
            this.m_groupPrimaryDnsInput = document.querySelector('#editor_cfg_primary_dns_input');
            this.m_groupSecondaryDnsInput = document.querySelector('#editor_cfg_secondary_dns_input');
            var ipv4andv6OnlyFilter = function (e) {
                var inputBox = e.target;
                inputBox.value = inputBox.value.replace(/[^0-9\\.:]/g, '');
            };
            this.m_groupPrimaryDnsInput.onkeyup = ipv4andv6OnlyFilter;
            this.m_groupSecondaryDnsInput.onkeyup = ipv4andv6OnlyFilter;
            this.m_isActiveInput = document.querySelector('#editor_group_input_isactive');
            this.m_antiTamperNoTerminateInput = document.querySelector('#editor_cfg_no_terminate_input');
            this.m_antiTamperDisableInternetInput = document.querySelector('#editor_cfg_disable_internet_input');
            this.m_antiTamperUseThresholdInput = document.querySelector('#editor_cfg_use_threshold_input');
            this.m_antiTamperThresholdCountInput = document.querySelector('#editor_cfg_threshold_count_input');
            this.m_antiTamperThresholdTimeoutInput = document.querySelector('#editor_cfg_threshold_period_input');
            this.m_antiTamperThresholdTriggerPeriodInput = document.querySelector('#editor_cfg_threshold_timeout_input');
            this.m_antiTamperBypassesPerDayInput = document.querySelector('#editor_cfg_bypasses_allowed_input');
            this.m_antiTamperBypassDurationInput = document.querySelector('#editor_cfg_bypass_duration_input');
            this.m_groupNlpThresholdInput = document.querySelector('#editor_cfg_nlp_threshold_input');
            this.m_textTriggerMaxSizeInput = document.querySelector('#editor_cfg_trigger_max_size_input');
            this.m_updateChannelSelectInput = document.querySelector('#editor_cfg_update_channel_input');
            this.m_groupNlpThresholdInput.onkeyup = function (e) {
                var inputBox = e.target;
                var value = inputBox.valueAsNumber;
                if (value > 1.0) {
                    inputBox.valueAsNumber = 1.0;
                }
                if (value < 0) {
                    inputBox.valueAsNumber = 0;
                }
            };
            this.m_filteredApplicationsList = document.querySelector('#group_filtered_applications');
            this.m_filteredApplicationsAsBlacklistInput = document.querySelector('#group_filteredapps_radio_blacklist');
            this.m_filteredApplicationsAsWhitelistInput = document.querySelector('#group_filteredapps_radio_whitelist');
            this.m_submitBtn = document.querySelector('#group_editor_submit');
            this.m_cancelBtn = document.querySelector('#group_editor_cancel');
            this.InitButtonHandlers();
        };
        GroupRecord.prototype.ConstructFilterAssignmentArea = function () {
            var _this = this;
            this.m_blacklistFiltersSearchInput = document.querySelector('#group_blacklist_filters_search_input');
            this.m_blacklistFiltersSearchPlaceholder = document.querySelector('#group_blacklist_filters_search_input_placeholder');
            this.m_whitelistFiltersSearchInput = document.querySelector('#group_whitelist_filters_search_input');
            this.m_whitelistFiltersSearchPlaceholder = document.querySelector('#group_whitelist_filters_search_input_placeholder');
            this.m_bypassFiltersSearchInput = document.querySelector('#group_bypass_filters_search_input');
            this.m_bypassFiltersSearchPlaceholder = document.querySelector('#group_bypass_filters_search_input_placeholder');
            this.m_unassignedFiltersSearchPlaceholder = document.querySelector('#group_unassigned_filters_search_input_placeholder');
            this.m_unassignedFiltersSearchInput = document.querySelector('#group_unassigned_filters_search_input');
            this.m_blacklistFiltersSearchInput.onkeyup = (function (e) {
                _this.OnSearchFieldInput(_this.m_blacklistFiltersSearchInput, _this.m_blacklistFiltersContainer, e);
            });
            this.m_whitelistFiltersSearchInput.onkeyup = (function (e) {
                _this.OnSearchFieldInput(_this.m_whitelistFiltersSearchInput, _this.m_whitelistFiltersContainer, e);
            });
            this.m_bypassFiltersSearchInput.onkeyup = (function (e) {
                _this.OnSearchFieldInput(_this.m_bypassFiltersSearchInput, _this.m_bypassFiltersContainer, e);
            });
            this.m_unassignedFiltersSearchInput.onkeyup = (function (e) {
                _this.OnSearchFieldInput(_this.m_unassignedFiltersSearchInput, _this.m_unassignedFiltersContainer, e);
            });
            var setupSearchBoxFocus = (function (searchBox, placeholder) {
                searchBox.onfocus = (function (e) {
                    searchBox.style.color = "white";
                    if (searchBox.value.length > 0) {
                        placeholder.textContent = searchBox.value;
                    }
                    else {
                        placeholder.textContent = "Type To Filter Selection";
                    }
                });
                searchBox.onblur = (function (e) {
                    searchBox.style.color = "transparent";
                    if (searchBox.value.length > 0) {
                        placeholder.textContent = searchBox.value;
                    }
                    else {
                        placeholder.textContent = "Type To Filter Selection";
                    }
                });
            });
            setupSearchBoxFocus(this.m_blacklistFiltersSearchInput, this.m_blacklistFiltersSearchPlaceholder);
            setupSearchBoxFocus(this.m_whitelistFiltersSearchInput, this.m_whitelistFiltersSearchPlaceholder);
            setupSearchBoxFocus(this.m_bypassFiltersSearchInput, this.m_bypassFiltersSearchPlaceholder);
            setupSearchBoxFocus(this.m_unassignedFiltersSearchInput, this.m_unassignedFiltersSearchPlaceholder);
            this.m_blacklistFiltersContainer = document.querySelector('#group_blacklist_filters');
            this.m_whitelistFiltersContainer = document.querySelector('#group_whitelist_filters');
            this.m_bypassFiltersContainer = document.querySelector('#group_bypass_filters');
            this.m_unassignedFiltersContainer = document.querySelector('#group_unassigned_filters');
        };
        GroupRecord.prototype.ResetSearchBoxes = function () {
            this.m_blacklistFiltersSearchInput.value = '';
            this.m_whitelistFiltersSearchInput.value = '';
            this.m_bypassFiltersSearchInput.value = '';
            this.m_unassignedFiltersSearchInput.value = '';
            this.m_blacklistFiltersSearchPlaceholder.textContent = 'Type To Filter Selection';
            this.m_whitelistFiltersSearchPlaceholder.textContent = 'Type To Filter Selection';
            this.m_bypassFiltersSearchPlaceholder.textContent = 'Type To Filter Selection';
            this.m_unassignedFiltersSearchPlaceholder.textContent = 'Type To Filter Selection';
        };
        GroupRecord.prototype.OnSearchFieldInput = function (input, searchTarget, e) {
            var currentTextValue = input.value.toLowerCase();
            var childrenToSearch = searchTarget.querySelectorAll('div[citadel-filter-list-id]');
            for (var i = 0; i < childrenToSearch.length; ++i) {
                if (currentTextValue.length <= 0) {
                    $(childrenToSearch.item(i)).show();
                    continue;
                }
                var childText = childrenToSearch.item(i).textContent.toLowerCase();
                if (!~childText.indexOf(currentTextValue)) {
                    $(childrenToSearch.item(i)).hide();
                }
                else {
                    $(childrenToSearch.item(i)).show();
                }
            }
        };
        GroupRecord.prototype.InitButtonHandlers = function () {
            var _this = this;
            this.m_cancelBtn.onclick = (function (e) {
                _this.StopEditing();
            });
            this.m_submitBtn.onclick = (function (e) {
                console.log("submitting");
                if (_this.m_mainForm.onsubmit != null) {
                    _this.m_mainForm.onsubmit(new Event("submit"));
                }
            });
        };
        GroupRecord.prototype.LoadFromObject = function (data) {
            this.m_groupId = data['id'];
            this.m_groupName = data['name'];
            this.m_isActive = data['isactive'];
            this.m_assignedFilterIds = data['assigned_filter_ids'];
            this.m_appConfig = JSON.parse(data['app_cfg']);
            console.log('json');
            console.log(JSON.parse(data['app_cfg']));
        };
        GroupRecord.prototype.LoadFromForm = function () {
            this.m_groupName = this.m_groupNameInput.value;
            this.m_isActive = this.m_isActiveInput.checked == true ? 1 : 0;
            var allAssignedFilters = new Array();
            var collectSelected = (function (container, blacklist, whitelist, bypass) {
                var assignedLists = container.querySelectorAll('div[citadel-filter-list-id]');
                for (var i = 0; i < assignedLists.length; ++i) {
                    var selectedBlacklist = {
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
            var whitelistedApplications = new Array();
            var blacklistedApplications = new Array();
            var allFilteredAppLines = this.m_filteredApplicationsList.value.trim().split('\n');
            if (allFilteredAppLines != null) {
                var uniqueFilteredApps_1 = {};
                var distinctFilteredApps_1 = [];
                allFilteredAppLines.forEach(function (value) {
                    if (typeof (uniqueFilteredApps_1[value]) == "undefined") {
                        distinctFilteredApps_1.push(value);
                    }
                    uniqueFilteredApps_1[value] = 0;
                });
                allFilteredAppLines = distinctFilteredApps_1;
            }
            var filterAppsKey = 'BlacklistedApplications';
            if (!this.m_filteredApplicationsAsBlacklistInput.checked) {
                filterAppsKey = 'WhitelistedApplications';
            }
            var appConfig = {
                'UpdateFrequency': this.m_groupUpdateCheckFrequencyInput.valueAsNumber,
                'PrimaryDns': this.m_groupPrimaryDnsInput.value,
                'SecondaryDns': this.m_groupSecondaryDnsInput.value,
                'CannotTerminate': this.m_antiTamperNoTerminateInput.checked,
                'BlockInternet': this.m_antiTamperDisableInternetInput.checked,
                'UseThreshold': this.m_antiTamperUseThresholdInput.checked,
                'ThresholdLimit': this.m_antiTamperThresholdCountInput.valueAsNumber,
                'ThresholdTriggerPeriod': this.m_antiTamperThresholdTriggerPeriodInput.valueAsNumber,
                'ThresholdTimeoutPeriod': this.m_antiTamperThresholdTimeoutInput.valueAsNumber,
                'BypassesPermitted': this.m_antiTamperBypassesPerDayInput.valueAsNumber,
                'BypassDuration': this.m_antiTamperBypassDurationInput.valueAsNumber,
                'NlpThreshold': this.m_groupNlpThresholdInput.valueAsNumber,
                'MaxTextTriggerScanningSize': this.m_textTriggerMaxSizeInput.valueAsNumber,
                'UpdateChannel': this.m_updateChannelSelectInput.options[this.m_updateChannelSelectInput.selectedIndex].value,
            };
            appConfig[filterAppsKey] = allFilteredAppLines;
            this.m_appConfig = appConfig;
        };
        GroupRecord.prototype.ClearFormErrorMessages = function () {
            var errElms = document.querySelectorAll('#group_form_errors > *');
            for (var i = 0; i < errElms.length; ++i) {
                var elm = errElms.item(i);
                elm.parentNode.removeChild(elm);
            }
        };
        GroupRecord.prototype.StartEditing = function (allFilters, data, cloneData) {
            var _this = this;
            if (data === void 0) { data = null; }
            if (cloneData === void 0) { cloneData = null; }
            var clearListContainer = (function (container) {
                var assignedChildren = container.querySelectorAll('div[citadel-filter-list-id]');
                for (var i = 0; i < assignedChildren.length; ++i) {
                    container.removeChild(assignedChildren.item(i));
                }
            });
            clearListContainer(this.m_blacklistFiltersContainer);
            clearListContainer(this.m_whitelistFiltersContainer);
            clearListContainer(this.m_bypassFiltersContainer);
            clearListContainer(this.m_unassignedFiltersContainer);
            this.ResetSearchBoxes();
            this.ClearFormErrorMessages();
            this.m_mainForm.reset();
            this.m_filteredApplicationsList.value = "";
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
            $('ul.tabs > li').removeClass('active');
            $('ul.tabs > li').first().addClass('active');
            $('div.frames > div.frame').hide();
            $('div.frames > div.frame').first().show();
            var allAssignedFilters = new Array();
            if (data != null && data.hasOwnProperty('assigned_filter_ids')) {
                allAssignedFilters = data['assigned_filter_ids'];
                console.log(allAssignedFilters);
            }
            else {
                if (cloneData != null && cloneData.hasOwnProperty('assigned_filter_ids')) {
                    allAssignedFilters = cloneData['assigned_filter_ids'];
                }
            }
            allFilters.each(function (elm) {
                var imageClassName = "";
                var draggableFilterOption = document.createElement('div');
                draggableFilterOption.setAttribute('citadel-filter-list-id', elm['id']);
                draggableFilterOption.style.setProperty('font-size', '10');
                draggableFilterOption.style.setProperty('display', 'inline-block');
                draggableFilterOption.style.setProperty('text-align', 'center');
                if ((elm['type']).toUpperCase().indexOf("TRIG") != -1) {
                    draggableFilterOption.style.setProperty('background', 'crimson');
                    draggableFilterOption.setAttribute('citadel-filter-list-type', 'trigger');
                    imageClassName = "mif-warning";
                }
                else if ((elm['type']).toUpperCase().indexOf("NLP") != -1) {
                    draggableFilterOption.style.setProperty('background', '#f0a30a');
                    draggableFilterOption.style.setProperty('color', 'white');
                    draggableFilterOption.setAttribute('citadel-filter-list-type', 'nlp');
                    imageClassName = "mif-language";
                }
                else {
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
                var alreadyHasThisFilterList = false;
                var existingItem = null;
                for (var j = 0; j < allAssignedFilters.length; ++j) {
                    if (allAssignedFilters[j]['filter_list_id'] == (elm['id'])) {
                        alreadyHasThisFilterList = true;
                        existingItem = allAssignedFilters[j];
                        break;
                    }
                }
                if (alreadyHasThisFilterList && existingItem != null) {
                    if (existingItem['as_blacklist'] == true) {
                        _this.m_blacklistFiltersContainer.appendChild(draggableFilterOption);
                    }
                    else if (existingItem['as_whitelist'] == true) {
                        _this.m_whitelistFiltersContainer.appendChild(draggableFilterOption);
                    }
                    else if (existingItem['as_bypass'] == true) {
                        _this.m_bypassFiltersContainer.appendChild(draggableFilterOption);
                    }
                }
                else {
                    _this.m_unassignedFiltersContainer.appendChild(draggableFilterOption);
                }
            });
            switch (data == null) {
                case true:
                    {
                        this.m_editorTitle.innerText = "Create New Group";
                        this.m_submitBtn.innerText = "Create Group";
                        this.m_isActiveInput.checked = true;
                    }
                    break;
                case false:
                    {
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
                        try {
                            for (var i = 0; i < this.m_updateChannelSelectInput.options.length; ++i) {
                                if (this.m_updateChannelSelectInput.options[i].value.toLowerCase() == this.m_appConfig['UpdateChannel'].toLowerCase()) {
                                    this.m_updateChannelSelectInput.selectedIndex = this.m_updateChannelSelectInput.options[i].index;
                                    break;
                                }
                            }
                        }
                        catch (ex) {
                            console.warn(ex);
                            console.warn("Either the update channel is null or it's an invalid value. Defaulting...");
                            this.m_updateChannelSelectInput.selectedIndex = 0;
                        }
                        this.m_groupUpdateCheckFrequencyInput.valueAsNumber = parseInt(this.m_appConfig['UpdateFrequency']);
                        this.m_groupPrimaryDnsInput.value = this.m_appConfig['PrimaryDns'];
                        this.m_groupSecondaryDnsInput.value = this.m_appConfig['SecondaryDns'];
                        if (this.m_groupPrimaryDnsInput.value == 'undefined') {
                            this.m_groupPrimaryDnsInput.value = '';
                        }
                        if (this.m_groupSecondaryDnsInput.value == 'undefined') {
                            this.m_groupSecondaryDnsInput.value = '';
                        }
                        var savedFilteredAppsList = void 0;
                        if ('BlacklistedApplications' in this.m_appConfig) {
                            this.m_filteredApplicationsAsBlacklistInput.checked = true;
                            this.m_filteredApplicationsAsWhitelistInput.checked = false;
                            savedFilteredAppsList = this.m_appConfig['BlacklistedApplications'];
                        }
                        else if ('WhitelistedApplications' in this.m_appConfig) {
                            this.m_filteredApplicationsAsBlacklistInput.checked = false;
                            this.m_filteredApplicationsAsWhitelistInput.checked = true;
                            savedFilteredAppsList = this.m_appConfig['WhitelistedApplications'];
                        }
                        else {
                            this.m_filteredApplicationsAsBlacklistInput.checked = true;
                            this.m_filteredApplicationsAsWhitelistInput.checked = false;
                        }
                        if (savedFilteredAppsList != null) {
                            savedFilteredAppsList.forEach(function (line) {
                                line = line.trim();
                                if (line.length > 0) {
                                    _this.m_filteredApplicationsList.value += line + "\n";
                                }
                            });
                        }
                    }
                    break;
            }
            if (cloneData != null) {
                this.m_editorTitle.innerText = "Clone Group";
                this.m_submitBtn.innerText = "Clone Group";
                this.LoadFromObject(cloneData);
                this.m_groupId = undefined;
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
                try {
                    for (var i = 0; i < this.m_updateChannelSelectInput.options.length; ++i) {
                        if (this.m_updateChannelSelectInput.options[i].value.toLowerCase() == this.m_appConfig['UpdateChannel'].toLowerCase()) {
                            this.m_updateChannelSelectInput.selectedIndex = this.m_updateChannelSelectInput.options[i].index;
                            break;
                        }
                    }
                }
                catch (ex) {
                    console.warn(ex);
                    console.warn("Either the update channel is null or it's an invalid value. Defaulting...");
                    this.m_updateChannelSelectInput.selectedIndex = 0;
                }
                this.m_groupUpdateCheckFrequencyInput.valueAsNumber = parseInt(this.m_appConfig['UpdateFrequency']);
                this.m_groupPrimaryDnsInput.value = this.m_appConfig['PrimaryDns'];
                this.m_groupSecondaryDnsInput.value = this.m_appConfig['SecondaryDns'];
                if (this.m_groupPrimaryDnsInput.value == 'undefined') {
                    this.m_groupPrimaryDnsInput.value = '';
                }
                if (this.m_groupSecondaryDnsInput.value == 'undefined') {
                    this.m_groupSecondaryDnsInput.value = '';
                }
                var savedFilteredAppsList = void 0;
                if ('BlacklistedApplications' in this.m_appConfig) {
                    this.m_filteredApplicationsAsBlacklistInput.checked = true;
                    this.m_filteredApplicationsAsWhitelistInput.checked = false;
                    savedFilteredAppsList = this.m_appConfig['BlacklistedApplications'];
                }
                else if ('WhitelistedApplications' in this.m_appConfig) {
                    this.m_filteredApplicationsAsBlacklistInput.checked = false;
                    this.m_filteredApplicationsAsWhitelistInput.checked = true;
                    savedFilteredAppsList = this.m_appConfig['WhitelistedApplications'];
                }
                else {
                    this.m_filteredApplicationsAsBlacklistInput.checked = true;
                    this.m_filteredApplicationsAsWhitelistInput.checked = false;
                }
                if (savedFilteredAppsList != null) {
                    savedFilteredAppsList.forEach(function (line) {
                        line = line.trim();
                        if (line.length > 0) {
                            _this.m_filteredApplicationsList.value += line + "\n";
                        }
                    });
                }
            }
            this.m_mainForm.onsubmit = (function (e) {
                var validateOpts = _this.ValidationOptions;
                var validresult = $(_this.m_mainForm).validate(validateOpts).form();
                var validator = $(_this.m_mainForm).validate(validateOpts);
                if (validator.valid()) {
                    validator.resetForm();
                    _this.ClearFormErrorMessages();
                    return _this.OnFormSubmitClicked(e, data == null);
                }
                return false;
            });
            $(this.m_editorOverlay).fadeIn(250);
        };
        GroupRecord.prototype.StopEditing = function () {
            $(this.m_editorOverlay).fadeOut(200);
        };
        GroupRecord.prototype.ToObject = function () {
            var obj = {
                'id': this.m_groupId,
                'name': this.m_groupName,
                'isactive': this.m_isActive,
                'assigned_filter_ids': this.m_assignedFilterIds,
                'app_cfg': JSON.stringify(this.m_appConfig)
            };
            return obj;
        };
        return GroupRecord;
    }(Citadel.BaseRecord));
    Citadel.GroupRecord = GroupRecord;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=grouprecord.js.map
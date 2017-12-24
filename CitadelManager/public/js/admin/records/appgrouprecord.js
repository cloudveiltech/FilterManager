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
    var AppGroupRecord = (function (_super) {
        __extends(AppGroupRecord, _super);
        function AppGroupRecord() {
            var _this = _super.call(this) || this;
            _this.ConstructFormReferences();
            return _this;
        }
        Object.defineProperty(AppGroupRecord.prototype, "RecordRoute", {
            get: function () {
                return 'api/admin/app_group';
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(AppGroupRecord.prototype, "ValidationOptions", {
            get: function () {
                var validationRules = {};
                validationRules[this.m_groupNameInput.id] = {
                    required: true
                };
                var validationErrorMessages = {};
                validationErrorMessages[this.m_groupNameInput.id] = 'App group name is required.';
                var validationOptions = {
                    rules: validationRules,
                    errorPlacement: (function (error, element) {
                        error.appendTo('#appgroup_form_errors');
                        $('#appgroup_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };
                return validationOptions;
            },
            enumerable: true,
            configurable: true
        });
        AppGroupRecord.prototype.ConstructFormReferences = function () {
            this.m_mainForm = document.querySelector('#editor_appgroup_form');
            this.m_editorTitle = document.querySelector('#appgroup_editing_title');
            this.m_editorOverlay = document.querySelector('#overlay_appgroup_editor');
            this.m_groupNameInput = document.querySelector('#editor_appgroup_name');
            this.m_sourceAppList = document.querySelector('#app_source_list');
            this.m_targetAppList = document.querySelector('#app_target_list');
            this.m_appsSourceToTargetBtn = document.querySelector('#apps_source_to_target');
            this.m_appSourceToTargetBtn = document.querySelector('#app_source_to_target');
            this.m_appTargetToSourceBtn = document.querySelector('#app_target_to_source');
            this.m_appsTargetToSourceBtn = document.querySelector('#apps_target_to_source');
            this.m_submitBtn = document.querySelector('#appgroup_editor_submit');
            this.m_cancelBtn = document.querySelector('#appgroup_editor_cancel');
            this.m_submitBtn.disabled = true;
            this.m_arrLeftApplications = [];
            this.m_arrRightApplications = [];
            this.m_groupApp = {};
            $(this.m_sourceAppList).empty();
            $(this.m_targetAppList).empty();
            $('#spiner_1').hide();
            this.InitButtonHandlers();
            this.getRetrieveApplications();
        };
        AppGroupRecord.prototype.getRetrieveApplications = function () {
            var _this = this;
            $('#spiner_1').show();
            var ajaxSettings = {
                method: "GET",
                timeout: 60000,
                url: "api/admin/applications",
                data: {},
                success: function (data, textStatus, jqXHR) {
                    $('#spiner_1').hide();
                    _this.m_arrLeftApplications = data;
                    if (_this.m_appgroupId > 0) {
                        _this.m_groupApp.forEach(function (app) {
                            var idx = -1;
                            var sel_seq_idx = 0;
                            _this.m_arrLeftApplications.forEach(function (item) {
                                idx++;
                                if (item.id == app.app_id) {
                                    sel_seq_idx = idx;
                                    _this.m_arrRightApplications.push(item);
                                }
                            });
                            if (sel_seq_idx >= 0) {
                                _this.m_arrLeftApplications.splice(sel_seq_idx, 1);
                            }
                        });
                        _this.drawRightApplications();
                    }
                    _this.drawLeftApplications();
                    _this.m_progressWait.Hide();
                    _this.m_submitBtn.disabled = false;
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#spiner_1').hide();
                    console.log(jqXHR.responseText);
                    console.log(errorThrown);
                    console.log(textStatus);
                    _this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(function () {
                        _this.m_progressWait.Hide();
                    }, 5000);
                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                    }
                    else {
                    }
                }
            };
            $.get(ajaxSettings);
        };
        AppGroupRecord.prototype.drawLeftApplications = function () {
            var _this = this;
            $(this.m_sourceAppList).empty();
            this.m_arrLeftApplications.forEach(function (item) {
                var newOption = document.createElement("option");
                newOption.text = item.name;
                newOption.value = item.id;
                _this.m_sourceAppList.add(newOption);
            });
        };
        AppGroupRecord.prototype.drawRightApplications = function () {
            var _this = this;
            $(this.m_targetAppList).empty();
            this.m_arrRightApplications.forEach(function (item) {
                var newOption = document.createElement("option");
                newOption.text = item.name;
                newOption.value = item.id;
                _this.m_targetAppList.add(newOption);
            });
        };
        AppGroupRecord.prototype.onMoveRightAllClicked = function (e) {
            var _this = this;
            this.m_arrLeftApplications.forEach(function (item) {
                _this.m_arrRightApplications.push(item);
            });
            this.m_arrLeftApplications = [];
            this.drawLeftApplications();
            this.drawRightApplications();
        };
        AppGroupRecord.prototype.onMoveLeftAllClicked = function (e) {
            var _this = this;
            this.m_arrRightApplications.forEach(function (item) {
                _this.m_arrLeftApplications.push(item);
            });
            this.m_arrRightApplications = [];
            this.drawLeftApplications();
            this.drawRightApplications();
        };
        AppGroupRecord.prototype.onMoveRightClicked = function (e) {
            var _this = this;
            if (this.m_sourceAppList.selectedIndex == -1)
                return;
            var _loop_1 = function () {
                var sel_id = parseInt(this_1.m_sourceAppList.selectedOptions[i].value);
                var idx = -1;
                var sel_seq_idx = 0;
                this_1.m_arrLeftApplications.forEach(function (item) {
                    idx++;
                    if (item.id == sel_id) {
                        _this.m_arrRightApplications.push(item);
                        sel_seq_idx = idx;
                        return;
                    }
                });
                if (sel_seq_idx > -1) {
                    this_1.m_arrLeftApplications.splice(sel_seq_idx, 1);
                }
            };
            var this_1 = this;
            for (var i = 0; i < this.m_sourceAppList.selectedOptions.length; i++) {
                _loop_1();
            }
            this.drawLeftApplications();
            this.drawRightApplications();
        };
        AppGroupRecord.prototype.onMoveLeftClicked = function (e) {
            var _this = this;
            if (this.m_targetAppList.selectedIndex == -1)
                return;
            var _loop_2 = function () {
                var sel_opt = this_2.m_targetAppList.selectedOptions[i];
                var sel_id = parseInt(sel_opt.value);
                var idx = -1;
                var find_id_to_remove = -1;
                this_2.m_arrRightApplications.forEach(function (item) {
                    idx++;
                    if (item.id == sel_id) {
                        find_id_to_remove = idx;
                        _this.m_arrLeftApplications.push(item);
                        return;
                    }
                });
                if (find_id_to_remove > -1) {
                    this_2.m_arrRightApplications.splice(find_id_to_remove, 1);
                }
            };
            var this_2 = this;
            for (var i = 0; i < this.m_targetAppList.selectedOptions.length; i++) {
                _loop_2();
            }
            this.drawLeftApplications();
            this.drawRightApplications();
        };
        AppGroupRecord.prototype.InitButtonHandlers = function () {
            var _this = this;
            this.m_cancelBtn.onclick = (function (e) {
                _this.StopEditing();
            });
            this.m_appsSourceToTargetBtn.onclick = (function (e) {
                _this.onMoveRightAllClicked(e);
            });
            this.m_appSourceToTargetBtn.onclick = (function (e) {
                _this.onMoveRightClicked(e);
            });
            this.m_appTargetToSourceBtn.onclick = (function (e) {
                _this.onMoveLeftClicked(e);
            });
            this.m_appsTargetToSourceBtn.onclick = (function (e) {
                _this.onMoveLeftAllClicked(e);
            });
        };
        AppGroupRecord.prototype.LoadFromObject = function (data) {
            this.m_appgroupId = data['id'];
            this.m_appGroupName = data['group_name'];
            this.m_groupApp = data['group_app'];
            this.m_dateRegistered = data['dt'];
        };
        AppGroupRecord.prototype.LoadFromForm = function () {
            this.m_appGroupName = this.m_groupNameInput.value;
        };
        AppGroupRecord.prototype.StartEditing = function (userData) {
            var _this = this;
            if (userData === void 0) { userData = null; }
            switch (userData == null) {
                case true:
                    {
                        this.m_editorTitle.innerText = "Add Application Group";
                        this.m_submitBtn.innerText = "Add";
                        this.m_mainForm.reset();
                    }
                    break;
                case false:
                    {
                        this.LoadFromObject(userData);
                        this.m_editorTitle.innerText = "Edit Application Group";
                        this.m_submitBtn.innerText = "Save";
                        this.m_groupNameInput.value = this.m_appGroupName;
                    }
                    break;
            }
            this.m_mainForm.onsubmit = (function (e) {
                var validateOpts = _this.ValidationOptions;
                var validresult = $(_this.m_mainForm).validate(validateOpts).form();
                if ($(_this.m_mainForm).validate(validateOpts).valid()) {
                    return _this.OnFormSubmitClicked(e, userData == null);
                }
                return false;
            });
            $(this.m_editorOverlay).fadeIn(250);
        };
        AppGroupRecord.prototype.StopEditing = function () {
            $(this.m_editorOverlay).fadeOut(200);
        };
        AppGroupRecord.prototype.ToObject = function () {
            var obj = {
                'id': this.m_appgroupId,
                'group_name': this.m_appGroupName,
                'apps': this.getSelectedAppIds(),
                'dt': this.m_dateRegistered
            };
            return obj;
        };
        AppGroupRecord.prototype.getSelectedAppIds = function () {
            var str = '';
            this.m_arrRightApplications.forEach(function (item) {
                if (str.length == 0) {
                    str = item.id;
                }
                else {
                    str += "," + item.id;
                }
            });
            return str;
        };
        return AppGroupRecord;
    }(Citadel.BaseRecord));
    Citadel.AppGroupRecord = AppGroupRecord;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=appgrouprecord.js.map
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
    var AppRecord = (function (_super) {
        __extends(AppRecord, _super);
        function AppRecord() {
            var _this = _super.call(this) || this;
            _this.ConstructFormReferences();
            return _this;
        }
        Object.defineProperty(AppRecord.prototype, "RecordRoute", {
            get: function () {
                return 'api/admin/app';
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(AppRecord.prototype, "ValidationOptions", {
            get: function () {
                var validationRules = {};
                validationRules[this.m_applicationNameInput.id] = {
                    required: true
                };
                var validationErrorMessages = {};
                validationErrorMessages[this.m_applicationNameInput.id] = 'Application name is required.';
                var validationOptions = {
                    rules: validationRules,
                    errorPlacement: (function (error, element) {
                        error.appendTo('#app_form_errors');
                        $('#app_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };
                return validationOptions;
            },
            enumerable: true,
            configurable: true
        });
        AppRecord.prototype.ConstructFormReferences = function () {
            this.m_mainForm = document.querySelector('#editor_application_form');
            this.m_editorTitle = document.querySelector('#application_editing_title');
            this.m_editorOverlay = document.querySelector('#overlay_application_editor');
            this.m_applicationNameInput = document.querySelector('#editor_application_name');
            this.m_applicationNotesInput = document.querySelector('#editor_application_notes');
            this.m_leftSelect = document.querySelector("#editor_application_source_list");
            this.m_rightSelect = document.querySelector("#editor_application_target_list");
            this.m_btnMoveRightAll = document.querySelector("#editor_application_right_all_btn");
            this.m_btnMoveRight = document.querySelector("#editor_application_right_btn");
            this.m_btnMoveLeft = document.querySelector("#editor_application_left_btn");
            this.m_btnMoveLeftAll = document.querySelector("#editor_application_left_all_btn");
            this.m_submitBtn = document.querySelector('#application_editor_submit');
            this.m_cancelBtn = document.querySelector('#application_editor_cancel');
            this.InitButtonHandlers();
        };
        AppRecord.prototype.getRetrieveData = function (flag) {
            var _this = this;
            var url = 'api/admin/get_appgroup_data';
            if (flag) {
                url += '/' + this.m_appId;
            }
            $("#spiner_5").show();
            this.m_submitBtn.disabled = true;
            var ajaxSettings = {
                method: "GET",
                timeout: 60000,
                url: url,
                data: {},
                success: function (data, textStatus, jqXHR) {
                    _this.m_appGroups = data.app_groups;
                    if (flag) {
                        var selected_app_groups = data.selected_app_groups;
                        _this.m_unselectedGroups = [];
                        _this.m_selectedGroups = [];
                        if (selected_app_groups.length > 0) {
                            _this.m_appGroups.forEach(function (app_group) {
                                _this.m_unselectedGroups.push(app_group.id);
                            });
                            selected_app_groups.forEach(function (app_group) {
                                _this.m_selectedGroups.push(app_group.app_group_id);
                                var pos = _this.m_unselectedGroups.indexOf(app_group.app_group_id);
                                _this.m_unselectedGroups.splice(pos, 1);
                            });
                        }
                        else {
                            _this.m_appGroups.forEach(function (app_group) {
                                _this.m_unselectedGroups.push(app_group.id);
                            });
                        }
                    }
                    else {
                        _this.m_unselectedGroups = [];
                        _this.m_selectedGroups = [];
                        _this.m_appGroups.forEach(function (app_group) {
                            _this.m_unselectedGroups.push(app_group.id);
                        });
                    }
                    _this.drawLeftGroups();
                    _this.drawRightGroups();
                    $("#spiner_5").hide();
                    _this.m_submitBtn.disabled = false;
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    _this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(function () { _this.m_progressWait.Hide(); }, 5000);
                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                    }
                    else {
                    }
                }
            };
            $.get(ajaxSettings);
        };
        AppRecord.prototype.InitButtonHandlers = function () {
            var _this = this;
            this.m_btnMoveRightAll.onclick = (function (e) {
                _this.onMoveRightAllClicked(e);
            });
            this.m_btnMoveRight.onclick = (function (e) {
                _this.onMoveRightClicked(e);
            });
            this.m_btnMoveLeft.onclick = (function (e) {
                _this.onMoveLeftClicked(e);
            });
            this.m_btnMoveLeftAll.onclick = (function (e) {
                _this.onMoveLeftAllClicked(e);
            });
            this.m_cancelBtn.onclick = (function (e) {
                _this.StopEditing();
            });
        };
        AppRecord.prototype.onMoveRightAllClicked = function (e) {
            var _this = this;
            this.m_unselectedGroups.forEach(function (group_id) {
                _this.m_selectedGroups.push(group_id);
            });
            this.m_unselectedGroups = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        AppRecord.prototype.onMoveLeftAllClicked = function (e) {
            var _this = this;
            this.m_selectedGroups.forEach(function (group_id) {
                _this.m_unselectedGroups.push(group_id);
            });
            this.m_selectedGroups = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        AppRecord.prototype.onMoveRightClicked = function (e) {
            if (this.m_leftSelect.selectedIndex == -1)
                return;
            for (var i = 0; i < this.m_leftSelect.selectedOptions.length; i++) {
                var sel_id = parseInt(this.m_leftSelect.selectedOptions[i].value);
                var sel_seq_idx = this.m_unselectedGroups.indexOf(sel_id);
                this.m_unselectedGroups.splice(sel_seq_idx, 1);
                this.m_selectedGroups.push(sel_id);
            }
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        AppRecord.prototype.onMoveLeftClicked = function (e) {
            if (this.m_rightSelect.selectedIndex == -1)
                return;
            for (var i = 0; i < this.m_rightSelect.selectedOptions.length; i++) {
                var sel_id = parseInt(this.m_rightSelect.selectedOptions[i].value);
                var sel_seq_idx = this.m_selectedGroups.indexOf(sel_id);
                this.m_selectedGroups.splice(sel_seq_idx, 1);
                this.m_unselectedGroups.push(sel_id);
            }
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        AppRecord.prototype.getGroupItem = function (group_id) {
            var group_item = null;
            this.m_appGroups.forEach(function (item) {
                if (item.id == group_id) {
                    group_item = item;
                    return;
                }
            });
            return group_item;
        };
        AppRecord.prototype.drawLeftGroups = function () {
            var _this = this;
            $(this.m_leftSelect).empty();
            this.m_unselectedGroups.forEach(function (group_id) {
                var newOption = document.createElement("option");
                var item = _this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;
                _this.m_leftSelect.add(newOption);
            });
        };
        AppRecord.prototype.drawRightGroups = function () {
            var _this = this;
            $(this.m_rightSelect).empty();
            this.m_selectedGroups.forEach(function (group_id) {
                var newOption = document.createElement("option");
                var item = _this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;
                _this.m_rightSelect.add(newOption);
            });
        };
        AppRecord.prototype.LoadFromObject = function (data) {
            this.m_appId = data['id'];
            this.m_appName = data['name'];
            this.m_appNotes = data['notes'];
            this.m_dateRegistered = data['dt'];
        };
        AppRecord.prototype.LoadFromForm = function () {
            this.m_appName = this.m_applicationNameInput.value;
            this.m_appNotes = this.m_applicationNotesInput.value;
        };
        AppRecord.prototype.StartEditing = function (userData) {
            var _this = this;
            if (userData === void 0) { userData = null; }
            switch (userData == null) {
                case true:
                    {
                        this.m_editorTitle.innerText = "Add Application";
                        this.m_submitBtn.innerText = "Add";
                        this.m_mainForm.reset();
                        this.getRetrieveData(false);
                    }
                    break;
                case false:
                    {
                        this.LoadFromObject(userData);
                        this.m_editorTitle.innerText = "Edit Application";
                        this.m_submitBtn.innerText = "Save";
                        this.m_applicationNameInput.value = this.m_appName;
                        this.m_applicationNotesInput.value = this.m_appNotes;
                        this.getRetrieveData(true);
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
        AppRecord.prototype.StopEditing = function () {
            $(this.m_editorOverlay).fadeOut(200);
        };
        AppRecord.prototype.ToObject = function () {
            var obj = {
                'id': this.m_appId,
                'name': this.m_appName,
                'notes': this.m_appNotes,
                'dt': this.m_dateRegistered,
                'assigned_appgroup': this.m_selectedGroups
            };
            return obj;
        };
        return AppRecord;
    }(Citadel.BaseRecord));
    Citadel.AppRecord = AppRecord;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=apprecord.js.map
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
    var UserRecord = (function (_super) {
        __extends(UserRecord, _super);
        function UserRecord() {
            var _this = _super.call(this) || this;
            _this.ConstructFormReferences();
            return _this;
        }
        Object.defineProperty(UserRecord.prototype, "RecordRoute", {
            get: function () {
                return 'api/admin/users';
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(UserRecord.prototype, "ValidationOptions", {
            get: function () {
                var validationRules = {};
                validationRules[this.m_emailInput.id] = {
                    required: true,
                    email: true
                };
                validationRules[this.m_passwordInput.id] = {
                    required: true,
                    equalTo: '#' + this.m_passwordConfirmInput.id
                };
                validationRules[this.m_passwordConfirmInput.id] = {
                    required: true,
                    equalTo: '#' + this.m_passwordInput.id
                };
                validationRules[this.m_numActivationsInput.id] = {
                    required: true,
                    number: true
                };
                var validationErrorMessages = {};
                validationErrorMessages[this.m_emailInput.id] = 'A valid email address is required.';
                validationErrorMessages[this.m_passwordInput.id] = 'Password must be specified and match the password confirmation field.';
                validationErrorMessages[this.m_passwordConfirmInput.id] = 'Password confirmation must be specified and match the password field.';
                validationErrorMessages[this.m_numActivationsInput.id] = 'Total number of permitted activations must be specified.';
                var validationOptions = {
                    rules: validationRules,
                    errorPlacement: (function (error, element) {
                        error.appendTo('#user_form_errors');
                        $('#user_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };
                return validationOptions;
            },
            enumerable: true,
            configurable: true
        });
        UserRecord.prototype.ConstructFormReferences = function () {
            this.m_mainForm = document.querySelector('#editor_user_form');
            this.m_editorTitle = document.querySelector('#user_editing_title');
            this.m_editorOverlay = document.querySelector('#overlay_user_editor');
            this.m_emailInput = document.querySelector('#editor_user_input_username');
            this.m_fullNameInput = document.querySelector('#editor_user_input_user_full_name');
            this.m_passwordInput = document.querySelector('#editor_user_input_password');
            this.m_passwordConfirmInput = document.querySelector('#editor_user_input_password_confirm');
            this.m_numActivationsInput = document.querySelector('#editor_user_input_num_activations');
            this.m_groupIdInput = document.querySelector('#editor_user_input_group_id');
            this.m_roleInput = document.querySelector('#editor_user_input_role_id');
            this.m_isActiveInput = document.querySelector('#editor_user_input_isactive');
            this.m_customerIdInput = document.querySelector('#editor_user_input_customer_id');
            this.m_submitBtn = document.querySelector('#user_editor_submit');
            this.m_cancelBtn = document.querySelector('#user_editor_cancel');
            this.InitButtonHandlers();
        };
        UserRecord.prototype.InitUserActivationTables = function () {
            var that = this;
            var id = 0;
            if (this.m_userId === undefined) {
                id = 0;
            }
            else {
                id = this.m_userId;
            }
            var activationTableColumns = [
                {
                    title: 'Action Id',
                    data: 'id',
                    visible: false
                },
                {
                    title: 'Identifier',
                    data: 'identifier',
                    visible: true
                },
                {
                    title: 'Device Id',
                    data: 'device_id',
                    visible: true
                },
                {
                    title: 'IP Address',
                    data: 'ip_address',
                    visible: true
                },
                {
                    title: 'Updated date',
                    data: 'updated_at',
                    visible: true
                },
                {
                    "mRender": function (data, type, row) {
                        return "<button id='delete_" + row.id + "' class='btn-delete'>Delete</button> <button id='block_" + row.id + "' class='btn-block'>Block</button>";
                    }
                }
            ];
            var activationTablesLoadFromAjaxSettings = {
                url: "api/admin/user_activations/" + id,
                dataSrc: "",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                method: "GET",
                error: (function (jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                    }
                })
            };
            var activationTableSettings = {
                autoWidth: true,
                stateSave: true,
                columns: activationTableColumns,
                ajax: activationTablesLoadFromAjaxSettings,
                rowCallback: (function (row, data) {
                })
            };
            activationTableSettings['responsive'] = true;
            this.m_ActivationTables = $('#user_activation_table').DataTable(activationTableSettings);
            this.m_ActivationTables.on('click', 'button.btn-delete', function (e) {
                var _this = this;
                e.preventDefault();
                if (confirm("Are you want to delete this token?")) {
                    var dataObject = {};
                    var id_str = e.target.id;
                    var id_1 = id_str.split("_")[1];
                    var ajaxSettings = {
                        method: "POST",
                        timeout: 60000,
                        url: 'api/admin/user_activations/delete/' + id_1,
                        data: dataObject,
                        success: function (data, textStatus, jqXHR) {
                            that.m_ActivationTables.ajax.url("api/admin/user_activations/" + that.m_userId);
                            that.m_ActivationTables.ajax.reload();
                            return false;
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
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
                    $.post(ajaxSettings);
                }
            });
            this.m_ActivationTables.on('click', 'button.btn-block', function (e) {
                var _this = this;
                e.preventDefault();
                console.log("block-action");
                if (confirm("Are you want to block this token?")) {
                    var dataObject = {};
                    var id_str = e.target.id;
                    var id_2 = id_str.split("_")[1];
                    var ajaxSettings = {
                        method: "POST",
                        timeout: 60000,
                        url: 'api/admin/user_activations/block/' + id_2,
                        data: dataObject,
                        success: function (data, textStatus, jqXHR) {
                            that.m_ActivationTables.ajax.url("api/admin/user_activations/" + that.m_userId);
                            that.m_ActivationTables.ajax.reload();
                            return false;
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
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
                    $.post(ajaxSettings);
                }
            });
        };
        UserRecord.prototype.InitButtonHandlers = function () {
            var _this = this;
            this.m_cancelBtn.onclick = (function (e) {
                _this.StopEditing();
            });
        };
        UserRecord.prototype.LoadFromObject = function (data) {
            this.m_userId = data['id'];
            this.m_userFullName = data['name'];
            this.m_userEmail = data['email'];
            this.m_userPassword = data['password'];
            this.m_groupId = data['group_id'];
            this.m_customerId = data['customer_id'];
            this.m_roleId = data['roles'][0]['id'];
            this.m_numActivations = data['activations_allowed'];
            this.m_isActive = data['isactive'];
            this.m_dateRegistered = data['dt'];
        };
        UserRecord.prototype.LoadFromForm = function () {
            this.m_userFullName = this.m_fullNameInput.value;
            this.m_userEmail = this.m_emailInput.value;
            this.m_userPassword = this.m_passwordInput.value;
            this.m_groupId = -1;
            this.m_roleId = -1;
            if (this.m_groupIdInput.selectedIndex != -1) {
                var selectedGroupOption = this.m_groupIdInput.options[this.m_groupIdInput.selectedIndex];
                this.m_groupId = parseInt(selectedGroupOption.value);
            }
            if (this.m_roleInput.selectedIndex != -1) {
                var selectedRoleOption = this.m_roleInput.options[this.m_roleInput.selectedIndex];
                this.m_roleId = parseInt(selectedRoleOption.value);
            }
            if (this.m_customerIdInput.value == "") {
                this.m_customerId = null;
            }
            else {
                this.m_customerId = this.m_customerIdInput.valueAsNumber;
            }
            this.m_numActivations = this.m_numActivationsInput.valueAsNumber;
            this.m_isActive = this.m_isActiveInput.checked == true ? 1 : 0;
        };
        UserRecord.prototype.StartEditing = function (allGroups, userData) {
            var _this = this;
            if (userData === void 0) { userData = null; }
            if (this.m_groupIdInput.options != null) {
                this.m_groupIdInput.options.length = 0;
            }
            allGroups.each(function (elm) {
                var option = document.createElement('option');
                option.text = elm['name'];
                option.value = elm['id'];
                _this.m_groupIdInput.options.add(option);
            });
            switch (userData == null) {
                case true:
                    {
                        this.m_editorTitle.innerText = "Create New User";
                        this.m_submitBtn.innerText = "Create User";
                        this.m_mainForm.reset();
                        if (this.m_groupIdInput.options != null && this.m_groupIdInput.options.length > 0) {
                            this.m_groupIdInput.selectedIndex = 0;
                        }
                        else {
                            this.m_groupIdInput.selectedIndex = -1;
                        }
                        if (this.m_roleInput.options != null && this.m_roleInput.options.length > 0) {
                            this.m_roleInput.selectedIndex = 0;
                        }
                        else {
                            this.m_roleInput.selectedIndex = -1;
                        }
                    }
                    break;
                case false:
                    {
                        this.LoadFromObject(userData);
                        this.m_editorTitle.innerText = "Edit User";
                        this.m_submitBtn.innerText = "Save";
                        this.m_fullNameInput.value = this.m_userFullName;
                        this.m_emailInput.value = this.m_userEmail;
                        if (this.m_customerId == null) {
                            this.m_customerIdInput.value = "";
                        }
                        else {
                            this.m_customerIdInput.value = this.m_customerId.toString();
                        }
                        this.m_passwordInput.value = new Array(30).join("x");
                        this.m_passwordConfirmInput.value = new Array(30).join("x");
                        this.m_numActivationsInput.value = this.m_numActivations.toString();
                        if (this.m_groupId != -1) {
                            var optionInList = this.m_groupIdInput.querySelector('option[value="' + this.m_groupId.toString() + '"]');
                            if (optionInList != null) {
                                this.m_groupIdInput.selectedIndex = optionInList.index;
                            }
                        }
                        else {
                            if (this.m_groupIdInput.options != null && this.m_groupIdInput.options.length > 0) {
                                this.m_groupIdInput.selectedIndex = 0;
                            }
                            else {
                                this.m_groupIdInput.selectedIndex = -1;
                            }
                        }
                        if (this.m_roleId != -1) {
                            var optionInList = this.m_roleInput.querySelector('option[value="' + this.m_roleId.toString() + '"]');
                            if (optionInList != null) {
                                this.m_roleInput.selectedIndex = optionInList.index;
                            }
                        }
                        else {
                            if (this.m_roleInput.options != null && this.m_roleInput.options.length > 0) {
                                this.m_roleInput.selectedIndex = 0;
                            }
                            else {
                                this.m_roleInput.selectedIndex = -1;
                            }
                        }
                        this.m_isActiveInput.checked = this.m_isActive != 0;
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
            if (userData != null) {
                this.m_userId = userData.id;
                if ($.fn.dataTable.isDataTable('#user_activation_table')) {
                    this.m_ActivationTables = $('#user_activation_table').DataTable();
                    this.m_ActivationTables.clear();
                    this.m_ActivationTables.draw();
                    this.m_ActivationTables.ajax.url("api/admin/user_activations/" + userData.id);
                    this.m_ActivationTables.ajax.reload();
                }
                else {
                    this.InitUserActivationTables();
                }
            }
            else {
                if ($.fn.dataTable.isDataTable('#user_activation_table')) {
                    this.m_ActivationTables = $('#user_activation_table').DataTable();
                    this.m_ActivationTables.clear();
                    this.m_ActivationTables.draw();
                }
                else {
                    this.InitUserActivationTables();
                }
            }
            $(this.m_editorOverlay).fadeIn(250);
        };
        UserRecord.prototype.StopEditing = function () {
            $(this.m_editorOverlay).fadeOut(200);
        };
        UserRecord.prototype.ToObject = function () {
            var obj = {
                'id': this.m_userId,
                'name': this.m_userFullName,
                'email': this.m_userEmail,
                'group_id': this.m_groupId,
                'role_id': this.m_roleId,
                'customer_id': this.m_customerId,
                'activations_allowed': this.m_numActivations,
                'isactive': this.m_isActive,
                'dt': this.m_dateRegistered
            };
            console.log("ddd", this.m_customerId);
            if (this.m_userPassword != null && this.m_userPassword.length > 0 && (this.m_userPassword != Array(30).join("x"))) {
                obj['password'] = this.m_userPassword;
                obj['password_verify'] = this.m_passwordConfirmInput.value;
            }
            return obj;
        };
        return UserRecord;
    }(Citadel.BaseRecord));
    Citadel.UserRecord = UserRecord;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=userrecord.js.map
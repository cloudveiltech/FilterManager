var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
var Citadel;
(function (Citadel) {
    var UserRecord = (function (_super) {
        __extends(UserRecord, _super);
        function UserRecord() {
            _super.call(this);
            this.ConstructFormReferences();
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
            this.m_submitBtn = document.querySelector('#user_editor_submit');
            this.m_cancelBtn = document.querySelector('#user_editor_cancel');
            this.InitButtonHandlers();
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
                    }
                    break;
                case false:
                    {
                        this.LoadFromObject(userData);
                        this.m_editorTitle.innerText = "Edit User";
                        this.m_submitBtn.innerText = "Save";
                        this.m_fullNameInput.value = this.m_userFullName;
                        this.m_emailInput.value = this.m_userEmail;
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
                            this.m_groupIdInput.selectedIndex = -1;
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
            $(this.m_editorOverlay).fadeIn(250);
        };
        UserRecord.prototype.StopEditing = function () {
            $(this.m_editorOverlay).fadeOut(200);
        };
        UserRecord.prototype.ToObject = function () {
            if (this.m_groupIdInput.selectedIndex != -1) {
                var selectedGroupOption = this.m_groupIdInput.options[this.m_groupIdInput.selectedIndex];
                this.m_groupId = parseInt(selectedGroupOption.value);
            }
            if (this.m_roleInput.selectedIndex != -1) {
                var selectedRoleOption = this.m_roleInput.options[this.m_roleInput.selectedIndex];
                this.m_roleId = parseInt(selectedRoleOption.value);
            }
            var obj = {
                'id': this.m_userId,
                'name': this.m_userFullName,
                'email': this.m_userEmail,
                'group_id': this.m_groupId,
                'role_id': this.m_roleId,
                'activations_allowed': this.m_numActivations,
                'isactive': this.m_isActive,
                'dt': this.m_dateRegistered
            };
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
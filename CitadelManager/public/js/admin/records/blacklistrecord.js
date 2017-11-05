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
    var BlacklistRecord = (function (_super) {
        __extends(BlacklistRecord, _super);
        function BlacklistRecord() {
            var _this = _super.call(this) || this;
            _this.ConstructFormReferences();
            return _this;
        }
        Object.defineProperty(BlacklistRecord.prototype, "RecordRoute", {
            get: function () {
                return 'api/admin/blacklists';
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(BlacklistRecord.prototype, "ValidationOptions", {
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
                        error.appendTo('#blacklist_form_errors');
                        $('#blacklist_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };
                return validationOptions;
            },
            enumerable: true,
            configurable: true
        });
        BlacklistRecord.prototype.ConstructFormReferences = function () {
            this.m_mainForm = document.querySelector('#editor_blacklist_form');
            this.m_editorTitle = document.querySelector('#blacklist_editing_title');
            this.m_editorOverlay = document.querySelector('#overlay_blacklist_editor');
            this.m_applicationNameInput = document.querySelector('#editor_blacklist_name');
            this.m_isActiveInput = document.querySelector('#editor_blacklist_input_isactive');
            this.m_submitBtn = document.querySelector('#blacklist_editor_submit');
            this.m_cancelBtn = document.querySelector('#blacklist_editor_cancel');
            this.InitButtonHandlers();
        };
        BlacklistRecord.prototype.InitButtonHandlers = function () {
            var _this = this;
            this.m_cancelBtn.onclick = (function (e) {
                _this.StopEditing();
            });
        };
        BlacklistRecord.prototype.LoadFromObject = function (data) {
            this.m_blacklistId = data['id'];
            this.m_applicationName = data['name'];
            this.m_isActive = data['isactive'];
            this.m_dateRegistered = data['dt'];
        };
        BlacklistRecord.prototype.LoadFromForm = function () {
            this.m_applicationName = this.m_applicationNameInput.value;
            this.m_isActive = this.m_isActiveInput.checked == true ? 1 : 0;
        };
        BlacklistRecord.prototype.StartEditing = function (userData) {
            var _this = this;
            if (userData === void 0) { userData = null; }
            switch (userData == null) {
                case true:
                    {
                        this.m_editorTitle.innerText = "Add Blacklist Application";
                        this.m_submitBtn.innerText = "Add";
                        this.m_mainForm.reset();
                    }
                    break;
                case false:
                    {
                        this.LoadFromObject(userData);
                        this.m_editorTitle.innerText = "Edit Blacklist Application";
                        this.m_submitBtn.innerText = "Save";
                        this.m_applicationNameInput.value = this.m_applicationName;
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
        BlacklistRecord.prototype.StopEditing = function () {
            $(this.m_editorOverlay).fadeOut(200);
        };
        BlacklistRecord.prototype.ToObject = function () {
            var obj = {
                'id': this.m_blacklistId,
                'name': this.m_applicationName,
                'isactive': this.m_isActive,
                'dt': this.m_dateRegistered
            };
            return obj;
        };
        return BlacklistRecord;
    }(Citadel.BaseRecord));
    Citadel.BlacklistRecord = BlacklistRecord;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=blacklistrecord.js.map
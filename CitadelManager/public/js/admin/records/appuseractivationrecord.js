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
    var AppUserActivationRecord = (function (_super) {
        __extends(AppUserActivationRecord, _super);
        function AppUserActivationRecord() {
            var _this = _super.call(this) || this;
            _this.ConstructFormReferences();
            return _this;
        }
        Object.defineProperty(AppUserActivationRecord.prototype, "RecordRoute", {
            get: function () {
                return 'api/admin/user_activations';
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(AppUserActivationRecord.prototype, "ValidationOptions", {
            get: function () {
                var validationRules = {};
                var validationErrorMessages = {};
                validationErrorMessages[this.m_applicationNameInput.id] = 'Application name is required.';
                var validationOptions = {
                    rules: validationRules,
                    errorPlacement: (function (error, element) {
                        error.appendTo('#activation_form_errors');
                        $('#activation_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };
                return validationOptions;
            },
            enumerable: true,
            configurable: true
        });
        AppUserActivationRecord.prototype.ConstructFormReferences = function () {
            this.m_mainForm = document.querySelector('#editor_activation_form');
            this.m_editorOverlay = document.querySelector('#overlay_activation_editor');
            this.m_userNameInput = document.querySelector('#editor_activation_input_user_full_name');
            this.m_identifierInput = document.querySelector('#editor_activation_input_identifier');
            this.m_deviceIdInput = document.querySelector('#editor_activation_input_device_id');
            this.m_ipAddressInput = document.querySelector('#editor_activation_input_ip_address');
            this.m_bypassQuantityInput = document.querySelector('#editor_activation_input_bypass_quantity');
            this.m_bypassPeriodInput = document.querySelector('#editor_activation_input_bypass_period');
            this.m_bypassUsedInput = document.querySelector('#editor_activation_input_bypass_used');
            this.m_submitBtn = document.querySelector('#activation_editor_submit');
            this.m_cancelBtn = document.querySelector('#activation_editor_cancel');
            this.InitButtonHandlers();
        };
        AppUserActivationRecord.prototype.InitButtonHandlers = function () {
            var _this = this;
            this.m_cancelBtn.onclick = (function (e) {
                _this.StopEditing();
            });
        };
        AppUserActivationRecord.prototype.LoadFromObject = function (data) {
            this.m_activationId = data['id'];
            this.m_userName = data['user']['name'];
            this.m_identifier = data['identifier'];
            this.m_ipAddress = data['ip_address'];
            this.m_deviceId = data['device_id'];
            if (data['bypass_quantity'] != null)
                this.m_bypassQuantity = data['bypass_quantity'];
            else
                this.m_bypassQuantity = null;
            if (data['bypass_period'] == null)
                this.m_bypassPeriod = data['bypass_period'];
            else
                this.m_bypassPeriod = null;
            this.m_bypassUsed = data['bypass_used'];
        };
        AppUserActivationRecord.prototype.LoadFromForm = function () {
            this.m_bypassQuantity = this.m_bypassQuantityInput.value == "" ? null : parseInt(this.m_bypassQuantityInput.value);
            this.m_bypassPeriod = this.m_bypassPeriodInput.value == "" ? null : parseInt(this.m_bypassPeriodInput.value);
        };
        AppUserActivationRecord.prototype.StartEditing = function (userData) {
            var _this = this;
            if (userData === void 0) { userData = null; }
            this.LoadFromObject(userData);
            this.m_userNameInput.value = this.m_userName;
            this.m_identifierInput.value = this.m_identifier;
            this.m_deviceIdInput.value = this.m_deviceId;
            this.m_ipAddressInput.value = this.m_ipAddress;
            if (this.m_bypassQuantity != null)
                this.m_bypassQuantityInput.value = this.m_bypassQuantity.toString();
            else
                this.m_bypassQuantityInput.value = "";
            if (this.m_bypassPeriod != null)
                this.m_bypassPeriodInput.value = this.m_bypassPeriod.toString();
            else
                this.m_bypassPeriodInput.value = "";
            this.m_bypassUsedInput.value = this.m_bypassUsed.toString();
            this.m_mainForm.onsubmit = (function (e) {
                return _this.OnFormSubmitClicked(e, userData == null);
            });
            $(this.m_editorOverlay).fadeIn(250);
        };
        AppUserActivationRecord.prototype.StopEditing = function () {
            $(this.m_editorOverlay).fadeOut(200);
        };
        AppUserActivationRecord.prototype.ToObject = function () {
            var obj = {
                'id': this.m_activationId,
                'bypass_quantity': this.m_bypassQuantity,
                'bypass_period': this.m_bypassPeriod
            };
            return obj;
        };
        AppUserActivationRecord.prototype.Block = function () {
            var _this = this;
            var dataObject = {};
            this.m_progressWait.Show('Block Record', 'Blocking record to server.');
            var ajaxSettings = {
                method: "POST",
                timeout: 60000,
                contents: { _token: $('meta[name="csrf-token"]').attr('content') },
                url: this.RecordRoute + '/block/' + this.m_activationId,
                data: dataObject,
                success: function (data, textStatus, jqXHR) {
                    _this.m_progressWait.Hide();
                    if (_this.m_actionCompleteCallback != null) {
                        _this.m_actionCompleteCallback("Blocked");
                    }
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
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
            $.ajax(ajaxSettings);
        };
        return AppUserActivationRecord;
    }(Citadel.BaseRecord));
    Citadel.AppUserActivationRecord = AppUserActivationRecord;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=appuseractivationrecord.js.map
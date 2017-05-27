var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
var Citadel;
(function (Citadel) {
    var DeactivationRequestRecord = (function (_super) {
        __extends(DeactivationRequestRecord, _super);
        function DeactivationRequestRecord() {
            _super.call(this);
            this.ConstructFormReferences();
        }
        Object.defineProperty(DeactivationRequestRecord.prototype, "RecordRoute", {
            get: function () {
                return 'api/admin/deactivationreq';
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(DeactivationRequestRecord.prototype, "ValidationOptions", {
            get: function () {
                var validationRules = {};
                var validationErrorMessages = {};
                var validationOptions = {
                    rules: validationRules,
                    errorPlacement: (function (error, element) {
                        error.appendTo('#deactivation_request_form_errors');
                        $('#deactivation_request_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };
                return validationOptions;
            },
            enumerable: true,
            configurable: true
        });
        DeactivationRequestRecord.prototype.ConstructFormReferences = function () {
            this.m_mainForm = document.querySelector('#editor_deactivation_request_form');
            this.m_editorTitle = document.querySelector('#deactivation_request_editing_title');
            this.m_editorOverlay = document.querySelector('#overlay_deactivation_request_editor');
            this.m_userNameInput = document.querySelector('#editor_deactivation_request_input_username');
            this.m_userNameInput.disabled = true;
            this.m_requestGrantedInput = document.querySelector('#editor_deactivation_request_input_isgranted');
            this.m_submitBtn = document.querySelector('#deactivation_request_editor_submit');
            this.m_cancelBtn = document.querySelector('#deactivation_request_editor_cancel');
            this.InitButtonHandlers();
        };
        DeactivationRequestRecord.prototype.InitButtonHandlers = function () {
            var _this = this;
            this.m_cancelBtn.onclick = (function (e) {
                _this.StopEditing();
            });
        };
        DeactivationRequestRecord.prototype.LoadFromObject = function (data) {
            this.m_requestId = data['id'];
            this.m_userName = data['user']['email'];
            this.m_requestGranted = data['granted'];
            this.m_dateRequested = data['time_first_requested'];
        };
        DeactivationRequestRecord.prototype.LoadFromForm = function () {
            this.m_requestGranted = this.m_requestGrantedInput.checked == true ? 1 : 0;
        };
        DeactivationRequestRecord.prototype.StartEditing = function (data) {
            var _this = this;
            if (data === void 0) { data = null; }
            switch (data == null) {
                case true:
                    {
                    }
                    break;
                case false:
                    {
                        this.LoadFromObject(data);
                        this.m_editorTitle.innerText = "Edit Deactivation Request";
                        this.m_submitBtn.innerText = "Save";
                        this.m_userNameInput.value = this.m_userName;
                        this.m_requestGrantedInput.checked = this.m_requestGranted != 0;
                    }
                    break;
            }
            this.m_mainForm.onsubmit = (function (e) {
                var validateOpts = _this.ValidationOptions;
                var validresult = $(_this.m_mainForm).validate(validateOpts).form();
                if ($(_this.m_mainForm).validate(validateOpts).valid()) {
                    return _this.OnFormSubmitClicked(e, data == null);
                }
                return false;
            });
            $(this.m_editorOverlay).fadeIn(250);
        };
        DeactivationRequestRecord.prototype.StopEditing = function () {
            $(this.m_editorOverlay).fadeOut(200);
        };
        DeactivationRequestRecord.prototype.ToObject = function () {
            var obj = {
                'id': this.m_requestId,
                'user_name': this.m_userName,
                'granted': this.m_requestGranted,
                'time_first_requested': this.m_dateRequested
            };
            return obj;
        };
        return DeactivationRequestRecord;
    }(Citadel.BaseRecord));
    Citadel.DeactivationRequestRecord = DeactivationRequestRecord;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=deactivationrequestrecord.js.map
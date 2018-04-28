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
    var VersionRecord = (function (_super) {
        __extends(VersionRecord, _super);
        function VersionRecord() {
            var _this = _super.call(this) || this;
            $("#btn_cancel").hide();
            _this.m_appName = "Cloud Veil";
            _this.m_fileName = "Cloud Veil";
            _this.InitUIComponents();
            return _this;
        }
        Object.defineProperty(VersionRecord.prototype, "RecordRoute", {
            get: function () {
                return 'api/admin/version';
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(VersionRecord.prototype, "ValidationOptions", {
            get: function () {
                var validationRules = {};
                validationRules[this.m_appNameInput.id] = {
                    required: true
                };
                validationRules[this.m_fileNameInput.id] = {
                    required: true
                };
                validationRules[this.m_alphaVersionInput.id] = {
                    required: true
                };
                validationRules[this.m_betaVersionInput.id] = {
                    required: true
                };
                validationRules[this.m_stableVersionInput.id] = {
                    required: true
                };
                var validationErrorMessages = {};
                validationErrorMessages[this.m_appNameInput.id] = 'App name is required.';
                validationErrorMessages[this.m_fileNameInput.id] = 'File name is required.';
                validationErrorMessages[this.m_alphaVersionInput.id] = 'Alpha version is required.';
                validationErrorMessages[this.m_betaVersionInput.id] = 'Beta version is required.';
                validationErrorMessages[this.m_stableVersionInput.id] = 'Stable version is required.';
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
        VersionRecord.prototype.InitUIComponents = function () {
            this.m_addBtn = document.querySelector('#btn_add');
            this.m_closeBtn = document.querySelector('#system_version_close');
            this.m_editorOverlay = document.querySelector('#overlay_system_version');
            this.m_mainForm = document.querySelector('#system_version_form');
            this.m_versionTitle = document.querySelector('#overlay_system_title');
            this.m_platformOSInput = document.querySelector('#platform_os_name');
            this.m_appNameInput = document.querySelector('#system_version_input_app_name');
            this.m_fileNameInput = document.querySelector('#system_version_input_file_name');
            this.m_alphaVersionInput = document.querySelector('#system_version_input_alpha_version');
            this.m_betaVersionInput = document.querySelector('#system_version_input_beta_version');
            this.m_stableVersionInput = document.querySelector('#system_version_input_stable_version');
            this.m_releaseDateInput = document.querySelector('#system_version_input_rdate');
            this.m_activeInput = document.querySelector('#system_version_default_version');
            this.m_changesInput = document.querySelector('#system_version_input_changes');
            this.InitButtonHandlers();
        };
        VersionRecord.prototype.InitButtonHandlers = function () {
            var _this = this;
            this.m_closeBtn.onclick = (function (e) {
                _this.StopEditing();
            });
        };
        VersionRecord.prototype.LoadFromObject = function (data) {
            this.m_versionId = data['id'];
            this.m_platformId = data['platform_id'];
            this.m_appName = data['app_name'];
            this.m_fileName = data['file_name'];
            this.m_versionNumber = data['version_number'];
            this.m_changes = data['changes'];
            this.m_alpha = data['alpha'];
            this.m_beta = data['beta'];
            this.m_stable = data['stable'];
            this.m_releaseDate = data['release_date'];
            this.m_active = data['active'];
        };
        VersionRecord.prototype.LoadFromForm = function () {
            var selectedPlatformOption = this.m_platformOSInput.options[this.m_platformOSInput.selectedIndex];
            this.m_platformId = parseInt(selectedPlatformOption.value);
            this.m_appName = this.m_appNameInput.value;
            this.m_fileName = this.m_fileNameInput.value;
            this.m_versionNumber = this.m_stableVersionInput.value;
            this.m_alpha = this.m_alphaVersionInput.value;
            this.m_beta = this.m_betaVersionInput.value;
            this.m_stable = this.m_stableVersionInput.value;
            this.m_releaseDate = this.m_releaseDateInput.value;
            this.m_active = this.m_activeInput.checked == true ? 1 : 0;
            this.m_changes = this.m_changesInput.value;
        };
        VersionRecord.prototype.StartEditing = function (rowData) {
            var _this = this;
            if (rowData === void 0) { rowData = null; }
            switch (rowData == null) {
                case true:
                    {
                        this.m_versionTitle.innerText = "Add New Version";
                        this.m_addBtn.innerText = "Add Version";
                        this.m_mainForm.reset();
                        this, this.m_platformId = 0;
                        this.loadPlatforms();
                        this.m_appNameInput.value = this.m_appName;
                        this.m_fileNameInput.value = this.m_fileName;
                    }
                    break;
                case false:
                    {
                        this.LoadFromObject(rowData);
                        this.m_versionTitle.innerText = "Edit Version";
                        this.m_addBtn.innerText = "Save";
                        this.m_appNameInput.value = this.m_appName;
                        this.m_fileNameInput.value = this.m_fileName;
                        this.m_alphaVersionInput.value = this.m_alpha;
                        this.m_betaVersionInput.value = this.m_beta;
                        this.m_stableVersionInput.value = this.m_stable;
                        this.m_releaseDateInput.value = this.m_releaseDate;
                        this.m_activeInput.checked = this.m_active === 1 ? true : false;
                        this.m_changesInput.value = this.m_changes;
                        this.loadPlatforms();
                    }
                    break;
            }
            this.m_mainForm.onsubmit = (function (e) {
                var validateOpts = _this.ValidationOptions;
                var validresult = $(_this.m_mainForm).validate(validateOpts).form();
                if ($(_this.m_mainForm).validate(validateOpts).valid()) {
                    return _this.OnFormSubmitClicked(e, rowData == null);
                }
                return false;
            });
            $(this.m_editorOverlay).fadeIn(250);
        };
        VersionRecord.prototype.StopEditing = function () {
            $(this.m_editorOverlay).fadeOut(200);
        };
        VersionRecord.prototype.loadPlatforms = function () {
            var _this = this;
            var ajaxSettings = {
                method: "GET",
                timeout: 60000,
                url: "api/admin/platforms",
                data: {},
                success: function (data, textStatus, jqXHR) {
                    _this._update_platforms(data.platforms);
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(textStatus);
                }
            };
            $.get(ajaxSettings);
        };
        VersionRecord.prototype._update_platforms = function (platforms) {
            $('#platform_os_name').empty();
            for (var _i = 0, platforms_1 = platforms; _i < platforms_1.length; _i++) {
                var item = platforms_1[_i];
                if (this.m_platformId === item.id) {
                    $('#platform_os_name').append("<option value=" + item.id + " selected>" + item.os_name + "</option>");
                }
                else {
                    $('#platform_os_name').append("<option value=" + item.id + ">" + item.os_name + "</option>");
                }
            }
        };
        VersionRecord.prototype.ToObject = function () {
            var obj = {
                'id': this.m_versionId,
                'platform_id': this.m_platformId,
                'app_name': this.m_appName,
                'file_name': this.m_fileName,
                'version_number': this.m_stable,
                'changes': this.m_changes,
                'alpha': this.m_alpha,
                'beta': this.m_beta,
                'stable': this.m_stable,
                'release_date': this.m_releaseDate,
                'active': this.m_active,
            };
            return obj;
        };
        return VersionRecord;
    }(Citadel.BaseRecord));
    Citadel.VersionRecord = VersionRecord;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=versionrecord.js.map
/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
///<reference path="../../progresswait.ts"/>
namespace Citadel {
    /**
     *
     *
     * @class VersionRecord
     */
    export class VersionRecord extends BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        ERROR_MESSAGE_APP_NAME = 'A valid application name is required.';
        ERROR_MESSAGE_FILE_NAME = 'A valid file name is required.';
        ERROR_MESSAGE_VERSION_ALPHA = 'Alpha version is required.';
        ERROR_MESSAGE_VERSION_BETA = 'Beta version is required.';
        ERROR_MESSAGE_VERSION_STABLE = 'Stable is required.';
        ERROR_MESSAGE_RELEASE_DATE = 'Release date is required.';
        ERROR_MESSAGE_CHANGES = 'Changes field is required.';

        MESSAGE_ACTION_FAILED = 'Error reported by the server during action.\n %ERROR_MSG% \nCheck console for more information.';

        TITLE_ACTION_FAILED = 'Action Failed';
        TITLE_NEW_VERSION = 'Add New Version';
        TITLE_EDIT_VERSION = 'Edit Version';

        BTN_LABEL_NEW_VERSION = 'Add';
        BTN_LABEL_EDIT_GROUP = 'Save';

        ERROR_MESSAGE_DELAY_TIME = 5000;
        FADE_IN_DELAY_TIME = 200;

        URL_ROUTE = 'api/admin/version';
        URL_PLATFORMS = 'api/admin/platforms';
        URL_EXTENSIONS = 'api/admin/extensions';

        // ──────────────────────────────────────────────────────────────
        //   :::::: V E R S I O N    R E C O R D   M E M B E R S ::::::
        // ──────────────────────────────────────────────────────────────
        private m_versionId: number;
        private m_platformId: number;
        private m_appName: string;
        private m_fileName: string;
        private m_versionNumber: string;
        private m_changes: string;
        private m_alpha: string;
        private m_alpha_signature: string;
        private m_beta: string;
        private m_beta_signature: string;
        private m_stable: string;
        private m_stable_signature: string;
        private m_releaseDate: string;
        private m_active: number;
        private m_fileExt: string;
        private m_extensions: any[];
        private m_platforms: any[];
        private m_loaded: number;

        // ──────────────────────────────────────────────
        //   :::::: H T M L      E L E M E N T S ::::::
        // ──────────────────────────────────────────────
        private m_mainForm: HTMLFormElement;

        private m_editorOverlay: HTMLDivElement;

        private m_h1_Version: HTMLHeadingElement;
        private m_inputOS: HTMLSelectElement;
        private m_inputFileExt: HTMLSelectElement;
        private m_inputAppName: HTMLInputElement;
        private m_inputFileName: HTMLInputElement;
        private m_inputAlphaVersion: HTMLInputElement;
        private m_inputAlphaVersionSignature: HTMLInputElement;
        private m_inputBetaVersion: HTMLInputElement;
        private m_inputBetaVersionSignature: HTMLInputElement;
        private m_inputStableVersion: HTMLInputElement;
        private m_inputStableVersionSignature: HTMLInputElement;
        private m_inputReleaseDate: HTMLInputElement;
        private m_inputIsActive: HTMLInputElement;
        private m_inputChange: HTMLTextAreaElement;


        private m_btnClose: HTMLButtonElement;
        private m_btnCancel: HTMLButtonElement;
        private m_btnAdd: HTMLButtonElement;

        public get RecordRoute(): string {
            return this.URL_ROUTE;
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions {
            let validationRules: JQueryValidation.RulesDictionary = {};

            validationRules[this.m_inputAppName.id] = {
                required: true
            };

            validationRules[this.m_inputFileName.id] = {
                required: true
            };

            validationRules[this.m_inputAlphaVersion.id] = {
                required: true
            };

            validationRules[this.m_inputBetaVersion.id] = {
                required: true
            };
            validationRules[this.m_inputStableVersion.id] = {
                required: true
            };

            validationRules[this.m_inputReleaseDate.id] = {
                required: true
            };

            validationRules[this.m_inputChange.id] = {
                required: true
            };

            let validationErrorMessages = {};
            validationErrorMessages[this.m_inputAppName.id] = this.ERROR_MESSAGE_APP_NAME;
            validationErrorMessages[this.m_inputFileName.id] = this.ERROR_MESSAGE_FILE_NAME;
            validationErrorMessages[this.m_inputAlphaVersion.id] = this.ERROR_MESSAGE_VERSION_ALPHA;
            validationErrorMessages[this.m_inputBetaVersion.id] = this.ERROR_MESSAGE_VERSION_BETA;
            validationErrorMessages[this.m_inputStableVersion.id] = this.ERROR_MESSAGE_VERSION_STABLE;
            validationErrorMessages[this.m_inputReleaseDate.id] = this.ERROR_MESSAGE_RELEASE_DATE;
            validationErrorMessages[this.m_inputChange.id] = this.ERROR_MESSAGE_CHANGES;

            let validationOptions: JQueryValidation.ValidationOptions = {
                rules: validationRules,
                errorPlacement: ((error: JQuery, element: JQuery): void => {
                    error.appendTo('#version_form_errors');
                    $('#version_form_errors').append('<br/>');
                }),
                messages: validationErrorMessages
            };

            return validationOptions;
        }

        constructor() {
            super();
            $("#btn_cancel").hide();
            this.m_appName = "CloudVeil";
            this.m_fileName = "CloudVeil";
            this.InitUIComponents();
        }

        private InitUIComponents(): void {
            this.m_extensions = [];
            this.m_platforms = [];
            this.m_loaded = 0;
            this.m_btnAdd = document.querySelector('#btn_add') as HTMLButtonElement;
            this.m_btnClose = document.querySelector('#system_version_close') as HTMLButtonElement;

            this.m_editorOverlay = document.querySelector('#overlay_system_version') as HTMLDivElement;
            this.m_mainForm = document.querySelector('#system_version_form') as HTMLFormElement;
            this.m_h1_Version = document.querySelector('#overlay_system_title') as HTMLHeadingElement;

            this.m_inputOS = document.querySelector('#platform_os_name') as HTMLSelectElement;
            this.m_inputAppName = document.querySelector('#system_version_input_app_name') as HTMLInputElement;
            this.m_inputFileName = document.querySelector('#system_version_input_file_name') as HTMLInputElement;

            this.m_inputAlphaVersion = document.querySelector('#system_version_input_alpha_version') as HTMLInputElement;
            this.m_inputAlphaVersionSignature = document.querySelector('#system_version_input_alpha_version_sig') as HTMLInputElement;

            this.m_inputBetaVersion = document.querySelector('#system_version_input_beta_version') as HTMLInputElement;
            this.m_inputBetaVersionSignature = document.querySelector('#system_version_input_beta_version_sig') as HTMLInputElement;

            this.m_inputStableVersion = document.querySelector('#system_version_input_stable_version') as HTMLInputElement;
            this.m_inputStableVersionSignature = document.querySelector('#system_version_input_stable_version_sig') as HTMLInputElement;

            this.m_inputReleaseDate = document.querySelector('#system_version_input_rdate') as HTMLInputElement;
            this.m_inputIsActive = document.querySelector('#system_version_default_version') as HTMLInputElement;
            this.m_inputChange = document.querySelector('#system_version_input_changes') as HTMLTextAreaElement;
            this.m_inputFileExt = document.querySelector('#system_version_ext') as HTMLSelectElement;

            this.InitButtonHandlers();
        }

        private InitButtonHandlers(): void {
            this.m_btnClose.onclick = ((e: MouseEvent): any => {
                this.StopEditing();
            });

            this.m_inputOS.onchange = ((e: MouseEvent): any => {
                this.onChangePlatform();
            });
        }

        protected LoadFromObject(data: Object): void {
            this.m_versionId = data['id'] as number;
            this.m_platformId = data['platform_id'] as number;
            this.m_appName = data['app_name'] as string;
            this.m_fileName = data['file_name'] as string;
            this.m_versionNumber = data['version_number'] as string;
            this.m_changes = data['changes'] as string;
            this.m_alpha = data['alpha'] as string;
            this.m_alpha_signature = data['alpha_ed_signature'] as string;
            this.m_beta = data['beta'] as string;
            this.m_beta_signature = data['beta_ed_signature'] as string;
            this.m_stable = data['stable'] as string;
            this.m_stable_signature = data['stable_ed_signature'] as string;
            this.m_releaseDate = data['release_date'] as string;
            this.m_active = data['active'] as number;
            this.m_fileExt = data['file_ext'] as string;
        }

        protected LoadFromForm(): void {
            let selectedPlatformOption = this.m_inputOS.options[this.m_inputOS.selectedIndex] as HTMLOptionElement;
            this.m_platformId = parseInt(selectedPlatformOption.value);
            this.m_appName = this.m_inputAppName.value;
            this.m_fileName = this.m_inputFileName.value;
            this.m_versionNumber = this.m_inputStableVersion.value;
            this.m_alpha = this.m_inputAlphaVersion.value;
            this.m_alpha_signature = this.m_inputAlphaVersionSignature.value;
            this.m_beta = this.m_inputBetaVersion.value;
            this.m_beta_signature = this.m_inputBetaVersionSignature.value;
            this.m_stable = this.m_inputStableVersion.value;
            this.m_stable_signature = this.m_inputStableVersionSignature.value;
            this.m_releaseDate = this.m_inputReleaseDate.value;
            this.m_active = this.m_inputIsActive.checked == true ? 1 : 0;
            this.m_changes = this.m_inputChange.value;
            let selectedFileExt = this.m_inputFileExt.options[this.m_inputFileExt.selectedIndex] as HTMLOptionElement;
            this.m_fileExt = selectedFileExt.value;

            console.log('m_fileExt', this.m_fileExt);
        }

        public StartEditing(rowData: Object = null): void {
            $('#version_form_errors').empty();          // remove all previous errors;

            switch (rowData == null) {
                case true: {
                    this.m_h1_Version.innerText = this.TITLE_NEW_VERSION;
                    this.m_btnAdd.innerText = this.BTN_LABEL_NEW_VERSION;
                    this.m_mainForm.reset();
                    this.m_platformId = 0;
                    this.loadPlatforms();
                    this.loadExtensions();

                    this.m_inputAppName.value = this.m_appName;
                    this.m_inputFileName.value = this.m_fileName;
                }
                    break;

                case false: {
                    // Editing an existing object here.
                    this.LoadFromObject(rowData);

                    this.m_h1_Version.innerText = this.TITLE_EDIT_VERSION;
                    this.m_btnAdd.innerText = this.BTN_LABEL_EDIT_GROUP;

                    this.m_inputAppName.value = this.m_appName;
                    this.m_inputFileName.value = this.m_fileName;
                    this.m_inputAlphaVersion.value = this.m_alpha;
                    this.m_inputAlphaVersionSignature.value = this.m_alpha_signature;
                    this.m_inputBetaVersion.value = this.m_beta;
                    this.m_inputBetaVersionSignature.value = this.m_beta_signature;
                    this.m_inputStableVersion.value = this.m_stable;
                    this.m_inputStableVersionSignature.value = this.m_stable_signature;
                    this.m_inputReleaseDate.value = this.m_releaseDate;
                    this.m_inputIsActive.checked = this.m_active === 1 ? true : false;
                    this.m_inputChange.value = this.m_changes;
                    this.loadPlatforms();
                    this.loadExtensions();
                }
                    break;
            }

            this.m_mainForm.onsubmit = ((e: Event): any => {

                let validateOpts = this.ValidationOptions;
                let validresult = $(this.m_mainForm).validate(validateOpts).form();

                if ($(this.m_mainForm).validate(validateOpts).valid()) {
                    return this.OnFormSubmitClicked(e, rowData == null);
                }

                return false;
            });

            $(this.m_editorOverlay).fadeIn(this.FADE_IN_DELAY_TIME);
        }

        public StopEditing(): void {
            $(this.m_editorOverlay).fadeOut(this.FADE_IN_DELAY_TIME);
        }

        public onChangePlatform(): void {
            var platform_id = $(this.m_inputOS).val();
            var sel_os = '';
            for (let os of this.m_platforms) {
                if (os.id == platform_id) {
                    sel_os = os.platform;
                }
            }
            var sel_ext = '';
            for (let ext of this.m_extensions) {
                if (ext.platform == sel_os) {
                    sel_ext = ext.extensions;
                }
            }
            var ext_arr = sel_ext.split(",");
            $(this.m_inputFileExt).html('');
            ext_arr.forEach((ext): void => {
                var newOption = document.createElement("option");
                newOption.text = ext;
                newOption.value = ext;
                this.m_inputFileExt.add(newOption);
            });
            if (this.m_fileExt != '' && this.m_fileExt != undefined && this.m_fileExt != null) {
                $(this.m_inputFileExt).val(this.m_fileExt);
            } else {
                this.m_inputFileExt.selectedIndex = 0;
            }
        }

        private loadPlatforms(): void {
            let ajaxSettings: JQueryAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: this.URL_PLATFORMS,
                data: {},
                success: (data: any): any => {
                    this.m_platforms = data.platforms;
                    this.m_loaded++;
                    this._update_platforms(data.platforms);
                    if (this.m_loaded >= 2) {
                        this.onChangePlatform();
                    }
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }

            $.ajax(ajaxSettings);
        }

        private loadExtensions(): void {
            let ajaxSettings: JQueryAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: this.URL_EXTENSIONS,
                data: {},
                success: (data: any): any => {
                    this.m_loaded++;
                    this.m_extensions = data.extensions;
                    if (this.m_loaded >= 2) {
                        this.onChangePlatform();
                    }
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }

            $.ajax(ajaxSettings);
        }

        private _update_platforms(platforms: any[]): void {
            $('#platform_os_name').empty();
            for (let item of platforms) {
                if (this.m_platformId === item.id) {
                    $('#platform_os_name').append("<option value=" + item.id + " selected>" + item.os_name + "</option>");
                } else {
                    $('#platform_os_name').append("<option value=" + item.id + ">" + item.os_name + "</option>");
                }
            }
        }

        public ToObject(): Object {
            let obj = {
                'id': this.m_versionId,
                'platform_id': this.m_platformId,
                'app_name': this.m_appName,
                'file_name': this.m_fileName,
                'version_number': this.m_stable,
                'changes': this.m_changes,
                'alpha': this.m_alpha,
                'alpha_ed_signature': this.m_alpha_signature,
                'beta': this.m_beta,
                'beta_ed_signature': this.m_beta_signature,
                'stable': this.m_stable,
                'stable_ed_signature': this.m_stable_signature,
                'release_date': this.m_releaseDate,
                'active': this.m_active,
                'file_ext': this.m_fileExt
            };
            return obj;
        }
    }
}
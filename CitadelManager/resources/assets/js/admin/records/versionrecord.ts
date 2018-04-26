/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
///<reference path="../progresswait.ts"/>
namespace Citadel
{
    /**
     * 
     * 
     * @class VersionRecord
     */
    export class VersionRecord extends BaseRecord
    {              
         /**
         * The div container that represents the entirety of our HTML UI. 
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf ApplyToGroupOverlay
         */
        private m_closeBtn: HTMLButtonElement;
        private m_cancelBtn: HTMLButtonElement;
        private m_addBtn: HTMLButtonElement;
        private m_editorOverlay: HTMLDivElement;
        private m_mainForm: HTMLFormElement;
        private m_versionTitle: HTMLHeadingElement;

        private m_platformOSInput: HTMLSelectElement;
        private m_appNameInput: HTMLInputElement;
        private m_fileNameInput: HTMLInputElement;
        private m_alphaVersionInput: HTMLInputElement;
        private m_betaVersionInput: HTMLInputElement;
        private m_stableVersionInput: HTMLInputElement;
        private m_releaseDateInput: HTMLInputElement;
        private m_activeInput: HTMLInputElement;
        private m_changesInput: HTMLTextAreaElement;

        private m_versionId: number;
        private m_platformId: number;
        private m_appName: string;
        private m_fileName: string;
        private m_versionNumber: string;
        private m_changes: string;
        private m_alpha: string;
        private m_beta: string;
        private m_stable: string;
        private m_releaseDate: string;
        private m_active: number;

        public get RecordRoute(): string
        {
            return 'api/admin/version';
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions
        {
            let validationRules: JQueryValidation.RulesDictionary = {};
            
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

            let validationErrorMessages = {};
            validationErrorMessages[this.m_appNameInput.id] = 'App name is required.';
            validationErrorMessages[this.m_fileNameInput.id] = 'File name is required.';
            validationErrorMessages[this.m_alphaVersionInput.id] = 'Alpha version is required.';
            validationErrorMessages[this.m_betaVersionInput.id] = 'Beta version is required.';
            validationErrorMessages[this.m_stableVersionInput.id] = 'Stable version is required.';

            let validationOptions: JQueryValidation.ValidationOptions =
                {
                    rules: validationRules,
                    errorPlacement: ((error: JQuery, element: JQuery): void =>
                    {
                        error.appendTo('#user_form_errors');
                        $('#user_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };

            return validationOptions;
        }

        /**
         * Creates an instance of AppVersion.
         * 
         * 
         * @memberOf AppVersion
         */
        constructor() 
        {
            super();
            $("#btn_cancel").hide();
            this.m_appName = "Cloud Veil";
            this.m_fileName = "Cloud Veil";
            this.InitUIComponents();
        }

        private InitUIComponents(): void
        {            
            this.m_addBtn = document.querySelector('#btn_add') as HTMLButtonElement;
            
            this.m_closeBtn = document.querySelector('#system_version_close') as HTMLButtonElement;
            this.m_editorOverlay = document.querySelector('#overlay_system_version') as HTMLDivElement;
            this.m_mainForm  = document.querySelector('#system_version_form') as HTMLFormElement;
            this.m_versionTitle  = document.querySelector('#overlay_system_title') as HTMLHeadingElement;

            this.m_platformOSInput = document.querySelector('#platform_os_name') as HTMLSelectElement;
            this.m_appNameInput = document.querySelector('#system_version_input_app_name') as HTMLInputElement;
            this.m_fileNameInput = document.querySelector('#system_version_input_file_name') as HTMLInputElement;
            this.m_alphaVersionInput = document.querySelector('#system_version_input_alpha_version') as HTMLInputElement;
            this.m_betaVersionInput = document.querySelector('#system_version_input_beta_version') as HTMLInputElement;
            this.m_stableVersionInput = document.querySelector('#system_version_input_stable_version') as HTMLInputElement;
            this.m_releaseDateInput = document.querySelector('#system_version_input_rdate') as HTMLInputElement;
            this.m_activeInput = document.querySelector('#system_version_default_version') as HTMLInputElement;
            this.m_changesInput = document.querySelector('#system_version_input_changes') as HTMLTextAreaElement;
            
            this.InitButtonHandlers();
        }

        private InitButtonHandlers(): void
        {
            this.m_closeBtn.onclick = ((e: MouseEvent): any =>
            {
                this.StopEditing();
            });
        }

        protected LoadFromObject(data: Object): void
        {
            this.m_versionId = data['id'] as number;
            this.m_platformId = data['platform_id'] as number;
            this.m_appName = data['app_name'] as string;
            this.m_fileName = data['file_name'] as string;
            this.m_versionNumber = data['version_number'] as string;
            this.m_changes = data['changes'] as string;
            this.m_alpha = data['alpha'] as string;
            this.m_beta = data['beta'] as string;
            this.m_stable = data['stable'] as string;
            this.m_releaseDate = data['release_date'] as string;
            this.m_active = data['active'] as number;
        }

        protected LoadFromForm(): void
        {
            let selectedPlatformOption = this.m_platformOSInput.options[this.m_platformOSInput.selectedIndex] as HTMLOptionElement;
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
        }

        public StartEditing(rowData: Object = null): void
        {
            switch (rowData == null)
            {
                case true:
                    {
                        this.m_versionTitle.innerText = "Add New Version";
                        this.m_addBtn.innerText = "Add Version";
                        this.m_mainForm.reset();
                        this,this.m_platformId = 0;
                        this.loadPlatforms();

                        this.m_appNameInput.value = this.m_appName;
                        this.m_fileNameInput.value = this.m_fileName;
                    }
                    break;

                case false:
                    {
                        // Editing an existing object here.
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

            this.m_mainForm.onsubmit = ((e: Event): any =>
            {

                let validateOpts = this.ValidationOptions;
                let validresult = $(this.m_mainForm).validate(validateOpts).form();

                if ($(this.m_mainForm).validate(validateOpts).valid())
                {
                    return this.OnFormSubmitClicked(e, rowData == null);
                }

                return false;
            });
            
            // Show the editor.
            $(this.m_editorOverlay).fadeIn(250);
        }

        public StopEditing(): void
        {
            $(this.m_editorOverlay).fadeOut(200);
        }

        private loadPlatforms(): void
        {
            let ajaxSettings: JQueryAjaxSettings =
            {
                method: "GET",
                timeout: 60000,
                url: "api/admin/platforms",
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                {                    
                    this._update_platforms(data.platforms);
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                {
                    console.log(textStatus);
                }
            }
            $.get(ajaxSettings);
        }

        private _update_platforms(platforms: any[]): void {
            $('#platform_os_name').empty();
            for( let item of platforms) {
                if(this.m_platformId === item.id) {
                    $('#platform_os_name').append("<option value=" + item.id + " selected>" + item.os_name + "</option>");
                } else {
                    $('#platform_os_name').append("<option value=" + item.id + ">" + item.os_name + "</option>");
                }
            }
        }

        public ToObject(): Object
        {
            let obj =
                {
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
        }
    }

}
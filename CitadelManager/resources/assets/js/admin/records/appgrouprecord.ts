/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel
{

    export class AppGroupRecord extends BaseRecord
    {
        //
        // ────────────────────────────────────────────────────────────────────────── I ──────────
        //   :::::: U S E R   D A T A   M E M B E R S : :  :   :    :     :        :          :
        // ────────────────────────────────────────────────────────────────────────────────────
        //        

        private m_appgroupId: number;
        private m_appGroupName: string;
        private m_selectedApps: string;
        private m_dateRegistered: string;
        //
        // ──────────────────────────────────────────────────────────────────────────────── II ──────────
        //   :::::: E D I T O R   H T M L   E L E M E N T S : :  :   :    :     :        :          :
        // ──────────────────────────────────────────────────────────────────────────────────────────
        //

        private m_mainForm: HTMLFormElement;

        /**
         * The Div of the editing overlay. This houses all of the editor
         * contents and overlays everything else with a super high z-index.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf appgroupRecord
         */
        private m_editorOverlay: HTMLDivElement;
        private m_editorTitle: HTMLHeadingElement;
        private m_groupNameInput: HTMLInputElement;
        private m_sourceAppList: HTMLSelectElement;
        private m_targetAppList: HTMLSelectElement;
        
        private m_appsSourceToTargetBtn: HTMLButtonElement;
        private m_appSourceToTargetBtn: HTMLButtonElement;
        private m_appTargetToSourceBtn: HTMLButtonElement;
        private m_appsTargetToSourceBtn: HTMLButtonElement;

        private m_submitBtn: HTMLButtonElement;
        private m_cancelBtn: HTMLButtonElement;

        private m_arrLeftApplications: any[];
        private m_arrRightApplications: any[];
        private m_groupApp: any;
        /**
         * Gets the base API route from this record type.
         * 
         * @readonly
         * @type {string}
         * @memberOf appgroupRecord
         */
        public get RecordRoute(): string
        {
            return 'api/admin/app_group';
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions
        {
            let validationRules: JQueryValidation.RulesDictionary = {};

            validationRules[this.m_groupNameInput.id] = {
                required: true
            };

            let validationErrorMessages = {};
            validationErrorMessages[this.m_groupNameInput.id] = 'App group name is required.';
            
            let validationOptions: JQueryValidation.ValidationOptions =
                {
                    rules: validationRules,
                    errorPlacement: ((error: JQuery, element: JQuery): void =>
                    {
                        error.appendTo('#appgroup_form_errors');
                        $('#appgroup_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };

            return validationOptions;
        }

        /**
         * Creates an instance of appgroupRecord.
         * 
         * 
         * @memberOf appgroupRecord
         */
        constructor() 
        {
            super();
            this.ConstructFormReferences();
        }

        private ConstructFormReferences(): void
        {
            this.m_mainForm = document.querySelector('#editor_appgroup_form') as HTMLFormElement;
            this.m_editorTitle = document.querySelector('#appgroup_editing_title') as HTMLHeadingElement;
            this.m_editorOverlay = document.querySelector('#overlay_appgroup_editor') as HTMLDivElement;

            this.m_groupNameInput = document.querySelector('#editor_appgroup_name') as HTMLInputElement;
            this.m_sourceAppList = document.querySelector('#app_source_list') as HTMLSelectElement;
            this.m_targetAppList = document.querySelector('#app_target_list') as HTMLSelectElement;
            
            this.m_appsSourceToTargetBtn = document.querySelector('#apps_source_to_target') as HTMLButtonElement;
            this.m_appSourceToTargetBtn = document.querySelector('#app_source_to_target') as HTMLButtonElement;
            this.m_appTargetToSourceBtn = document.querySelector('#app_target_to_source') as HTMLButtonElement;
            this.m_appsTargetToSourceBtn = document.querySelector('#apps_target_to_source') as HTMLButtonElement;

            this.m_submitBtn = document.querySelector('#appgroup_editor_submit') as HTMLButtonElement;
            this.m_cancelBtn = document.querySelector('#appgroup_editor_cancel') as HTMLButtonElement;
            this.m_submitBtn.disabled = true;
            this.m_arrLeftApplications = [];
            this.m_arrRightApplications = [];
            this.m_groupApp = {};
            $(this.m_sourceAppList).empty();
            $(this.m_targetAppList).empty();
            
            $('#spiner_1').hide();
            this.InitButtonHandlers();            
            this.getRetrieveApplications();
        }
        private getRetrieveApplications() {
            $('#spiner_1').show();
            let ajaxSettings: JQueryAjaxSettings =
            {
                method: "GET",
                timeout: 60000,
                url: "api/admin/applications",
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                {
                    $('#spiner_1').hide();
                    this.m_arrLeftApplications = data;
                    if (this.m_appgroupId > 0) {
                        this.m_groupApp.forEach((app: any): void =>
                        {
                            let idx = -1;
                            let sel_seq_idx = 0;                            
                            this.m_arrLeftApplications.forEach((item: any) => {
                                idx ++;
                                if(item.id == app.app_id) {
                                    sel_seq_idx = idx;
                                    this.m_arrRightApplications.push(item);
                                }
                            });
                            if(sel_seq_idx >= 0) {
                                this.m_arrLeftApplications.splice(sel_seq_idx, 1);
                            }
                        });
                        this.drawRightApplications();
                    }
                    this.drawLeftApplications();

                    this.m_progressWait.Hide();
                    this.m_submitBtn.disabled = false;
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                {
                    $('#spiner_1').hide();
                    console.log(jqXHR.responseText);
                    console.log(errorThrown);
                    console.log(textStatus);

                    this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(() => 
                        {
                            this.m_progressWait.Hide();
                        }, 5000);

                    if (jqXHR.status > 399 && jqXHR.status < 500)
                    {
                        // Almost certainly auth related error. Redirect to login
                        // by signalling for logout.
                        //window.location.href = 'login.php?logout';
                    }
                    else
                    {
                        
                    }
                }
            }
            $.get(ajaxSettings);
        }
    
        private drawLeftApplications() {
            $(this.m_sourceAppList).empty();
            this.m_arrLeftApplications.forEach((item: any): void =>
            {
                var newOption = document.createElement("option");
                newOption.text = item.name;
                newOption.value = item.id;
                this.m_sourceAppList.add(newOption);
            });
        }

        private drawRightApplications() {
            $(this.m_targetAppList).empty();
            this.m_arrRightApplications.forEach((item: any): void =>
            {
                var newOption = document.createElement("option");
                newOption.text = item.name;
                newOption.value = item.id;
                this.m_targetAppList.add(newOption);
            });
        }

        public onMoveRightAllClicked(e: MouseEvent): void {
            this.m_arrLeftApplications.forEach((item: any): void =>
            {
                this.m_arrRightApplications.push(item);
            });
            this.m_arrLeftApplications = [];
            this.drawLeftApplications();
            this.drawRightApplications();
        }

        public onMoveLeftAllClicked(e: MouseEvent): void {
            this.m_arrRightApplications.forEach((item: any): void =>
            {
                this.m_arrLeftApplications.push(item);
            });
            this.m_arrRightApplications = [];
            this.drawLeftApplications();
            this.drawRightApplications();
        }

        public onMoveRightClicked(e: MouseEvent): void {
            if(this.m_sourceAppList.selectedIndex == -1) return;
            for(var i = 0; i < this.m_sourceAppList.selectedOptions.length; i++) {
                let sel_id = parseInt(this.m_sourceAppList.selectedOptions[i].value);
                let idx = -1;
                let sel_seq_idx = 0;
                this.m_arrLeftApplications.forEach((item: any): void =>
                {
                    idx ++;
                    if(item.id == sel_id) {
                        this.m_arrRightApplications.push(item);
                        sel_seq_idx = idx;
                        return;                    
                    }
                });
                if(sel_seq_idx > -1) {
                    this.m_arrLeftApplications.splice(sel_seq_idx,1);
                }
            }
            
            this.drawLeftApplications();
            this.drawRightApplications();
            
        }

        public onMoveLeftClicked(e: MouseEvent): void {
            if(this.m_targetAppList.selectedIndex == -1) return;
            for(var i = 0; i < this.m_targetAppList.selectedOptions.length; i++) {
                let sel_opt = this.m_targetAppList.selectedOptions[i];
                let sel_id = parseInt(sel_opt.value);
                let idx = -1;
                let find_id_to_remove = -1;
                this.m_arrRightApplications.forEach((item: any): void =>
                {
                    idx ++;
                    if(item.id == sel_id) {
                        find_id_to_remove = idx;
                        this.m_arrLeftApplications.push(item);
                        return;                    
                    }
                });

                if(find_id_to_remove > -1) {
                    this.m_arrRightApplications.splice(find_id_to_remove,1);
                }
                
            }
            this.drawLeftApplications();
            this.drawRightApplications();
        }
        private InitButtonHandlers(): void
        {
            this.m_cancelBtn.onclick = ((e: MouseEvent): any =>
            {
                this.StopEditing();
            });
            this.m_appsSourceToTargetBtn.onclick = ((e: MouseEvent) =>
            {
                this.onMoveRightAllClicked(e);        
            });

            this.m_appSourceToTargetBtn.onclick = ((e: MouseEvent) =>
            {
                this.onMoveRightClicked(e);        
            });

            this.m_appTargetToSourceBtn.onclick = ((e: MouseEvent) =>
            {
                this.onMoveLeftClicked(e);        
            });

            this.m_appsTargetToSourceBtn.onclick = ((e: MouseEvent) =>
            {
                this.onMoveLeftAllClicked(e);        
            });
        }

        protected LoadFromObject(data: Object): void
        {
            this.m_appgroupId = data['id'] as number;
            this.m_appGroupName = data['group_name'] as string;
            this.m_groupApp = data['group_app'] as object;
            this.m_dateRegistered = data['dt'] as string;
        }

        protected LoadFromForm(): void
        {
            this.m_appGroupName = this.m_groupNameInput.value;
        }

        public StartEditing(userData: Object = null): void
        {
            switch (userData == null)
            {

                case true:
                    {
                        // Creating a new object here.

                        this.m_editorTitle.innerText = "Add Application Group";
                        this.m_submitBtn.innerText = "Add";

                        this.m_mainForm.reset();
                    }
                    break;

                case false:
                    {
                        // Editing an existing object here.
                        this.LoadFromObject(userData);

                        this.m_editorTitle.innerText = "Edit Application Group";
                        this.m_submitBtn.innerText = "Save";
                        this.m_groupNameInput.value = this.m_appGroupName;                        
                    }
                    break;
            }

            this.m_mainForm.onsubmit = ((e: Event): any =>
            {

                let validateOpts = this.ValidationOptions;
                let validresult = $(this.m_mainForm).validate(validateOpts).form();

                if ($(this.m_mainForm).validate(validateOpts).valid())
                {
                    return this.OnFormSubmitClicked(e, userData == null);
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
        public ToObject(): Object
        {
            let obj =
                {
                    'id': this.m_appgroupId,
                    'group_name': this.m_appGroupName,
                    'apps': this.getSelectedAppIds(),
                    'dt': this.m_dateRegistered
                };

            return obj;
        }

        private getSelectedAppIds(): string {
            var str = '';
            
            this.m_arrRightApplications.forEach((item: any): void =>
            {
                if( str.length == 0) {
                    str = item.id;
                } else {
                    str += "," + item.id;
                }
            });
            return str;
        }
    }

}
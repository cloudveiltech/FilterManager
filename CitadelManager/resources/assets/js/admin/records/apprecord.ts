/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel
{

    export class AppRecord extends BaseRecord
    {
        //
        // ────────────────────────────────────────────────────────────────────────── I ──────────
        //   :::::: U S E R   D A T A   M E M B E R S : :  :   :    :     :        :          :
        // ────────────────────────────────────────────────────────────────────────────────────
        //        

        private m_appId: number;
        private m_appName: string;
        private m_appNotes: string;
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
         * @memberOf AppRecord
         */
        private m_editorOverlay: HTMLDivElement;
        private m_editorTitle: HTMLHeadingElement;
        private m_applicationNameInput: HTMLInputElement;
        private m_applicationNotesInput: HTMLInputElement;
        private m_leftSelect: HTMLSelectElement;
        private m_rightSelect: HTMLSelectElement;
        private m_btnMoveRightAll: HTMLButtonElement;
        private m_btnMoveRight: HTMLButtonElement;
        private m_btnMoveLeft:HTMLButtonElement;
        private m_btnMoveLeftAll:HTMLButtonElement;
        
        private m_submitBtn: HTMLButtonElement;
        private m_cancelBtn: HTMLButtonElement;


        private m_appGroups: any[];
        private m_selectedGroups: any[];
        private m_unselectedGroups: any[];
        /**
         * Gets the base API route from this record type.
         * 
         * @readonly
         * @type {string}
         * @memberOf AppRecord
         */
        public get RecordRoute(): string
        {
            return 'api/admin/app';
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions
        {
            let validationRules: JQueryValidation.RulesDictionary = {};

            validationRules[this.m_applicationNameInput.id] = {
                required: true
            };

            let validationErrorMessages = {};
            validationErrorMessages[this.m_applicationNameInput.id] = 'Application name is required.';
            
            let validationOptions: JQueryValidation.ValidationOptions =
                {
                    rules: validationRules,
                    errorPlacement: ((error: JQuery, element: JQuery): void =>
                    {
                        error.appendTo('#app_form_errors');
                        $('#app_form_errors').append('<br/>');
                    }),
                    messages: validationErrorMessages
                };

            return validationOptions;
        }

        /**
         * Creates an instance of AppRecord.
         * 
         * 
         * @memberOf AppRecord
         */
        constructor() 
        {
            super();
            this.ConstructFormReferences();
        }

        private ConstructFormReferences(): void
        {
            this.m_mainForm = document.querySelector('#editor_application_form') as HTMLFormElement;
            this.m_editorTitle = document.querySelector('#application_editing_title') as HTMLHeadingElement;
            this.m_editorOverlay = document.querySelector('#overlay_application_editor') as HTMLDivElement;
        
            this.m_applicationNameInput = document.querySelector('#editor_application_name') as HTMLInputElement;
            this.m_applicationNotesInput = document.querySelector('#editor_application_notes') as HTMLInputElement;
            this.m_leftSelect   = document.querySelector("#editor_application_source_list") as HTMLSelectElement;
            this.m_rightSelect  = document.querySelector("#editor_application_target_list") as HTMLSelectElement;

            this.m_btnMoveRightAll = document.querySelector("#editor_application_right_all_btn") as HTMLButtonElement;
            this.m_btnMoveRight    = document.querySelector("#editor_application_right_btn") as HTMLButtonElement;
            this.m_btnMoveLeft   = document.querySelector("#editor_application_left_btn") as HTMLButtonElement;
            this.m_btnMoveLeftAll= document.querySelector("#editor_application_left_all_btn") as HTMLButtonElement;

            this.m_submitBtn = document.querySelector('#application_editor_submit') as HTMLButtonElement;
            this.m_cancelBtn = document.querySelector('#application_editor_cancel') as HTMLButtonElement;
            this.InitButtonHandlers();
        }
        private getRetrieveData(flag:boolean) {
            let url = 'api/admin/get_appgroup_data';
            if( flag ) {
                url += '/' + this.m_appId;
            }
            $("#spiner_5").show();
            this.m_submitBtn.disabled = true;
            let ajaxSettings: JQueryAjaxSettings =
            {
                method: "GET",
                timeout: 60000,
                url: url,
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                {                    
                    this.m_appGroups = data.app_groups;
                    if(flag) {
                        let selected_app_groups = data.selected_app_groups;
                        this.m_unselectedGroups = [];
                        this.m_selectedGroups = [];
                        if(selected_app_groups.length > 0) {
                            this.m_appGroups.forEach((app_group: any): void =>
                            {                                
                                this.m_unselectedGroups.push(app_group.id);
                            });
                            selected_app_groups.forEach((app_group: any): void =>
                            {
                                this.m_selectedGroups.push(app_group.app_group_id);
                                let pos = this.m_unselectedGroups.indexOf(app_group.app_group_id);
                                this.m_unselectedGroups.splice(pos, 1);
                            });   
                        } else {
                            this.m_appGroups.forEach((app_group: any): void =>
                            {
                                this.m_unselectedGroups.push(app_group.id);
                            });    
                        }
                    } else {
                        this.m_unselectedGroups = [];
                        this.m_selectedGroups = [];
                        this.m_appGroups.forEach((app_group: any): void =>
                        {
                            this.m_unselectedGroups.push(app_group.id);
                        });
                    }
                    this.drawLeftGroups();
                    this.drawRightGroups();
                    $("#spiner_5").hide();
                    this.m_submitBtn.disabled = false;
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                {
                    this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(() => { this.m_progressWait.Hide(); }, 5000);
                    if (jqXHR.status > 399 && jqXHR.status < 500){                        
                    } else {                        
                    }
                }
            }
            $.get(ajaxSettings);
        } 
        private InitButtonHandlers(): void
        {

            this.m_btnMoveRightAll.onclick = ((e: MouseEvent) =>
            {
                this.onMoveRightAllClicked(e);        
            });

            this.m_btnMoveRight.onclick = ((e: MouseEvent) =>
            {
                this.onMoveRightClicked(e);        
            });

            this.m_btnMoveLeft.onclick = ((e: MouseEvent) =>
            {
                this.onMoveLeftClicked(e);        
            });

            this.m_btnMoveLeftAll.onclick = ((e: MouseEvent) =>
            {
                this.onMoveLeftAllClicked(e);        
            });
            this.m_cancelBtn.onclick = ((e: MouseEvent): any =>
            {
                this.StopEditing();
            });
        }
        public onMoveRightAllClicked(e: MouseEvent): void {
            this.m_unselectedGroups.forEach((group_id): void =>
            {
                this.m_selectedGroups.push(group_id);
            });
            this.m_unselectedGroups = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        }

        public onMoveLeftAllClicked(e: MouseEvent): void {
            this.m_selectedGroups.forEach((group_id): void =>
            {
                this.m_unselectedGroups.push(group_id);
            });
            this.m_selectedGroups = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        }

        public onMoveRightClicked(e: MouseEvent): void {
            if(this.m_leftSelect.selectedIndex == -1) return;
            for (var i = 0; i < this.m_leftSelect.selectedOptions.length; i++) {
                let sel_id = parseInt(this.m_leftSelect.selectedOptions[i].value);                
                let sel_seq_idx = this.m_unselectedGroups.indexOf(sel_id);
                this.m_unselectedGroups.splice(sel_seq_idx,1);
                this.m_selectedGroups.push(sel_id);
            }
            
            this.drawLeftGroups();
            this.drawRightGroups();
            
        }

        public onMoveLeftClicked(e: MouseEvent): void {
            if(this.m_rightSelect.selectedIndex == -1) return;
            for (var i = 0; i < this.m_rightSelect.selectedOptions.length; i++) {                
                let sel_id = parseInt(this.m_rightSelect.selectedOptions[i].value);
                let sel_seq_idx = this.m_selectedGroups.indexOf(sel_id);
                this.m_selectedGroups.splice(sel_seq_idx,1);
                this.m_unselectedGroups.push(sel_id);
            }
            this.drawLeftGroups();
            this.drawRightGroups();
        }

        private getGroupItem(group_id) {
            var group_item = null;
            this.m_appGroups.forEach((item: any): void =>
            {
                if(item.id == group_id) {
                    group_item = item;
                    return;
                }
            });
            return group_item;
        }

        private drawLeftGroups() {
            $(this.m_leftSelect).empty();            
            this.m_unselectedGroups.forEach((group_id): void =>
            {
                var newOption = document.createElement("option");
                var item = this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;                
                this.m_leftSelect.add(newOption);
            });
        }

        private drawRightGroups() {
            $(this.m_rightSelect).empty();
            this.m_selectedGroups.forEach((group_id): void =>
            {
                var newOption = document.createElement("option");
                var item = this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;                
                this.m_rightSelect.add(newOption);
            });
        }

        protected LoadFromObject(data: Object): void
        {

            this.m_appId = data['id'] as number;
            this.m_appName = data['name'] as string;
            this.m_appNotes = data['notes'] as string;
            this.m_dateRegistered = data['dt'] as string;
        }

        protected LoadFromForm(): void
        {
            this.m_appName = this.m_applicationNameInput.value;
            this.m_appNotes = this.m_applicationNotesInput.value;
        }

        public StartEditing(userData: Object = null): void
        {
            switch (userData == null)
            {
                case true:
                    {
                        // Creating a new object here.
                        this.m_editorTitle.innerText = "Add Application";
                        this.m_submitBtn.innerText = "Add";
                        this.m_mainForm.reset();
                        this.getRetrieveData(false);
                    }
                    break;

                case false:
                    {
                        // Editing an existing object here.
                        this.LoadFromObject(userData);

                        this.m_editorTitle.innerText = "Edit Application";
                        this.m_submitBtn.innerText = "Save";
                        this.m_applicationNameInput.value = this.m_appName;
                        this.m_applicationNotesInput.value = this.m_appNotes;
                        this.getRetrieveData(true);
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
                    'id': this.m_appId,
                    'name': this.m_appName,
                    'notes': this.m_appNotes,
                    'dt': this.m_dateRegistered,
                    'assigned_appgroup': this.m_selectedGroups
                };
            return obj;
        }
    }
}
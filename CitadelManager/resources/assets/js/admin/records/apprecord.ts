/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel {

    export class AppRecord extends BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        ERROR_MESSAGE_APP_NAME      = 'Application name is required.';

        MESSAGE_ACTION_FAILED       = 'Error reported by the server during action.\n %ERROR_MSG% \nCheck console for more information.';

        TITLE_ACTION_FAILED         = 'Action Failed';
        TITLE_NEW_APP               = 'Add Application';
        TITLE_EDIT_APP              = 'Edit Application';

        BTN_LABEL_ADD_APP           = 'Add';
        BTN_LABEL_EDIT_APP          = 'Save';

        ERROR_MESSAGE_DELAY_TIME    = 5000;
        FADE_IN_DELAY_TIME          = 200;

        URL_ROUTE                   = 'api/admin/app';
        URL_APPGROUP_DATA           = 'api/admin/get_appgroup_data'

        // ─────────────────────────────────────────────────────────
        //   :::::: A P P    R E C O R D     M E M B E R S ::::::
        // ─────────────────────────────────────────────────────────
        private m_appId             : number;
        private m_appName           : string;
        private m_appNotes          : string;
        private m_registeredAt      : string;
        private m_platformName      : string;

        private m_appGroups         : any[];
        private m_selectedGroups    : any[];
        private m_unselectedGroups  : any[];

        //
        // ─────────────────────────────────────────────────────────
        //   :::::: E D I T O R   HT M L   E L E M E N T S ::::::
        // ─────────────────────────────────────────────────────────
        //

        private m_mainForm          : HTMLFormElement;

        private m_editorOverlay     : HTMLDivElement;
        private m_editorTitle       : HTMLHeadingElement;

        private m_inputAppName      : HTMLInputElement;
        private m_inputAppNotes     : HTMLInputElement;
        private m_inputPlatfornName : HTMLInputElement;


        private m_selectLeft        : HTMLSelectElement;
        private m_selectRight       : HTMLSelectElement;

        private m_btn_L_R_All       : HTMLButtonElement;
        private m_btn_L_R_One       : HTMLButtonElement;
        private m_btn_R_L_One       : HTMLButtonElement;
        private m_btn_R_L_All       : HTMLButtonElement;

        private m_btnSubmit         : HTMLButtonElement;
        private m_btnCancel         : HTMLButtonElement;

        public get RecordRoute(): string {
            return this.URL_ROUTE;
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions {
            let validationRules: JQueryValidation.RulesDictionary = {};

            validationRules[this.m_inputAppName.id] = {
                required: true
            };

            let validationErrorMessages = {};
            validationErrorMessages[this.m_inputAppName.id] = this.ERROR_MESSAGE_APP_NAME;

            let validationOptions: JQueryValidation.ValidationOptions = {
                rules: validationRules,
                errorPlacement: ((error: JQuery, element: JQuery): void => {
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
        constructor() {
            super();
            this.ConstructFormReferences();
        }

        private ConstructFormReferences(): void {
            this.m_mainForm                 = document.querySelector('#editor_application_form') as HTMLFormElement;
            this.m_editorTitle              = document.querySelector('#application_editing_title') as HTMLHeadingElement;
            this.m_editorOverlay            = document.querySelector('#overlay_application_editor') as HTMLDivElement;

            this.m_inputAppName             = document.querySelector('#editor_application_name') as HTMLInputElement;
            this.m_inputAppNotes            = document.querySelector('#editor_application_notes') as HTMLInputElement;
            this.m_selectLeft               = document.querySelector("#editor_application_source_list") as HTMLSelectElement;
            this.m_selectRight              = document.querySelector("#editor_application_target_list") as HTMLSelectElement;

            this.m_btn_L_R_All              = document.querySelector("#editor_application_right_all_btn") as HTMLButtonElement;
            this.m_btn_L_R_One              = document.querySelector("#editor_application_right_btn") as HTMLButtonElement;
            this.m_btn_R_L_One              = document.querySelector("#editor_application_left_btn") as HTMLButtonElement;
            this.m_btn_R_L_All              = document.querySelector("#editor_application_left_all_btn") as HTMLButtonElement;

            this.m_btnSubmit                = document.querySelector('#application_editor_submit') as HTMLButtonElement;
            this.m_btnCancel                = document.querySelector('#application_editor_cancel') as HTMLButtonElement;
            this.m_inputPlatfornName         = document.querySelector('#app_platform_name') as HTMLInputElement;
            this.InitButtonHandlers();
        }

        private _getAppGroupData(flag: boolean) {
            let url = this.URL_APPGROUP_DATA;
            if (flag) {
                url += '/' + this.m_appId;
            }

            $("#spiner_5").show();

            this.m_btnSubmit.disabled = true;
            let ajaxSettings: JQueryAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: url,
                data: {},
                success: (data: any): any => {
                    this.m_appGroups = data.app_groups;

                    if (flag) {
                        let selected_app_groups = data.selected_app_groups;
                        this.m_unselectedGroups = [];
                        this.m_selectedGroups = [];
                        if (selected_app_groups.length > 0) {
                            this.m_appGroups.forEach((app_group: any): void => {
                                this.m_unselectedGroups.push(app_group.id);
                            });
                            selected_app_groups.forEach((app_group: any): void => {
                                this.m_selectedGroups.push(app_group.app_group_id);
                                let pos = this.m_unselectedGroups.indexOf(app_group.app_group_id);
                                this.m_unselectedGroups.splice(pos, 1);
                            });
                        } else {
                            this.m_appGroups.forEach((app_group: any): void => {
                                this.m_unselectedGroups.push(app_group.id);
                            });
                        }
                    } else {
                        this.m_unselectedGroups = [];
                        this.m_selectedGroups = [];
                        this.m_appGroups.forEach((app_group: any): void => {
                            this.m_unselectedGroups.push(app_group.id);
                        });
                    }

                    this.drawLeftGroups();
                    this.drawRightGroups();

                    $("#spiner_5").hide();
                    this.m_btnSubmit.disabled = false;

                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    this.m_progressWait.Show(this.TITLE_ACTION_FAILED, this.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));
                    setTimeout(() => {
                        this.m_progressWait.Hide();
                    }, this.ERROR_MESSAGE_DELAY_TIME);
                }
            }

            $.ajax(ajaxSettings);
        }

        private InitButtonHandlers(): void {

            this.m_btn_L_R_All.onclick = ((e: MouseEvent) => {
                this.onMoveRightAllClicked(e);
            });

            this.m_btn_L_R_One.onclick = ((e: MouseEvent) => {
                this.onMoveRightClicked(e);
            });

            this.m_btn_R_L_One.onclick = ((e: MouseEvent) => {
                this.onMoveLeftClicked(e);
            });

            this.m_btn_R_L_All.onclick = ((e: MouseEvent) => {
                this.onMoveLeftAllClicked(e);
            });
            this.m_btnCancel.onclick = ((e: MouseEvent): any => {
                this.StopEditing();
            });
        }

        public onMoveRightAllClicked(e: MouseEvent): void {
            this.m_unselectedGroups.forEach((group_id): void => {
                this.m_selectedGroups.push(group_id);
            });

            this.m_unselectedGroups = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        }

        public onMoveLeftAllClicked(e: MouseEvent): void {
            this.m_selectedGroups.forEach((group_id): void => {
                this.m_unselectedGroups.push(group_id);
            });

            this.m_selectedGroups = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        }

        public onMoveRightClicked(e: MouseEvent): void {
            if (this.m_selectLeft.selectedIndex == -1) return;

            for (var i = 0; i < this.m_selectLeft.selectedOptions.length; i++) {
                let sel_id = parseInt(this.m_selectLeft.selectedOptions[i].value);
                let sel_seq_idx = this.m_unselectedGroups.indexOf(sel_id);
                this.m_unselectedGroups.splice(sel_seq_idx, 1);
                this.m_selectedGroups.push(sel_id);
            }

            this.drawLeftGroups();
            this.drawRightGroups();
        }

        public onMoveLeftClicked(e: MouseEvent): void {
            if (this.m_selectRight.selectedIndex == -1) return;

            for (var i = 0; i < this.m_selectRight.selectedOptions.length; i++) {
                let sel_id = parseInt(this.m_selectRight.selectedOptions[i].value);
                let sel_seq_idx = this.m_selectedGroups.indexOf(sel_id);
                this.m_selectedGroups.splice(sel_seq_idx, 1);
                this.m_unselectedGroups.push(sel_id);
            }

            this.drawLeftGroups();
            this.drawRightGroups();
        }

        private getGroupItem(group_id) {
            var group_item = null;

            this.m_appGroups.forEach((item: any): void => {
                if (item.id == group_id) {
                    group_item = item;
                    return;
                }
            });

            return group_item;
        }

        private drawLeftGroups() {
            $(this.m_selectLeft).empty();

            this.m_unselectedGroups.forEach((group_id): void => {
                var newOption = document.createElement("option");
                var item = this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;
                this.m_selectLeft.add(newOption);
            });
        }

        private drawRightGroups() {
            $(this.m_selectRight).empty();

            this.m_selectedGroups.forEach((group_id): void => {
                var newOption = document.createElement("option");
                var item = this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;
                this.m_selectRight.add(newOption);
            });
        }

        protected LoadFromObject(data: Object): void {
            this.m_appId            = data['id'] as number;
            this.m_appName          = data['name'] as string;
            this.m_appNotes         = data['notes'] as string;
            this.m_registeredAt     = data['dt'] as string;

            this.m_platformName     = data['platform_name'] as string;
        }

        protected LoadFromForm(): void {
            this.m_appName          = this.m_inputAppName.value;
            this.m_appNotes         = this.m_inputAppNotes.value;
            this.m_platformName     = this.m_inputPlatfornName.value;
        }

        public StartEditing(userData: Object = null): void {
            switch (userData == null) {
                case true:
                    {
                        // Creating a new object here.
                        this.m_editorTitle.innerText = this.TITLE_NEW_APP;
                        this.m_btnSubmit.innerText = this.BTN_LABEL_ADD_APP;
                        this.m_inputPlatfornName.value = "WIN";
                        this.m_mainForm.reset();
                        this._getAppGroupData(false);
                    }
                    break;

                case false:
                    {
                        // Editing an existing object here.
                        this.LoadFromObject(userData);

                        this.m_editorTitle.innerText = this.TITLE_EDIT_APP;
                        this.m_btnSubmit.innerText = this.BTN_LABEL_EDIT_APP;
                        this.m_inputAppName.value = this.m_appName;
                        this.m_inputAppNotes.value = this.m_appNotes;
                        this.m_inputPlatfornName.value = this.m_platformName;

                        this._getAppGroupData(true);
                    }
                    break;
            }

            this.m_mainForm.onsubmit = ((e: Event): any => {

                let validateOpts = this.ValidationOptions;
                let validresult = $(this.m_mainForm).validate(validateOpts).form();

                if ($(this.m_mainForm).validate(validateOpts).valid()) {
                    return this.OnFormSubmitClicked(e, userData == null);
                }

                return false;
            });

            // Show the editor.
            $(this.m_editorOverlay).fadeIn(this.FADE_IN_DELAY_TIME);
        }

        public StopEditing(): void {
            $(this.m_editorOverlay).fadeOut(this.FADE_IN_DELAY_TIME);
        }

        public ToObject(): Object {
            let obj = {
                'id'                : this.m_appId,
                'name'              : this.m_appName,
                'notes'             : this.m_appNotes,
                'dt'                : this.m_registeredAt,
                'assigned_appgroup' : this.m_selectedGroups,
                'platform_name'     : this.m_platformName
            };

            return obj;
        }
    }
}
/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel {

    export class AppGroupRecord extends BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        ERROR_MESSAGE_GROUP_NAME        = 'App group name is required.';

        MESSAGE_ACTION_FAILED           = 'Error reported by the server during action.\n %ERROR_MSG% \nCheck console for more information.';

        TITLE_ACTION_FAILED             = 'Action Failed';
        TITLE_NEW_APP_GROUP             = 'Add Application Group';
        TITLE_EDIT_APP_GROUP            = 'Edit Application Group';

        BTN_LABEL_CREATE_APP_GROUP      = 'Create';
        BTN_LABEL_EDIT_APP_GROUP        = 'Save';

        ERROR_MESSAGE_DELAY_TIME        = 5000;
        FADE_IN_DELAY_TIME              = 200;

        URL_ROUTE                       = 'api/admin/app_group';
        URL_APPLICATIONS                = 'api/admin/applications';

        SOURCE_LIST_SELECTORS = ["#app_source_list_filter", "#app_source_list"];
        TARGET_LIST_SELECTORS = ["#app_target_list_filter", "#app_target_list"];

        // ────────────────────────────────────────────────────
        //   :::::: A P P G R O U P     M E M B E R S  ::::::
        // ────────────────────────────────────────────────────
        private m_appGroupId            : number;
        private m_appGroupName          : string;
        private m_selectedApps          : string;
        private m_registeredAt          : string;
        private m_os                    : string;
        private m_leftApps              : any[];
        private m_rightApps             : any[];

        private m_groupApp              : any;

        // ─────────────────────────────────────────────────────────
        //   :::::: E D I T O R   H T M L   E L E M E N T S ::::::
        // ─────────────────────────────────────────────────────────

        private m_mainForm              : HTMLFormElement;

        private m_editorOverlay         : HTMLDivElement;
        private m_editorTitle           : HTMLHeadingElement;

        private m_inputGroupName        : HTMLInputElement;

        private m_selectSource          : HTMLSelectElement;
        private m_selectTarget          : HTMLSelectElement;

        private m_btn_S_T_All           : HTMLButtonElement;
        private m_btn_S_T_One           : HTMLButtonElement;
        private m_btn_T_S_One           : HTMLButtonElement;
        private m_btn_T_S_All           : HTMLButtonElement;

        private m_btnSubmit             : HTMLButtonElement;
        private m_btnCancel             : HTMLButtonElement;

        constructor() {
            super();
            this.ConstructFormReferences();
        }

        public get RecordRoute(): string {
            return this.URL_ROUTE;
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions {
            let validationRules: JQueryValidation.RulesDictionary = {};

            validationRules[this.m_inputGroupName.id] = {
                required: true
            };

            let validationErrorMessages = {};
            validationErrorMessages[this.m_inputGroupName.id] = this.ERROR_MESSAGE_GROUP_NAME;

            let validationOptions: JQueryValidation.ValidationOptions = {
                rules: validationRules,
                errorPlacement: ((error: JQuery, element: JQuery): void => {
                    error.appendTo('#appgroup_form_errors');
                    $('#appgroup_form_errors').append('<br/>');
                }),
                messages: validationErrorMessages
            };

            return validationOptions;
        }

        private ConstructFormReferences(): void {
            this.m_mainForm             = document.querySelector('#editor_appgroup_form') as HTMLFormElement;
            this.m_editorTitle          = document.querySelector('#appgroup_editing_title') as HTMLHeadingElement;
            this.m_editorOverlay        = document.querySelector('#overlay_appgroup_editor') as HTMLDivElement;

            this.m_inputGroupName       = document.querySelector('#editor_appgroup_name') as HTMLInputElement;
            this.m_selectSource         = document.querySelector('#app_source_list') as HTMLSelectElement;
            this.m_selectTarget         = document.querySelector('#app_target_list') as HTMLSelectElement;

            this.m_btn_S_T_All          = document.querySelector('#apps_source_to_target') as HTMLButtonElement;
            this.m_btn_S_T_One          = document.querySelector('#app_source_to_target') as HTMLButtonElement;
            this.m_btn_T_S_One          = document.querySelector('#app_target_to_source') as HTMLButtonElement;
            this.m_btn_T_S_All          = document.querySelector('#apps_target_to_source') as HTMLButtonElement;

            this.m_btnSubmit            = document.querySelector('#appgroup_editor_submit') as HTMLButtonElement;
            this.m_btnCancel            = document.querySelector('#appgroup_editor_cancel') as HTMLButtonElement;
            this.m_btnSubmit.disabled = true;
            this.m_leftApps             = [];
            this.m_rightApps            = [];
            this.m_groupApp             = {};

            $(this.m_selectSource).empty();
            $(this.m_selectTarget).empty();

            $('#spiner_1').hide();
            this.InitButtonHandlers();
            this._getApplications();
        }

        private _getApplications() {

            $('#spiner_1').show();

            let ajaxSettings: JQueryAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: this.URL_APPLICATIONS,
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    $('#spiner_1').hide();

                    this.m_leftApps = data;
                    if (this.m_appGroupId > 0) {
                        this.m_groupApp.forEach((app: any): void => {
                            let idx = -1;
                            let sel_seq_idx = 0;
                            this.m_leftApps.forEach((item: any) => {
                                idx++;
                                if (item.id == app.app_id) {
                                    sel_seq_idx = idx;
                                    this.m_rightApps.push(item);
                                }
                            });
                            if (sel_seq_idx >= 0) {
                                this.m_leftApps.splice(sel_seq_idx, 1);
                            }
                        });
                        this._drawApplications(this.m_rightApps, this.m_selectTarget);
                    }
                    this._drawApplications(this.m_leftApps, this.m_selectSource);

                    this.m_progressWait.Hide();
                    this.m_btnSubmit.disabled = false;

                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    $('#spiner_1').hide();

                    this.m_progressWait.Show(this.TITLE_ACTION_FAILED, this.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));
                    setTimeout(() => {
                        this.m_progressWait.Hide();
                    }, this.ERROR_MESSAGE_DELAY_TIME);
                }
            }

            $.ajax(ajaxSettings);
        }

        private _getSelectedAppIds(): string {
            var str = '';

            this.m_rightApps.forEach((item: any): void => {
                if (str.length == 0) {
                    str = item.id;
                } else {
                    str += "," + item.id;
                }
            });
            return str;
        }

        private _drawApplications(data: any[], selectBox: HTMLSelectElement): void {
            $(selectBox).empty();
            data.forEach((item: any): void => {
                var newOption = document.createElement("option");
                newOption.text = item.name;
                newOption.value = item.id;
                selectBox.add(newOption);
            });
        }

        public onMoveRightAllClicked(e: MouseEvent): void {
            this.m_leftApps.forEach((item: any): void => {
                this.m_rightApps.push(item);
            });
            this.m_leftApps = [];

            this._drawApplications(this.m_leftApps, this.m_selectSource);
            this._drawApplications(this.m_rightApps, this.m_selectTarget);

            this.filterLists();
        }

        public onMoveLeftAllClicked(e: MouseEvent): void {
            this.m_rightApps.forEach((item: any): void => {
                this.m_leftApps.push(item);
            });
            this.m_rightApps = [];

            this._drawApplications(this.m_leftApps, this.m_selectSource);
            this._drawApplications(this.m_rightApps, this.m_selectTarget);

            this.filterLists();
        }

        public onMoveRightClicked(e: MouseEvent): void {
            if (this.m_selectSource.selectedIndex == -1) return;

            for (var i = 0; i < this.m_selectSource.selectedOptions.length; i++) {
                let sel_id = parseInt(this.m_selectSource.selectedOptions[i].value);
                let idx = -1;
                let sel_seq_idx = 0;
                this.m_leftApps.forEach((item: any): void => {
                    idx++;
                    if (item.id == sel_id) {
                        this.m_rightApps.push(item);
                        sel_seq_idx = idx;
                        return;
                    }
                });
                if (sel_seq_idx > -1) {
                    this.m_leftApps.splice(sel_seq_idx, 1);
                }
            }

            this._drawApplications(this.m_leftApps, this.m_selectSource);
            this._drawApplications(this.m_rightApps, this.m_selectTarget);

            this.filterLists();
        }

        public onMoveLeftClicked(e: MouseEvent): void {
            if (this.m_selectTarget.selectedIndex == -1) return;

            for (var i = 0; i < this.m_selectTarget.selectedOptions.length; i++) {
                let sel_opt = this.m_selectTarget.selectedOptions[i];
                let sel_id = parseInt(sel_opt.value);
                let idx = -1;
                let find_id_to_remove = -1;
                this.m_rightApps.forEach((item: any): void => {
                    idx++;
                    if (item.id == sel_id) {
                        find_id_to_remove = idx;
                        this.m_leftApps.push(item);
                        return;
                    }
                });

                if (find_id_to_remove > -1) {
                    this.m_rightApps.splice(find_id_to_remove, 1);
                }

            }

            this._drawApplications(this.m_leftApps, this.m_selectSource);
            this._drawApplications(this.m_rightApps, this.m_selectTarget);

            this.filterLists();
        }

        private InitButtonHandlers(): void {
            this.m_btnCancel.onclick = ((e: MouseEvent): any => {
                this.StopEditing();
            });
            this.m_btn_S_T_All.onclick = ((e: MouseEvent) => {
                this.onMoveRightAllClicked(e);
            });

            this.m_btn_S_T_One.onclick = ((e: MouseEvent) => {
                this.onMoveRightClicked(e);
            });

            this.m_btn_T_S_One.onclick = ((e: MouseEvent) => {
                this.onMoveLeftClicked(e);
            });

            this.m_btn_T_S_All.onclick = ((e: MouseEvent) => {
                this.onMoveLeftAllClicked(e);
            });
        }

        protected LoadFromObject(data: Object): void {
            this.m_appGroupId           = data['id'] as number;
            this.m_appGroupName         = data['group_name'] as string;
            this.m_groupApp             = data['group_app'] as object;
            this.m_registeredAt         = data['dt'] as string;
            this.m_os                   = data['os_platform'] as string;
        }

        protected LoadFromForm(): void {
            this.m_appGroupName         = this.m_inputGroupName.value;
        }

        public ToObject(): Object {
            let obj = {
                'id'            : this.m_appGroupId,
                'group_name'    : this.m_appGroupName,
                'apps'          : this._getSelectedAppIds(),
                'dt'            : this.m_registeredAt
            };

            return obj;
        }

        public StartEditing(userData: Object = null): void {
            switch (userData == null) {

                case true:
                    {
                        // Creating a new object here.

                        this.m_editorTitle.innerText = this.TITLE_NEW_APP_GROUP;
                        this.m_btnSubmit.innerText = this.BTN_LABEL_CREATE_APP_GROUP;

                        this.m_mainForm.reset();
                    }
                    break;

                case false:
                    {
                        // Editing an existing object here.
                        this.LoadFromObject(userData);

                        this.m_editorTitle.innerText = this.TITLE_EDIT_APP_GROUP;
                        this.m_btnSubmit.innerText = this.BTN_LABEL_EDIT_APP_GROUP;
                        this.m_inputGroupName.value = this.m_appGroupName;
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

            $(this.m_editorOverlay).fadeIn(this.FADE_IN_DELAY_TIME);

            this.SetFilterListHandler(this.SOURCE_LIST_SELECTORS[0], this.SOURCE_LIST_SELECTORS[1]);
            this.SetFilterListHandler(this.TARGET_LIST_SELECTORS[0], this.TARGET_LIST_SELECTORS[1]);
        }

        private filterLists() {
            this.FilterList(this.SOURCE_LIST_SELECTORS[0], this.SOURCE_LIST_SELECTORS[1]);
            this.FilterList(this.TARGET_LIST_SELECTORS[0], this.TARGET_LIST_SELECTORS[1]);
        }
        public StopEditing(): void {
            $(this.m_editorOverlay).fadeOut(this.FADE_IN_DELAY_TIME);
        }
    }
}
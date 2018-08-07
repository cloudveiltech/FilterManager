/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel {

    export class UserRecord extends BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        ERROR_MESSAGE_EMAIL = 'A valid email address is required.';
        ERROR_MESSAGE_PASSWORD = 'Password must be specified and match the password confirmation field.';
        ERROR_MESSAGE_CONFIRM_PASSWORD = 'Password confirmation must be specified and match the password field.';
        ERROR_MESSAGE_ACTIVATION = 'Total number of permitted activations must be specified.';

        ERROR_MESSAGE_DELAY_TIME = 5000;
        FADE_IN_DELAY_TIME = 200;

        MESSAGE_BLOCK_ACTIVATION_CONFIRM = 'Are you sure you want to delete this activation and block the token?  The user will need to sign in again.';
        MESSAGE_DELETE_ACTIVATION_CONFIRM = 'Are you sure you want to delete this activation?';
        MESSAGE_ACTION_FAILED = 'Error reported by the server during action.\n %ERROR_MSG% \nCheck console for more information.';

        TITLE_NEW_USER = 'Create New User';
        TITLE_EDIT_USER = 'Edit User';
        TITLE_ACTION_FAILED = 'Action Failed';

        BTN_NEW_USER = 'Create';
        BTN_EDIT_USER = 'Save';

        URL_ROUTE = 'api/admin/users';
        URL_UPDATE_ALERT = 'api/admin/activations/update_alert';
        URL_UPDATE_CHECK_IN_DAYS = 'api/admin/activations/update_check_in_days';
        URL_DELETE_ACTIVATION = 'api/admin/user_activations/delete';
        URL_BLOCK_ACTIVATION = 'api/admin/user_activations/block';


        // ───────────────────────────────────────────────────
        //   :::::: U S E R   D A T A   M E M B E R S ::::::
        // ───────────────────────────────────────────────────
        private m_id                : number;
        private m_fullName          : string;
        private m_email             : string;
        private m_password ?        : string;
        private m_groupId           : number;
        private m_roleId            : number;
        private m_numActivations    : number;
        private m_customerId        : number;
        private m_isActive          : number;
        private m_reportLevel       : number;
        private m_registeredAt      : string;

        // ─────────────────────────────────────────────────────────
        //   :::::: E D I T O R   H T M L   E L E M E N T S ::::::
        // ─────────────────────────────────────────────────────────
        private m_mainForm          : HTMLFormElement;

        private m_editorOverlay     : HTMLDivElement;
        private m_editorTitle       : HTMLHeadingElement;

        // ─────────────────────────────────────────────
        //   ::::: I N P U T    E L E M E N T S ::::::
        // ─────────────────────────────────────────────
        private m_inputFullName         : HTMLInputElement;
        private m_inputEmail            : HTMLInputElement;
        private m_inputPassword         : HTMLInputElement;
        private m_inputPasswordConfirm  : HTMLInputElement;
        private m_inputActivationCount  : HTMLInputElement;
        private m_inputCustomerId       : HTMLInputElement;
        private m_inputIsActive         : HTMLInputElement;
        private m_inputReportLevel      : HTMLInputElement;

        // ───────────────────────────────────────────────
        //   ::::: S E L E C T    E L E M E N T S ::::::
        // ───────────────────────────────────────────────
        private m_selectGroup       : HTMLSelectElement;
        private m_selectRole        : HTMLSelectElement;

        // ───────────────────────────────────────────────
        //   ::::: B U T T O N    E L E M E N T S ::::::
        // ───────────────────────────────────────────────
        private m_btnSubmit         : HTMLButtonElement;
        private m_btnCancel         : HTMLButtonElement;

        private jsonData: any[];

        // ─────────────────────────────────────────────────
        //   ::::: A C T I V A T I O N    T A B L E ::::::
        // ─────────────────────────────────────────────────
        private m_tableSettings: DataTables.Settings;
        private m_tableColumns: DataTables.ColumnSettings[];
        private m_ActivationTables: DataTables.Api;

        // ─────────────────────────────────────────────────
        //   ::::: M E M B E R     F U N C T I O N S ::::::
        // ─────────────────────────────────────────────────

        constructor() {
            super();
            this.ConstructFormReferences();
        }

        private ConstructFormReferences(): void {
            this.m_mainForm = document.querySelector('#editor_user_form') as HTMLFormElement;
            this.m_editorTitle = document.querySelector('#user_editing_title') as HTMLHeadingElement;
            this.m_editorOverlay = document.querySelector('#overlay_user_editor') as HTMLDivElement;

            this.m_inputEmail = document.querySelector('#editor_user_input_username') as HTMLInputElement;
            this.m_inputFullName = document.querySelector('#editor_user_input_user_full_name') as HTMLInputElement;
            this.m_inputPassword = document.querySelector('#editor_user_input_password') as HTMLInputElement;
            this.m_inputPasswordConfirm = document.querySelector('#editor_user_input_password_confirm') as HTMLInputElement;
            this.m_inputActivationCount = document.querySelector('#editor_user_input_num_activations') as HTMLInputElement;
            this.m_selectGroup = document.querySelector('#editor_user_input_group_id') as HTMLSelectElement;
            this.m_selectRole = document.querySelector('#editor_user_input_role_id') as HTMLSelectElement;
            this.m_inputIsActive = document.querySelector('#editor_user_input_isactive') as HTMLInputElement;
            this.m_inputCustomerId = document.querySelector('#editor_user_input_customer_id') as HTMLInputElement;
            this.m_inputReportLevel = document.querySelector('#editor_user_report_level') as HTMLInputElement;
            this.m_btnSubmit = document.querySelector('#user_editor_submit') as HTMLButtonElement;
            this.m_btnCancel = document.querySelector('#user_editor_cancel') as HTMLButtonElement;

            this.InitButtonHandlers();
        }

        public get RecordRoute(): string {
            return this.URL_ROUTE;
        }

        // ─────────────────────────────────────────────────────────
        //   ::::: G E T / R E M O V E     F U N C T I O N S ::::::
        // ─────────────────────────────────────────────────────────
        private getIdFromElementId(element_id: string): number {
            // styled <ID>_<NUMBER>
            var arr_id = element_id.split('_');

            return arr_id.length > 1 ? parseInt(arr_id[1]) : -1;
        }

        private getValueFromSelect(selectBox: HTMLSelectElement): number {
            let val = -1;
            if (selectBox.selectedIndex != -1) {
                let option = selectBox.options[selectBox.selectedIndex] as HTMLOptionElement;
                val = parseInt(option.value);
            }

            return val;
        }

        private removeActivationById(id: number): void {
            var index = -1;
            for (var i = 0; i < this.jsonData.length; i++) {
                if (this.jsonData[i].id == id) {
                    index = i;
                    break;
                }
            }
            if(index >= 0) {
                this.jsonData.splice(index, 1);
            }
        }

        // ────────────────────────────────────────────────────
        //   ::::: C O N V E R T     F U N C T I O N S ::::::
        // ────────────────────────────────────────────────────
        protected LoadFromObject(data: Object): void {
            this.m_id               = data['id'] as number;
            this.m_fullName         = data['name'] as string;
            this.m_email            = data['email'] as string;
            this.m_password         = data['password'] as string;
            this.m_groupId          = data['group_id'] as number;
            this.m_customerId       = data['customer_id'] as number;
            this.m_roleId           = data['roles'][0]['id'];
            this.m_numActivations   = data['activations_allowed'];
            this.m_isActive         = data['isactive'];
            this.m_registeredAt     = data['dt'] as string;
            this.m_reportLevel      = data['report_level'] as number;
            this.jsonData           = data['activations'];
        }

        protected LoadFromForm(): void {
            this.m_fullName         = this.m_inputFullName.value;
            this.m_email            = this.m_inputEmail.value;
            this.m_password         = this.m_inputPassword.value;
            this.m_groupId          = this.getValueFromSelect(this.m_selectGroup);
            this.m_roleId           = this.getValueFromSelect(this.m_selectRole);
            this.m_customerId       = this.m_inputCustomerId.value == "" ? null:this.m_inputCustomerId.valueAsNumber;
            this.m_numActivations   = this.m_inputActivationCount.valueAsNumber;
            this.m_isActive         = this.m_inputIsActive.checked == true ? 1 : 0;
            this.m_reportLevel      = this.m_inputReportLevel.checked == true ? 1 : 0;
        }

        public ToObject(): Object {
            let obj = {
                'id': this.m_id,
                'name': this.m_fullName,
                'email': this.m_email,
                'group_id': this.m_groupId,
                'role_id': this.m_roleId,
                'customer_id': this.m_customerId,
                'activations_allowed': this.m_numActivations,
                'isactive': this.m_isActive,
                'dt': this.m_registeredAt,
                'report_level': this.m_reportLevel,
            };

            if (this.m_password != null && this.m_password.length > 0 && (this.m_password != Array(30).join("x"))) {
                obj['password'] = this.m_password;
                obj['password_verify'] = this.m_inputPasswordConfirm.value;
            }

            return obj;
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions {
            let validationRules: JQueryValidation.RulesDictionary = {};

            validationRules[this.m_inputEmail.id] = {
                required: true,
                email: true
            };

            validationRules[this.m_inputPassword.id] = {
                required: true,
                equalTo: '#' + this.m_inputPasswordConfirm.id
            };

            validationRules[this.m_inputPasswordConfirm.id] = {
                required: true,
                equalTo: '#' + this.m_inputPassword.id
            };

            validationRules[this.m_inputActivationCount.id] = {
                required: true,
                number: true
            };

            let validationErrorMessages = {};
            validationErrorMessages[this.m_inputEmail.id] = this.ERROR_MESSAGE_EMAIL;
            validationErrorMessages[this.m_inputPassword.id] = this.ERROR_MESSAGE_PASSWORD;
            validationErrorMessages[this.m_inputPasswordConfirm.id] = this.ERROR_MESSAGE_CONFIRM_PASSWORD;
            validationErrorMessages[this.m_inputActivationCount.id] = this.ERROR_MESSAGE_ACTIVATION;

            let validationOptions: JQueryValidation.ValidationOptions = {
                rules: validationRules,
                errorPlacement: ((error: JQuery, element: JQuery): void => {
                    error.appendTo('#user_form_errors');
                    $('#user_form_errors').append('<br/>');
                }),
                messages: validationErrorMessages
            };

            return validationOptions;
        }

        // ───────────────────────────────────────────────
        //   ::::: C L A S S      H A N D L E R S ::::::
        // ───────────────────────────────────────────────
        private InitUserActivationTables() {
            let that = this;
            let id = (this.m_id === undefined) ? 0 : this.m_id;

            this.m_tableColumns = [{
                    title: 'Action Id',
                    data: 'id',
                    visible: false
                },
                {
                    title: 'Identifier',
                    data: 'identifier',
                    visible: true,
                },
                {
                    title: 'Device Id',
                    data: 'device_id',
                    visible: true,
                    width: '300px'
                },
                {
                    title: 'App Version',
                    data: 'app_version',
                    visible: true,
                    width: '180px'
                },
                {
                    title: 'IP Address',
                    data: 'ip_address',
                    visible: true,
                    width: '200px'
                },
                {
                    title: 'Updated date',
                    data: 'updated_at',
                    visible: true,
                    width: "280px"
                },
                {
                    title: 'Check-in Days',
                    data: 'check_in_days',
                    visible: true,
                    width: "100px",
                    render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                        return "<input type='number' min='0' data-id='" + row.id + "' value='" + data + "' class='check-in-days' />";
                    })
                },
                {
                    title: 'Alert Partner',
                    data: 'alert_partner',
                    visible: true,
                    width: "90px",
                    render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                        var chk_report = (data === 1) ? 'checked' : '';

                        var str = '';
                        str += '<label class=\'switch-original\'>';
                        str += '    <input type=\'checkbox\' data-id=\'' + row.id + '\'' + chk_report + ' />';
                        str += '    <span class=\'check\'></span>';
                        str += '</label>';

                        return str;
                    })
                },
                {
                    width: "300px",
                    render: function (data, type, row) {
                        var strButtons = "";
                        strButtons += "<button id='delete_" + row.id + "' class='btn-delete button primary'>Delete</button> ";
                        strButtons += " &nbsp;<button id='block_" + row.id + "' class='btn-block button primary'>Block</button>";

                        return strButtons;
                    }
                }
            ];

            this.m_tableSettings = {
                autoWidth: true,
                stateSave: true,
                responsive: true,
                columns: this.m_tableColumns,
                data: this.jsonData,
                destroy: true,
                rowCallback: ((row: Node, data: any[] | Object): void => {

                }),
                drawCallback: ((settings): void => {
                    let that = this;

                    $("#user_activation_table").off("change", "input[type='checkbox']");
                    $("#user_activation_table").on("change", "input[type='checkbox']", function () {
                        let id = $(this).attr("data-id");
                        let val = 0;
                        var objCheck = <HTMLInputElement> this;
                        if (objCheck.checked) val = 1;

                        let checkAjaxSettings: JQuery.UrlAjaxSettings = {
                            method: "POST",
                            timeout: 60000,
                            url: that.URL_UPDATE_ALERT,
                            data: {
                                id: id,
                                value: val
                            },
                            success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                                return false;
                            },
                            error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                console.log(errorThrown);
                                if (jqXHR.status > 399 && jqXHR.status < 500) {} else {}
                            }
                        }

                        $.post(checkAjaxSettings);
                    });

                    $("#user_activation_table").off("blur", "input[type='number']");
                    $("#user_activation_table").on("blur", "input[type='number']", function () {
                        let id = $(this).attr("data-id");
                        var objInput = < HTMLInputElement > this;
                        let val = objInput.value;

                        let checkAjaxSettings: JQuery.UrlAjaxSettings = {
                            method: "POST",
                            timeout: 60000,
                            url: that.URL_UPDATE_CHECK_IN_DAYS,
                            data: {
                                id: id,
                                value: val
                            },
                            success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                                return false;
                            },
                            error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                console.log(errorThrown);
                            }
                        }

                        $.post(checkAjaxSettings);
                    });

                    $("#user_activation_table").off("click", "button.btn-delete");
                    $("#user_activation_table").on('click', 'button.btn-delete', function (e) {
                        e.preventDefault();
                        if (confirm(that.MESSAGE_DELETE_ACTIVATION_CONFIRM)) {
                            let dataObject = {};
                            let id = that.getIdFromElementId(e.target.id);

                            let ajaxSettings: JQuery.UrlAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: that.URL_DELETE_ACTIVATION + '/' + id,
                                data: dataObject,
                                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                                    that.removeActivationById(id);
                                    that.InitUserActivationTables();

                                    return false;
                                },
                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    that.m_progressWait.Show(that.TITLE_ACTION_FAILED, that.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));

                                    setTimeout(() => {
                                        that.m_progressWait.Hide();
                                    }, that.ERROR_MESSAGE_DELAY_TIME);
                                }
                            }

                            $.post(ajaxSettings);
                        }
                    });

                    $("#user_activation_table").off("click", "button.btn-block");
                    $("#user_activation_table").on('click', 'button.btn-block', function (e) {
                        e.preventDefault();
                        if (confirm(that.MESSAGE_BLOCK_ACTIVATION_CONFIRM)) {
                            let dataObject = {};
                            let id = that.getIdFromElementId(e.target.id);

                            let ajaxSettings: JQuery.UrlAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: that.URL_BLOCK_ACTIVATION + '/' + id,
                                data: dataObject,
                                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                                    that.removeActivationById(id);
                                    that.InitUserActivationTables();
                                    return false;
                                },
                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    that.m_progressWait.Show(that.TITLE_ACTION_FAILED, that.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));

                                    setTimeout(() => {
                                        that.m_progressWait.Hide();
                                    }, that.ERROR_MESSAGE_DELAY_TIME);
                                }
                            }

                            $.post(ajaxSettings);
                        }
                    });
                })
            };

            this.m_ActivationTables = $('#user_activation_table').DataTable(this.m_tableSettings);
        }

        private InitButtonHandlers(): void {
            this.m_btnCancel.onclick = ((e: MouseEvent): any => {
                if (this.m_actionCompleteCallback != null) {
                    this.m_actionCompleteCallback("Cancel");
                } else {
                    this.StopEditing();
                }
            });
        }

        public StartEditing(allGroups, userData: Object = null): void {

            if (this.m_selectGroup.options != null) {
                this.m_selectGroup.options.length = 0;
            }

            for (var elm of allGroups) {
                let option = document.createElement('option') as HTMLOptionElement;
                option.text = elm['name'];
                option.value = elm['id'];
                this.m_selectGroup.options.add(option);
            }

            switch (userData == null) {

                case true:
                    {
                        this.m_editorTitle.innerText = this.TITLE_NEW_USER;
                        this.m_btnSubmit.innerText = this.BTN_NEW_USER;

                        this.m_mainForm.reset();
                        if (this.m_selectGroup.options != null && this.m_selectGroup.options.length > 0) {
                            this.m_selectGroup.selectedIndex = 0;
                        } else {
                            this.m_selectGroup.selectedIndex = -1;
                        }

                        if (this.m_selectRole.options != null && this.m_selectRole.options.length > 0) {
                            this.m_selectRole.selectedIndex = 0;
                        } else {
                            this.m_selectRole.selectedIndex = -1;
                        }
                    }
                    break;

                case false:
                    {
                        this.LoadFromObject(userData);

                        this.m_editorTitle.innerText = this.TITLE_EDIT_USER;
                        this.m_btnSubmit.innerText = this.BTN_EDIT_USER;

                        this.m_inputFullName.value = this.m_fullName;
                        this.m_inputEmail.value = this.m_email;
                        this.m_inputCustomerId.value = (this.m_customerId == null) ? '':this.m_customerId.toString();

                        this.m_inputPassword.value = new Array(30).join("x");
                        this.m_inputPasswordConfirm.value = new Array(30).join("x");

                        this.m_inputActivationCount.value = this.m_numActivations.toString();

                        if (this.m_groupId != -1) {
                            let optionInList = this.m_selectGroup.querySelector('option[value="' + this.m_groupId.toString() + '"]') as HTMLOptionElement;
                            if (optionInList != null) {
                                this.m_selectGroup.selectedIndex = optionInList.index;
                            }
                        } else {
                            if (this.m_selectGroup.options != null && this.m_selectGroup.options.length > 0) {
                                this.m_selectGroup.selectedIndex = 0;
                            } else {
                                this.m_selectGroup.selectedIndex = -1;
                            }
                        }

                        if (this.m_roleId != -1) {
                            let optionInList = this.m_selectRole.querySelector('option[value="' + this.m_roleId.toString() + '"]') as HTMLOptionElement;
                            if (optionInList != null) {
                                this.m_selectRole.selectedIndex = optionInList.index;
                            }
                        } else {
                            if (this.m_selectRole.options != null && this.m_selectRole.options.length > 0) {
                                this.m_selectRole.selectedIndex = 0;
                            } else {
                                this.m_selectRole.selectedIndex = -1;
                            }
                        }

                        this.m_inputIsActive.checked = this.m_isActive != 0;
                        this.m_inputReportLevel.checked = this.m_reportLevel != 0;
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

            if (userData != null) {
                this.m_id = userData['id'];
                this.InitUserActivationTables();
            } else {
                this.m_id = 0;
                this.jsonData = [];
                this.InitUserActivationTables();
            }

            $(this.m_editorOverlay).fadeIn(this.FADE_IN_DELAY_TIME);
        }

        public StopEditing(): void {
            $(this.m_editorOverlay).fadeOut(this.FADE_IN_DELAY_TIME);
        }
    }
}
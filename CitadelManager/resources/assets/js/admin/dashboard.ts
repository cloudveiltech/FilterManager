/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../progresswait.ts"/>
///<reference path="listuploadoverlay.ts"/>
///<reference path="records/userrecord.ts"/>
///<reference path="records/grouprecord.ts"/>
///<reference path="records/filterlistrecord.ts"/>
///<reference path="records/apprecord.ts"/>
///<reference path="records/appgrouprecord.ts"/>
///<reference path="records/appuseractivationrecord.ts"/>
///<reference path="records/versionrecord.ts"/>

namespace Citadel {
    /**
     * An enum containing all valid view states for the administrator dashboard.
     *
     * @enum {number}
     */
    enum DashboardViewStates {
        UserListView,
        GroupListView,
        FilterListView,
        DeactivationRequestListView,
        AppView,
        AppGroupView,
        AppUserActivationView,
        SystemVersionView
    }

    export class Dashboard {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────

        MESSAGE_VERSION_SET_DEFAULT             = 'Do you want to set this version as default version?';
        MESSAGE_CONFIRM_SIGNOUT                 = 'Are you sure you\'d like to sign out?';
        MESSAGE_CONFIRM_USER_DELETE             = 'Really delete user? THIS CANNOT BE UNDONE!!!';
        MESSAGE_CONFIRM_GROUP_DELETE            = 'Really delete group? THIS CANNOT BE UNDONE!!!';
        MESSAGE_CONFIRM_FILTER_DELETE           = 'Really delete filter list? THIS CANNOT BE UNDONE!!!';
        MESSAGE_CONFIRM_FILTER_DELETE_ALL_NAMESPACE ='Really delete all filters with the same type in this lists\' namespace? THIS CANNOT BE UNDONE!!!';
        MESSAGE_CONFIRM_FILTER_DELETE_TYPE_NAMESPACE = 'Really delete all filters in this lists\' namespace? THIS CANNOT BE UNDONE!!!';
        MESSAGE_CONFIRM_DEACTIVATION_REQUEST_DELETE = 'Really delete user deactivation request? THIS CANNOT BE UNDONE!!!';
        MESSAGE_CONFIRM_APPLICATION_DELETE      = 'Really delete Application? THIS CANNOT BE UNDONE!!!';
        MESSAGE_CONFIRM_APPGROUP_DELETE         = 'Really delete Application? THIS CANNOT BE UNDONE!!!';
        MESSAGE_CONFIRM_ACTIVATION_DELETE       = 'Really delete app user activation? THIS CANNOT BE UNDONE!!!';
        MESSAGE_CONFIRM_ACTIVATION_BLOCK        = 'Really block app user activation? THIS CANNOT BE UNDONE!!!';

        MESSAGE_LOAD_FAIL                       = 'Failed to load group record from table selection.';
        MESSAGE_ACTION_FAILED                   = 'Error reported by the server during action. console for more information.';

        URL_FETCH_FILTERLIST                = 'api/admin/filterlist/all';
        URL_FETCH_GROUP                     = 'api/admin/group/all';

        URL_FETCH_USERS_TABLE               = 'api/admin/users';
        URL_FETCH_GROUPS_TABLE              = 'api/admin/groups';
        URL_FETCH_FILTERS_TABLE             = 'api/admin/filterlists';
        URL_FETCH_DEACTIVATES_TABLE         = 'api/admin/deactivationreq';
        URL_FETCH_APPLICATIONS_TABLE        = 'api/admin/app';
        URL_FETCH_APP_GROUPS_TABLE          = 'api/admin/app_group';
        URL_FETCH_ACTIVATIONS_TABLE         = 'api/admin/activations';
        URL_FETCH_VERSIONS_TABLE            = 'api/admin/versions';

        URL_UPDATE_USER_FIELD               = 'api/admin/users/update_field';
        URL_UPDATE_GROUP_FIELD              = 'api/admin/groups/update_field';
        URL_UPDATE_DEACTIVATION_FIELD       = 'api/admin/deactivationreq/update_field';
        URL_UPDATE_ACTIVATION_FIELD         = 'api/admin/activations/update_field';
        URL_UPDATE_VERSION_STATUS           = 'api/admin/versions/update_status';

        ICON_USER                           = '<span class=\'mif-user self-scale-group fg-green\'></span>';
        ICON_EMAIL                          = '<span class=\'mif-mail self-scale-group color-gray\'></span>';
        ICON_GROUP                          = '<span class=\'mif-organization self-scale-group fg-green\'></span>';
        ICON_GROUP_DETACTIVE                = '<span class=\'mif-organization self-scale-group color-gray\'></span>';
        ICON_IP                             = '<span class=\'mif-flow-tree self-scale-3\'></span>';
        ICON_FILTER                         = '<span class=\'mif-filter fg-green\'></span>';
        ICON_WARNING                        = '<span class=\'mif-warning fg-red\'></span>';
        SPAN_ACTIVE                         = '<span class=\'active-s status\'>Active</span>';
        SPAN_INACTIVE                       = '<span class=\'inactive-s status\'>Inactive</span>';
        // ───────────────────────────────────────────────────────────────────
        //   :::::: M A I N   M E N U   B U T T O N   E L E M E N T S ::::::
        // ───────────────────────────────────────────────────────────────────

        private m_tabBtnUsers                   : HTMLLinkElement;
        private m_tabBtnGroups                  : HTMLLinkElement;
        private m_tabBtnFilterLists             : HTMLLinkElement;
        private m_tabBtnUserRequest             : HTMLLinkElement;
        private m_tabBtnAppGroup                : HTMLLinkElement;
        private m_tabBtnActivation              : HTMLLinkElement;
        private m_tabBtnVersion                 : HTMLLinkElement;

        // Container Views
        private m_viewUser                      : HTMLDivElement;
        private m_viewGroup                     : HTMLDivElement;
        private m_viewFilter                    : HTMLDivElement;
        private m_viewDeactivationReq       : HTMLDivElement;
        private m_viewApplication               : HTMLDivElement;
        private m_viewAppGroup                  : HTMLDivElement;
        private m_viewActivation                : HTMLDivElement;
        private m_viewVersion                   : HTMLDivElement;

        private m_currentViewState              : DashboardViewStates;

        // User View
        private m_btnCreateUser                 : HTMLButtonElement;
        private m_btnDeleteUser                 : HTMLButtonElement;

        // Group View
        private m_btnCreateGroup                : HTMLButtonElement;
        private m_btnDeleteGroup                : HTMLButtonElement;
        private m_btnCloneGroup                 : HTMLButtonElement;

        // Filterlist View
        private m_btnUploadFL                   : HTMLButtonElement;   // Upload Filter List Button
        private m_btnDeleteFL                   : HTMLButtonElement;   // Delete Filter List Button
        private m_btnDeleteFLInNamespace        : HTMLButtonElement;
        private m_btnDeleteFLTypeInNamespace    : HTMLButtonElement;

        // Deactivation Request View
        private m_btnDeleteDR                   : HTMLButtonElement;   // Delete User Deactivation Request Button
        private m_btnRefreshDR                  : HTMLButtonElement;   // Refresh User Deactivation Request Button

        // Application View
        private m_btnAddApplication             : HTMLButtonElement;
        private m_btnRemoveApplication          : HTMLButtonElement;
        private m_btnApplyToGroup               : HTMLButtonElement;

        private m_btnApp                        : HTMLInputElement;     // Switch App Group -> App
        private m_btnAppGroup                   : HTMLInputElement;     // Switch App -> App Group

        // Activation View
        private m_btnDeleteActivation           : HTMLButtonElement;
        private m_btnBlockActivation            : HTMLButtonElement;

        // ─────────────────────────────────────────────────────────────────
        //   :::::: T O P   M E N U   B A R   U I   E L E M E N T S ::::::
        // ─────────────────────────────────────────────────────────────────
        private m_btnSignOut                    : HTMLLIElement;
        private m_btnCreateVersion              : HTMLButtonElement;
        private m_btnDeleteVersion              : HTMLButtonElement;
        private m_btnSystemPlatform             : HTMLButtonElement;

        // ───────────────────────────────────────
        //   :::::: D A T A   T A B L E S ::::::
        // ───────────────────────────────────────

        public m_tableUsers                     : DataTables.Api;
        public m_tableGroups                    : DataTables.Api;
        public m_tableFilterLists               : DataTables.Api;
        public m_tableDeactivationRequests      : DataTables.Api;
        public m_tableAppLists                  : DataTables.Api;
        public m_tableAppGroupLists             : DataTables.Api;
        public m_tableActivation                : DataTables.Api;
        public m_tableVersions                  : DataTables.Api;

        private m_filterListUploadController    : ListUploadOverlay;
        private m_filterGroupSelectionArea      : dragula.Drake;

        private m_allFilters;
        private m_allGroups;

        /**
         * Creates an instance of Dashboard.
         *
         *
         * @memberOf Dashboard
         */
        constructor() {
            // Setup nav.
            this.ConstructNavigation();
            this.loadAllFilters();
            this.loadAllGroups();
            // Initialize views.
            this.ConstructManagementViews();

            this.m_filterListUploadController = new ListUploadOverlay();

            // Force table redraw any time an upload of new lists fails or succeeds.
            this.m_filterListUploadController.UploadCompleteCallback = ((): void => {
                this.ForceTableRedraw(this.m_tableFilterLists);
                this.ForceTableRedraw(this.m_tableGroups);
                this.m_filterListUploadController.Hide();
                this.loadAllFilters();
            });

            this.m_filterListUploadController.UploadFailedCallback = ((): void => {
                this.ForceTableRedraw(this.m_tableFilterLists);
                this.ForceTableRedraw(this.m_tableGroups);
                this.m_filterListUploadController.Hide();
            });
        }

        private loadAllFilters(): void {
            let ajaxSettings: JQueryAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: this.URL_FETCH_FILTERLIST,
                success: (data: any): any => {
                    this.m_allFilters = data;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(errorThrown);
                }
            }

            $.ajax(ajaxSettings);
        }

        private loadAllGroups(): void {
            let ajaxSettings: JQueryAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: this.URL_FETCH_GROUP,
                success: (data: any): any => {
                    this.m_allGroups = data;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(errorThrown);
                }
            }

            $.ajax(ajaxSettings);
        }


        private ConstructManagementViews(): void {
            // Grab main view container references.
            this.m_viewUser             = document.getElementById('view_user_management') as HTMLDivElement;
            this.m_viewGroup            = document.getElementById('view_group_management') as HTMLDivElement;
            this.m_viewFilter           = document.getElementById('view_filter_management') as HTMLDivElement;
            this.m_viewDeactivationReq  = document.getElementById('view_user_deactivation_request_management') as HTMLDivElement;
            this.m_viewApplication      = document.getElementById('view_app_management') as HTMLDivElement;
            this.m_viewAppGroup         = document.getElementById('view_app_group_management') as HTMLDivElement;
            this.m_viewActivation       = document.getElementById('view_app_user_activations_management') as HTMLDivElement;
            this.m_viewVersion          = document.getElementById('view_system_versions_management') as HTMLDivElement;

            // Build the tables.
            this.ConstructTables();
            this.ConstructDragula();

            this.ViewState = DashboardViewStates.UserListView;
        }

        private ConstructDragula(): void {
            let c: Element[] = [
                document.getElementById('group_blacklist_filters'),
                document.getElementById('group_whitelist_filters'),
                document.getElementById('group_bypass_filters'),
                document.getElementById('group_unassigned_filters')
            ];

            let d: dragula.DragulaOptions = {
                containers: c,
                revertOnSpill: true,
                removeOnSpill: false,
                direction: 'vertical',
                copy: false,
                delay: false,
                mirrorContainer: document.body,

                isContainer: ((element: Element): any => {
                    return element.classList.contains('dragula-container');
                }),

                accepts: ((el ? : Element, target ? : Element, source ? : Element, sibling ? : Element): boolean => {
                    var attr = el.getAttribute('citadel-filter-list-type');
                    if ((attr.toLowerCase() === 'nlp' || attr.toLowerCase() === 'trigger') &&
                        (target.id != 'group_blacklist_filters' && target.id != 'group_unassigned_filters')) {
                        return false;
                    }

                    return true;
                })
            };


            this.m_filterGroupSelectionArea = dragula(d);
        }

        /**
         * Runs the DataTables initialization on all tables we have to present.
         *
         * @private
         *
         * @memberOf Dashboard
         */
        private ConstructTables(): void {
            let height = $("body").height();

            let userTableConstruction = (() => {

                let userTableColumns: DataTables.ColumnSettings[] = [{
                        title: 'User Id',
                        data: 'id',
                        visible: false,
                    },
                    {
                        title: 'Group Id',
                        data: 'group.id',
                        defaultContent: 'Unassigned',
                        visible: false
                    },
                    {
                        title: 'User Name',
                        data: 'name',
                        className: 'content-left user-name',
                        visible: true,
                        width: '300px',
                        render: ((data: string): any => {
                            return this.ICON_USER + ` <b>${data}</b>`;
                        })
                    },
                    {
                        title: 'Group Name',
                        data: 'group.name',
                        className: 'content-left',
                        defaultContent: 'Unassigned',
                        visible: true,
                        width: '220px',
                        render: ((data: string): any => {
                            return this.ICON_GROUP_DETACTIVE + ' - ' + data;
                        })
                    },
                    {
                        title: 'User Email',
                        data: 'email',
                        className: 'content-left user-email',
                        visible: true,
                        render: ((data: string)=> {
                            return this.ICON_EMAIL + ' ' + data;
                        })
                    },
                    {
                        title: 'Roles',
                        data: 'roles[, ].display_name',
                        className: 'content-left',
                        defaultContent: 'None',
                        width: '220px'
                    },
                    {
                        title: 'License Used',
                        data: 'activations_allowed',
                        className: 'content-center',
                        visible: true,
                        render: ((data: any, t: string, row: any): any => {
                            return "<span class='license_used'>" + row.activations_used
                             + "</span> of <span class='license_allowed'>" + data + "</span>";
                        }),
                        width: '150px'
                    },
                    {
                        title: 'Report Level',
                        data: 'report_level',
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            var chk_report = (data === 1) ? "checked" : "";
                            var str = "<label class='switch-original'><input type='checkbox' id='user_report_" + row.id + "' " + chk_report + " /><span class='check'></span></label>";
                            return str;
                        }),
                        className: 'content-center',
                        width: '130px'
                    },
                    {
                        title: 'Status',
                        data: 'isactive',
                        className: 'content-center',
                        visible: true,
                        render: ((data: number): any => {
                            if (data == null) return '';
                            return (data == 1) ? this.SPAN_ACTIVE:this.SPAN_INACTIVE;
                        }),
                        width: '90px'
                    },
                    {
                        title: 'Date Registered',
                        data: 'created_at',
                        visible: true,
                        width: '180px',
                        className: 'updated_date'
                    }
                ];

                let userTablesLoadFromAjaxSettings: DataTables.AjaxSettings = {
                    url: this.URL_FETCH_USERS_TABLE,
                    dataSrc: function (json) {
                        return json.data;
                    },
                    method: "GET",
                    error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                        console.log(jqXHR);
                    })
                };

                let usersTableSettings: DataTables.Settings = {
                    scrollY: '' + (height - 470) + 'px',
                    scrollCollapse: true,
                    autoWidth: true,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    deferLoading: 0,
                    columns: userTableColumns,
                    ajax: userTablesLoadFromAjaxSettings,
                    rowCallback: ((row: Node, data: any[] | Object): void => {
                        this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: ((settings): void => {
                        let that = this;
                        $("#user_table").off("change", "input[type='checkbox']");
                        $("#user_table").on("change", "input[type='checkbox']", function () {
                            let id_str = this['id'];    // get id from checkbox element
                            let val = 0;
                            if (this['checked']) {
                                val = 1;
                            }

                            let checkAjaxSettings: JQueryAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: that.URL_UPDATE_USER_FIELD,
                                data: {
                                    id: id_str,
                                    value: val
                                },
                                success: (data: any): any => {
                                    that.ForceTableRedraw(that.m_tableUsers);
                                    return false;
                                },
                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    console.log(errorThrown);
                                }
                            }

                            $.ajax(checkAjaxSettings);
                        });
                    })
                };
                this.m_tableUsers = $('#user_table').DataTable(usersTableSettings);
            });

            let groupTableConstruction = (() => {
                let groupTableColumns: DataTables.ColumnSettings[] = [{
                        title: 'Group Id',
                        data: 'id',
                        visible: false
                    },
                    {
                        title: 'Group Name',
                        data: 'name',
                        visible: true,
                        className: 'content-left',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return this.ICON_GROUP + '- <b title=\'' + row.user_count + ' users are registered to this group.\'>' + data + '</b> <span class=\'user_count\'>(' + row.user_count + ')</span>';
                        })
                    },
                    {
                        title: 'Primary DNS',
                        data: 'primary_dns',
                        visible: true,
                        render: ((data: string): any => {
                            return (data === null || data === '')? '': this.ICON_IP + ' ' + data;
                        }),
                        className: 'content-left',
                        width: '190px'
                    },
                    {
                        title: 'Secondary DNS',
                        data: 'secondary_dns',
                        visible: true,
                        render: ((data: string): any => {
                            return (data === null || data === '')? '': this.ICON_IP + ' ' + data;
                        }),
                        className: 'content-left',
                        width: '190px'
                    },
                    {
                        title: 'Terminate/Internet/Threshold',
                        data: 'terminate',
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            if (data === null || data === '') {
                                return '';
                            }
                            var app_cfg = JSON.parse(row.app_cfg);
                            var chk_terminate = app_cfg.CannotTerminate ? 'checked' : '';
                            var chk_internet = app_cfg.BlockInternet ? 'checked' : '';
                            var chk_threshold = app_cfg.UseThreshold ? 'checked' : '';
                            var str = '<label class=\'switch-original\'><input type=\'checkbox\' id=\'group_terminate_' + row.id + '\'' + chk_terminate + ' /><span class=\'check\'></span></label>';
                            str += '<label class=\'switch-original\'><input type=\'checkbox\' id=\'group_internet_' + row.id + '\' ' + chk_internet + ' /><span class=\'check\'></span></label>';
                            str += '<label class=\'switch-original\'><input type=\'checkbox\' id=\'group_threshold_' + row.id + '\' ' + chk_threshold + ' /><span class=\'check\'></span></label>';
                            return str;
                        }),
                        className: 'content-left',
                        width: '290px'
                    },
                    {
                        title: 'Bypass',
                        data: 'bypass',
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            if (data === null || data === '') {
                                return '';
                            }
                            var app_cfg = JSON.parse(row.app_cfg);
                            var bypass_permitted = app_cfg['BypassesPermitted'] === null || app_cfg['BypassesPermitted'] === 0 ? "" : "<span class='mif-clipboard self-scale-4 fg-cyan'></span> " + app_cfg['BypassesPermitted'] + "<span class='unit_day'>/day</span>";
                            var bypass_duration = app_cfg['BypassDuration'] === null || app_cfg['BypassDuration'] === 0 ? "" : " <span class='mif-alarm-on self-scale-5 fg-pink'></span> " + app_cfg['BypassDuration'] + "<span class='unit_min'>mins</span>";
                            return bypass_permitted + bypass_duration;
                        }),
                        className: 'content-left',
                        width: '180px'
                    },
                    {
                        title: 'Report Level',
                        data: 'report_level',
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            var app_cfg = JSON.parse(row.app_cfg);
                            var chk_report = (app_cfg.ReportLevel == 1) ? "checked" : "";

                            return "<label class='switch-original'><input type='checkbox' id='group_report_" + row.id + "' " + chk_report + " /><span class='check'></span></label>";
                        }),
                        className: 'content-center',
                        width: '150px'
                    },
                    {
                        title: 'Status',
                        data: 'isactive',
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return data == null? '': data == 1? this.SPAN_ACTIVE : this.SPAN_INACTIVE;
                        }),
                        className: 'content-center',
                        width: '60px'
                    },
                    {
                        title: 'Date Registered',
                        data: 'created_at',
                        visible: true,
                        width: '180px',
                        className: 'updated_date'
                    }
                ];

                let groupTablesLoadFromAjaxSettings: DataTables.AjaxSettings = {
                    url: this.URL_FETCH_GROUPS_TABLE,
                    dataSrc: function (json) {
                        return json.data;
                    },
                    method: "GET",
                    error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                        console.log(errorThrown);
                    })
                };

                let groupTableSettings: DataTables.Settings = {
                    scrollY: '' + (height - 470) + 'px',
                    scrollCollapse: true,
                    autoWidth: true,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    deferLoading: 0,
                    columns: groupTableColumns,
                    ajax: groupTablesLoadFromAjaxSettings,
                    rowCallback: ((row: Node, data: any[] | Object): void => {
                        this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: ((settings): void => {
                        let that = this;
                        $("#group_table").off("change", "input[type='checkbox']");
                        $("#group_table").on("change", "input[type='checkbox']", function () {
                            let id_str = this['id'];
                            let val = 0;

                            if (this['checked']) {
                                val = 1;
                            }

                            let checkAjaxSettings: JQueryAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: that.URL_UPDATE_GROUP_FIELD,
                                data: {
                                    id: id_str,
                                    value: val
                                },
                                success: (data: any): any => {
                                    that.ForceTableRedraw(that.m_tableGroups);
                                    return false;
                                },
                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    console.log(errorThrown);
                                }
                            }

                            $.ajax(checkAjaxSettings);
                        });
                    })
                };

                this.m_tableGroups = $('#group_table').DataTable(groupTableSettings);
            });

            let filterTableConstruction = (() => {
                let filterTableColumns: DataTables.ColumnSettings[] = [{
                        title: 'ID',
                        data: 'id',
                        visible: false
                    },
                    {
                        title: 'Category Name',
                        data: 'category',
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return (data == null)? '':row.type === 'Filters'?this.ICON_FILTER + ' ' + data:this.ICON_WARNING + ' ' + data;
                        })
                    },
                    {
                        title: 'List Group Name',
                        data: 'namespace',
                        visible: true,
                        render: ((data: string): any => {
                            return (data == null)?'':data;
                        })
                    },
                    {
                        title: 'Type',
                        data: 'type',
                        visible: true,
                        render: ((data: string): any => {
                            return (data == null)? '': data === 'Filters'?this.ICON_FILTER + ' ' + data:this.ICON_WARNING + ' ' + data;
                        }),
                    },
                    {
                        title: '# Entries',
                        data: 'entries_count',
                        visible: true
                    },
                    {
                        title: 'Date Created',
                        data: 'created_at',
                        visible: true,
                        width: '180px',
                        className: 'updated_date'
                    }
                ];

                let filterTablesLoadFromAjaxSettings: DataTables.AjaxSettings = {
                    url: this.URL_FETCH_FILTERS_TABLE,
                    dataSrc: function (json) {
                        return json.data;
                    },
                    method: "GET",
                    error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                        console.log(errorThrown);
                    })
                };

                let filterTableSettings: DataTables.Settings = {
                    scrollY: '' + (height - 470) + 'px',
                    scrollCollapse: true,
                    autoWidth: true,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    deferLoading: 0,
                    columns: filterTableColumns,
                    ajax: filterTablesLoadFromAjaxSettings,
                    rowCallback: ((row: Node, data: any[] | Object): void => {
                        this.OnTableRowCreated(row, data);
                    })
                };

                this.m_tableFilterLists = $('#filter_table').DataTable(filterTableSettings);

            });

            let deactivationRequestConstruction = (() => {
                let userDeactivationRequestTableColumns: DataTables.ColumnSettings[] = [{
                        title: 'ID',
                        data: 'id',
                        visible: false
                    },
                    {
                        title: 'User Full Name',
                        data: 'user.name',
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return "<span class='mif-user self-scale-group fg-green'></span>  <b title='" + data + "'>" + data + "</b>"
                        }),
                        width: '240px'
                    },
                    {
                        title: 'Username',
                        data: 'user.email',
                        visible: true,
                        width: '240px'
                    },
                    {
                        title: 'Device Name',
                        data: 'device_id',
                        visible: true
                    },
                    {
                        title: 'Granted',
                        data: 'granted',
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            if (data == null) {
                                return "";
                            }

                            var chk_report = (data === 1) ? "checked" : "";
                            var str = "<label class='switch-original'><input type='checkbox' id='deactivatereq_enabled_" + row.id + "' " + chk_report + " /><span class='check'></span></label>";
                            return str;
                        }),
                        width: '100px'
                    },
                    {
                        title: 'Date Requested',
                        data: 'created_at',
                        visible: true,
                        width: '180px',
                        className: 'updated_date'
                    }
                ];

                let userDeactivationRequestTablesLoadFromAjaxSettings: DataTables.AjaxSettings = {
                    url: this.URL_FETCH_DEACTIVATES_TABLE,
                    dataSrc: function (json) {
                        return json.data;
                    },
                    method: "GET",
                    error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                        console.log(errorThrown);
                    })
                };

                let userDeactivationRequestTableSettings: DataTables.Settings = {
                    scrollY: '' + (height - 470) + 'px',
                    scrollCollapse: true,
                    autoWidth: true,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    deferLoading: 0,
                    columns: userDeactivationRequestTableColumns,
                    ajax: userDeactivationRequestTablesLoadFromAjaxSettings,
                    rowCallback: ((row: Node, data: any[] | Object): void => {
                        this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: ((settings): void => {
                        let that = this;
                        $("#user_deactivation_request_table").off("change", "input[type='checkbox']");
                        $("#user_deactivation_request_table").on("change", "input[type='checkbox']", function () {
                            let id_str = this['id'];
                            let val = 0;

                            if (this['checked']) {
                                val = 1;
                            }

                            let checkAjaxSettings: JQueryAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: that.URL_UPDATE_DEACTIVATION_FIELD,
                                data: {
                                    id: id_str,
                                    value: val
                                },
                                success: (data: any): any => {
                                    that.ForceTableRedraw(that.m_tableDeactivationRequests);
                                    return false;
                                },
                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    console.log(errorThrown);
                                }
                            }

                            $.ajax(checkAjaxSettings);
                        });
                    })
                };

                this.m_tableDeactivationRequests = $('#user_deactivation_request_table').DataTable(userDeactivationRequestTableSettings);
            });

            let appListTableConstruction = (() => {
                let appListTableColumns: DataTables.ColumnSettings[] = [{
                        title: 'App Id',
                        data: 'id',
                        visible: false
                    },
                    {
                        title: 'Application Name',
                        data: 'name',
                        visible: true,
                        width: '200px',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return "<span class='mif-file-binary self-scale-group fg-green'></span>  <b title='" + data + "'>" + data + "</b>";
                        })
                    },
                    {
                        title: 'Notes',
                        data: 'notes',
                        visible: true,
                        width: '240px'
                    },
                    {
                        title: 'Linked Group',
                        data: 'group_name',
                        orderable: false,
                        visible: true
                    },
                    {
                        title: 'Date Modified',
                        data: 'updated_at',
                        visible: true,
                        width: '220px'
                    }
                ];

                let appListTablesLoadFromAjaxSettings: DataTables.AjaxSettings = {
                    url: this.URL_FETCH_APPLICATIONS_TABLE,
                    dataSrc: function (json) {
                        return json.data;
                    },
                    method: "GET",
                    error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                        console.log(errorThrown);
                    })
                };

                let appListTableSettings: DataTables.Settings = {
                    scrollY: '' + (height - 470) + 'px',
                    scrollCollapse: true,
                    autoWidth: true,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    deferLoading: 0,
                    columns: appListTableColumns,
                    ajax: appListTablesLoadFromAjaxSettings,
                    rowCallback: ((row: Node, data: any[] | Object): void => {
                        this.OnTableRowCreated(row, data);
                    })
                };

                this.m_tableAppLists = $('#app_table').DataTable(appListTableSettings);
            });
            let appGroupListTableConstruction = (() => {
                let appGroupListTableColumns: DataTables.ColumnSettings[] = [{
                        title: 'App Group Id',
                        data: 'id',
                        visible: false
                    },
                    {
                        title: 'App Group Name',
                        data: 'group_name',
                        visible: true,
                        width: '200px',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return "<span class='mif-file-folder self-scale-group fg-green'></span>  <b title='" + data + "'>" + data + "</b>";
                        })
                    },
                    {
                        title: 'Linked Apps',
                        data: 'app_names',
                        orderable: false,
                        visible: true
                    },
                    {
                        title: 'Date Modified',
                        data: 'updated_at',
                        visible: true,
                        width: '230px'
                    }
                ];

                let appGroupListTablesLoadFromAjaxSettings: DataTables.AjaxSettings = {
                    url: this.URL_FETCH_APP_GROUPS_TABLE,
                    dataSrc: function (json) {
                        return json.data;
                    },
                    method: "GET",
                    error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                        console.log(errorThrown);
                    })
                };

                let appGroupListTableSettings: DataTables.Settings = {
                    scrollY: '' + (height - 470) + 'px',
                    scrollCollapse: true,
                    autoWidth: true,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    deferLoading: 0,
                    columns: appGroupListTableColumns,
                    ajax: appGroupListTablesLoadFromAjaxSettings,
                    rowCallback: ((row: Node, data: any[] | Object): void => {
                        this.OnTableRowCreated(row, data);
                    })
                };

                this.m_tableAppGroupLists = $('#app_group_table').DataTable(appGroupListTableSettings);
            });

            let appUserActivationTableConstruction = (() => {
                let appUserActivationTableColumns: DataTables.ColumnSettings[] = [{
                        title: 'Activation Id',
                        data: 'id',
                        visible: false
                    },
                    {
                        title: 'User',
                        data: 'name',
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            var name = data;
                            if (data.length > 17) {
                                name = data.substring(0, 14) + "...";
                            }

                            var str = "";
                            str += "<span class='mif-user self-scale-group fg-green'></span>  ";
                            str += "<b title='" + data + "'>" + name + "</b>";
                            return str;
                        }),
                        width: '200px'
                    },
                    {
                        title: 'Device Id',
                        data: 'device_id',
                        visible: true,
                        className: 'device_id',
                        width: '200px'
                    },
                    {
                        title: 'IP Address',
                        data: 'ip_address',
                        visible: true,
                        width: '200px',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            var user_ip = "<span class='mif-flow-tree self-scale-3'></span>";
                            var name = data;
                            if (data === null || data === undefined) {
                                name = "-";
                                data = "*";
                                return "";
                            } else {

                                if (data.length > 20) {
                                    name = data.substring(0, 15) + "...";
                                }
                            }
                            return user_ip + " <span title='" + data + "'>" + name + "</span>";
                        })
                    },
                    {
                        title: '#Bypass Used/Quantity/Period',
                        data: 'bypass_quantity',
                        visible: true,
                        width: '210px',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            var bypass_used = "";
                            if (row.bypass_used === null || row.bypass_used === 0)
                                bypass_used = "<span class='mif-info self-scale-2 unset_value_color'></span> <span class='unset_value_color'>-</span> ";
                            else
                                bypass_used = "<span class='mif-info self-scale-2 fg-cyan'></span> " + row.bypass_used + " ";

                            var bypass_permitted = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                            if (data === null || data === 0)
                                bypass_permitted += "<span class='mif-clipboard self-scale-1 unset_value_color'></span> <span class='unset_value_color'>-</span> ";
                            else
                                bypass_permitted += "<span class='mif-clipboard self-scale-1 fg-cyan'></span> " + data + " ";
                            bypass_permitted += "<span class='unit_day'>/day</span>";

                            var bypass_duration = "";
                            if (row.bypass_period === null || row.bypass_period === 0)
                                bypass_duration += "<span class='mif-alarm-on self-scale unset_value_color'></span> <span class='unset_value_color'>-</span> ";
                            else
                                bypass_duration += "<span class='mif-alarm-on self-scale fg-pink'></span> " + row.bypass_period + " ";
                            bypass_duration += "<span class='unit_min'>mins</span>";
                            return bypass_used + bypass_permitted + bypass_duration;
                        })
                    },
                    {
                        title: 'Report Level',
                        data: 'report_level',
                        visible: true,
                        width: '140px',
                        className: 'content-center',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            var chk_report = (data === 1) ? "checked" : "";
                            return "<label class='switch-original'><input type='checkbox' id='useractivation_report_" + row.id + "' " + chk_report + " /><span class='check'></span></label>";
                        }),
                    },
                    {
                        title: 'Version',
                        data: 'app_version',
                        visible: true,
                        width: '110px',
                        className: 'content-center'
                    },
                    {
                        title: 'Updated date',
                        data: 'updated_at',
                        visible: true,
                        width: '170px',
                        className: 'updated_date'
                    }
                ];

                let appUserActivationTablesLoadFromAjaxSettings: DataTables.AjaxSettings = {
                    url: this.URL_FETCH_ACTIVATIONS_TABLE,
                    dataSrc: function (json) {
                        return json.data;
                    },
                    method: "GET",
                    error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                        console.log(errorThrown);
                    })
                };

                let appUserActivationTableSettings: DataTables.Settings = {
                    scrollY: '' + (height - 470) + 'px',
                    scrollCollapse: true,
                    autoWidth: true,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    deferLoading: 0,
                    columns: appUserActivationTableColumns,
                    ajax: appUserActivationTablesLoadFromAjaxSettings,
                    rowCallback: ((row: Node, data: any[] | Object): void => {
                        this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: ((settings): void => {
                        let that = this;
                        $("#app_user_activations_table").off("change", "input[type='checkbox']");
                        $("#app_user_activations_table").on("change", "input[type='checkbox']", function () {
                            let id_str = this['id'];
                            let val = 0;

                            if (this['checked']) {
                                val = 1;
                            }

                            let checkAjaxSettings: JQueryAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: that.URL_UPDATE_ACTIVATION_FIELD,
                                data: {
                                    id: id_str,
                                    value: val
                                },
                                success: (data: any): any => {
                                    that.ForceTableRedraw(that.m_tableActivation);
                                    return false;
                                },
                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    console.log(errorThrown);
                                }
                            }

                            $.ajax(checkAjaxSettings);
                        });
                    })
                };

                this.m_tableActivation = $('#app_user_activations_table').DataTable(appUserActivationTableSettings);
                $('<button id="refresh_user_activations"><span class="mif-loop2 "></span> Refresh</button>').appendTo('#app_user_activations_table_wrapper div.dataTables_filter');
                $("#refresh_user_activations").click(() => {
                    this.ForceTableRedraw(this.m_tableActivation);
                })
            });
            let systemVersionTableConstruction = (() => {

                let systemVersionTableColumns: DataTables.ColumnSettings[] = [{
                        title: 'Version Id',
                        data: 'id',
                        visible: false,
                    },
                    {
                        title: 'Platform',
                        data: 'platform',
                        className: 'content-left',
                        orderable: false,
                        visible: true,
                        width: '200px',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            var name = row.os_name;
                            var span = "";
                            if (data === "WIN") {
                                span = "<span class='mif-windows os_win'></span>";
                            } else if (data === "OSX") {
                                span = "<span class='mif-apple os_mac'></span>";
                            } else if (data === "LINUX") {
                                span = "<span class='mif-linux os_linux'></span>";
                            } else {
                                span = "<span class='mif-notification os_mac'></span>";
                            }
                            if (row.active === 1) {
                                return span + " &nbsp; <b>" + name + "</b>";
                            } else {
                                return "<span class='inactive'>" + span + " &nbsp; <b>" + name + "</b></span>";
                            }
                        })
                    },
                    {
                        title: 'App Name',
                        data: 'app_name',
                        orderable: false,
                        className: 'content-left',
                        visible: true,
                        width: '140px',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {

                            if (row.active === 1) {
                                return data;
                            } else {
                                return "<span class='inactive'>" + data + "</span>";
                            }
                        })
                    },
                    {
                        title: 'File Name',
                        data: 'file_name',
                        className: 'content-left',
                        orderable: false,
                        visible: true,
                        width: '140px',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return (row.active === 1) ? data : '<span class=\'inactive\'>' + data + '</span>';
                        })
                    },
                    {
                        title: 'Version',
                        data: 'version_number',
                        orderable: false,
                        className: 'content-left version_number',
                        defaultContent: 'None',
                        width: '100px',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return (row.active === 1) ? data : '<span class=\'inactive\'>' + data + '</span>';
                        })
                    },
                    {
                        title: 'Release Date',
                        data: 'release_date',
                        orderable: false,
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return (row.active === 1) ? data : '<span class=\'inactive\'>' + data + '</span>';
                        }),
                        className: 'content-center version_date',
                        width: '180px'
                    },
                    {
                        title: 'Alpha',
                        data: 'alpha',
                        orderable: false,
                        className: 'content-center sub_version_number',
                        visible: true,
                        width: '100px',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return (row.active === 1) ? data : '<span class=\'inactive\'>' + data + '</span>';
                        })
                    },
                    {
                        title: 'Beta',
                        data: 'beta',
                        orderable: false,
                        visible: true,
                        width: '100px',
                        className: 'content-center sub_version_number',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return (row.active === 1) ? data : '<span class=\'inactive\'>' + data + '</span>';
                        })
                    },
                    {
                        title: 'Stable',
                        data: 'stable',
                        orderable: false,
                        visible: true,
                        width: '100px',
                        className: 'content-center sub_version_number',
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return (row.active === 1) ? data : '<span class=\'inactive\'>' + data + '</span>';
                        })
                    },
                    {
                        title: 'Changes',
                        data: 'changes',
                        orderable: false,
                        className: 'content-left',
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            return (row.active === 1) ? data : '<span class=\'inactive\'>' + data + '</span>';
                        })
                    },
                    {
                        title: 'Action',
                        data: 'active',
                        orderable: false,
                        visible: true,
                        render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any => {
                            if (data === 1) {
                                return "<label class='checked-alone'></label>";
                            } else {
                                return "<label class='switch-original'><input type='checkbox' id='versions_" + row.id + "' /><span class='check'></span></label>";
                            }
                        }),
                        className: 'content-left padding-left-10',
                        width: '60px'
                    }
                ];

                let systemVersionTablesLoadFromAjaxSettings: DataTables.AjaxSettings = {
                    url: this.URL_FETCH_VERSIONS_TABLE,
                    dataSrc: function (json) {
                        return json.data;
                    },
                    method: "GET",
                    error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                        console.log(errorThrown);
                    })
                };

                let systemVersionsTableSettings: DataTables.Settings = {
                    scrollY: '' + (height - 470) + 'px',
                    scrollCollapse: true,
                    autoWidth: true,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    deferLoading: 0,
                    columns: systemVersionTableColumns,
                    ajax: systemVersionTablesLoadFromAjaxSettings,
                    rowCallback: ((row: Node, data: any[] | Object): void => {
                        this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: ((settings): void => {
                        let that = this;
                        $("#system_versions_table").off("change", "input[type='checkbox']");
                        $("#system_versions_table").on("change", "input[type='checkbox']", function () {

                            if (!confirm(that.MESSAGE_VERSION_SET_DEFAULT)) {
                                // var objCheck = < HTMLInputElement > this;
                                this['checked'] = false;
                                return;
                            }

                            let id_str = this['id'];
                            let checkAjaxSettings: JQueryAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: that.URL_UPDATE_VERSION_STATUS,
                                data: {
                                    id: id_str
                                },
                                success: (data: any): any => {
                                    that.ForceTableRedraw(that.m_tableVersions);
                                    return false;
                                },
                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    console.log(errorThrown);
                                }
                            }

                            $.ajax(checkAjaxSettings);
                        });
                    })
                };
                this.m_tableVersions = $('#system_versions_table').DataTable(systemVersionsTableSettings);
            });

            userTableConstruction();
            groupTableConstruction();
            filterTableConstruction();
            deactivationRequestConstruction();
            appListTableConstruction();
            appGroupListTableConstruction();
            appUserActivationTableConstruction();
            systemVersionTableConstruction();
        }

        private ConstructNavigation(): void {

            this.m_btnSignOut           = document.getElementById('btn_sign_out') as HTMLLIElement;

            this.m_tabBtnUsers          = document.querySelector('a[href="#tab_users"]') as HTMLLinkElement;
            this.m_tabBtnGroups         = document.querySelector('a[href="#tab_groups"]') as HTMLLinkElement;
            this.m_tabBtnFilterLists    = document.querySelector('a[href="#tab_filter_lists"]') as HTMLLinkElement;
            this.m_tabBtnUserRequest    = document.querySelector('a[href="#tab_user_deactivation_requests"]') as HTMLLinkElement;
            this.m_tabBtnAppGroup       = document.querySelector('a[href="#tab_app_groups"]') as HTMLLinkElement;
            this.m_tabBtnActivation     = document.querySelector('a[href="#tab_app_user_activations"]') as HTMLLinkElement;
            this.m_tabBtnVersion        = document.querySelector('a[href="#tab_system_versions"]') as HTMLLinkElement;
            // Init user management button references.
            this.m_btnCreateUser        = document.getElementById('btn_user_add') as HTMLButtonElement;
            this.m_btnDeleteUser        = document.getElementById('btn_user_delete') as HTMLButtonElement;
            this.m_btnDeleteUser.disabled = true;

            // Init group management button references.
            this.m_btnCreateGroup       = document.getElementById('btn_group_add') as HTMLButtonElement;
            this.m_btnDeleteGroup       = document.getElementById('btn_group_delete') as HTMLButtonElement;
            this.m_btnCloneGroup        = document.getElementById('btn_group_clone') as HTMLButtonElement;
            this.m_btnDeleteGroup.disabled = true;
            this.m_btnCloneGroup.disabled = true;

            // Init Filter List/Data management button references.
            this.m_btnUploadFL          = document.getElementById('btn_add_filter_lists') as HTMLButtonElement;
            this.m_btnDeleteFL          = document.getElementById('btn_delete_filter_list') as HTMLButtonElement;
            this.m_btnDeleteFLInNamespace       = document.getElementById('btn_delete_filter_list_namespace') as HTMLButtonElement;
            this.m_btnDeleteFLTypeInNamespace   = document.getElementById('btn_delete_filter_list_type_namespace') as HTMLButtonElement;
            this.m_btnDeleteFL.disabled = true;
            this.m_btnDeleteFLInNamespace.disabled = true;
            this.m_btnDeleteFLTypeInNamespace.disabled = true;

            // Init user deactivation request.
            this.m_btnDeleteDR          = document.getElementById('btn_delete_user_deactivation_request') as HTMLButtonElement;
            this.m_btnDeleteDR.disabled = true;

            this.m_btnRefreshDR         = document.getElementById('btn_refresh_user_deactivation_request_list') as HTMLButtonElement;

            this.m_btnApp               = document.getElementById('global_radio_app') as HTMLInputElement;
            this.m_btnAppGroup          = document.getElementById('global_radio_app_group') as HTMLInputElement;
            this.m_btnAddApplication    = document.getElementById('btn_application_add') as HTMLButtonElement;
            this.m_btnRemoveApplication = document.getElementById('btn_application_remove') as HTMLButtonElement;
            this.m_btnRemoveApplication.disabled = true;
            this.m_btnApplyToGroup      = document.getElementById('btn_apply_group') as HTMLButtonElement;

            this.m_btnDeleteActivation  = document.getElementById('btn_delete_activation') as HTMLButtonElement;
            this.m_btnBlockActivation   = document.getElementById('btn_block_activations') as HTMLButtonElement;
            this.m_btnDeleteActivation.disabled = true;
            this.m_btnBlockActivation.disabled = true;

            this.m_btnCreateVersion     = document.getElementById('btn_version_add') as HTMLButtonElement;
            this.m_btnDeleteVersion     = document.getElementById('btn_version_delete') as HTMLButtonElement;
            this.m_btnDeleteVersion.disabled = true;
            this.m_btnSystemPlatform    = document.getElementById('btn_sysem_platform') as HTMLButtonElement;

            this.InitButtonHandlers();
        }

        private InitButtonHandlers(): void {
            this.m_btnSignOut.onclick = ((e: MouseEvent) => {
                this.OnSignOutClicked(e);
            });

            this.m_btnCreateUser.onclick = ((e: MouseEvent) => {
                this.OnCreateUserClicked(e);
            });

            this.m_btnDeleteUser.onclick = ((e: MouseEvent) => {
                this.OnDeleteUserClicked(e);
            });

            this.m_btnCreateGroup.onclick = ((e: MouseEvent) => {
                this.OnCreateGroupClicked(e);
            });

            this.m_btnDeleteGroup.onclick = ((e: MouseEvent) => {
                this.OnDeleteGroupClicked(e);
            });

            this.m_btnCloneGroup.onclick = ((e: MouseEvent) => {
                this.OnCloneGroupClicked(e);
            });
            this.m_btnUploadFL.onclick = ((e: MouseEvent) => {
                this.m_filterListUploadController.Show(this.m_tableFilterLists.data());
            });

            this.m_btnDeleteFL.onclick = ((e: MouseEvent) => {
                this.OnDeleteFilterListClicked(e);
            });

            this.m_btnDeleteFLInNamespace.onclick = ((e: MouseEvent) => {
                this.OnDeleteFilterListInNamespaceClicked(e, false);
            });

            this.m_btnDeleteFLTypeInNamespace.onclick = ((e: MouseEvent) => {
                this.OnDeleteFilterListInNamespaceClicked(e, true);
            });

            this.m_btnDeleteDR.onclick = ((e: MouseEvent) => {
                this.OnDeleteUserDeactivationRequestClicked(e);
            });

            this.m_btnRefreshDR.onclick = ((e: MouseEvent) => {
                this.ForceTableRedraw(this.m_tableDeactivationRequests);
            });

            this.m_btnCreateVersion.onclick = ((e: MouseEvent) => {
                this.OnClickAddVersion(e);
            });

            this.m_btnDeleteVersion.onclick = ((e: MouseEvent) => {
                this.OnClickDeleteVersion(e);
            });

            this.m_btnSystemPlatform.onclick = ((e: MouseEvent) => {
                this.OnClickPlatform(e);
            });

            this.m_tabBtnUsers.onclick = ((e: MouseEvent) => {
                this.ViewState = DashboardViewStates.UserListView;
            });

            this.m_tabBtnGroups.onclick = ((e: MouseEvent) => {
                this.ViewState = DashboardViewStates.GroupListView;
            });

            this.m_tabBtnFilterLists.onclick = ((e: MouseEvent) => {
                this.ViewState = DashboardViewStates.FilterListView;
            });

            this.m_tabBtnUserRequest.onclick = ((e: MouseEvent) => {
                this.ViewState = DashboardViewStates.DeactivationRequestListView;
            });


            this.m_tabBtnVersion.onclick = ((e: MouseEvent) => {
                this.ViewState = DashboardViewStates.SystemVersionView;
            });

            this.m_tabBtnAppGroup.onclick = ((e: MouseEvent) => {
                if (this.m_btnApp.checked) {
                    this.ViewState = DashboardViewStates.AppView;
                } else {
                    this.ViewState = DashboardViewStates.AppGroupView;
                }
            });

            this.m_btnApp.onclick = ((e: MouseEvent) => {
                this.ViewState = DashboardViewStates.AppView;
                let itemIsActuallySelected              = $("#app_table").children().next().find(".selected").length > 0 ? true : false;
                this.m_btnRemoveApplication.disabled    = true;
                this.m_btnAddApplication.innerHTML      = '<span class="icon mif-stack"></span>Add <br /> Application';
                this.m_btnRemoveApplication.innerHTML   = '<span class="mif-cancel"></span>Remove <br /> Application';
                this.m_btnApplyToGroup.innerHTML        = '<span class="icon mif-checkmark" style="color:green"></span> Apply<br />To App Group'
            });

            this.m_btnAppGroup.onclick = ((e: MouseEvent) => {
                this.ViewState = DashboardViewStates.AppGroupView;
                let itemIsActuallySelected              = $("#app_group_table").children().next().find(".selected").length > 0 ? true : false;
                this.m_btnRemoveApplication.disabled    = true;
                this.m_btnAddApplication.innerHTML      = '<span class="icon mif-stack"></span>Add <br /> Application <br /> Group';
                this.m_btnRemoveApplication.innerHTML   = '<span class="mif-cancel"></span>Remove <br /> Application <br /> Group';
                this.m_btnApplyToGroup.innerHTML        = '<span class="icon mif-checkmark" style="color:green"></span> Apply<br />To User Group'
            });

            this.m_btnAddApplication.onclick = ((e: MouseEvent) => {
                this.OnAddApplicationClicked(e);
            });

            this.m_btnRemoveApplication.onclick = ((e: MouseEvent) => {
                this.onRemoveApplicationClicked(e);
            });

            this.m_btnApplyToGroup.onclick = ((e: MouseEvent) => {
                this.onApplyToGroupClicked(e);
            });

            this.m_tabBtnActivation.onclick = ((e: MouseEvent) => {
                this.ViewState = DashboardViewStates.AppUserActivationView;
            });

            this.m_btnDeleteActivation.onclick = ((e: MouseEvent) => {
                this.onDeleteAppUserActivationClicked(e);
            });

            this.m_btnBlockActivation.onclick = ((e: MouseEvent) => {
                this.onBlockAppUserActivationClicked(e);
            });
        }

        private OnTableRowCreated(row: Node, data: any[] | Object): void {
            let tableRow = row as HTMLTableRowElement;
            tableRow.onclick = ((e: MouseEvent) => {
                this.OnTableRowClicked(e, data);
            });

            tableRow.ondblclick = ((e: MouseEvent) => {
                this.OnTableRowDoubleClicked(e, data);
            });
        }

        private OnTableRowClicked(e: MouseEvent, data: any[] | Object): void {
            e.stopImmediatePropagation();
            e.stopPropagation();

            if (!$(e.currentTarget).hasClass('dataTables_empty')) {
                $(e.currentTarget).toggleClass('selected').siblings().removeClass('selected');
            }

            let parentTable = $(e.currentTarget).closest('table')[0] as HTMLTableElement;
            let itemIsActuallySelected = $(e.currentTarget).hasClass('selected');

            switch (parentTable.id) {
                case 'user_table':
                    {
                        this.m_btnDeleteUser.disabled = !itemIsActuallySelected;
                    }
                    break;

                case 'group_table':
                    {
                        this.m_btnDeleteGroup.disabled = !itemIsActuallySelected;
                        this.m_btnCloneGroup.disabled = !itemIsActuallySelected;
                    }
                    break;

                case 'filter_table':
                    {
                        this.m_btnDeleteFL.disabled = !itemIsActuallySelected;
                        this.m_btnDeleteFLInNamespace.disabled = !itemIsActuallySelected;
                        this.m_btnDeleteFLTypeInNamespace.disabled = !itemIsActuallySelected;
                    }
                    break;

                case 'user_deactivation_request_table':
                    {
                        this.m_btnDeleteDR.disabled = !itemIsActuallySelected;
                    }
                    break;

                case 'app_table':
                    {
                        this.m_btnRemoveApplication.disabled = !itemIsActuallySelected;
                    }
                    break;

                case 'app_group_table':
                    {
                        this.m_btnRemoveApplication.disabled = !itemIsActuallySelected;
                    }
                    break;
                case 'app_user_activations_table':
                    {
                        this.m_btnDeleteActivation.disabled = !itemIsActuallySelected;
                        this.m_btnBlockActivation.disabled = !itemIsActuallySelected;
                    }
                    break;
                case 'system_versions_table':
                    {
                        this.m_btnDeleteVersion.disabled = !itemIsActuallySelected;
                    }
                    break;
            }
        }

        private OnTableRowDoubleClicked(e: MouseEvent, data: any[] | Object): void {
            e.stopImmediatePropagation();
            e.stopPropagation();

            let selectedRow = e.currentTarget as HTMLTableRowElement;
            let parentTable = $(selectedRow).closest('table')[0];

            switch (parentTable.id) {
                case 'user_table':
                    {
                        let userRecord = new UserRecord();

                        userRecord.ActionCompleteCallback = ((action: string): void => {
                            userRecord.StopEditing();
                            this.ForceTableRedraw(this.m_tableUsers);
                        });

                        userRecord.StartEditing(this.m_allGroups, data);
                    }
                    break;

                case 'group_table':
                    {
                        let groupRecord = new GroupRecord();

                        groupRecord.StartEditing(this.m_allFilters, data);

                        groupRecord.ActionCompleteCallback = ((action: string): void => {
                            groupRecord.StopEditing();

                            this.ForceTableRedraw(this.m_tableGroups);
                            this.ForceTableRedraw(this.m_tableUsers);
                        });
                    }
                    break;

                case 'filter_table':
                    {

                    }
                    break;

                case 'user_deactivation_request_table':
                    {
                        let deactivationRequestRecord = new DeactivationRequestRecord();

                        deactivationRequestRecord.StartEditing(data);

                        deactivationRequestRecord.ActionCompleteCallback = ((action: string): void => {
                            deactivationRequestRecord.StopEditing();
                            this.ForceTableRedraw(this.m_tableDeactivationRequests);
                        });
                    }
                    break;
                case 'app_table':
                    {
                        let appRecord = new AppRecord();

                        appRecord.ActionCompleteCallback = ((action: string): void => {
                            appRecord.StopEditing();
                            this.ForceTableRedraw(this.m_tableAppLists);
                        });

                        appRecord.StartEditing(data);
                    }
                    break;

                case 'app_group_table':
                    {
                        let appGroupRecord = new AppGroupRecord();

                        appGroupRecord.ActionCompleteCallback = ((action: string): void => {
                            appGroupRecord.StopEditing();
                            this.ForceTableRedraw(this.m_tableAppGroupLists);
                        });

                        appGroupRecord.StartEditing(data);
                    }
                    break;
                case 'app_user_activations_table':
                    {
                        let appUserActivationRecord = new AppUserActivationRecord();

                        appUserActivationRecord.ActionCompleteCallback = ((action: string): void => {
                            appUserActivationRecord.StopEditing();
                            this.ForceTableRedraw(this.m_tableActivation);
                        });
                        appUserActivationRecord.StartEditing(data);
                    }
                    break;

                case 'system_versions_table':
                    {
                        let versionRecord = new VersionRecord();

                        versionRecord.ActionCompleteCallback = ((action: string): void => {
                            versionRecord.StopEditing();
                            this.ForceTableRedraw(this.m_tableVersions);
                        });
                        versionRecord.StartEditing(data);
                    }
                    break;
            }
        }

        private GetSelectedRowForTable(table: DataTables.Api): DataTables.RowMethods {

            let selectedRow = $(table).find('tr .selected').first();

            if (selectedRow == null) {
                return null;
            }

            return table.row(selectedRow);
        }

        private ClearSelectedItemsInTable(table: DataTables.Api): void {
            $(table).children().removeClass('selected');
        }

        public ForceTableRedraw(table: DataTables.Api, resetPagination: boolean = false): void {
            table.ajax.reload();
        }

        private OnSignOutClicked(e: MouseEvent): any {
            if (confirm(this.MESSAGE_CONFIRM_SIGNOUT)) {
                $.post(
                    'logout',
                    (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                        location.reload();
                    }
                )
            }
        }

        private OnCreateUserClicked(e: MouseEvent): any {
            let newUser = new UserRecord();

            var usergoup_data = this.m_tableGroups.data();
            newUser.StartEditing(this.m_allGroups, this.m_tableUsers.data()['all_user_roles']);

            newUser.ActionCompleteCallback = ((action: string): void => {
                newUser.StopEditing();
                this.ForceTableRedraw(this.m_tableUsers);
            });
        }

        private OnClickPlatform(e: MouseEvent): any {
            let platformOverlay = new PlatformOverlay();
            platformOverlay.StartEditing();
        }

        private OnClickAddVersion(e: MouseEvent): any {
            let appVersion = new VersionRecord();
            appVersion.ActionCompleteCallback = ((action: string): void => {
                appVersion.StopEditing();
                this.ForceTableRedraw(this.m_tableVersions);
            });

            appVersion.StartEditing();
        }

        private OnClickDeleteVersion(e: MouseEvent): any {
            let selectedItem = this.m_tableVersions.row('.selected').data();

            if (selectedItem != null) {
                var versionObject: VersionRecord;

                try {
                    versionObject = BaseRecord.CreateFromObject(VersionRecord, selectedItem);

                    versionObject.ActionCompleteCallback = ((action: string): void => {
                        this.ForceTableRedraw(this.m_tableVersions);
                    });

                    if (confirm(this.MESSAGE_CONFIRM_USER_DELETE)) {
                        versionObject.Delete();
                    }
                } catch (e) {
                    console.log('Failed to load user record from table selection.');
                    console.log(e);
                }
            }
        }

        private OnDeleteUserClicked(e: MouseEvent): any {
            let selectedItem = this.m_tableUsers.row('.selected').data();

            if (selectedItem != null) {
                var userObject: UserRecord;

                try {
                    userObject = BaseRecord.CreateFromObject(UserRecord, selectedItem);

                    // We want to update the table after a delete.
                    userObject.ActionCompleteCallback = ((action: string): void => {
                        this.ForceTableRedraw(this.m_tableUsers);
                    });

                    if (confirm(this.MESSAGE_CONFIRM_USER_DELETE)) {
                        userObject.Delete();
                    }
                } catch (e) {
                    console.log('Failed to load user record from table selection.');
                    console.log(e);
                }
            }
        }

        private OnCreateGroupClicked(e: MouseEvent): any {
            let groupRecord = new GroupRecord();

            groupRecord.ActionCompleteCallback = ((action: string): void => {

                groupRecord.StopEditing();
                this.ForceTableRedraw(this.m_tableGroups);
            });

            groupRecord.StartEditing(this.m_tableFilterLists.data());
        }

        private OnDeleteGroupClicked(e: MouseEvent): any {
            let selectedItem = this.m_tableGroups.row('.selected').data();

            if (selectedItem != null) {
                var groupObject: GroupRecord;

                try {
                    groupObject = BaseRecord.CreateFromObject(GroupRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    groupObject.ActionCompleteCallback = ((action: string): void => {
                        this.ForceTableRedraw(this.m_tableUsers);
                        this.ForceTableRedraw(this.m_tableGroups);
                    });

                    if (confirm(this.MESSAGE_CONFIRM_GROUP_DELETE)) {
                        groupObject.Delete();
                    }
                } catch (e) {
                    console.log(this.MESSAGE_LOAD_FAIL);
                }
            }
        }

        private OnCloneGroupClicked(e: MouseEvent): any {
            let selectedItem = this.m_tableGroups.row('.selected').data();
            if (selectedItem != null) {
                let groupRecord = new GroupRecord();
                groupRecord.StartEditing(this.m_tableFilterLists.data(), null, selectedItem);
                groupRecord.ActionCompleteCallback = ((action: string): void => {
                    groupRecord.StopEditing();
                    this.ForceTableRedraw(this.m_tableGroups);
                    this.ForceTableRedraw(this.m_tableUsers);
                });
            }
        }

        private OnDeleteFilterListClicked(e: MouseEvent): any {
            let selectedItem = this.m_tableFilterLists.row('.selected').data();

            if (selectedItem != null) {
                var filterListObject: FilterListRecord;

                try {
                    filterListObject = BaseRecord.CreateFromObject(FilterListRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    filterListObject.ActionCompleteCallback = ((action: string): void => {
                        this.loadAllFilters();
                        this.ForceTableRedraw(this.m_tableFilterLists);
                    });

                    if (confirm(this.MESSAGE_CONFIRM_FILTER_DELETE)) {
                        filterListObject.Delete();
                    }
                } catch (e) {
                    console.log(this.MESSAGE_ACTION_FAILED);
                }
            }
        }

        private OnDeleteFilterListInNamespaceClicked(e: MouseEvent, constraintToType: boolean): any {
            let selectedItem = this.m_tableFilterLists.row('.selected').data();

            if (selectedItem != null) {
                var filterListObject: FilterListRecord;

                try {
                    filterListObject = BaseRecord.CreateFromObject(FilterListRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    filterListObject.ActionCompleteCallback = ((action: string): void => {
                        this.loadAllFilters();
                        this.ForceTableRedraw(this.m_tableFilterLists);
                    });

                    let confirmMsg = constraintToType == true ? this.MESSAGE_CONFIRM_FILTER_DELETE_ALL_NAMESPACE: this.MESSAGE_CONFIRM_FILTER_DELETE_TYPE_NAMESPACE;

                    if (confirm(confirmMsg)) {
                        filterListObject.DeleteAllInNamespace(constraintToType);
                    }
                } catch (e) {
                    console.log(this.MESSAGE_ACTION_FAILED);
                }
            }
        }

        private OnDeleteUserDeactivationRequestClicked(e: MouseEvent): any {
            let selectedItem = this.m_tableDeactivationRequests.row('.selected').data();

            if (selectedItem != null) {
                var deactivationRequestObject: DeactivationRequestRecord;

                try {
                    deactivationRequestObject = BaseRecord.CreateFromObject(DeactivationRequestRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    deactivationRequestObject.ActionCompleteCallback = ((action: string): void => {
                        this.ForceTableRedraw(this.m_tableDeactivationRequests);
                    });

                    if (confirm(this.MESSAGE_CONFIRM_DEACTIVATION_REQUEST_DELETE)) {
                        deactivationRequestObject.Delete();
                    }
                } catch (e) {
                    console.log(this.MESSAGE_ACTION_FAILED);
                }
            }
        }

        private OnAddApplicationClicked(e: MouseEvent): any {
            if (this.m_btnApp.checked) {
                let newApp = new AppRecord();

                newApp.StartEditing();
                newApp.ActionCompleteCallback = ((action: string): void => {
                    newApp.StopEditing();
                    this.ForceTableRedraw(this.m_tableAppLists);
                });
            } else {
                let newAppGroup = new AppGroupRecord();
                newAppGroup.StartEditing();
                newAppGroup.ActionCompleteCallback = ((action: string): void => {

                    newAppGroup.StopEditing();
                    this.ForceTableRedraw(this.m_tableAppGroupLists);
                });
            }
        }

        private onRemoveApplicationClicked(e: MouseEvent): any {
            this.m_btnRemoveApplication.disabled = true;
            if (this.m_btnApp.checked) {
                let selectedItem = this.m_tableAppLists.row('.selected').data();
                if (selectedItem != null) {
                    var appObj: AppRecord;

                    try {
                        appObj = BaseRecord.CreateFromObject(AppRecord, selectedItem);

                        // We want to update both users and groups after delete.
                        appObj.ActionCompleteCallback = ((action: string): void => {
                            this.ForceTableRedraw(this.m_tableAppLists);
                        });

                        if (confirm(this.MESSAGE_CONFIRM_APPLICATION_DELETE)) {
                            appObj.Delete();
                        }
                    } catch (e) {
                        this.m_btnRemoveApplication.disabled = false;
                        console.log(this.MESSAGE_LOAD_FAIL);
                    }
                }
            } else {
                let selectedItem = this.m_tableAppGroupLists.row('.selected').data();
                if (selectedItem != null) {
                    var appGroupObj: AppGroupRecord;

                    try {
                        appGroupObj = BaseRecord.CreateFromObject(AppGroupRecord, selectedItem);

                        appGroupObj.ActionCompleteCallback = ((action: string): void => {
                            this.ForceTableRedraw(this.m_tableAppGroupLists);
                        });

                        if (confirm(this.MESSAGE_CONFIRM_APPGROUP_DELETE)) {
                            appGroupObj.Delete();
                        }
                    } catch (e) {
                        this.m_btnRemoveApplication.disabled = false;
                        console.log(this.MESSAGE_LOAD_FAIL);
                    }
                }
            }
        }

        private onApplyToGroupClicked(e: MouseEvent): any {
            if (this.m_btnApp.checked) {
                let apply_app_to_app_group_overlay = new ApplyAppToAppGroup(this);
                apply_app_to_app_group_overlay.Show();
            } else {
                let apply_app_group_to_user_group_overlay = new ApplyAppgroupToUsergroup(this);
                apply_app_group_to_user_group_overlay.Show();
            }
        }

        private onDeleteAppUserActivationClicked(e: MouseEvent): any {
            let selectedItem = this.m_tableActivation.row('.selected').data();

            if (selectedItem != null) {
                var appUserActivationObject: AppUserActivationRecord;

                try {
                    appUserActivationObject = BaseRecord.CreateFromObject(AppUserActivationRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    appUserActivationObject.ActionCompleteCallback = ((action: string): void => {
                        this.ForceTableRedraw(this.m_tableActivation);
                    });

                    if (confirm(this.MESSAGE_CONFIRM_ACTIVATION_DELETE)) {
                        appUserActivationObject.Delete();
                    }
                } catch (e) {
                    console.log(this.MESSAGE_ACTION_FAILED);
                }
            }
        }

        private onBlockAppUserActivationClicked(e: MouseEvent): any {
            let selectedItem = this.m_tableActivation.row('.selected').data();

            if (selectedItem != null) {
                var appUserActivationObject: AppUserActivationRecord;
                try {
                    appUserActivationObject = BaseRecord.CreateFromObject(AppUserActivationRecord, selectedItem);

                    appUserActivationObject.ActionCompleteCallback = ((action: string): void => {
                        this.ForceTableRedraw(this.m_tableActivation);
                    });

                    if (confirm(this.MESSAGE_CONFIRM_ACTIVATION_BLOCK)) {
                        appUserActivationObject.Block();
                    }
                } catch (e) {
                    console.log(this.MESSAGE_ACTION_FAILED);
                }
            }
        }

        private set ViewState(value: DashboardViewStates) {

            this.m_viewUser.style.display                   = "none";
            this.m_viewGroup.style.display                  = "none";
            this.m_viewFilter.style.display                 = "none";
            this.m_viewDeactivationReq.style.display        = "none";
            this.m_viewApplication.style.display            = "none";
            this.m_viewAppGroup.style.display               = "none";
            this.m_viewActivation.style.display             = "none";
            this.m_viewVersion.style.display                = "none";

            switch (value) {
                case DashboardViewStates.UserListView:
                    {
                        this.ForceTableRedraw(this.m_tableUsers);
                        this.m_viewUser.style.display = "block";
                        this.m_btnDeleteUser.disabled = true;
                    }
                    break;

                case DashboardViewStates.GroupListView:
                    {
                        this.ForceTableRedraw(this.m_tableGroups);
                        this.m_viewGroup.style.display = "block";
                        this.m_btnDeleteGroup.disabled = true;
                        this.m_btnCloneGroup.disabled = true;
                    }
                    break;

                case DashboardViewStates.FilterListView:
                    {
                        this.ForceTableRedraw(this.m_tableFilterLists);
                        this.m_viewFilter.style.display = "block";
                        this.m_btnDeleteFL.disabled = true;
                        this.m_btnDeleteFLInNamespace.disabled = true;
                        this.m_btnDeleteFLTypeInNamespace.disabled = true;
                    }
                    break;

                case DashboardViewStates.DeactivationRequestListView:
                    {
                        this.ForceTableRedraw(this.m_tableDeactivationRequests);
                        this.m_viewDeactivationReq.style.display = "block";
                        this.m_btnDeleteDR.disabled = true;
                    }
                    break;

                case DashboardViewStates.AppView:
                    {
                        this.ForceTableRedraw(this.m_tableAppLists);
                        this.m_viewApplication.style.display = "block";
                        this.m_btnRemoveApplication.disabled = true;
                    }
                    break;

                case DashboardViewStates.AppGroupView:
                    {
                        this.ForceTableRedraw(this.m_tableAppGroupLists);
                        this.m_viewAppGroup.style.display = "block";
                        this.m_btnRemoveApplication.disabled = true;
                    }
                    break;
                case DashboardViewStates.AppUserActivationView:
                    {
                        this.ForceTableRedraw(this.m_tableActivation);
                        this.m_viewActivation.style.display = "block";
                        this.m_btnDeleteActivation.disabled = true;
                        this.m_btnBlockActivation.disabled = true;
                    }
                    break;
                case DashboardViewStates.SystemVersionView:
                    {
                        this.ForceTableRedraw(this.m_tableVersions);
                        this.m_viewVersion.style.display = "block";
                        this.m_btnDeleteVersion.disabled = true;
                    }
                    break;
            }

            this.m_currentViewState = value;
        }

        private get ViewState(): DashboardViewStates {
            return this.m_currentViewState;
        }
    }

    let citadelDashboard: Dashboard;

    document.onreadystatechange = (event: ProgressEvent) => {
        if (document.readyState.toUpperCase() == "complete".toUpperCase()) {
            $.ajaxPrefilter(function (options, originalOptions, xhr) { // this will run before each request
                var token = $('meta[name="csrf-token"]').attr('content'); // or _token, whichever you are using
                if (token) {
                    return xhr.setRequestHeader('X-CSRF-TOKEN', token); // adds directly to the XmlHttpRequest Object
                }
            });

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            citadelDashboard = new Dashboard();
        }
    };
}
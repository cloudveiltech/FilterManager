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

namespace Citadel
{
    /**
     * An enum containing all valid view states for the administrator dashboard.
     * 
     * @enum {number}
     */
    enum DashboardViewStates
    {
        UserListView,
        GroupListView,
        FilterListView,
        DeactivationRequestListView,
        AppView,
        AppGroupView,
        AppUserActivationView,
        SystemVersionView
    }

    /**
     * The Dashboard class is used to drive the UI for the administrator
     * Dashboard. The Dashboard enables the administrator to create and manage
     * user accounts, create and manage group policies, assign users to group
     * policies, create and manage filtering lists and other filtering data and
     * assign them to group policies.
     * 
     * @class Dashboard
     */
    export class Dashboard
    {

        //
        // ────────────────────────────────────────────────────────────────────────────────────────── I ──────────
        //   :::::: M A I N   M E N U   B U T T O N   E L E M E N T S : :  :   :    :     :        :          :
        // ────────────────────────────────────────────────────────────────────────────────────────────────────
        //

        /**
         * Clickable main menu tab that hosts all menu action buttons for user
         * management.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_tabBtnUsers: HTMLLinkElement;
        private m_tabBtnGroups: HTMLLinkElement;
        private m_tabBtnFilterLists: HTMLLinkElement;
        private m_tabBtnUserRequest: HTMLLinkElement;
        private m_tabBtnAppGroup: HTMLLinkElement;
        private m_tabBtnAppUserActivation: HTMLLinkElement;
        private m_tabBtnSystemVersion: HTMLLinkElement;

        /**
         * Buttons in User Management View
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnCreateUser: HTMLButtonElement;
        private m_btnDeleteUser: HTMLButtonElement;

        /// Group management tab elements.

        /**
         * Buttons in Group Management View
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnCreateGroup: HTMLButtonElement;
        private m_btnDeleteGroup: HTMLButtonElement;
        private m_btnCloneGroup: HTMLButtonElement;
        
        /// Filter list/data management tab elements.

        /**
         * Buttons in Filter Management View
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf Dashboard
         */
        private m_btnUploadFilterLists: HTMLButtonElement;
        private m_btnDeleteFilterList: HTMLButtonElement;
        private m_btnDeleteFilterListInNamespace: HTMLButtonElement;
        private m_btnDeleteFilterListTypeInNamespace: HTMLButtonElement;

        /**
         * Button to initiate the process of deleting an existing, selected 
         * filter list.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf Dashboard
         */
        private m_btnDeleteUserDeactivationRequest: HTMLButtonElement;
        private m_btnRefreshUserDeactivationRequests: HTMLButtonElement;

        /// Global Applist & AppGroupList tab elements.

        /**
         * Button to initiate the process of Add a new item to list.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnAddItem: HTMLButtonElement;
        private m_btnRemoveItem: HTMLButtonElement;
        private m_btnApplyToGroup: HTMLButtonElement;
        /**
         * RadioButton to indicate AppList
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf DashboardMenu
         */
        private m_btnApp: HTMLInputElement;
        private m_btnAppGroup: HTMLInputElement;

        /**
         * Button to initiate the process of removing item an existing, selected
         * item.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnDeleteAppUserActivation: HTMLButtonElement;
        private m_btnBlockAppUserActivation: HTMLButtonElement;
        
        
        //
        // ──────────────────────────────────────────────────────────────────────────────────────── II ──────────
        //   :::::: T O P   M E N U   B A R   U I   E L E M E N T S : :  :   :    :     :        :          :
        // ──────────────────────────────────────────────────────────────────────────────────────────────────
        //

        /**
         * Represents the sign out button.
         * 
         * @private
         * 
         * @memberOf Dashboard
         */
        private m_btnSignOut;
        /**
         * Buttons in User Management View
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnCreateVersion: HTMLButtonElement;
        private m_btnDeleteVersion: HTMLButtonElement;
        private m_btnSystemPlatform: HTMLButtonElement;
        //
        // ──────────────────────────────────────────────────────────────────────────────── III ──────────
        //   :::::: D A T A   P R E S E N T E R   V I E W S : :  :   :    :     :        :          :
        // ──────────────────────────────────────────────────────────────────────────────────────────
        //

        /**
         * Host container where user related data is displayed.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_viewUserManagement: HTMLDivElement;
        private m_viewGroupManagement: HTMLDivElement;
        private m_viewFilterManagement: HTMLDivElement;
        private m_viewUserDeactivationRequestManagement: HTMLDivElement;
        private m_viewAppManagement: HTMLDivElement;
        private m_viewAppGroupManagement: HTMLDivElement;
        private m_viewAppUserActivationManagement: HTMLDivElement;
        private m_viewSystemVersionManagement: HTMLDivElement;
        //
        // ────────────────────────────────────────────────────────────── IV ──────────
        //   :::::: D A T A   T A B L E S : :  :   :    :     :        :          :
        // ────────────────────────────────────────────────────────────────────────
        //

        /**
         * DataTables.
         * 
         * @private
         * @type {DataTables.DataTable}
         * @memberOf Dashboard
         */
        private m_tableUsers: DataTables.DataTable;
        private m_tableGroups: DataTables.DataTable;
        private m_tableFilterLists: DataTables.DataTable;
        private m_tableUserDeactivationRequests: DataTables.DataTable;
        public m_tableAppLists: DataTables.DataTable;
        public m_tableAppGroupLists: DataTables.DataTable;
        private m_tableAppUserActivationTable: DataTables.DataTable;
        private m_tableSystemVersions: DataTables.DataTable;
        /**
         * Represents the current view state of the application. This must not
         * be accessed directly, but rather the getters and setters should be
         * called, even though they are private as well. The getters and setter
         * incorporate functionality that enforces the validity of various view
         * states by synchronizing visual elements and data as required for each
         * different state.
         * 
         * @private
         * @type {DashboardViewStates}
         * @memberOf Dashboard
         */
        private m_currentViewState: DashboardViewStates;

        /**
         * Modal overlay for handling filter list uploading.
         * 
         * @private
         * @type {ListUploadOverlay}
         * @memberOf Dashboard
         */
        private m_filterListUploadController: ListUploadOverlay;

        /**
         * Dragula, we use to allow editing of a groups assigned filters.
         * 
         * @private
         * @type {dragula.Drake}
         * @memberOf Dashboard
         */
        private m_filterGroupSelectionArea: dragula.Drake;

        private m_allFilters;
        private m_allGroups;

        /**
         * Creates an instance of Dashboard.
         * 
         * 
         * @memberOf Dashboard
         */
        constructor()
        {
            // Setup nav.
            this.ConstructNavigation();
            this.loadAllFilters();
            this.loadAllGroups();
            // Initialize views.
            this.ConstructManagementViews();

            this.m_filterListUploadController = new ListUploadOverlay();

            // Force table redraw any time an upload of new lists fails or succeeds.
            this.m_filterListUploadController.UploadCompleteCallback = (():void =>
            {
                this.ForceTableRedraw(this.m_tableFilterLists);
                this.ForceTableRedraw(this.m_tableGroups);
                this.m_filterListUploadController.Hide();
                this.loadAllFilters();
            });
            this.m_filterListUploadController.UploadFailedCallback = (():void =>
            {
                this.ForceTableRedraw(this.m_tableFilterLists);
                this.ForceTableRedraw(this.m_tableGroups);
                this.m_filterListUploadController.Hide();
            });
        }

        private loadAllFilters(): void {
            let ajaxSettings: JQueryAjaxSettings = {
                method:"GET",
                timeout: 60000,
                url: "api/admin/filterlist/all",
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    this.m_allFilters = data;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(errorThrown);
                }
            }

            $.post(ajaxSettings);
        }

        private loadAllGroups(): void {
            let ajaxSettings: JQueryAjaxSettings = {
                method:"GET",
                timeout: 60000,
                url: "api/admin/group/all",
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    this.m_allGroups = data;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(errorThrown);
                }
            }

            $.post(ajaxSettings);
        }

        
        private ConstructManagementViews(): void
        {
            // Grab main view container references.
            this.m_viewUserManagement = document.getElementById('view_user_management') as HTMLDivElement;
            this.m_viewGroupManagement = document.getElementById('view_group_management') as HTMLDivElement;
            this.m_viewFilterManagement = document.getElementById('view_filter_management') as HTMLDivElement;
            this.m_viewUserDeactivationRequestManagement = document.getElementById('view_user_deactivation_request_management') as HTMLDivElement;
            this.m_viewAppManagement = document.getElementById('view_app_management') as HTMLDivElement;
            this.m_viewAppGroupManagement = document.getElementById('view_app_group_management') as HTMLDivElement;
            this.m_viewAppUserActivationManagement = document.getElementById('view_app_user_activations_management') as HTMLDivElement;
            this.m_viewSystemVersionManagement = document.getElementById('view_system_versions_management') as HTMLDivElement;
            // Build the tables.
            this.ConstructTables();

            // Build dragula for editing group's assigned filters.
            this.ConstructDragula();
            // Set the current state to the user lists view.
            this.ViewState = DashboardViewStates.UserListView;
        }

        private ConstructDragula(): void
        {
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
                isContainer: ((element: Element) : any =>
                {
                    // Easy way to make dragula containers. Just add the class below.
                    return element.classList.contains('dragula-container');
                }),
                accepts: ((el?: Element, target?: Element, source?: Element, sibling?: Element): boolean =>
                {
                    // Don't allow NLP filters to be anything but blacklist. NLP is not
                    // designed to function any other way.
                    //
                    // Same goes for triggers. They're blacklist-specific as well.
                    var attr = el.getAttribute('citadel-filter-list-type');
                    if((attr.toLowerCase() === 'nlp' || attr.toLowerCase() === 'trigger') && (target.id != 'group_blacklist_filters' && target.id != 'group_unassigned_filters'))
                    {
                        return false;
                    }

                    // Allow everything else to be dragged anywhere.
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
        private ConstructTables(): void
        {
            let height = $("body").height();
            
            let userTableConstruction = (() =>
            {  
                
                let userTableColumns: DataTables.ColumnSettings[] =
                    [
                        {
                            title: 'User Id',
                            data: 'id',
                            visible: false,                            
                        },
                        {
                            // This field belongs to a different table, so it
                            // needs to be included on the server side!
                            title: 'Group Id',
                            data: 'group.id',
                            defaultContent: 'Unassigned',                            
                            visible: false
                        },
                        {
                            title: 'User Name',
                            data: 'name',
                            className: 'content-left',
                            visible: true,
                            width: '200px',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {       
                                var name = data;        
                                if(data.length > 15) {
                                    name = data.substring(0,11) + "...";
                                }
                                return "<span class='mif-user self-scale-group fg-green'></span>  <b title='"+data+"'>" + name + "</b>";
                            })
                        },
                        {
                            // This field belongs to a different table, so it
                            // needs to be included on the server side!
                            title: 'Group Name',
                            data: 'group.name',
                            className: 'content-left',
                            defaultContent: 'Unassigned',
                            visible: true,
                            width: '220px'
                        },
                        {
                            title: 'User Email',
                            data: 'email',
                            className: 'content-left',
                            visible: true
                        },
                        {
                            title: 'Roles',                            
                            data: 'roles[, ].display_name',
                            className: 'content-left',
                            defaultContent: 'None',
                            width: '180px'
                        },
                        {
                            // This field belongs to a different database, so it
                            // needs to be included on the server side!
                            title: '#Lic.Used / #Licenses',
                            data: 'activations_allowed',
                            className: 'content-center',
                            visible: true,                            
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                return "<span class='license_used'>"+row.activations_used+"</span> / <span class='license_allowed'>"+data+"</span>";
                            }),
                            width: '250px'
                        },
                        {
                            title: 'Report Level',
                            data: 'report_level',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                var chk_report = (data === 1) ? "checked":"";
                                var str = "<label class='switch-original'><input type='checkbox' id='user_report_"+row.id+"' "+chk_report+" /><span class='check'></span></label>";
                                return str;
                            }),
                            className: 'content-center',
                            width: '150px'
                        },
                        {
                            title: 'Status',
                            data: 'isactive',
                            className: 'content-center',
                            visible: true,                            
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                if(data == null)
                                {
                                    return "";
                                }

                                if(data == 1)
                                {
                                    return "<span class='active-s status'>Active</span>";
                                }
                                else
                                {
                                    return "<span class='inactive-s status'>Inactive</span>";
                                }
                            }),
                            width: '100px'
                        },
                        {
                            title: 'Date Registered',
                            data: 'created_at',
                            visible: true,
                            width: '180px',
                            className:'updated_date'
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let userTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/users",
                        dataSrc: function ( json ) {
                            return json.data;
                        },                        
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        method: "GET",
                        error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                        {
                            if(jqXHR.status > 399 && jqXHR.status < 500)
                            {
                                // Almost certainly auth related error. Redirect to login
                                // by signalling for logout.
                                ////window.location.href = 'login.php?logout';
                            }
                        })
                    };

                // Define user table settings, ENSURE TO INCLUDE AJAX SETTINGS!
               
                let usersTableSettings: DataTables.Settings = {
                    scrollY: ''+ (height - 470) + 'px',
                    scrollCollapse: true,
                    autoWidth: true,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    deferLoading: 0,
                    columns: userTableColumns,
                    ajax: userTablesLoadFromAjaxSettings,
                    rowCallback: ((row: Node, data: any[] | Object): void =>
                    {
                        this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: (( settings ): void =>
                    {
                        let that = this;
                        $("#user_table").off("change", "input[type='checkbox']");
                        $("#user_table").on("change", "input[type='checkbox']", function () {
                            let id_str = this.id;
                            let val = 0;
                            if (this.checked) {
                                val = 1;
                            }
                            let checkAjaxSettings: JQueryAjaxSettings = {
                                method:"POST",
                                timeout: 60000,
                                url: "api/admin/users/update_field",
                                data: {id: id_str, value: val},
                                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                                    that.ForceTableRedraw(that.m_tableUsers);
                                    return false;
                                },
                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    console.log(errorThrown);
                                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                                        // Almost certainly auth related error. Redirect to login
                                        // by signalling for logout.
                                        //window.location.href = 'login.php?logout';
                                    } else {
                                        
                                    }
                                }
                            }
                
                            $.post(checkAjaxSettings);
                        });
                    })
                };
                this.m_tableUsers = $('#user_table').DataTable(usersTableSettings);
            });

            let groupTableConstruction = (() =>
            {
                let groupTableColumns: DataTables.ColumnSettings[] =
                    [
                        {
                            title: 'Group Id',
                            data: 'id',
                            visible: false
                        },
                        {
                            title: 'Group Name',
                            data: 'name',
                            visible: true,
                            className: 'content-left',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {               
                                return "<span class='mif-organization self-scale-group fg-green'></span> - <b title='" + row.user_count+ " users are registered in this group.'>" + data + "</b> <span class='user_count'>("+row.user_count+")</span>";
                            })
                        },
                        {
                            title: 'Primary DNS',
                            data: 'primary_dns',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                var str = "";
                                if(data===null || data==="") {
                                    
                                } else {

                                    str = "<span class='mif-flow-tree self-scale-3'></span> " + data;
                                }
                                return str;
                            }),
                            className: 'content-left',
                            width: '190px'
                        },
                        {
                            title: 'Secondary DNS',
                            data: 'secondary_dns',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                var str = "";
                                if(data===null ||data==="") {
                                    
                                } else {

                                    str = "<span class='mif-flow-tree self-scale-3'></span> " + data;
                                }
                                return str;
                            }),
                            className: 'content-left',
                            width: '190px'
                        },
                        {
                            title: 'Terminate/Internet/Threshold',
                            data: 'terminate',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                if(data === null || data === "")
                                {
                                    return "";
                                }
                                var app_cfg = JSON.parse(row.app_cfg);
                                var chk_terminate = app_cfg.CannotTerminate ? "checked":"";
                                var chk_internet = app_cfg.BlockInternet ? "checked":"";
                                var chk_threshold = app_cfg.UseThreshold ? "checked":"";
                                //var str = "<label class='"+chk_terminate+"'></label>";
                                var str = "<label class='switch-original'><input type='checkbox' id='group_terminate_"+row.id+"' "+chk_terminate+" /><span class='check'></span></label>";
                                str += "<label class='switch-original'><input type='checkbox' id='group_internet_"+row.id+"' "+chk_internet+" /><span class='check'></span></label>";
                                str += "<label class='switch-original'><input type='checkbox' id='group_threshold_"+row.id+"' "+chk_threshold+" /><span class='check'></span></label>";
                                return str;
                            }),
                            className: 'content-left',
                            width: '290px'
                        },
                        {
                            title: 'Bypass',
                            data: 'bypass',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                if(data === null || data === "")
                                {
                                    return "";
                                }
                                var app_cfg = JSON.parse(row.app_cfg);
                                var bypass_permitted = app_cfg['BypassesPermitted']===null || app_cfg['BypassesPermitted']===0 ? "": "<span class='mif-clipboard self-scale-4 fg-cyan'></span> " + app_cfg['BypassesPermitted'] + "<span class='unit_day'>/day</span>";
                                var bypass_duration = app_cfg['BypassDuration']===null || app_cfg['BypassDuration']===0?"":" <span class='mif-alarm-on self-scale-5 fg-pink'></span> " + app_cfg['BypassDuration'] + "<span class='unit_min'>mins</span>";
                                return bypass_permitted + bypass_duration;
                            }),
                            className: 'content-left',
                            width: '180px'
                        },
                        {
                            title: 'Report Level',
                            data: 'report_level',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                //console.log(row.id, data);
                                var app_cfg = JSON.parse(row.app_cfg);
                                
                                var chk_report = (app_cfg.ReportLevel == 1) ? "checked":"";
                                //return app_cfg.ReportLevel;
                                return "<label class='switch-original'><input type='checkbox' id='group_report_"+row.id+"' "+chk_report+" /><span class='check'></span></label>";
                            }),
                            className: 'content-center',
                            width: '150px'
                        },
                        {
                            title: 'Status',
                            data: 'isactive',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                if(data == null)
                                {
                                    return "";
                                }

                                if(data == 1)
                                {
                                    return "<span class='active-s status'>Active</span>";
                                }
                                else
                                {
                                    return "<span class='inactive-s status'>Inactive</span>";
                                }
                            }),
                            className: 'content-center',
                            width: '60px'
                        },
                        {
                            title: 'Date Registered',
                            data: 'created_at',
                            visible: true,
                            width: '180px',
                            className:'updated_date'
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let groupTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/groups",
                        dataSrc: function ( json ) {
                            return json.data;
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        method: "GET",
                        error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                        {
                            if(jqXHR.status > 399 && jqXHR.status < 500)
                            {
                                // Almost certainly auth related error. Redirect to login
                                // by signalling for logout.
                                //////window.location.href = 'login.php?logout';
                            }
                        })
                    };

                // Define group table settings, ENSURE TO INCLUDE AJAX SETTINGS!
                let groupTableSettings: DataTables.Settings =
                    {
                        scrollY: ''+ (height - 470) + 'px',
                        scrollCollapse: true,
                        autoWidth: true,
                        stateSave: true,
                        processing: true,
                        serverSide: true,
                        responsive: true,
                        deferLoading: 0,
                        columns: groupTableColumns,
                        ajax: groupTablesLoadFromAjaxSettings,

                        // We grab the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        }),
                        drawCallback: (( settings ) :void =>
                        {
                            let that = this;
                            $("#group_table").off("change", "input[type='checkbox']");
                            $("#group_table").on("change", "input[type='checkbox']", function () {
                                let id_str = this.id;
                                let val = 0;
                                if (this.checked) {
                                    val = 1;
                                }
                                let checkAjaxSettings: JQueryAjaxSettings = {
                                    method:"POST",
                                    timeout: 60000,
                                    url: "api/admin/groups/update_field",
                                    data: {id: id_str, value: val},
                                    success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                                        that.ForceTableRedraw(that.m_tableGroups);
                                        return false;
                                    },
                                    error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                        console.log(errorThrown);
                                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                                            // Almost certainly auth related error. Redirect to login
                                            // by signalling for logout.
                                            //window.location.href = 'login.php?logout';
                                        } else {
                                            
                                        }
                                    }
                                }
                    
                                $.post(checkAjaxSettings);
                            });
                        })
                    };

                this.m_tableGroups = $('#group_table').DataTable(groupTableSettings);
            });

            let filterTableConstruction = (() =>
            {
                let filterTableColumns: DataTables.ColumnSettings[] =
                    [
                        {
                            title: 'ID',
                            data: 'id',
                            visible: false
                        },
                        {
                            title: 'Category Name',
                            data: 'category',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                if(data == null)
                                {
                                    return "";
                                }

                                if(row.type === "Filters")
                                {
                                    return "<span class='mif-filter fg-green'></span> " + data;
                                }
                                else
                                {
                                    return "<span class='mif-warning fg-red'></span> " + data;
                                }
                            })
                        },
                        {
                            title: 'List Group Name',
                            data: 'namespace',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                if(data == null)
                                {
                                    return "";
                                }
                                return data;
                            })
                        },
                        {
                            title: 'Type',
                            data: 'type',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                if(data == null)
                                {
                                    return "";
                                }

                                if(data === "Filters")
                                {
                                    return "<span class='mif-filter fg-green'></span> " + data;
                                }
                                else
                                {
                                    return "<span class='mif-warning fg-red'></span> " + data;
                                }
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
                            className:'updated_date'
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let filterTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/filterlists",
                        dataSrc: function ( json ) {
                            return json.data;
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        method: "GET",
                        error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                        {
                            if(jqXHR.status > 399 && jqXHR.status < 500)
                            {
                                // Almost certainly auth related error. Redirect to login
                                // by signalling for logout.
                                //////window.location.href = 'login.php?logout';
                            }
                        })
                    };

                // Define filter table settings, ENSURE TO INCLUDE AJAX SETTINGS!
                let filterTableSettings: DataTables.Settings =
                    {
                        scrollY: ''+ (height - 470) + 'px',
                        scrollCollapse: true,
                        autoWidth: true,
                        stateSave: true,
                        processing: true,
                        serverSide: true,
                        responsive: true,
                        deferLoading: 0,
                        columns: filterTableColumns,
                        ajax: filterTablesLoadFromAjaxSettings,

                        // We grab the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        })
                    };

                this.m_tableFilterLists = $('#filter_table').DataTable(filterTableSettings);

            });

            let deactivationRequestConstruction = (() =>
            {
                let userDeactivationRequestTableColumns: DataTables.ColumnSettings[] =
                    [
                        {
                            title: 'ID',
                            data: 'id',
                            visible: false
                        },
                        {
                            title: 'User Full Name',
                            data: 'user.name',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                return "<span class='mif-user self-scale-group fg-green'></span>  <b title='"+data+"'>" + data + "</b>"
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
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                if(data == null)
                                {
                                    return "";
                                }

                                var chk_report = (data === 1) ? "checked":"";
                                var str = "<label class='switch-original'><input type='checkbox' id='deactivatereq_enabled_"+row.id+"' "+chk_report+" /><span class='check'></span></label>";
                                return str;
                            }),
                            width: '100px'
                        },
                        {
                            title: 'Date Requested',
                            data: 'created_at',
                            visible: true,
                            width: '180px',
                            className:'updated_date'
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let userDeactivationRequestTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/deactivationreq",
                        dataSrc: function ( json ) {
                            return json.data;
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        
                        method: "GET",
                        error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                        {
                            if(jqXHR.status > 399 && jqXHR.status < 500)
                            {
                                // Almost certainly auth related error. Redirect to login
                                // by signalling for logout.
                                //////window.location.href = 'login.php?logout';
                            }
                        })
                    };

                // Define filter table settings, ENSURE TO INCLUDE AJAX SETTINGS!
                let userDeactivationRequestTableSettings: DataTables.Settings =
                    {
                        scrollY: ''+ (height - 470) + 'px',
                        scrollCollapse: true,
                        autoWidth: true,
                        stateSave: true,
                        processing: true,
                        serverSide: true,
                        responsive: true,
                        deferLoading: 0,
                        columns: userDeactivationRequestTableColumns,
                        ajax: userDeactivationRequestTablesLoadFromAjaxSettings,

                        // We grap the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        }),
                        drawCallback: (( settings ) :void =>
                        {
                            let that = this;
                            $("#user_deactivation_request_table").off("change", "input[type='checkbox']");
                            $("#user_deactivation_request_table").on("change", "input[type='checkbox']", function () {
                                let id_str = this.id;
                                let val = 0;
                                if (this.checked) {
                                    val = 1;
                                }
                                let checkAjaxSettings: JQueryAjaxSettings = {
                                    method:"POST",
                                    timeout: 60000,
                                    url: "api/admin/deactivationreq/update_field",
                                    data: {id: id_str, value: val},
                                    success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                                        that.ForceTableRedraw(that.m_tableUserDeactivationRequests);
                                        return false;
                                    },
                                    error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                        console.log(errorThrown);
                                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                                            // Almost certainly auth related error. Redirect to login
                                            // by signalling for logout.
                                            //window.location.href = 'login.php?logout';
                                        } else {
                                            
                                        }
                                    }
                                }
                    
                                $.post(checkAjaxSettings);
                            });
                        })
                    };

                this.m_tableUserDeactivationRequests = $('#user_deactivation_request_table').DataTable(userDeactivationRequestTableSettings);
            });

            let appListTableConstruction = (() =>
            {
                let appListTableColumns: DataTables.ColumnSettings[] =
                    [
                        {
                            title: 'App Id',
                            data: 'id',
                            visible: false
                        },
                        {
                            title: 'Application Name',
                            data: 'name',
                            visible: true,
                            width: '200px',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                return "<span class='mif-file-binary self-scale-group fg-green'></span>  <b title='"+data+"'>" + data + "</b>";
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
                            bSortable: false,
                            visible: true
                        },
                        {
                            title: 'Date Modified',
                            data: 'updated_at',
                            visible: true,
                            width: '220px'
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let appListTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/app",
                        dataSrc: function ( json ) {
                            return json.data;
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        method: "GET",
                        error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                        {
                            if(jqXHR.status > 399 && jqXHR.status < 500)
                            {
                                // Almost certainly auth related error. Redirect to login
                                // by signalling for logout.
                                //////window.location.href = 'login.php?logout';
                            }
                        })
                    };

                // Define AppList table settings, ENSURE TO INCLUDE AJAX SETTINGS!
                let appListTableSettings: DataTables.Settings =
                    {
                        scrollY: ''+ (height - 470) + 'px',
                        scrollCollapse: true,
                        autoWidth: true,
                        stateSave: true,
                        processing: true,
                        serverSide: true,
                        responsive: true,
                        deferLoading: 0,
                        columns: appListTableColumns,
                        ajax: appListTablesLoadFromAjaxSettings,

                        // We grab the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        })
                    };

                this.m_tableAppLists = $('#app_table').DataTable(appListTableSettings);
            });
            let appGroupListTableConstruction = (() =>
            {
                let appGroupListTableColumns: DataTables.ColumnSettings[] =
                    [
                        {
                            title: 'App Group Id',
                            data: 'id',
                            visible: false
                        },
                        {
                            title: 'App Group Name',
                            data: 'group_name',
                            visible: true,
                            width: '200px',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                return "<span class='mif-file-folder self-scale-group fg-green'></span>  <b title='"+data+"'>" + data + "</b>";
                            })
                        },
                        {
                            title: 'Linked Apps',
                            data: 'app_names',
                            bSortable: false,
                            visible: true
                        },
                        {
                            title: 'Date Modified',
                            data: 'updated_at',
                            visible: true,
                            width: '230px'
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let appGroupListTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/app_group",
                        dataSrc: function ( json ) {
                            return json.data;
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        method: "GET",
                        error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                        {
                            if(jqXHR.status > 399 && jqXHR.status < 500)
                            {
                                // Almost certainly auth related error. Redirect to login
                                // by signalling for logout.
                                //////window.location.href = 'login.php?logout';
                            }
                        })
                    };

                // Define AppList table settings, ENSURE TO INCLUDE AJAX SETTINGS!
                let appGroupListTableSettings: DataTables.Settings =
                    {
                        scrollY: ''+ (height - 470) + 'px',
                        scrollCollapse: true,
                        autoWidth: true,
                        stateSave: true,
                        processing: true,
                        serverSide: true,
                        responsive: true,
                        deferLoading: 0,
                        columns: appGroupListTableColumns,
                        ajax: appGroupListTablesLoadFromAjaxSettings,

                        // We grab the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        })
                    };

                this.m_tableAppGroupLists = $('#app_group_table').DataTable(appGroupListTableSettings);
            });

            let appUserActivationTableConstruction = (() =>
            {
                let appUserActivationTableColumns: DataTables.ColumnSettings[] =
                    [
                        {
                            title: 'Activation Id',
                            data: 'id',
                            visible: false
                        },
                        {
                            title: 'User',
                            data: 'user.name',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                var name = data;        
                                if(data.length > 17) {
                                    name = data.substring(0,14) + "...";
                                }
                                return "<span class='mif-user self-scale-group fg-green'></span>  <b title='"+data+"'>" + name + "</b>";
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
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                var user_ip = "<span class='mif-flow-tree self-scale-3'></span>";
                                var name = data;
                                if(data === null || data === undefined) {
                                    name = "-";
                                    data = "*";
                                    return "";
                                } else {

                                    if(data.length > 20) {
                                        name = data.substring(0,15) + "...";
                                    }
                                }
                                return user_ip + " <span title='"+data+"'>"+name+"</span>";
                            })
                        },
                        {
                            title: '#Bypass Used/Quantity/Period',
                            data: 'bypass_quantity',
                            visible: true,
                            width: '210px',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                var bypass_used = "";
                                if(row.bypass_used===null || row.bypass_used===0 )
                                    bypass_used = "<span class='mif-info self-scale-2 unset_value_color'></span> <span class='unset_value_color'>-</span> ";
                                else
                                    bypass_used = "<span class='mif-info self-scale-2 fg-cyan'></span> " + row.bypass_used + " ";
                                
                                var bypass_permitted = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                                if(data===null || data===0 )
                                    bypass_permitted += "<span class='mif-clipboard self-scale-1 unset_value_color'></span> <span class='unset_value_color'>-</span> ";
                                else
                                    bypass_permitted += "<span class='mif-clipboard self-scale-1 fg-cyan'></span> " + data + " ";
                                bypass_permitted += "<span class='unit_day'>/day</span>";
                                
                                var bypass_duration = "";
                                if(row.bypass_period===null || row.bypass_period===0 )
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
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                //var chk_report = (data === 1) ? "checked-alone":"unchecked-alone";
                                //return "<label class='"+chk_report+"'></label>";
                                var chk_report = (data === 1) ? "checked":"";
                                //return app_cfg.ReportLevel;
                                return "<label class='switch-original'><input type='checkbox' id='useractivation_report_"+row.id+"' "+chk_report+" /><span class='check'></span></label>";
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

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let appUserActivationTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/activations",
                        dataSrc: function ( json ) {
                            return json.data;
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        method: "GET",
                        error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                        {
                            if(jqXHR.status > 399 && jqXHR.status < 500)
                            {
                                // Almost certainly auth related error. Redirect to login
                                // by signalling for logout.
                                //////window.location.href = 'login.php?logout';
                            }
                        })
                    };

                // Define AppList table settings, ENSURE TO INCLUDE AJAX SETTINGS!
                let appUserActivationTableSettings: DataTables.Settings =
                    {
                        scrollY: ''+ (height - 470) + 'px',
                        scrollCollapse: true,
                        autoWidth: true,
                        stateSave: true,
                        processing: true,
                        serverSide: true,
                        responsive: true,
                        deferLoading: 0,
                        columns: appUserActivationTableColumns,
                        ajax: appUserActivationTablesLoadFromAjaxSettings,

                        // We grab the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        }),
                        drawCallback: (( settings ) :void =>
                        {
                            let that = this;
                            $("#app_user_activations_table").off("change", "input[type='checkbox']");
                            $("#app_user_activations_table").on("change", "input[type='checkbox']", function () {
                                let id_str = this.id;
                                let val = 0;
                                if (this.checked) {
                                    val = 1;
                                }
                                let checkAjaxSettings: JQueryAjaxSettings = {
                                    method:"POST",
                                    timeout: 60000,
                                    url: "api/admin/activations/update_field",
                                    data: {id: id_str, value: val},
                                    success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                                        that.ForceTableRedraw(that.m_tableAppUserActivationTable);
                                        return false;
                                    },
                                    error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                        console.log(errorThrown);
                                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                                            // Almost certainly auth related error. Redirect to login
                                            // by signalling for logout.
                                            //window.location.href = 'login.php?logout';
                                        } else {
                                            
                                        }
                                    }
                                }
                    
                                $.post(checkAjaxSettings);
                            });
                        })
                    };

                this.m_tableAppUserActivationTable = $('#app_user_activations_table').DataTable(appUserActivationTableSettings);
                $('<button id="refresh_user_activations"><span class="mif-loop2 "></span> Refresh</button>').appendTo('#app_user_activations_table_wrapper div.dataTables_filter');
                $("#refresh_user_activations").click((e:MouseEvent):void => {
                    this.ForceTableRedraw(this.m_tableAppUserActivationTable);
                })
            });
            let systemVersionTableConstruction = (() =>
            {  
                
                let systemVersionTableColumns: DataTables.ColumnSettings[] =
                    [
                        {
                            title: 'Version Id',
                            data: 'id',
                            visible: false,                            
                        },
                        {
                            title: 'Platform',
                            data: 'platform',
                            className: 'content-left',
                            visible: true,
                            width: '200px',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {       
                                var name = row.os_name;        
                                var span = "";
                                if(data ==="WIN")
                                {
                                    span = "<span class='mif-windows os_win'></span>";
                                } else if(data ==="OSX") {
                                    span = "<span class='mif-apple os_mac'></span>";
                                } else if(data ==="LINUX") {
                                    span = "<span class='mif-linux os_linux'></span>";
                                } else {
                                    span = "<span class='mif-notification os_mac'></span>";
                                }
                                if( row.active === 1) {
                                    return span+ " &nbsp; <b>" + name + "</b>";
                                } else {
                                    return "<span class='inactive'>" + span+ " &nbsp; <b>" + name + "</b></span>";
                                }                                
                            })
                        },
                        {
                            // This field belongs to a different table, so it
                            // needs to be included on the server side!
                            title: 'App Name',
                            data: 'app_name',
                            className: 'content-left',
                            visible: true,
                            width: '140px',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {       
                                
                                if( row.active === 1) {
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
                            visible: true,
                            width: '140px',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {       
                                
                                if( row.active === 1) {
                                    return data;
                                } else {
                                    return "<span class='inactive'>" + data + "</span>";
                                }                                
                            })
                        },
                        {
                            title: 'Version',                            
                            data: 'version_number',
                            className: 'content-left version_number',
                            defaultContent: 'None',
                            width: '100px',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {       
                                
                                if( row.active === 1) {
                                    return data;
                                } else {
                                    return "<span class='inactive'>" + data + "</span>";
                                }                                
                            })
                        },
                        {
                            title: 'Release Date',
                            data: 'release_date',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                if( row.active === 1) {
                                    return data;
                                } else {
                                    return "<span class='inactive'>" + data + "</span>";
                                }  
                            }),
                            className: 'content-center version_date',
                            width: '180px'
                        },
                        {
                            title: 'Alpha',
                            data: 'alpha',
                            className: 'content-center sub_version_number',
                            visible: true, 
                            width: '100px',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {       
                                
                                if( row.active === 1) {
                                    return data;
                                } else {
                                    return "<span class='inactive'>" + data + "</span>";
                                }                                
                            })
                        },
                        {
                            title: 'Beta',
                            data: 'beta',
                            visible: true,
                            width: '100px',
                            className:'content-center sub_version_number',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {       
                                
                                if( row.active === 1) {
                                    return data;
                                } else {
                                    return "<span class='inactive'>" + data + "</span>";
                                }                                
                            })
                        },
                        {
                            title: 'Stable',
                            data: 'stable',
                            visible: true,
                            width: '100px',
                            className:'content-center sub_version_number',
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {       
                                
                                if( row.active === 1) {
                                    return data;
                                } else {
                                    return "<span class='inactive'>" + data + "</span>";
                                }                                
                            })
                        },
                        {
                            // This field belongs to a different database, so it
                            // needs to be included on the server side!
                            title: 'Changes',
                            data: 'changes',
                            className: 'content-left',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {       
                                
                                if( row.active === 1) {
                                    return data;
                                } else {
                                    return "<span class='inactive'>" + data + "</span>";
                                }                                
                            })
                        },
                        {
                            title: 'Action',
                            data: 'active',
                            visible: true,
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                
                                if( data === 1) {
                                    return "<label class='checked-alone'></label>";
                                } else {
                                    return "<label class='switch-original'><input type='checkbox' id='versions_"+row.id+"' /><span class='check'></span></label>";
                                }
                            }),
                            className: 'content-left padding-left-10',
                            width: '60px'
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let systemVersionTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/versions",
                        dataSrc: function ( json ) {
                            return json.data;
                        },                        
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        method: "GET",
                        error: ((jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                        {
                            if(jqXHR.status > 399 && jqXHR.status < 500)
                            {
                                // Almost certainly auth related error. Redirect to login
                                // by signalling for logout.
                                ////window.location.href = 'login.php?logout';
                            }
                        })
                    };

                // Define user table settings, ENSURE TO INCLUDE AJAX SETTINGS!
               
                let systemVersionsTableSettings: DataTables.Settings = {
                    scrollY: ''+ (height - 470) + 'px',
                    scrollCollapse: true,
                    autoWidth: true,
                    stateSave: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    deferLoading: 0,
                    columns: systemVersionTableColumns,
                    ajax: systemVersionTablesLoadFromAjaxSettings,
                    rowCallback: ((row: Node, data: any[] | Object): void =>
                    {
                        this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: (( settings ): void =>
                    {
                        let that = this;
                        $("#system_versions_table").off("change", "input[type='checkbox']");
                        $("#system_versions_table").on("change", "input[type='checkbox']", function () {
                            
                            if(!confirm("Do you want to set this version as default version?"))
                            {                                
                                this.checked = false;
                                return;
                            }
                                
                            let id_str = this.id;
                            let checkAjaxSettings: JQueryAjaxSettings = {
                                method:"POST",
                                timeout: 60000,
                                url: "api/admin/versions/update_status",
                                data: {id: id_str},
                                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                                    that.ForceTableRedraw(that.m_tableSystemVersions);
                                    return false;
                                },
                                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                                    console.log(errorThrown);
                                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                                        // Almost certainly auth related error. Redirect to login
                                        // by signalling for logout.
                                        //window.location.href = 'login.php?logout';
                                    } else {
                                        
                                    }
                                }
                            }
                
                            $.post(checkAjaxSettings);
                        }); 
                    })
                };
                this.m_tableSystemVersions = $('#system_versions_table').DataTable(systemVersionsTableSettings);
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

        private ConstructNavigation(): void
        {
            // Get references to the top level menu buttons.            
            this.m_btnSignOut = document.getElementById('btn_sign_out') as HTMLLIElement;
            
            // Get reference to the main menu tab buttons.
            this.m_tabBtnUsers = document.querySelector('a[href="#tab_users"]') as HTMLLinkElement;
            this.m_tabBtnGroups = document.querySelector('a[href="#tab_groups"]') as HTMLLinkElement;
            this.m_tabBtnFilterLists = document.querySelector('a[href="#tab_filter_lists"]') as HTMLLinkElement;
            this.m_tabBtnUserRequest = document.querySelector('a[href="#tab_user_deactivation_requests"]') as HTMLLinkElement;
            this.m_tabBtnAppGroup = document.querySelector('a[href="#tab_app_groups"]') as HTMLLinkElement;
            this.m_tabBtnAppUserActivation = document.querySelector('a[href="#tab_app_user_activations"]') as HTMLLinkElement;
            this.m_tabBtnSystemVersion = document.querySelector('a[href="#tab_system_versions"]') as HTMLLinkElement;
            // Init user management button references.
            this.m_btnCreateUser = document.getElementById('btn_user_add') as HTMLButtonElement;
            this.m_btnDeleteUser = document.getElementById('btn_user_delete') as HTMLButtonElement;

            // These buttons cannot be enabled until a user is selected.
            this.m_btnDeleteUser.disabled = true;

            // Init group management button references.
            this.m_btnCreateGroup = document.getElementById('btn_group_add') as HTMLButtonElement;
            this.m_btnDeleteGroup = document.getElementById('btn_group_delete') as HTMLButtonElement;
            this.m_btnCloneGroup = document.getElementById('btn_group_clone') as HTMLButtonElement;
            // Delete button & Clone button cannot be enabled until a group is selected.
            this.m_btnDeleteGroup.disabled = true;
            this.m_btnCloneGroup.disabled = true;
            
            // Init Filter List/Data management button references.          
            this.m_btnUploadFilterLists = document.getElementById('btn_add_filter_lists') as HTMLButtonElement;  
            this.m_btnDeleteFilterList = document.getElementById('btn_delete_filter_list') as HTMLButtonElement;
            this.m_btnDeleteFilterListInNamespace = document.getElementById('btn_delete_filter_list_namespace') as HTMLButtonElement;
            this.m_btnDeleteFilterListTypeInNamespace = document.getElementById('btn_delete_filter_list_type_namespace') as HTMLButtonElement;

            // Delete button cannot be enabled until a list is selected.
            this.m_btnDeleteFilterList.disabled = true;
            this.m_btnDeleteFilterListInNamespace.disabled = true;
            this.m_btnDeleteFilterListTypeInNamespace.disabled = true;

            // Init user deactivation request.
            this.m_btnDeleteUserDeactivationRequest = document.getElementById('btn_delete_user_deactivation_request') as HTMLButtonElement;
            this.m_btnDeleteUserDeactivationRequest.disabled = true;
            
            this.m_btnRefreshUserDeactivationRequests = document.getElementById('btn_refresh_user_deactivation_request_list') as HTMLButtonElement;

            // Init Global White/Black List buttons
            this.m_btnApp = document.getElementById('global_radio_app') as HTMLInputElement;
            this.m_btnAppGroup = document.getElementById('global_radio_app_group') as HTMLInputElement;
            this.m_btnAddItem = document.getElementById('btn_application_add') as HTMLButtonElement;
            this.m_btnRemoveItem = document.getElementById('btn_application_remove') as HTMLButtonElement;
            this.m_btnRemoveItem.disabled = true;
            this.m_btnApplyToGroup = document.getElementById('btn_apply_group') as HTMLButtonElement;

            this.m_btnDeleteAppUserActivation = document.getElementById('btn_delete_activation') as HTMLButtonElement;
            this.m_btnBlockAppUserActivation = document.getElementById('btn_block_activations') as HTMLButtonElement;
            this.m_btnDeleteAppUserActivation.disabled = true;
            this.m_btnBlockAppUserActivation.disabled = true;

            this.m_btnCreateVersion = document.getElementById('btn_version_add') as HTMLButtonElement;
            this.m_btnDeleteVersion = document.getElementById('btn_version_delete') as HTMLButtonElement;
            this.m_btnDeleteVersion.disabled = true;
            this.m_btnSystemPlatform = document.getElementById('btn_sysem_platform') as HTMLButtonElement;
            // Get handlers setup for all input.
            this.InitButtonHandlers();
        }

        
        /**
         * Initializes click handlers for our UI buttons. Grabs the events with
         * a fat arrow so that our this context is preserved, meaning that this
         * will represent this class. The events are then passed to member
         * functions for cleanliness.
         * 
         * @private
         * 
         * @memberOf Dashboard
         */
        private InitButtonHandlers(): void
        {
            this.m_btnSignOut.onclick = ((e: MouseEvent) =>
            {
                this.OnSignOutClicked(e);
            });

            this.m_btnCreateUser.onclick = ((e: MouseEvent) =>
            {
                this.OnCreateUserClicked(e);
            });

            this.m_btnDeleteUser.onclick = ((e: MouseEvent) =>
            {
                this.OnDeleteUserClicked(e);
            });

            this.m_btnCreateGroup.onclick = ((e: MouseEvent) =>
            {
                this.OnCreateGroupClicked(e);
            });

            this.m_btnDeleteGroup.onclick = ((e: MouseEvent) =>
            {
                this.OnDeleteGroupClicked(e);
            });

            this.m_btnCloneGroup.onclick = ((e: MouseEvent) =>
            {
               this.OnCloneGroupClicked(e); 
            });
            this.m_btnUploadFilterLists.onclick = ((e: MouseEvent) =>
            {
                this.m_filterListUploadController.Show(this.m_tableFilterLists.data());
            });

            this.m_btnDeleteFilterList.onclick = ((e: MouseEvent) =>
            {
                this.OnDeleteFilterListClicked(e);
            });

            this.m_btnDeleteFilterListInNamespace.onclick = ((e: MouseEvent) =>
            {
                this.OnDeleteFilterListInNamespaceClicked(e, false);
            });

            this.m_btnDeleteFilterListTypeInNamespace.onclick = ((e: MouseEvent) =>
            {
                this.OnDeleteFilterListInNamespaceClicked(e, true);
            });

            this.m_btnDeleteUserDeactivationRequest.onclick = ((e: MouseEvent) =>
            {
                this.OnDeleteUserDeactivationRequestClicked(e);
            });

            this.m_btnRefreshUserDeactivationRequests.onclick = ((e: MouseEvent) =>
            {
                this.ForceTableRedraw(this.m_tableUserDeactivationRequests);
            });
            
            this.m_btnCreateVersion.onclick = ((e: MouseEvent) =>
            {
                this.OnClickAddVersion(e);
            });

            this.m_btnDeleteVersion.onclick = ((e: MouseEvent) =>
            {
                this.OnClickDeleteVersion(e);
            });
            
            this.m_btnSystemPlatform.onclick = ((e: MouseEvent) =>
            {
                this.OnClickPlatform(e);
            });
            // In our main menu tab click handlers, all we need to do is
            // synchronize the view state, so we just call the setter from
            // within.
            this.m_tabBtnUsers.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.UserListView;
            });

            this.m_tabBtnGroups.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.GroupListView;
            });

            this.m_tabBtnFilterLists.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.FilterListView;
            });

            this.m_tabBtnUserRequest.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.DeactivationRequestListView;
            });

            
            this.m_tabBtnSystemVersion.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.SystemVersionView;
            });


            this.m_tabBtnAppGroup.onclick = ((e: MouseEvent) =>
            {
                if(this.m_btnApp.checked) {
                    this.ViewState = DashboardViewStates.AppView;
                } else {
                    this.ViewState = DashboardViewStates.AppGroupView;
                }
            });
            this.m_btnApp.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.AppView;
                let itemIsActuallySelected = $("#app_table").children().next().find(".selected").length > 0?true:false;
                this.m_btnRemoveItem.disabled  = itemIsActuallySelected;
                this.m_btnAddItem.innerHTML = '<span class="icon mif-stack"></span>Add <br /> Application';
                this.m_btnRemoveItem.innerHTML = '<span class="mif-cancel"></span>Remove <br /> Application';
                this.m_btnApplyToGroup.innerHTML = '<span class="icon mif-checkmark" style="color:green"></span> Apply<br />To App Group'
            });

            this.m_btnAppGroup.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.AppGroupView;
                let itemIsActuallySelected = $("#app_group_table").children().next().find(".selected").length > 0?true:false;
                this.m_btnRemoveItem.disabled  = itemIsActuallySelected;
                this.m_btnAddItem.innerHTML = '<span class="icon mif-stack"></span>Add <br /> Application <br /> Group';
                this.m_btnRemoveItem.innerHTML = '<span class="mif-cancel"></span>Remove <br /> Application <br /> Group';
                this.m_btnApplyToGroup.innerHTML = '<span class="icon mif-checkmark" style="color:green"></span> Apply<br />To User Group'
            });
            this.m_btnAddItem.onclick = ((e: MouseEvent) => 
            {
                this.OnAddApplicationClicked(e);
            });
            this.m_btnRemoveItem.onclick = ((e: MouseEvent) =>
            {
               this.onRemoveApplicationClicked(e); 
            });
            this.m_btnApplyToGroup.onclick = ((e: MouseEvent) =>
            {
                this.onApplyToGroupClicked(e);        
            });

            this.m_tabBtnAppUserActivation.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.AppUserActivationView;
            });

            this.m_btnDeleteAppUserActivation.onclick = ((e: MouseEvent) =>
            {
                this.onDeleteAppUserActivationClicked(e); 
            });

            this.m_btnBlockAppUserActivation.onclick = ((e: MouseEvent) =>
            {
                this.onBlockAppUserActivationClicked(e); 
            });

        }   

        /**
         * Called whenever a table row is created in any table. All we want to
         * do here is add a click handler with a fat arrow so we can keep the
         * context of these events class local.
         * 
         * @private
         * @param {Node} row
         * @param {(any[] | Object)} data
         * 
         * @memberOf Dashboard
         */
        private OnTableRowCreated(row: Node, data: any[] | Object): void
        {
            let tableRow = row as HTMLTableRowElement;
            tableRow.onclick = ((e: MouseEvent) =>
            {
                this.OnTableRowClicked(e, data);
            });

            tableRow.ondblclick = ((e: MouseEvent) =>
            {
                this.OnTableRowDoubleClicked(e, data);
            });
           
        }

        /**
         * Called whenever a row is clicked in any of our data tables. We simply
         * want to toggle the selected class on the item clicked for each table,
         * and clear this class from all siblings. This way, in other logic, we
         * can just query the table for the row that has the selected class in
         * order to react to user actions.
         * 
         * @private
         * @param {JQueryEventObject} eventObject
         * 
         * @memberOf Dashboard
         */
        private OnTableRowClicked(e: MouseEvent, data: any[] | Object): void
        {
            // Here we have to stop the propagation of the event, otherwise it will end up
            // triggering on the table cells. We only want rows selectable.
            e.stopImmediatePropagation();
            e.stopPropagation();


            // If this is a valid table row entry, give it the "selected" class,
            // while removing that class from all its siblings.
            if (!$(e.currentTarget).hasClass('dataTables_empty')) 
            {
                $(e.currentTarget).toggleClass('selected').siblings().removeClass('selected');
            }

            // Get the parent HTML table element.
            let parentTable = $(e.currentTarget).closest('table')[0] as HTMLTableElement;

            // Find out if the item is actually selected after the toggle. If it is,
            // then we're going to enable/disable the delete button for whichever
            // table the row belongs to.
            let itemIsActuallySelected = $(e.currentTarget).hasClass('selected');

            switch (parentTable.id)
            {
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
                        this.m_btnDeleteFilterList.disabled = !itemIsActuallySelected;
                        this.m_btnDeleteFilterListInNamespace.disabled = !itemIsActuallySelected;
                        this.m_btnDeleteFilterListTypeInNamespace.disabled = !itemIsActuallySelected;
                    }
                    break;

                    case 'user_deactivation_request_table':
                    {
                        this.m_btnDeleteUserDeactivationRequest.disabled = !itemIsActuallySelected;
                    }
                    break;

                case 'app_table':
                    {
                        this.m_btnRemoveItem.disabled = !itemIsActuallySelected;
                    }
                    break;

                case 'app_group_table':
                    {
                        this.m_btnRemoveItem.disabled = !itemIsActuallySelected;
                    }
                    break;  
                case 'app_user_activations_table':
                    {
                        this.m_btnDeleteAppUserActivation.disabled = !itemIsActuallySelected;
                        this.m_btnBlockAppUserActivation.disabled = !itemIsActuallySelected;
                    }
                    break;
                case 'system_versions_table':
                    {
                        this.m_btnDeleteVersion.disabled = !itemIsActuallySelected;
                    }
                    break;
            }   
        }

        /**
         * Called whenever a row in any of our data tables is double clicked.
         * This is considered an action by the user to start the process of
         * editing the data in the double-clicked row.
         * 
         * @private
         * @param {MouseEvent} e
         * @param {(any[] | Object)} data
         * 
         * @memberOf Dashboard
         */
        private OnTableRowDoubleClicked(e: MouseEvent, data: any[] | Object): void
        {
            // Stop the event so it doesn't go anywhere else. We're handling it here.
            e.stopImmediatePropagation();
            e.stopPropagation();
            // Get a typed instanced of the selected row.
            let selectedRow = e.currentTarget as HTMLTableRowElement;

            // Get the parent HTML table element.
            let parentTable = $(selectedRow).closest('table')[0] as HTMLTableElement;

            // Switch the unique ID on the parent table to figure out which type of
            // element we're going to start editing.
            switch (parentTable.id)
            {
                case 'user_table':
                    {
                        let userRecord = new UserRecord();

                        userRecord.ActionCompleteCallback = ((action: string): void =>
                        {

                            userRecord.StopEditing();

                            // Whenever we do any action on a user record
                            // successfully, we want to simply redraw the user
                            // table to get the updated data showing.
                            this.ForceTableRedraw(this.m_tableUsers);
                        });

                        // We supply everything in the groups table so that the user's group
                        // can be changed to any available group.
                        userRecord.StartEditing(this.m_allGroups, data);
                    }
                    break;

                case 'group_table':
                    {
                        let groupRecord = new GroupRecord();

                        // We supply everything in the filter lists table so that assigned
                        // filters can be laid out for editing.
                        groupRecord.StartEditing(this.m_allFilters, data);

                        groupRecord.ActionCompleteCallback = ((action: string): void =>
                        {

                            groupRecord.StopEditing();

                            // Whenever we do any action on a group record
                            // successfully, we want to simply redraw the group
                            // table to get the updated data showing.
                            this.ForceTableRedraw(this.m_tableGroups);

                            // We're also going to force redraw the user table, because
                            // group table data is shown in there, and the user might have
                            // changed a group name or something.
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

                        // We supply everything in the filter lists table so that assigned
                        // filters can be laid out for editing.
                        deactivationRequestRecord.StartEditing(data);

                        deactivationRequestRecord.ActionCompleteCallback = ((action: string): void =>
                        {

                            deactivationRequestRecord.StopEditing();

                            // Whenever we do any action on a group record
                            // successfully, we want to simply redraw the group
                            // table to get the updated data showing.
                            this.ForceTableRedraw(this.m_tableUserDeactivationRequests);
                        });
                    }
                    break;
                case 'app_table':
                    {
                        let appRecord = new AppRecord();
                        
                        appRecord.ActionCompleteCallback = ((action: string): void =>
                        {

                            appRecord.StopEditing();

                            // Whenever we do any action on a user record
                            // successfully, we want to simply redraw the user
                            // table to get the updated data showing.
                            this.ForceTableRedraw(this.m_tableAppLists);
                        });

                        // We supply everything in the groups table so that the user's group
                        // can be changed to any available group.
                        appRecord.StartEditing(data);
                    }
                    break;

                case 'app_group_table':
                    {
                        let appGroupRecord = new AppGroupRecord();
                        
                        appGroupRecord.ActionCompleteCallback = ((action: string): void =>
                        {

                            appGroupRecord.StopEditing();

                            // Whenever we do any action on a user record
                            // successfully, we want to simply redraw the user
                            // table to get the updated data showing.
                            this.ForceTableRedraw(this.m_tableAppGroupLists);
                        });

                        // We supply everything in the groups table so that the user's group
                        // can be changed to any available group.
                        appGroupRecord.StartEditing(data);
                    }
                    break;
                case 'app_user_activations_table':
                    {
                        let appUserActivationRecord = new AppUserActivationRecord();
                        
                        appUserActivationRecord.ActionCompleteCallback = ((action: string): void =>
                        {
                            appUserActivationRecord.StopEditing();
                            this.ForceTableRedraw(this.m_tableAppUserActivationTable);
                        });
                        appUserActivationRecord.StartEditing(data);
                    }
                    break;
                    
                case 'system_versions_table':
                    {
                        let versionRecord = new VersionRecord();
                        
                        versionRecord.ActionCompleteCallback = ((action: string): void =>
                        {
                            versionRecord.StopEditing();
                            this.ForceTableRedraw(this.m_tableSystemVersions);
                        });
                        versionRecord.StartEditing(data);
                    }
                    break;  
            }
        }

        /**
         * Gets the selected item in a table, if any. Return will be null if no current selected item.
         * 
         * @private
         * @param {DataTables.DataTable} table
         * @returns {DataTables.RowMethods}
         * 
         * @memberOf Dashboard
         */
        private GetSelectedRowForTable(table: DataTables.DataTable): DataTables.RowMethods
        {

            let selectedRow = $(table).find('tr .selected').first();

            if (selectedRow == null)
            {
                return null;
            }

            return table.row(selectedRow);
        }

        /**
         * Clears any and all selected item(s) in the given table.
         * 
         * @private
         * @param {DataTables.DataTable} table
         * 
         * @memberOf Dashboard
         */
        private ClearSelectedItemsInTable(table: DataTables.DataTable): void
        {
            $(table).children().removeClass('selected');
        }

        /**
         * Forces the given table to re-render itself.
         * 
         * @private
         * @param {DataTables.DataTable} table
         * @param {boolean} [resetPagination=false]
         * 
         * @memberOf Dashboard
         */
        public ForceTableRedraw(table: DataTables.DataTable, resetPagination: boolean = false): void
        {
            table.ajax.reload();
        }
        
        /**
         * Called whenever the user clicks the sign out button.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private OnSignOutClicked(e: MouseEvent): any
        {
            if (confirm("Are you sure you'd like to sign out?"))
            {
                $.post(
                    'logout',
                    (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                    {
                        location.reload();
                    }
                )
            }
        }
        /**
         * Called whenever the user clicks the user creation button. Internally
         * we'll validate that the current state is correct for this action.
         * Then, we'll present the appropriate input view which itself will
         * handle input validation, as well as POSTing appropriate commands to
         * the server.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private OnCreateUserClicked(e: MouseEvent): any
        {
            let newUser = new UserRecord();

            // We supply everything in the groups table so that the user's group
            // can be changed to any available group.            
            newUser.StartEditing(this.m_tableGroups.data(), this.m_tableUsers.data()['all_user_roles']);

            newUser.ActionCompleteCallback = ((action: string): void =>
            {

                newUser.StopEditing();

                // Whenever we do any action on a user record
                // successfully, we want to simply redraw the user
                // table to get the updated data showing.
                this.ForceTableRedraw(this.m_tableUsers);
            });
        }

        private OnClickPlatform(e: MouseEvent): any {
            let platformOverlay = new PlatformOverlay();
            platformOverlay.StartEditing();
        }

        private OnClickAddVersion(e: MouseEvent): any {
            let appVersion = new VersionRecord();
            appVersion.ActionCompleteCallback = ((action: string): void =>
            {
                appVersion.StopEditing();
                this.ForceTableRedraw(this.m_tableSystemVersions);
            });
            
            appVersion.StartEditing();
        }

        private OnClickDeleteVersion(e: MouseEvent): any {
            let selectedItem = this.m_tableSystemVersions.row('.selected').data();

            if (selectedItem != null)
            {
                var versionObject: VersionRecord;

                try 
                {
                    versionObject = BaseRecord.CreateFromObject(VersionRecord, selectedItem);

                    // We want to update the table after a delete.
                    versionObject.ActionCompleteCallback = ((action: string): void =>
                    {
                        this.ForceTableRedraw(this.m_tableSystemVersions);
                    });

                    if (confirm("Really delete user? THIS CANNOT BE UNDONE!!!"))
                    {
                        versionObject.Delete();
                    }
                }
                catch (e)
                {
                    console.log('Failed to load user record from table selection.');
                    console.log(e);
                }
            }
        }
        /**
         * Called whenever the user clicks the user deletion button. Internally
         * we'll validate that the current state is correct for this action, and
         * then initiate the process of POSTing this action as a command to the
         * server.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private OnDeleteUserClicked(e: MouseEvent): any 
        {
            let selectedItem = this.m_tableUsers.row('.selected').data();

            if (selectedItem != null)
            {
                var userObject: UserRecord;

                try 
                {
                    userObject = BaseRecord.CreateFromObject(UserRecord, selectedItem);

                    // We want to update the table after a delete.
                    userObject.ActionCompleteCallback = ((action: string): void =>
                    {
                        this.ForceTableRedraw(this.m_tableUsers);
                    });

                    if (confirm("Really delete user? THIS CANNOT BE UNDONE!!!"))
                    {
                        userObject.Delete();
                    }
                }
                catch (e)
                {
                    console.log('Failed to load user record from table selection.');
                    console.log(e);
                }
            }
        }

        /**
         * Called whenever the user clicks the group creation button. Internally
         * we'll validate that the current state is correct for this action.
         * Then, we'll present the appropriate input view which itself will
         * handle input validation, as well as POSTing appropriate commands to
         * the server.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private OnCreateGroupClicked(e: MouseEvent): any
        {
            let groupRecord = new GroupRecord();

            groupRecord.ActionCompleteCallback = ((action: string): void =>
            {

                groupRecord.StopEditing();

                // Whenever we do any action on a group record
                // successfully, we want to simply redraw the group
                // table to get the updated data showing.
                this.ForceTableRedraw(this.m_tableGroups);
            });

            // We supply everything in the filter lists table so that assigned 
            // filters can be laid out for editing.
            groupRecord.StartEditing(this.m_tableFilterLists.data());
        }

        /**
         * Called whenever the user clicks the group deletion button. Internally
         * we'll validate that the current state is correct for this action, and
         * then initiate the process of POSTing this action as a command to the
         * server.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private OnDeleteGroupClicked(e: MouseEvent): any
        {
            let selectedItem = this.m_tableGroups.row('.selected').data();

            if (selectedItem != null)
            {
                var groupObject: GroupRecord;

                try 
                {
                    groupObject = BaseRecord.CreateFromObject(GroupRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    groupObject.ActionCompleteCallback = ((action: string): void =>
                    {
                        this.ForceTableRedraw(this.m_tableUsers);
                        this.ForceTableRedraw(this.m_tableGroups);
                    });

                    if (confirm("Really delete group? THIS CANNOT BE UNDONE!!!"))
                    {
                        groupObject.Delete();
                    }
                }
                catch (e)
                {
                    console.log('Failed to load group record from table selection.');
                }
            }
        }

        /**
         * Called whenever the user clicks the group deletion button. Internally
         * we'll validate that the current state is correct for this action, and
         * then initiate the process of POSTing this action as a command to the
         * server.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private OnCloneGroupClicked(e: MouseEvent): any
        {
            let selectedItem = this.m_tableGroups.row('.selected').data();
            if (selectedItem != null)
            {
                let groupRecord = new GroupRecord();
                groupRecord.StartEditing(this.m_tableFilterLists.data(), null, selectedItem);
                groupRecord.ActionCompleteCallback = ((action: string): void =>
                {
                    groupRecord.StopEditing();
                    this.ForceTableRedraw(this.m_tableGroups);
                    this.ForceTableRedraw(this.m_tableUsers);
                });
            }
        }

        /**
         * Called whenever the user clicks the delete filter list button.
         * Internally we'll validate that the current state is correct for this
         * action, and then initiate the process of POSTing this action as a
         * command to the server.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private OnDeleteFilterListClicked(e: MouseEvent): any
        {
            let selectedItem = this.m_tableFilterLists.row('.selected').data();

            if (selectedItem != null)
            {
                var filterListObject: FilterListRecord;

                try 
                {
                    filterListObject = BaseRecord.CreateFromObject(FilterListRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    filterListObject.ActionCompleteCallback = ((action: string): void =>
                    {   
                        this.loadAllFilters();
                        this.ForceTableRedraw(this.m_tableFilterLists);
                    });

                    if (confirm("Really delete filter list? THIS CANNOT BE UNDONE!!!"))
                    {
                        filterListObject.Delete();
                    }
                }
                catch (e)
                {
                    console.log('Failed to load filter list record from table selection.');
                }
            }
        }

        /**
         * Called whenever the user clicks the delete filter in namespace button.
         * Internally we'll validate that the current state is correct for this
         * action, and then initiate the process of POSTing this action as a
         * command to the server.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private OnDeleteFilterListInNamespaceClicked(e: MouseEvent, constraintToType: boolean): any
        {
            let selectedItem = this.m_tableFilterLists.row('.selected').data();

            if (selectedItem != null)
            {
                var filterListObject: FilterListRecord;

                try 
                {
                    filterListObject = BaseRecord.CreateFromObject(FilterListRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    filterListObject.ActionCompleteCallback = ((action: string): void =>
                    {
                        this.loadAllFilters();
                        this.ForceTableRedraw(this.m_tableFilterLists);
                    });
                    
                    let confirmMsg = constraintToType == true ? "Really delete all filters with the same type in this lists' namespace? THIS CANNOT BE UNDONE!!!" : "Really delete all filters in this lists' namespace? THIS CANNOT BE UNDONE!!!";

                    if (confirm("Really delete all filters in this lists' namespace? THIS CANNOT BE UNDONE!!!"))
                    {
                        filterListObject.DeleteAllInNamespace(constraintToType);
                    }
                }
                catch (e)
                {
                    console.log('Failed to load filter list record from table selection.');
                }
            }
        }

        private OnDeleteUserDeactivationRequestClicked(e: MouseEvent): any
        {
            let selectedItem = this.m_tableUserDeactivationRequests.row('.selected').data();

            if (selectedItem != null)
            {
                var deactivationRequestObject: DeactivationRequestRecord;

                try 
                {
                    deactivationRequestObject = BaseRecord.CreateFromObject(DeactivationRequestRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    deactivationRequestObject.ActionCompleteCallback = ((action: string): void =>
                    {
                        this.ForceTableRedraw(this.m_tableUserDeactivationRequests);
                    });

                    if (confirm("Really delete user deactivation request? THIS CANNOT BE UNDONE!!!"))
                    {
                        deactivationRequestObject.Delete();
                    }
                }
                catch (e)
                {
                    console.log('Failed to load filter list record from table selection.');
                }
            }
        }


        /**
         * Called whenever the user clicks the Add Application button. Internally
         * we'll validate that the current state is correct for this action.
         * Then, we'll present the appropriate input view which itself will
         * handle input validation, as well as POSTing appropriate commands to
         * the server.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private OnAddApplicationClicked(e: MouseEvent): any
        {
            if(this.m_btnApp.checked) {
                let newApp = new AppRecord();

                // We supply everything in the groups table so that the user's group
                // can be changed to any available group.            
                newApp.StartEditing( );
                newApp.ActionCompleteCallback = ((action: string): void =>
                {

                    newApp.StopEditing();

                    // Whenever we do any action on a user record
                    // successfully, we want to simply redraw the user
                    // table to get the updated data showing.
                    this.ForceTableRedraw(this.m_tableAppLists);
                });
            } else {
                let newAppGroup = new AppGroupRecord();
                // We supply everything in the groups table so that the user's group
                // can be changed to any available group.            
                newAppGroup.StartEditing( );
                newAppGroup.ActionCompleteCallback = ((action: string): void =>
                {

                    newAppGroup.StopEditing();

                    // Whenever we do any action on a user record
                    // successfully, we want to simply redraw the user
                    // table to get the updated data showing.
                    this.ForceTableRedraw(this.m_tableAppGroupLists);
                });
            }
        }
        /**
         * Called whenever the user clicks the Remove Application button. Internally
         * we'll validate that the current state is correct for this action.
         * Then, we'll present the appropriate input view which itself will
         * handle input validation, as well as POSTing appropriate commands to
         * the server.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private onRemoveApplicationClicked(e: MouseEvent): any
        {
            this.m_btnRemoveItem.disabled = true;
            if(this.m_btnApp.checked) {
                let selectedItem = this.m_tableAppLists.row('.selected').data();
                if (selectedItem != null)
                {
                    var appObj: AppRecord;
    
                    try 
                    {
                        appObj = BaseRecord.CreateFromObject(AppRecord, selectedItem);
    
                        // We want to update both users and groups after delete.
                        appObj.ActionCompleteCallback = ((action: string): void =>
                        {
                            this.ForceTableRedraw(this.m_tableAppLists);
                        });
    
                        if (confirm("Really delete Application? THIS CANNOT BE UNDONE!!!"))
                        {
                            appObj.Delete();
                        }
                    }
                    catch (e)
                    {
                        this.m_btnRemoveItem.disabled = false;
                        console.log('Failed to load application record from table selection.');
                    }
                }
            } else {
                let selectedItem = this.m_tableAppGroupLists.row('.selected').data();
                if (selectedItem != null)
                {
                    var appGroupObj: AppGroupRecord;
    
                    try 
                    {
                        appGroupObj = BaseRecord.CreateFromObject(AppGroupRecord, selectedItem);
    
                        // We want to update both users and groups after delete.
                        appGroupObj.ActionCompleteCallback = ((action: string): void =>
                        {
                            this.ForceTableRedraw(this.m_tableAppGroupLists);
                        });
    
                        if (confirm("Really delete Application? THIS CANNOT BE UNDONE!!!"))
                        {
                            appGroupObj.Delete();
                        }
                    }
                    catch (e)
                    {
                        this.m_btnRemoveItem.disabled = false;
                        console.log('Failed to load application record from table selection.');
                    }
                }
            }
        }
        /**
         * Called whenever the user clicks the Apply to Groupbutton. Internally
         * we'll validate that the current state is correct for this action.
         * Then, we'll present the appropriate input view which itself will
         * handle input validation, as well as POSTing appropriate commands to
         * the server.
         * 
         * @private
         * @param {MouseEvent} e
         * @returns {*}
         * 
         * @memberOf Dashboard
         */
        private onApplyToGroupClicked(e: MouseEvent): any
        {
            if (this.m_btnApp.checked) {
                let apply_app_to_app_group_overlay = new ApplyAppToAppGroup(this);
                apply_app_to_app_group_overlay.Show();
            } else {
                let apply_app_group_to_user_group_overlay = new ApplyAppgroupToUsergroup(this);
                apply_app_group_to_user_group_overlay.Show();
            }
        }

        private onDeleteAppUserActivationClicked(e: MouseEvent): any
        {
            let selectedItem = this.m_tableAppUserActivationTable.row('.selected').data();

            if (selectedItem != null)
            {
                var appUserActivationObject: AppUserActivationRecord;

                try 
                {
                    appUserActivationObject = BaseRecord.CreateFromObject(AppUserActivationRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    appUserActivationObject.ActionCompleteCallback = ((action: string): void =>
                    {
                        this.ForceTableRedraw(this.m_tableAppUserActivationTable);
                    });

                    if (confirm("Really delete app user activation? THIS CANNOT BE UNDONE!!!"))
                    {
                        appUserActivationObject.Delete();
                    }
                }
                catch (e)
                {
                    console.log('Failed to load filter list record from table selection.');
                }
            }
        }

        private onBlockAppUserActivationClicked(e: MouseEvent): any
        {
            let selectedItem = this.m_tableAppUserActivationTable.row('.selected').data();

            if (selectedItem != null)
            {
                var appUserActivationObject: AppUserActivationRecord;
                try 
                {
                    appUserActivationObject = BaseRecord.CreateFromObject(AppUserActivationRecord, selectedItem);

                    // We want to update both users and groups after delete.
                    appUserActivationObject.ActionCompleteCallback = ((action: string): void =>
                    {
                        this.ForceTableRedraw(this.m_tableAppUserActivationTable);
                    });

                    if (confirm("Really delete app user activation? THIS CANNOT BE UNDONE!!!"))
                    {
                        appUserActivationObject.Block();
                    }
                }
                catch (e)
                {
                    console.log('Failed to load filter list record from table selection.');
                }
            }
        }
        /**
         * Sets the current view state. This will enforce that visual elements
         * and data are synchronized as they should be for the specified view
         * state.
         * 
         * @private
         * 
         * @memberOf Dashboard
         */
        private set ViewState(value: DashboardViewStates)
        {
            // Every time the view state is changed, we're going to
            // force all of the tables to redraw. The reason for this
            // is that, due to network delay, it's not possible to
            // try and force synchronization between tables any
            // other way.

            this.m_viewUserManagement.style.display = "none";
            this.m_viewGroupManagement.style.display = "none";
            this.m_viewFilterManagement.style.display = "none";
            this.m_viewUserDeactivationRequestManagement.style.display = "none";
            this.m_viewAppManagement.style.display = "none";
            this.m_viewAppGroupManagement.style.display = "none";
            this.m_viewAppUserActivationManagement.style.display = "none";
            this.m_viewSystemVersionManagement.style.display = "none";
          
            switch (value)
            {
                case DashboardViewStates.UserListView:
                    {
                        this.ForceTableRedraw(this.m_tableUsers);
                        this.m_viewUserManagement.style.display = "block";
                    }
                    break;

                case DashboardViewStates.GroupListView:
                    {
                        this.ForceTableRedraw(this.m_tableGroups);
                        this.m_viewGroupManagement.style.display = "block";
                    }
                    break;

                case DashboardViewStates.FilterListView:
                    {
                        this.ForceTableRedraw(this.m_tableFilterLists);
                        this.m_viewFilterManagement.style.display = "block";
                    }
                    break;

                case DashboardViewStates.DeactivationRequestListView:
                    {
                        this.ForceTableRedraw(this.m_tableUserDeactivationRequests);
                        this.m_viewUserDeactivationRequestManagement.style.display = "block";
                    }
                    break;

                case DashboardViewStates.AppView:
                    {
                        this.ForceTableRedraw(this.m_tableAppLists);
                        this.m_viewAppManagement.style.display = "block";
                    }
                    break;

                case DashboardViewStates.AppGroupView:
                    {
                        this.ForceTableRedraw(this.m_tableAppGroupLists);
                        this.m_viewAppGroupManagement.style.display = "block";
                    }
                    break;
                case DashboardViewStates.AppUserActivationView:
                    {
                        this.ForceTableRedraw(this.m_tableAppUserActivationTable);
                        this.m_viewAppUserActivationManagement.style.display = "block";
                    }
                    break;
                case DashboardViewStates.SystemVersionView:
                    {
                        this.ForceTableRedraw(this.m_tableSystemVersions);
                        this.m_viewSystemVersionManagement.style.display = "block";
                    }
                    break;
            }

            this.m_currentViewState = value;
        }

        /**
         * Gets the current view state.
         * 
         * @private
         * @type {DashboardViewStates}
         * @memberOf Dashboard
         */
        private get ViewState(): DashboardViewStates
        {
            return this.m_currentViewState;
        }
    }

    // Our Dashboard instance. Will be set when document is ready.
    let citadelDashboard: Dashboard;

    // Create new instance of the dashboard class on page load.
    document.onreadystatechange = (event: ProgressEvent) =>
    {
        if (document.readyState.toUpperCase() == "complete".toUpperCase())
        {
            $.ajaxPrefilter(function(options, originalOptions, xhr) { // this will run before each request
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
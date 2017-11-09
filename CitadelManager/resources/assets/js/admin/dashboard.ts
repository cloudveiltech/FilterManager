/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../progresswait.ts"/>
///<reference path="applytogroupoverlay.ts"/>
///<reference path="listuploadoverlay.ts"/>
///<reference path="records/userrecord.ts"/>
///<reference path="records/grouprecord.ts"/>
///<reference path="records/filterlistrecord.ts"/>
///<reference path="records/whitelistrecord.ts"/>
///<reference path="records/blacklistrecord.ts"/>
///<reference path="records/appuseractivationrecord.ts"/>

namespace Citadel
{
    /**
     * An enum containing all valid view states for the administrator dashboard.
     * 
     * @enum {number}
     */
    enum DashboardViewStates
    {
        /**
         * A view with a dataTables instance that lists all existing users.
         */
        UserListView,

        /**
         * A view with a dataTables instance that lists all existing groups.
         */
        GroupListView,

        /**
         * A view with a dataTables instance that lists all existing filter
         * lists.
         */
        FilterListView,

        /**
         * A view with a dataTables instance that lists all existing user
         * deactivation requests.
         */
        DeactivationRequestListView,
        /**
         * A view with a dataTables instance that lists all existing global
         * whitelist & blacklist.
         */
        WhiteListView,
        BlackListView,
        /**
         * A view with a dataTables instance that lists all existing global
         * App User Activations
         */
        AppUserActivationView
        
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
    class Dashboard
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

        /**
         * Clickable main menu tab that hosts all menu action buttons for group
         * management.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_tabBtnGroups: HTMLLinkElement;

        /**
         * Clickable main menu tab that hosts all menu action buttons for filter
         * list management.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_tabBtnFilterLists: HTMLLinkElement;

        /**
         * Clickable main menu tab that hosts all menu action buttons for user
         * request management.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_tabBtnUserRequest: HTMLLinkElement;

        /**
         * Clickable main menu tab that hosts all menu action buttons for global
         * whitelist & blacklist management.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_tabBtnWhiteBlackLists: HTMLLinkElement;

        /**
         * Clickable main menu tab that hosts all menu action buttons for global
         * AppUserActivation management.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_tabBtnAppUserActivation: HTMLLinkElement;

        /**
         * Button to initiate the process of creating a new user.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnCreateUser: HTMLButtonElement;

        /**
         * Button to initiate the process of deleting an existing, selected
         * user.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnDeleteUser: HTMLButtonElement;

        /// Group management tab elements.

        /**
         * Button to initiate the process of creating a new group.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnCreateGroup: HTMLButtonElement;

        /**
         * Button to initiate the process of deleting an existing, selected
         * group.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnDeleteGroup: HTMLButtonElement;
        /**
         * Button to initiate the process of creating a new group.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnCloneGroup: HTMLButtonElement;
        
        /// Filter list/data management tab elements.

        /**
         * Button used to force open the list file upload modal overlay.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf Dashboard
         */
        private m_btnUploadFilterLists: HTMLButtonElement;

        /**
         * Button to initiate the process of deleting an existing, selected
         * filter list.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnDeleteFilterList: HTMLButtonElement;

        /**
         * Button to initiate the process of deleting all filter lists in the same
         * namespace as a selected list.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnDeleteFilterListInNamespace: HTMLButtonElement;


        /**
         * Button to initiate the process of deleting all filter lists in the same namespace
         * that are of the same type as the list selected.
         */
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

        /**
         * Button to initiate the process of refreshing the user deactivation
         * request table.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf Dashboard
         */
        private m_btnRefreshUserDeactivationRequests: HTMLButtonElement;

        /// Global Whitelist & Blacklist tab elements.

        /**
         * Button to initiate the process of Add a new item to list.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnAddItem: HTMLButtonElement;

        /**
         * Button to initiate the process of removing item an existing, selected
         * item.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnRemoveItem: HTMLButtonElement;
        /**
         * Button to initiate the process of removing item an existing, selected
         * item.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf DashboardMenu
         */
        private m_btnApplyToGroup: HTMLButtonElement;
        /**
         * RadioButton to indicate whitelist
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf DashboardMenu
         */
        private m_btnWhitelist: HTMLInputElement;
        
        /**
         * RadioButton to indicate blacklist
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf DashboardMenu
         */
        private m_btnBlacklist: HTMLInputElement;

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

        /**
         * Host container where group related data is displayed.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_viewGroupManagement: HTMLDivElement;

        /**
         * Host container where filter list related data is displayed.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_viewFilterManagement: HTMLDivElement;

        /**
         * Host container where user request data is displayed.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_viewUserDeactivationRequestManagement: HTMLDivElement;

        /**
         * Host container where blacklist/whitelist related data is displayed.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_viewWhiteListManagement: HTMLDivElement;
        private m_viewBlackListManagement: HTMLDivElement;

        /**
         * Host container where App User Activations related data is displayed.
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf Dashboard
         */
        private m_viewAppUserActivationManagement: HTMLDivElement;
        //
        // ────────────────────────────────────────────────────────────── IV ──────────
        //   :::::: D A T A   T A B L E S : :  :   :    :     :        :          :
        // ────────────────────────────────────────────────────────────────────────
        //

        /**
         * User DataTable.
         * 
         * @private
         * @type {DataTables.DataTable}
         * @memberOf Dashboard
         */
        private m_tableUsers: DataTables.DataTable;

        /**
         * Group DataTable.
         * 
         * @private
         * @type {DataTables.DataTable}
         * @memberOf Dashboard
         */
        private m_tableGroups: DataTables.DataTable;

        /**
         * Filter list DataTable.
         * 
         * @private
         * @type {DataTables.DataTable}
         * @memberOf Dashboard
         */
        private m_tableFilterLists: DataTables.DataTable;

        /**
         * Deactivation requests table.
         * 
         * @private
         * @type {DataTables.DataTable}
         * @memberOf Dashboard
         */
        private m_tableUserDeactivationRequests: DataTables.DataTable;

        /**
         *  BlackWhite DataTable.
         * 
         * @private
         * @type {DataTables.DataTable}
         * @memberOf Dashboard
         */
        private m_tableWhiteLists: DataTables.DataTable;
        private m_tableBlackLists: DataTables.DataTable;

        /**
         * App User Activations table.
         * 
         * @private
         * @type {DataTables.DataTable}
         * @memberOf Dashboard
         */
        private m_tableAppUserActivationTable: DataTables.DataTable;
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

            // Initialize views.
            this.ConstructManagementViews();

            this.m_filterListUploadController = new ListUploadOverlay();

            // Force table redraw any time an upload of new lists fails or succeeds.
            this.m_filterListUploadController.UploadCompleteCallback = (():void =>
            {
                this.ForceTableRedraw(this.m_tableFilterLists);
                this.ForceTableRedraw(this.m_tableGroups);
                this.m_filterListUploadController.Hide();
            });
            this.m_filterListUploadController.UploadFailedCallback = (():void =>
            {
                this.ForceTableRedraw(this.m_tableFilterLists);
                this.ForceTableRedraw(this.m_tableGroups);
                this.m_filterListUploadController.Hide();
            });
        }

        private ConstructManagementViews(): void
        {
            // Grab main view container references.
            this.m_viewUserManagement = document.getElementById('view_user_management') as HTMLDivElement;
            this.m_viewGroupManagement = document.getElementById('view_group_management') as HTMLDivElement;
            this.m_viewFilterManagement = document.getElementById('view_filter_management') as HTMLDivElement;
            this.m_viewUserDeactivationRequestManagement = document.getElementById('view_user_deactivation_request_management') as HTMLDivElement;
            this.m_viewWhiteListManagement = document.getElementById('view_whitelist_management') as HTMLDivElement;
            this.m_viewBlackListManagement = document.getElementById('view_blacklist_management') as HTMLDivElement;
            this.m_viewAppUserActivationManagement = document.getElementById('view_app_user_activations_management') as HTMLDivElement;
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
                            // This field belongs to a different table, so it
                            // needs to be included on the server side!
                            title: 'Group Name',
                            data: 'group.name',
                            className: 'phone',
                            defaultContent: 'Unassigned',
                            visible: true
                        },
                        {
                            title: 'Name',
                            data: 'name',
                            className: 'desktop',
                            visible: true
                        },
                        {
                            title: 'Username',
                            data: 'email',
                            className: 'phone',
                            visible: true
                        },
                        {
                            title: 'Roles',                            
                            data: 'roles[, ].display_name',
                            className: 'desktop',
                            defaultContent: 'None'
                        },
                        {
                            title: 'Active',
                            data: 'isactive',
                            className: 'phone',
                            visible: true,                            
                            render: ((data: any, t: string, row: any, meta: DataTables.CellMetaSettings): any =>
                            {
                                if(data == null)
                                {
                                    return "";
                                }

                                if(data == 1)
                                {
                                    return "True";
                                }
                                else
                                {
                                    return "False";
                                }
                            })
                        },
                        {
                            // This field belongs to a different database, so it
                            // needs to be included on the server side!
                            title: '# Licenses',
                            data: 'activations_allowed',
                            className: 'desktop',
                            visible: true
                        },
                        {
                            // This field belongs to a different database, so it
                            // needs to be included on the server side!
                            title: '# Lic. Used',
                            data: 'activations_used',
                            className: 'desktop',
                            visible: true
                        },
                        {
                            title: 'Date Registered',
                            data: 'created_at',
                            className: 'desktop',
                            visible: true
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let userTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/users",
                        dataSrc: "",                        
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
                let usersTableSettings: DataTables.Settings =
                    {
                        autoWidth: true,
                        stateSave: true,
                        columns: userTableColumns,
                        ajax: userTablesLoadFromAjaxSettings,
                        
                        // We grab the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        })
                    };

                    usersTableSettings['resonsive'] = true;
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
                            visible: true
                        },
                        {
                            title: 'Active',
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
                                    return "True";
                                }
                                else
                                {
                                    return "False";
                                }
                            })
                        },
                        {
                            title: 'Assigned Filters',
                            data: 'assigned_filter_ids',
                            visible: false
                        },
                        {
                            title: 'Date Registered',
                            data: 'created_at',
                            visible: true
                        },
                        {
                            title: 'Date Modified',
                            data: 'updated_at',
                            visible: true
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let groupTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/groups",
                        dataSrc: "",
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
                        autoWidth: true,
                        stateSave: true,
                        columns: groupTableColumns,
                        ajax: groupTablesLoadFromAjaxSettings,

                        // We grab the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
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
                            visible: true
                        },
                        {
                            title: 'List Group Name',
                            data: 'namespace',
                            visible: true
                        },
                        {
                            title: 'Type',
                            data: 'type',
                            visible: true
                        },
                        {
                            title: '# Entries',
                            data: 'entries_count',
                            visible: true
                        },
                        {
                            title: 'Date Created',
                            data: 'created_at',
                            visible: true
                        },
                        {
                            title: 'Date Modified',
                            data: 'updated_at',
                            visible: true
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let filterTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/filterlists",
                        dataSrc: "",
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
                        autoWidth: true,
                        stateSave: true,
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
                            visible: true
                        },
                        {
                            title: 'Username',
                            data: 'user.email',
                            visible: true
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
                                
                                if(data == 1)
                                {
                                    return "True";
                                }
                                else
                                {
                                    return "False";
                                }
                            })
                        },
                        {
                            title: 'Date Requested',
                            data: 'created_at',
                            visible: true
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let userDeactivationRequestTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/deactivationreq",
                        dataSrc: "",
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
                        autoWidth: true,
                        stateSave: true,
                        columns: userDeactivationRequestTableColumns,
                        ajax: userDeactivationRequestTablesLoadFromAjaxSettings,

                        // We grap the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        })
                    };

                this.m_tableUserDeactivationRequests = $('#user_deactivation_request_table').DataTable(userDeactivationRequestTableSettings);
            });

            let whiteListTableConstruction = (() =>
            {
                let whiteListTableColumns: DataTables.ColumnSettings[] =
                    [
                        {
                            title: 'Whitelist Id',
                            data: 'id',
                            visible: false
                        },
                        {
                            title: 'Whitelist Application Name',
                            data: 'name',
                            visible: true
                        },
                        {
                            title: 'Active',
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
                                    return "True";
                                }
                                else
                                {
                                    return "False";
                                }
                            })
                        },
                        {
                            title: 'Date Registered',
                            data: 'created_at',
                            visible: true
                        },
                        {
                            title: 'Date Modified',
                            data: 'updated_at',
                            visible: true
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let whiteListTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/whitelists",
                        dataSrc: "",
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

                // Define whiteList table settings, ENSURE TO INCLUDE AJAX SETTINGS!
                let whiteListTableSettings: DataTables.Settings =
                    {
                        autoWidth: true,
                        stateSave: true,
                        columns: whiteListTableColumns,
                        ajax: whiteListTablesLoadFromAjaxSettings,

                        // We grab the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        })
                    };

                this.m_tableWhiteLists = $('#whitelist_table').DataTable(whiteListTableSettings);
            });
            let blackListTableConstruction = (() =>
            {
                let blackListTableColumns: DataTables.ColumnSettings[] =
                    [
                        {
                            title: 'Blacklist Id',
                            data: 'id',
                            visible: false
                        },
                        {
                            title: 'Blacklist Application Name',
                            data: 'name',
                            visible: true
                        },
                        {
                            title: 'Active',
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
                                    return "True";
                                }
                                else
                                {
                                    return "False";
                                }
                            })
                        },
                        {
                            title: 'Date Registered',
                            data: 'created_at',
                            visible: true
                        },
                        {
                            title: 'Date Modified',
                            data: 'updated_at',
                            visible: true
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let blackListTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/blacklists",
                        dataSrc: "",
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

                // Define whiteList table settings, ENSURE TO INCLUDE AJAX SETTINGS!
                let blackListTableSettings: DataTables.Settings =
                    {
                        autoWidth: true,
                        stateSave: true,
                        columns: blackListTableColumns,
                        ajax: blackListTablesLoadFromAjaxSettings,

                        // We grab the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        })
                    };

                this.m_tableBlackLists = $('#blacklist_table').DataTable(blackListTableSettings);
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
                            visible: true
                        },
                        {
                            title: 'Identifier',
                            data: 'identifier',
                            visible: true
                        },
                        {
                            title: 'Device Id',
                            data: 'device_id',
                            visible: true
                        },
                        {
                            title: 'IP Address',
                            data: 'ip_address',
                            visible: true
                        },
                        {
                            title: 'Bypass Quantity',
                            data: 'bypass_quantity',
                            visible: true
                        },
                        {
                            title: 'Bypass Period',
                            data: 'bypass_period',
                            visible: true
                        },
                        {
                            title: 'Bypass Used',
                            data: 'bypass_used',
                            visible: true
                        },
                        {
                            title: 'Updated date',
                            data: 'updated_at',
                            visible: true
                        }
                    ];

                // Set our table's loading AJAX settings to call the admin
                // control API with the appropriate arguments.
                let appUserActivationTablesLoadFromAjaxSettings: DataTables.AjaxSettings =
                    {
                        url: "api/admin/activations",
                        dataSrc: "",
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

                // Define whiteList table settings, ENSURE TO INCLUDE AJAX SETTINGS!
                let appUserActivationTableSettings: DataTables.Settings =
                    {
                        autoWidth: true,
                        stateSave: true,
                        columns: appUserActivationTableColumns,
                        ajax: appUserActivationTablesLoadFromAjaxSettings,

                        // We grab the row callback with a fat arrow to keep the
                        // class context. Otherwise, we'll lose it in the
                        // callback, and "this" will be the datatable or a child
                        // of it.
                        rowCallback: ((row: Node, data: any[] | Object): void =>
                        {
                            this.OnTableRowCreated(row, data);
                        })
                    };

                this.m_tableAppUserActivationTable = $('#app_user_activations_table').DataTable(appUserActivationTableSettings);
            });

            userTableConstruction();
            groupTableConstruction();
            filterTableConstruction();
            deactivationRequestConstruction();
            whiteListTableConstruction();
            blackListTableConstruction();
            appUserActivationTableConstruction();
        }

        private ConstructNavigation(): void
        {
            // Get references to the top level menu buttons.            
            this.m_btnSignOut = document.getElementById('btn_sign_out') as HTMLLinkElement;

            // Get reference to the main menu tab buttons.
            this.m_tabBtnUsers = document.querySelector('a[href="#tab_users"]') as HTMLLinkElement;
            this.m_tabBtnGroups = document.querySelector('a[href="#tab_groups"]') as HTMLLinkElement;
            this.m_tabBtnFilterLists = document.querySelector('a[href="#tab_filter_lists"]') as HTMLLinkElement;
            this.m_tabBtnUserRequest = document.querySelector('a[href="#tab_user_deactivation_requests"]') as HTMLLinkElement;
            this.m_tabBtnWhiteBlackLists = document.querySelector('a[href="#tab_user_global_white_black_list"]') as HTMLLinkElement;
            this.m_tabBtnAppUserActivation = document.querySelector('a[href="#tab_app_user_activations"]') as HTMLLinkElement;
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
            this.m_btnBlacklist = document.getElementById('global_radio_blacklist') as HTMLInputElement;
            this.m_btnWhitelist = document.getElementById('global_radio_whitelist') as HTMLInputElement;
            this.m_btnAddItem = document.getElementById('btn_application_add') as HTMLButtonElement;
            this.m_btnRemoveItem = document.getElementById('btn_application_remove') as HTMLButtonElement;
            this.m_btnRemoveItem.disabled = true;
            this.m_btnApplyToGroup = document.getElementById('btn_apply_group') as HTMLButtonElement;

            this.m_btnDeleteAppUserActivation = document.getElementById('btn_delete_activation') as HTMLButtonElement;
            this.m_btnBlockAppUserActivation = document.getElementById('btn_block_activations') as HTMLButtonElement;
            this.m_btnDeleteAppUserActivation.disabled = true;
            this.m_btnBlockAppUserActivation.disabled = true;
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
            this.m_tabBtnWhiteBlackLists.onclick = ((e: MouseEvent) =>
            {
                if(this.m_btnWhitelist.checked) {
                    this.ViewState = DashboardViewStates.WhiteListView;
                } else {
                    this.ViewState = DashboardViewStates.BlackListView;
                }
            });
            this.m_btnWhitelist.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.WhiteListView;
                let itemIsActuallySelected = $("#whitelist_table").children().next().find(".selected").length > 0?true:false;
                
                this.m_btnRemoveItem.disabled  = itemIsActuallySelected;
            });

            this.m_tabBtnAppUserActivation.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.AppUserActivationView;
            });

            this.m_btnBlacklist.onclick = ((e: MouseEvent) =>
            {
                this.ViewState = DashboardViewStates.BlackListView;

                let itemIsActuallySelected = $("#blacklist_table").children().next().find(".selected").length > 0?true:false;
                
                this.m_btnRemoveItem.disabled  = itemIsActuallySelected;
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

                case 'whitelist_table':
                    {
                        this.m_btnRemoveItem.disabled = !itemIsActuallySelected;
                    }
                    break;

                case 'blacklist_table':
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

            console.log(data);

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
                        userRecord.StartEditing(this.m_tableGroups.data(), data);
                    }
                    break;

                case 'group_table':
                    {
                        let groupRecord = new GroupRecord();

                        // We supply everything in the filter lists table so that assigned
                        // filters can be laid out for editing.
                        groupRecord.StartEditing(this.m_tableFilterLists.data(), data);

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
                case 'whitelist_table':
                    {
                        let whitelistRecord = new WhitelistRecord();
                        
                        whitelistRecord.ActionCompleteCallback = ((action: string): void =>
                        {

                            whitelistRecord.StopEditing();

                            // Whenever we do any action on a user record
                            // successfully, we want to simply redraw the user
                            // table to get the updated data showing.
                            this.ForceTableRedraw(this.m_tableWhiteLists);
                        });

                        // We supply everything in the groups table so that the user's group
                        // can be changed to any available group.
                        whitelistRecord.StartEditing(data);
                    }
                    break;

                case 'blacklist_table':
                    {
                        let blacklistRecord = new BlacklistRecord();
                        
                        blacklistRecord.ActionCompleteCallback = ((action: string): void =>
                        {

                            blacklistRecord.StopEditing();

                            // Whenever we do any action on a user record
                            // successfully, we want to simply redraw the user
                            // table to get the updated data showing.
                            this.ForceTableRedraw(this.m_tableBlackLists);
                        });

                        // We supply everything in the groups table so that the user's group
                        // can be changed to any available group.
                        blacklistRecord.StartEditing(data);
                    }
                    break;      
                case 'app_user_activations_table':
                    {
                        let appUserActivationRecord = new AppUserActivationRecord();
                        
                        appUserActivationRecord.ActionCompleteCallback = ((action: string): void =>
                        {

                            appUserActivationRecord.StopEditing();

                            // Whenever we do any action on a user record
                            // successfully, we want to simply redraw the user
                            // table to get the updated data showing.
                            
                            this.ForceTableRedraw(this.m_tableAppUserActivationTable);
                        });

                        // We supply everything in the groups table so that the user's group
                        // can be changed to any available group.
                        appUserActivationRecord.StartEditing(data);
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
        private ForceTableRedraw(table: DataTables.DataTable, resetPagination: boolean = false): void
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
            console.log('OnCreateUserClicked');
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
            console.log('OnCreateGroupClicked');

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
                        this.ForceTableRedraw(this.m_tableUsers);
                        this.ForceTableRedraw(this.m_tableGroups);
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
                        this.ForceTableRedraw(this.m_tableUsers);
                        this.ForceTableRedraw(this.m_tableGroups);
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
            if(this.m_btnWhitelist.checked) {
                let newWhitelist = new WhitelistRecord();

                // We supply everything in the groups table so that the user's group
                // can be changed to any available group.            
                newWhitelist.StartEditing( );
                newWhitelist.ActionCompleteCallback = ((action: string): void =>
                {

                    newWhitelist.StopEditing();

                    // Whenever we do any action on a user record
                    // successfully, we want to simply redraw the user
                    // table to get the updated data showing.
                    this.ForceTableRedraw(this.m_tableWhiteLists);
                });
            } else {
                let newBlacklist = new BlacklistRecord();
                // We supply everything in the groups table so that the user's group
                // can be changed to any available group.            
                newBlacklist.StartEditing( );
                newBlacklist.ActionCompleteCallback = ((action: string): void =>
                {

                    newBlacklist.StopEditing();

                    // Whenever we do any action on a user record
                    // successfully, we want to simply redraw the user
                    // table to get the updated data showing.
                    this.ForceTableRedraw(this.m_tableBlackLists);
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
            if(this.m_btnWhitelist.checked) {
                let selectedItem = this.m_tableWhiteLists.row('.selected').data();
                if (selectedItem != null)
                {
                    var whiteListObj: WhitelistRecord;
    
                    try 
                    {
                        whiteListObj = BaseRecord.CreateFromObject(WhitelistRecord, selectedItem);
    
                        // We want to update both users and groups after delete.
                        whiteListObj.ActionCompleteCallback = ((action: string): void =>
                        {
                            this.ForceTableRedraw(this.m_tableWhiteLists);
                        });
    
                        if (confirm("Really delete Whitelist Application? THIS CANNOT BE UNDONE!!!"))
                        {
                            whiteListObj.Delete();
                        }
                    }
                    catch (e)
                    {
                        this.m_btnRemoveItem.disabled = false;
                        console.log('Failed to load whitelist record from table selection.');
                    }
                }
            } else {
                let selectedItem = this.m_tableBlackLists.row('.selected').data();
                if (selectedItem != null)
                {
                    var blackListObj: BlacklistRecord;
    
                    try 
                    {
                        blackListObj = BaseRecord.CreateFromObject(BlacklistRecord, selectedItem);
    
                        // We want to update both users and groups after delete.
                        blackListObj.ActionCompleteCallback = ((action: string): void =>
                        {
                            this.ForceTableRedraw(this.m_tableBlackLists);
                        });
    
                        if (confirm("Really delete Blacklist Application? THIS CANNOT BE UNDONE!!!"))
                        {
                            blackListObj.Delete();
                        }
                    }
                    catch (e)
                    {
                        this.m_btnRemoveItem.disabled = false;
                        console.log('Failed to load blacklist record from table selection.');
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
            let apply_overlay = new ApplyToGroupOverlay();
            apply_overlay.Show();
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

            this.m_viewUserManagement.style.visibility = "hidden";
            this.m_viewGroupManagement.style.visibility = "hidden";
            this.m_viewFilterManagement.style.visibility = "hidden";
            this.m_viewUserDeactivationRequestManagement.style.visibility = "hidden";
            this.m_viewWhiteListManagement.style.visibility = "hidden";
            this.m_viewBlackListManagement.style.visibility = "hidden";
            this.m_viewAppUserActivationManagement.style.visibility = "hidden";
          
            switch (value)
            {
                case DashboardViewStates.UserListView:
                    {
                        this.ForceTableRedraw(this.m_tableUsers);
                        this.m_viewUserManagement.style.visibility = "visible";
                    }
                    break;

                case DashboardViewStates.GroupListView:
                    {
                        this.ForceTableRedraw(this.m_tableGroups);
                        this.m_viewGroupManagement.style.visibility = "visible";
                    }
                    break;

                case DashboardViewStates.FilterListView:
                    {
                        this.ForceTableRedraw(this.m_tableFilterLists);
                        this.m_viewFilterManagement.style.visibility = "visible";
                    }
                    break;

                case DashboardViewStates.DeactivationRequestListView:
                    {
                        this.m_viewUserDeactivationRequestManagement.style.visibility = "visible";
                    }
                    break;

                case DashboardViewStates.WhiteListView:
                    {
                        this.ForceTableRedraw(this.m_tableWhiteLists);
                        this.m_viewWhiteListManagement.style.visibility = "visible";
                    }
                    break;

                case DashboardViewStates.BlackListView:
                    {
                        this.ForceTableRedraw(this.m_tableBlackLists);
                        this.m_viewBlackListManagement.style.visibility = "visible";
                    }
                    break;
                case DashboardViewStates.AppUserActivationView:
                    {
                        this.ForceTableRedraw(this.m_tableAppUserActivationTable);
                        this.m_viewAppUserActivationManagement.style.visibility = "visible";
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
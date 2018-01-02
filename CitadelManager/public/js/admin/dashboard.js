var Citadel;
(function (Citadel) {
    var DashboardViewStates;
    (function (DashboardViewStates) {
        DashboardViewStates[DashboardViewStates["UserListView"] = 0] = "UserListView";
        DashboardViewStates[DashboardViewStates["GroupListView"] = 1] = "GroupListView";
        DashboardViewStates[DashboardViewStates["FilterListView"] = 2] = "FilterListView";
        DashboardViewStates[DashboardViewStates["DeactivationRequestListView"] = 3] = "DeactivationRequestListView";
        DashboardViewStates[DashboardViewStates["AppView"] = 4] = "AppView";
        DashboardViewStates[DashboardViewStates["AppGroupView"] = 5] = "AppGroupView";
        DashboardViewStates[DashboardViewStates["AppUserActivationView"] = 6] = "AppUserActivationView";
    })(DashboardViewStates || (DashboardViewStates = {}));
    var Dashboard = (function () {
        function Dashboard() {
            var _this = this;
            this.ConstructNavigation();
            this.ConstructManagementViews();
            this.m_filterListUploadController = new Citadel.ListUploadOverlay();
            this.m_filterListUploadController.UploadCompleteCallback = (function () {
                _this.ForceTableRedraw(_this.m_tableFilterLists);
                _this.ForceTableRedraw(_this.m_tableGroups);
                _this.m_filterListUploadController.Hide();
            });
            this.m_filterListUploadController.UploadFailedCallback = (function () {
                _this.ForceTableRedraw(_this.m_tableFilterLists);
                _this.ForceTableRedraw(_this.m_tableGroups);
                _this.m_filterListUploadController.Hide();
            });
        }
        Dashboard.prototype.ConstructManagementViews = function () {
            this.m_viewUserManagement = document.getElementById('view_user_management');
            this.m_viewGroupManagement = document.getElementById('view_group_management');
            this.m_viewFilterManagement = document.getElementById('view_filter_management');
            this.m_viewUserDeactivationRequestManagement = document.getElementById('view_user_deactivation_request_management');
            this.m_viewAppManagement = document.getElementById('view_app_management');
            this.m_viewAppGroupManagement = document.getElementById('view_app_group_management');
            this.m_viewAppUserActivationManagement = document.getElementById('view_app_user_activations_management');
            this.ConstructTables();
            this.ConstructDragula();
            this.ViewState = DashboardViewStates.UserListView;
        };
        Dashboard.prototype.ConstructDragula = function () {
            var c = [
                document.getElementById('group_blacklist_filters'),
                document.getElementById('group_whitelist_filters'),
                document.getElementById('group_bypass_filters'),
                document.getElementById('group_unassigned_filters')
            ];
            var d = {
                containers: c,
                revertOnSpill: true,
                removeOnSpill: false,
                direction: 'vertical',
                copy: false,
                delay: false,
                mirrorContainer: document.body,
                isContainer: (function (element) {
                    return element.classList.contains('dragula-container');
                }),
                accepts: (function (el, target, source, sibling) {
                    var attr = el.getAttribute('citadel-filter-list-type');
                    if ((attr.toLowerCase() === 'nlp' || attr.toLowerCase() === 'trigger') && (target.id != 'group_blacklist_filters' && target.id != 'group_unassigned_filters')) {
                        return false;
                    }
                    return true;
                })
            };
            this.m_filterGroupSelectionArea = dragula(d);
        };
        Dashboard.prototype.ConstructTables = function () {
            var _this = this;
            var userTableConstruction = (function () {
                var userTableColumns = [
                    {
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
                        render: (function (data, t, row, meta) {
                            if (data == null) {
                                return "";
                            }
                            if (data == 1) {
                                return "True";
                            }
                            else {
                                return "False";
                            }
                        })
                    },
                    {
                        title: '# Licenses',
                        data: 'activations_allowed',
                        className: 'desktop',
                        visible: true
                    },
                    {
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
                var userTablesLoadFromAjaxSettings = {
                    url: "api/admin/users",
                    dataSrc: "",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "GET",
                    error: (function (jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                        }
                    })
                };
                var usersTableSettings = {
                    autoWidth: true,
                    stateSave: true,
                    columns: userTableColumns,
                    ajax: userTablesLoadFromAjaxSettings,
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    })
                };
                usersTableSettings['resonsive'] = true;
                _this.m_tableUsers = $('#user_table').DataTable(usersTableSettings);
            });
            var groupTableConstruction = (function () {
                var groupTableColumns = [
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
                        render: (function (data, t, row, meta) {
                            if (data == null) {
                                return "";
                            }
                            if (data == 1) {
                                return "True";
                            }
                            else {
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
                var groupTablesLoadFromAjaxSettings = {
                    url: "api/admin/groups",
                    dataSrc: "",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "GET",
                    error: (function (jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                        }
                    })
                };
                var groupTableSettings = {
                    autoWidth: true,
                    stateSave: true,
                    columns: groupTableColumns,
                    ajax: groupTablesLoadFromAjaxSettings,
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    })
                };
                _this.m_tableGroups = $('#group_table').DataTable(groupTableSettings);
            });
            var filterTableConstruction = (function () {
                var filterTableColumns = [
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
                var filterTablesLoadFromAjaxSettings = {
                    url: "api/admin/filterlists",
                    dataSrc: "",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "GET",
                    error: (function (jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                        }
                    })
                };
                var filterTableSettings = {
                    autoWidth: true,
                    stateSave: true,
                    columns: filterTableColumns,
                    ajax: filterTablesLoadFromAjaxSettings,
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    })
                };
                _this.m_tableFilterLists = $('#filter_table').DataTable(filterTableSettings);
            });
            var deactivationRequestConstruction = (function () {
                var userDeactivationRequestTableColumns = [
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
                        render: (function (data, t, row, meta) {
                            if (data == null) {
                                return "";
                            }
                            if (data == 1) {
                                return "True";
                            }
                            else {
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
                var userDeactivationRequestTablesLoadFromAjaxSettings = {
                    url: "api/admin/deactivationreq",
                    dataSrc: "",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "GET",
                    error: (function (jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                        }
                    })
                };
                var userDeactivationRequestTableSettings = {
                    autoWidth: true,
                    stateSave: true,
                    columns: userDeactivationRequestTableColumns,
                    ajax: userDeactivationRequestTablesLoadFromAjaxSettings,
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    })
                };
                _this.m_tableUserDeactivationRequests = $('#user_deactivation_request_table').DataTable(userDeactivationRequestTableSettings);
            });
            var appListTableConstruction = (function () {
                var appListTableColumns = [
                    {
                        title: 'App Id',
                        data: 'id',
                        visible: false
                    },
                    {
                        title: 'Application Name',
                        data: 'name',
                        visible: true,
                        width: '200px'
                    },
                    {
                        title: 'Notes',
                        data: 'notes',
                        visible: true,
                        width: '200px'
                    },
                    {
                        title: 'Linked Group',
                        data: 'group_name',
                        visible: true
                    },
                    {
                        title: 'Date Modified',
                        data: 'updated_at',
                        visible: true,
                        width: '200px'
                    }
                ];
                var appListTablesLoadFromAjaxSettings = {
                    url: "api/admin/app",
                    dataSrc: "",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "GET",
                    error: (function (jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                        }
                    })
                };
                var appListTableSettings = {
                    autoWidth: true,
                    stateSave: true,
                    columns: appListTableColumns,
                    ajax: appListTablesLoadFromAjaxSettings,
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    })
                };
                _this.m_tableAppLists = $('#app_table').DataTable(appListTableSettings);
            });
            var appGroupListTableConstruction = (function () {
                var appGroupListTableColumns = [
                    {
                        title: 'App Group Id',
                        data: 'id',
                        visible: false
                    },
                    {
                        title: 'App Group Name',
                        data: 'group_name',
                        visible: true,
                        width: '200px'
                    },
                    {
                        title: 'Linked Apps',
                        data: 'app_names',
                        visible: true
                    },
                    {
                        title: 'Date Modified',
                        data: 'updated_at',
                        visible: true,
                        width: '200px'
                    }
                ];
                var appGroupListTablesLoadFromAjaxSettings = {
                    url: "api/admin/app_group",
                    dataSrc: "",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "GET",
                    error: (function (jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                        }
                    })
                };
                var appGroupListTableSettings = {
                    autoWidth: true,
                    stateSave: true,
                    columns: appGroupListTableColumns,
                    ajax: appGroupListTablesLoadFromAjaxSettings,
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    })
                };
                _this.m_tableAppGroupLists = $('#app_group_table').DataTable(appGroupListTableSettings);
            });
            var appUserActivationTableConstruction = (function () {
                var appUserActivationTableColumns = [
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
                        title: 'App Version',
                        data: 'app_version',
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
                var appUserActivationTablesLoadFromAjaxSettings = {
                    url: "api/admin/activations",
                    dataSrc: "",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "GET",
                    error: (function (jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                        }
                    })
                };
                var appUserActivationTableSettings = {
                    autoWidth: true,
                    stateSave: true,
                    columns: appUserActivationTableColumns,
                    ajax: appUserActivationTablesLoadFromAjaxSettings,
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    })
                };
                _this.m_tableAppUserActivationTable = $('#app_user_activations_table').DataTable(appUserActivationTableSettings);
            });
            userTableConstruction();
            groupTableConstruction();
            filterTableConstruction();
            deactivationRequestConstruction();
            appListTableConstruction();
            appGroupListTableConstruction();
            appUserActivationTableConstruction();
        };
        Dashboard.prototype.ConstructNavigation = function () {
            this.m_btnSignOut = document.getElementById('btn_sign_out');
            this.m_tabBtnUsers = document.querySelector('a[href="#tab_users"]');
            this.m_tabBtnGroups = document.querySelector('a[href="#tab_groups"]');
            this.m_tabBtnFilterLists = document.querySelector('a[href="#tab_filter_lists"]');
            this.m_tabBtnUserRequest = document.querySelector('a[href="#tab_user_deactivation_requests"]');
            this.m_tabBtnAppGroup = document.querySelector('a[href="#tab_app_groups"]');
            this.m_tabBtnAppUserActivation = document.querySelector('a[href="#tab_app_user_activations"]');
            this.m_btnCreateUser = document.getElementById('btn_user_add');
            this.m_btnDeleteUser = document.getElementById('btn_user_delete');
            this.m_btnDeleteUser.disabled = true;
            this.m_btnCreateGroup = document.getElementById('btn_group_add');
            this.m_btnDeleteGroup = document.getElementById('btn_group_delete');
            this.m_btnCloneGroup = document.getElementById('btn_group_clone');
            this.m_btnDeleteGroup.disabled = true;
            this.m_btnCloneGroup.disabled = true;
            this.m_btnUploadFilterLists = document.getElementById('btn_add_filter_lists');
            this.m_btnDeleteFilterList = document.getElementById('btn_delete_filter_list');
            this.m_btnDeleteFilterListInNamespace = document.getElementById('btn_delete_filter_list_namespace');
            this.m_btnDeleteFilterListTypeInNamespace = document.getElementById('btn_delete_filter_list_type_namespace');
            this.m_btnDeleteFilterList.disabled = true;
            this.m_btnDeleteFilterListInNamespace.disabled = true;
            this.m_btnDeleteFilterListTypeInNamespace.disabled = true;
            this.m_btnDeleteUserDeactivationRequest = document.getElementById('btn_delete_user_deactivation_request');
            this.m_btnDeleteUserDeactivationRequest.disabled = true;
            this.m_btnRefreshUserDeactivationRequests = document.getElementById('btn_refresh_user_deactivation_request_list');
            this.m_btnApp = document.getElementById('global_radio_app');
            this.m_btnAppGroup = document.getElementById('global_radio_app_group');
            this.m_btnAddItem = document.getElementById('btn_application_add');
            this.m_btnRemoveItem = document.getElementById('btn_application_remove');
            this.m_btnRemoveItem.disabled = true;
            this.m_btnApplyToGroup = document.getElementById('btn_apply_group');
            this.m_btnDeleteAppUserActivation = document.getElementById('btn_delete_activation');
            this.m_btnBlockAppUserActivation = document.getElementById('btn_block_activations');
            this.m_btnDeleteAppUserActivation.disabled = true;
            this.m_btnBlockAppUserActivation.disabled = true;
            this.InitButtonHandlers();
        };
        Dashboard.prototype.InitButtonHandlers = function () {
            var _this = this;
            this.m_btnSignOut.onclick = (function (e) {
                _this.OnSignOutClicked(e);
            });
            this.m_btnCreateUser.onclick = (function (e) {
                _this.OnCreateUserClicked(e);
            });
            this.m_btnDeleteUser.onclick = (function (e) {
                _this.OnDeleteUserClicked(e);
            });
            this.m_btnCreateGroup.onclick = (function (e) {
                _this.OnCreateGroupClicked(e);
            });
            this.m_btnDeleteGroup.onclick = (function (e) {
                _this.OnDeleteGroupClicked(e);
            });
            this.m_btnCloneGroup.onclick = (function (e) {
                _this.OnCloneGroupClicked(e);
            });
            this.m_btnUploadFilterLists.onclick = (function (e) {
                _this.m_filterListUploadController.Show(_this.m_tableFilterLists.data());
            });
            this.m_btnDeleteFilterList.onclick = (function (e) {
                _this.OnDeleteFilterListClicked(e);
            });
            this.m_btnDeleteFilterListInNamespace.onclick = (function (e) {
                _this.OnDeleteFilterListInNamespaceClicked(e, false);
            });
            this.m_btnDeleteFilterListTypeInNamespace.onclick = (function (e) {
                _this.OnDeleteFilterListInNamespaceClicked(e, true);
            });
            this.m_btnDeleteUserDeactivationRequest.onclick = (function (e) {
                _this.OnDeleteUserDeactivationRequestClicked(e);
            });
            this.m_btnRefreshUserDeactivationRequests.onclick = (function (e) {
                _this.ForceTableRedraw(_this.m_tableUserDeactivationRequests);
            });
            this.m_tabBtnUsers.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.UserListView;
            });
            this.m_tabBtnGroups.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.GroupListView;
            });
            this.m_tabBtnFilterLists.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.FilterListView;
            });
            this.m_tabBtnUserRequest.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.DeactivationRequestListView;
            });
            this.m_tabBtnAppGroup.onclick = (function (e) {
                if (_this.m_btnApp.checked) {
                    _this.ViewState = DashboardViewStates.AppView;
                }
                else {
                    _this.ViewState = DashboardViewStates.AppGroupView;
                }
            });
            this.m_btnApp.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.AppView;
                var itemIsActuallySelected = $("#app_table").children().next().find(".selected").length > 0 ? true : false;
                _this.m_btnRemoveItem.disabled = itemIsActuallySelected;
                _this.m_btnAddItem.innerHTML = '<span class="icon mif-stack"></span>Add <br /> Application';
                _this.m_btnRemoveItem.innerHTML = '<span class="mif-cancel"></span>Remove <br /> Application';
                _this.m_btnApplyToGroup.innerHTML = '<span class="icon mif-checkmark" style="color:green"></span> Apply<br />To App Group';
            });
            this.m_btnAppGroup.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.AppGroupView;
                var itemIsActuallySelected = $("#app_group_table").children().next().find(".selected").length > 0 ? true : false;
                _this.m_btnRemoveItem.disabled = itemIsActuallySelected;
                _this.m_btnAddItem.innerHTML = '<span class="icon mif-stack"></span>Add <br /> Application <br /> Group';
                _this.m_btnRemoveItem.innerHTML = '<span class="mif-cancel"></span>Remove <br /> Application <br /> Group';
                _this.m_btnApplyToGroup.innerHTML = '<span class="icon mif-checkmark" style="color:green"></span> Apply<br />To User Group';
            });
            this.m_btnAddItem.onclick = (function (e) {
                _this.OnAddApplicationClicked(e);
            });
            this.m_btnRemoveItem.onclick = (function (e) {
                _this.onRemoveApplicationClicked(e);
            });
            this.m_btnApplyToGroup.onclick = (function (e) {
                _this.onApplyToGroupClicked(e);
            });
            this.m_tabBtnAppUserActivation.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.AppUserActivationView;
            });
            this.m_btnDeleteAppUserActivation.onclick = (function (e) {
                _this.onDeleteAppUserActivationClicked(e);
            });
            this.m_btnBlockAppUserActivation.onclick = (function (e) {
                _this.onBlockAppUserActivationClicked(e);
            });
        };
        Dashboard.prototype.OnTableRowCreated = function (row, data) {
            var _this = this;
            var tableRow = row;
            tableRow.onclick = (function (e) {
                _this.OnTableRowClicked(e, data);
            });
            tableRow.ondblclick = (function (e) {
                _this.OnTableRowDoubleClicked(e, data);
            });
        };
        Dashboard.prototype.OnTableRowClicked = function (e, data) {
            e.stopImmediatePropagation();
            e.stopPropagation();
            if (!$(e.currentTarget).hasClass('dataTables_empty')) {
                $(e.currentTarget).toggleClass('selected').siblings().removeClass('selected');
            }
            var parentTable = $(e.currentTarget).closest('table')[0];
            var itemIsActuallySelected = $(e.currentTarget).hasClass('selected');
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
            }
        };
        Dashboard.prototype.OnTableRowDoubleClicked = function (e, data) {
            var _this = this;
            e.stopImmediatePropagation();
            e.stopPropagation();
            var selectedRow = e.currentTarget;
            var parentTable = $(selectedRow).closest('table')[0];
            switch (parentTable.id) {
                case 'user_table':
                    {
                        var userRecord_1 = new Citadel.UserRecord();
                        userRecord_1.ActionCompleteCallback = (function (action) {
                            userRecord_1.StopEditing();
                            _this.ForceTableRedraw(_this.m_tableUsers);
                        });
                        userRecord_1.StartEditing(this.m_tableGroups.data(), data);
                    }
                    break;
                case 'group_table':
                    {
                        var groupRecord_1 = new Citadel.GroupRecord();
                        groupRecord_1.StartEditing(this.m_tableFilterLists.data(), data);
                        groupRecord_1.ActionCompleteCallback = (function (action) {
                            groupRecord_1.StopEditing();
                            _this.ForceTableRedraw(_this.m_tableGroups);
                            _this.ForceTableRedraw(_this.m_tableUsers);
                        });
                    }
                    break;
                case 'filter_table':
                    {
                    }
                    break;
                case 'user_deactivation_request_table':
                    {
                        var deactivationRequestRecord_1 = new Citadel.DeactivationRequestRecord();
                        deactivationRequestRecord_1.StartEditing(data);
                        deactivationRequestRecord_1.ActionCompleteCallback = (function (action) {
                            deactivationRequestRecord_1.StopEditing();
                            _this.ForceTableRedraw(_this.m_tableUserDeactivationRequests);
                        });
                    }
                    break;
                case 'app_table':
                    {
                        var appRecord_1 = new Citadel.AppRecord();
                        appRecord_1.ActionCompleteCallback = (function (action) {
                            appRecord_1.StopEditing();
                            _this.ForceTableRedraw(_this.m_tableAppLists);
                        });
                        appRecord_1.StartEditing(data);
                    }
                    break;
                case 'app_group_table':
                    {
                        var appGroupRecord_1 = new Citadel.AppGroupRecord();
                        appGroupRecord_1.ActionCompleteCallback = (function (action) {
                            appGroupRecord_1.StopEditing();
                            _this.ForceTableRedraw(_this.m_tableAppGroupLists);
                        });
                        appGroupRecord_1.StartEditing(data);
                    }
                    break;
                case 'app_user_activations_table':
                    {
                        var appUserActivationRecord_1 = new Citadel.AppUserActivationRecord();
                        appUserActivationRecord_1.ActionCompleteCallback = (function (action) {
                            appUserActivationRecord_1.StopEditing();
                            _this.ForceTableRedraw(_this.m_tableAppUserActivationTable);
                        });
                        appUserActivationRecord_1.StartEditing(data);
                    }
                    break;
            }
        };
        Dashboard.prototype.GetSelectedRowForTable = function (table) {
            var selectedRow = $(table).find('tr .selected').first();
            if (selectedRow == null) {
                return null;
            }
            return table.row(selectedRow);
        };
        Dashboard.prototype.ClearSelectedItemsInTable = function (table) {
            $(table).children().removeClass('selected');
        };
        Dashboard.prototype.ForceTableRedraw = function (table, resetPagination) {
            if (resetPagination === void 0) { resetPagination = false; }
            table.ajax.reload();
        };
        Dashboard.prototype.OnSignOutClicked = function (e) {
            if (confirm("Are you sure you'd like to sign out?")) {
                $.post('logout', function (data, textStatus, jqXHR) {
                    location.reload();
                });
            }
        };
        Dashboard.prototype.OnCreateUserClicked = function (e) {
            var _this = this;
            var newUser = new Citadel.UserRecord();
            newUser.StartEditing(this.m_tableGroups.data(), this.m_tableUsers.data()['all_user_roles']);
            newUser.ActionCompleteCallback = (function (action) {
                newUser.StopEditing();
                _this.ForceTableRedraw(_this.m_tableUsers);
            });
        };
        Dashboard.prototype.OnDeleteUserClicked = function (e) {
            var _this = this;
            var selectedItem = this.m_tableUsers.row('.selected').data();
            if (selectedItem != null) {
                var userObject;
                try {
                    userObject = Citadel.BaseRecord.CreateFromObject(Citadel.UserRecord, selectedItem);
                    userObject.ActionCompleteCallback = (function (action) {
                        _this.ForceTableRedraw(_this.m_tableUsers);
                    });
                    if (confirm("Really delete user? THIS CANNOT BE UNDONE!!!")) {
                        userObject.Delete();
                    }
                }
                catch (e) {
                    console.log('Failed to load user record from table selection.');
                    console.log(e);
                }
            }
        };
        Dashboard.prototype.OnCreateGroupClicked = function (e) {
            var _this = this;
            var groupRecord = new Citadel.GroupRecord();
            groupRecord.ActionCompleteCallback = (function (action) {
                groupRecord.StopEditing();
                _this.ForceTableRedraw(_this.m_tableGroups);
            });
            groupRecord.StartEditing(this.m_tableFilterLists.data());
        };
        Dashboard.prototype.OnDeleteGroupClicked = function (e) {
            var _this = this;
            var selectedItem = this.m_tableGroups.row('.selected').data();
            if (selectedItem != null) {
                var groupObject;
                try {
                    groupObject = Citadel.BaseRecord.CreateFromObject(Citadel.GroupRecord, selectedItem);
                    groupObject.ActionCompleteCallback = (function (action) {
                        _this.ForceTableRedraw(_this.m_tableUsers);
                        _this.ForceTableRedraw(_this.m_tableGroups);
                    });
                    if (confirm("Really delete group? THIS CANNOT BE UNDONE!!!")) {
                        groupObject.Delete();
                    }
                }
                catch (e) {
                    console.log('Failed to load group record from table selection.');
                }
            }
        };
        Dashboard.prototype.OnCloneGroupClicked = function (e) {
            var _this = this;
            var selectedItem = this.m_tableGroups.row('.selected').data();
            if (selectedItem != null) {
                var groupRecord_2 = new Citadel.GroupRecord();
                groupRecord_2.StartEditing(this.m_tableFilterLists.data(), null, selectedItem);
                groupRecord_2.ActionCompleteCallback = (function (action) {
                    groupRecord_2.StopEditing();
                    _this.ForceTableRedraw(_this.m_tableGroups);
                    _this.ForceTableRedraw(_this.m_tableUsers);
                });
            }
        };
        Dashboard.prototype.OnDeleteFilterListClicked = function (e) {
            var _this = this;
            var selectedItem = this.m_tableFilterLists.row('.selected').data();
            if (selectedItem != null) {
                var filterListObject;
                try {
                    filterListObject = Citadel.BaseRecord.CreateFromObject(Citadel.FilterListRecord, selectedItem);
                    filterListObject.ActionCompleteCallback = (function (action) {
                        _this.ForceTableRedraw(_this.m_tableUsers);
                        _this.ForceTableRedraw(_this.m_tableGroups);
                        _this.ForceTableRedraw(_this.m_tableFilterLists);
                    });
                    if (confirm("Really delete filter list? THIS CANNOT BE UNDONE!!!")) {
                        filterListObject.Delete();
                    }
                }
                catch (e) {
                    console.log('Failed to load filter list record from table selection.');
                }
            }
        };
        Dashboard.prototype.OnDeleteFilterListInNamespaceClicked = function (e, constraintToType) {
            var _this = this;
            var selectedItem = this.m_tableFilterLists.row('.selected').data();
            if (selectedItem != null) {
                var filterListObject;
                try {
                    filterListObject = Citadel.BaseRecord.CreateFromObject(Citadel.FilterListRecord, selectedItem);
                    filterListObject.ActionCompleteCallback = (function (action) {
                        _this.ForceTableRedraw(_this.m_tableUsers);
                        _this.ForceTableRedraw(_this.m_tableGroups);
                        _this.ForceTableRedraw(_this.m_tableFilterLists);
                    });
                    var confirmMsg = constraintToType == true ? "Really delete all filters with the same type in this lists' namespace? THIS CANNOT BE UNDONE!!!" : "Really delete all filters in this lists' namespace? THIS CANNOT BE UNDONE!!!";
                    if (confirm("Really delete all filters in this lists' namespace? THIS CANNOT BE UNDONE!!!")) {
                        filterListObject.DeleteAllInNamespace(constraintToType);
                    }
                }
                catch (e) {
                    console.log('Failed to load filter list record from table selection.');
                }
            }
        };
        Dashboard.prototype.OnDeleteUserDeactivationRequestClicked = function (e) {
            var _this = this;
            var selectedItem = this.m_tableUserDeactivationRequests.row('.selected').data();
            if (selectedItem != null) {
                var deactivationRequestObject;
                try {
                    deactivationRequestObject = Citadel.BaseRecord.CreateFromObject(Citadel.DeactivationRequestRecord, selectedItem);
                    deactivationRequestObject.ActionCompleteCallback = (function (action) {
                        _this.ForceTableRedraw(_this.m_tableUserDeactivationRequests);
                    });
                    if (confirm("Really delete user deactivation request? THIS CANNOT BE UNDONE!!!")) {
                        deactivationRequestObject.Delete();
                    }
                }
                catch (e) {
                    console.log('Failed to load filter list record from table selection.');
                }
            }
        };
        Dashboard.prototype.OnAddApplicationClicked = function (e) {
            var _this = this;
            if (this.m_btnApp.checked) {
                var newApp_1 = new Citadel.AppRecord();
                newApp_1.StartEditing();
                newApp_1.ActionCompleteCallback = (function (action) {
                    newApp_1.StopEditing();
                    _this.ForceTableRedraw(_this.m_tableAppLists);
                });
            }
            else {
                var newAppGroup_1 = new Citadel.AppGroupRecord();
                newAppGroup_1.StartEditing();
                newAppGroup_1.ActionCompleteCallback = (function (action) {
                    newAppGroup_1.StopEditing();
                    _this.ForceTableRedraw(_this.m_tableAppGroupLists);
                });
            }
        };
        Dashboard.prototype.onRemoveApplicationClicked = function (e) {
            var _this = this;
            this.m_btnRemoveItem.disabled = true;
            if (this.m_btnApp.checked) {
                var selectedItem = this.m_tableAppLists.row('.selected').data();
                if (selectedItem != null) {
                    var appObj;
                    try {
                        appObj = Citadel.BaseRecord.CreateFromObject(Citadel.AppRecord, selectedItem);
                        appObj.ActionCompleteCallback = (function (action) {
                            _this.ForceTableRedraw(_this.m_tableAppLists);
                        });
                        if (confirm("Really delete Application? THIS CANNOT BE UNDONE!!!")) {
                            appObj.Delete();
                        }
                    }
                    catch (e) {
                        this.m_btnRemoveItem.disabled = false;
                        console.log('Failed to load application record from table selection.');
                    }
                }
            }
            else {
                var selectedItem = this.m_tableAppGroupLists.row('.selected').data();
                if (selectedItem != null) {
                    var appGroupObj;
                    try {
                        appGroupObj = Citadel.BaseRecord.CreateFromObject(Citadel.AppGroupRecord, selectedItem);
                        appGroupObj.ActionCompleteCallback = (function (action) {
                            _this.ForceTableRedraw(_this.m_tableAppGroupLists);
                        });
                        if (confirm("Really delete Application? THIS CANNOT BE UNDONE!!!")) {
                            appGroupObj.Delete();
                        }
                    }
                    catch (e) {
                        this.m_btnRemoveItem.disabled = false;
                        console.log('Failed to load application record from table selection.');
                    }
                }
            }
        };
        Dashboard.prototype.onApplyToGroupClicked = function (e) {
            if (this.m_btnApp.checked) {
                var apply_app_to_app_group_overlay = new Citadel.ApplyAppToAppGroup(this);
                apply_app_to_app_group_overlay.Show();
            }
            else {
                var apply_app_group_to_user_group_overlay = new Citadel.ApplyAppgroupToUsergroup(this);
                apply_app_group_to_user_group_overlay.Show();
            }
        };
        Dashboard.prototype.onDeleteAppUserActivationClicked = function (e) {
            var _this = this;
            var selectedItem = this.m_tableAppUserActivationTable.row('.selected').data();
            if (selectedItem != null) {
                var appUserActivationObject;
                try {
                    appUserActivationObject = Citadel.BaseRecord.CreateFromObject(Citadel.AppUserActivationRecord, selectedItem);
                    appUserActivationObject.ActionCompleteCallback = (function (action) {
                        _this.ForceTableRedraw(_this.m_tableAppUserActivationTable);
                    });
                    if (confirm("Really delete app user activation? THIS CANNOT BE UNDONE!!!")) {
                        appUserActivationObject.Delete();
                    }
                }
                catch (e) {
                    console.log('Failed to load filter list record from table selection.');
                }
            }
        };
        Dashboard.prototype.onBlockAppUserActivationClicked = function (e) {
            var _this = this;
            var selectedItem = this.m_tableAppUserActivationTable.row('.selected').data();
            if (selectedItem != null) {
                var appUserActivationObject;
                try {
                    appUserActivationObject = Citadel.BaseRecord.CreateFromObject(Citadel.AppUserActivationRecord, selectedItem);
                    appUserActivationObject.ActionCompleteCallback = (function (action) {
                        _this.ForceTableRedraw(_this.m_tableAppUserActivationTable);
                    });
                    if (confirm("Really delete app user activation? THIS CANNOT BE UNDONE!!!")) {
                        appUserActivationObject.Block();
                    }
                }
                catch (e) {
                    console.log('Failed to load filter list record from table selection.');
                }
            }
        };
        Object.defineProperty(Dashboard.prototype, "ViewState", {
            get: function () {
                return this.m_currentViewState;
            },
            set: function (value) {
                this.m_viewUserManagement.style.visibility = "hidden";
                this.m_viewGroupManagement.style.visibility = "hidden";
                this.m_viewFilterManagement.style.visibility = "hidden";
                this.m_viewUserDeactivationRequestManagement.style.visibility = "hidden";
                this.m_viewAppManagement.style.visibility = "hidden";
                this.m_viewAppGroupManagement.style.visibility = "hidden";
                this.m_viewAppUserActivationManagement.style.visibility = "hidden";
                switch (value) {
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
                    case DashboardViewStates.AppView:
                        {
                            this.ForceTableRedraw(this.m_tableAppLists);
                            this.m_viewAppManagement.style.visibility = "visible";
                        }
                        break;
                    case DashboardViewStates.AppGroupView:
                        {
                            this.ForceTableRedraw(this.m_tableAppGroupLists);
                            this.m_viewAppGroupManagement.style.visibility = "visible";
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
            },
            enumerable: true,
            configurable: true
        });
        return Dashboard;
    }());
    Citadel.Dashboard = Dashboard;
    var citadelDashboard;
    document.onreadystatechange = function (event) {
        if (document.readyState.toUpperCase() == "complete".toUpperCase()) {
            $.ajaxPrefilter(function (options, originalOptions, xhr) {
                var token = $('meta[name="csrf-token"]').attr('content');
                if (token) {
                    return xhr.setRequestHeader('X-CSRF-TOKEN', token);
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
})(Citadel || (Citadel = {}));
//# sourceMappingURL=dashboard.js.map
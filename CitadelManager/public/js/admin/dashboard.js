var Citadel;
(function (Citadel) {
    var DashboardViewStates;
    (function (DashboardViewStates) {
        DashboardViewStates[DashboardViewStates["UserListView"] = 0] = "UserListView";
        DashboardViewStates[DashboardViewStates["GroupListView"] = 1] = "GroupListView";
        DashboardViewStates[DashboardViewStates["FilterListView"] = 2] = "FilterListView";
        DashboardViewStates[DashboardViewStates["DeactivationRequestListView"] = 3] = "DeactivationRequestListView";
        DashboardViewStates[DashboardViewStates["WhiteListView"] = 4] = "WhiteListView";
        DashboardViewStates[DashboardViewStates["BlackListView"] = 5] = "BlackListView";
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
            this.m_viewWhiteListManagement = document.getElementById('view_whitelist_management');
            this.m_viewBlackListManagement = document.getElementById('view_blacklist_management');
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
            var whiteListTableConstruction = (function () {
                var whiteListTableColumns = [
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
                var whiteListTablesLoadFromAjaxSettings = {
                    url: "api/admin/whitelists",
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
                var whiteListTableSettings = {
                    autoWidth: true,
                    stateSave: true,
                    columns: whiteListTableColumns,
                    ajax: whiteListTablesLoadFromAjaxSettings,
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    })
                };
                _this.m_tableWhiteLists = $('#whitelist_table').DataTable(whiteListTableSettings);
            });
            var blackListTableConstruction = (function () {
                var blackListTableColumns = [
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
                var blackListTablesLoadFromAjaxSettings = {
                    url: "api/admin/blacklists",
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
                var blackListTableSettings = {
                    autoWidth: true,
                    stateSave: true,
                    columns: blackListTableColumns,
                    ajax: blackListTablesLoadFromAjaxSettings,
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    })
                };
                _this.m_tableBlackLists = $('#blacklist_table').DataTable(blackListTableSettings);
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
            whiteListTableConstruction();
            blackListTableConstruction();
            appUserActivationTableConstruction();
        };
        Dashboard.prototype.ConstructNavigation = function () {
            this.m_btnSignOut = document.getElementById('btn_sign_out');
            this.m_tabBtnUsers = document.querySelector('a[href="#tab_users"]');
            this.m_tabBtnGroups = document.querySelector('a[href="#tab_groups"]');
            this.m_tabBtnFilterLists = document.querySelector('a[href="#tab_filter_lists"]');
            this.m_tabBtnUserRequest = document.querySelector('a[href="#tab_user_deactivation_requests"]');
            this.m_tabBtnWhiteBlackLists = document.querySelector('a[href="#tab_user_global_white_black_list"]');
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
            this.m_btnBlacklist = document.getElementById('global_radio_blacklist');
            this.m_btnWhitelist = document.getElementById('global_radio_whitelist');
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
            this.m_tabBtnWhiteBlackLists.onclick = (function (e) {
                if (_this.m_btnWhitelist.checked) {
                    _this.ViewState = DashboardViewStates.WhiteListView;
                }
                else {
                    _this.ViewState = DashboardViewStates.BlackListView;
                }
            });
            this.m_btnWhitelist.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.WhiteListView;
                var itemIsActuallySelected = $("#whitelist_table").children().next().find(".selected").length > 0 ? true : false;
                _this.m_btnRemoveItem.disabled = itemIsActuallySelected;
            });
            this.m_tabBtnAppUserActivation.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.AppUserActivationView;
            });
            this.m_btnBlacklist.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.BlackListView;
                var itemIsActuallySelected = $("#blacklist_table").children().next().find(".selected").length > 0 ? true : false;
                _this.m_btnRemoveItem.disabled = itemIsActuallySelected;
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
        };
        Dashboard.prototype.OnTableRowDoubleClicked = function (e, data) {
            var _this = this;
            e.stopImmediatePropagation();
            e.stopPropagation();
            console.log(data);
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
                case 'whitelist_table':
                    {
                        var whitelistRecord_1 = new Citadel.WhitelistRecord();
                        whitelistRecord_1.ActionCompleteCallback = (function (action) {
                            whitelistRecord_1.StopEditing();
                            _this.ForceTableRedraw(_this.m_tableWhiteLists);
                        });
                        whitelistRecord_1.StartEditing(data);
                    }
                    break;
                case 'blacklist_table':
                    {
                        var blacklistRecord_1 = new Citadel.BlacklistRecord();
                        blacklistRecord_1.ActionCompleteCallback = (function (action) {
                            blacklistRecord_1.StopEditing();
                            _this.ForceTableRedraw(_this.m_tableBlackLists);
                        });
                        blacklistRecord_1.StartEditing(data);
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
            console.log('OnCreateUserClicked');
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
            console.log('OnCreateGroupClicked');
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
            if (this.m_btnWhitelist.checked) {
                var newWhitelist_1 = new Citadel.WhitelistRecord();
                newWhitelist_1.StartEditing();
                newWhitelist_1.ActionCompleteCallback = (function (action) {
                    newWhitelist_1.StopEditing();
                    _this.ForceTableRedraw(_this.m_tableWhiteLists);
                });
            }
            else {
                var newBlacklist_1 = new Citadel.BlacklistRecord();
                newBlacklist_1.StartEditing();
                newBlacklist_1.ActionCompleteCallback = (function (action) {
                    newBlacklist_1.StopEditing();
                    _this.ForceTableRedraw(_this.m_tableBlackLists);
                });
            }
        };
        Dashboard.prototype.onRemoveApplicationClicked = function (e) {
            var _this = this;
            this.m_btnRemoveItem.disabled = true;
            if (this.m_btnWhitelist.checked) {
                var selectedItem = this.m_tableWhiteLists.row('.selected').data();
                if (selectedItem != null) {
                    var whiteListObj;
                    try {
                        whiteListObj = Citadel.BaseRecord.CreateFromObject(Citadel.WhitelistRecord, selectedItem);
                        whiteListObj.ActionCompleteCallback = (function (action) {
                            _this.ForceTableRedraw(_this.m_tableWhiteLists);
                        });
                        if (confirm("Really delete Whitelist Application? THIS CANNOT BE UNDONE!!!")) {
                            whiteListObj.Delete();
                        }
                    }
                    catch (e) {
                        this.m_btnRemoveItem.disabled = false;
                        console.log('Failed to load whitelist record from table selection.');
                    }
                }
            }
            else {
                var selectedItem = this.m_tableBlackLists.row('.selected').data();
                if (selectedItem != null) {
                    var blackListObj;
                    try {
                        blackListObj = Citadel.BaseRecord.CreateFromObject(Citadel.BlacklistRecord, selectedItem);
                        blackListObj.ActionCompleteCallback = (function (action) {
                            _this.ForceTableRedraw(_this.m_tableBlackLists);
                        });
                        if (confirm("Really delete Blacklist Application? THIS CANNOT BE UNDONE!!!")) {
                            blackListObj.Delete();
                        }
                    }
                    catch (e) {
                        this.m_btnRemoveItem.disabled = false;
                        console.log('Failed to load blacklist record from table selection.');
                    }
                }
            }
        };
        Dashboard.prototype.onApplyToGroupClicked = function (e) {
            var apply_overlay = new Citadel.ApplyToGroupOverlay();
            apply_overlay.Show();
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
                this.m_viewWhiteListManagement.style.visibility = "hidden";
                this.m_viewBlackListManagement.style.visibility = "hidden";
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
            },
            enumerable: true,
            configurable: true
        });
        return Dashboard;
    }());
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
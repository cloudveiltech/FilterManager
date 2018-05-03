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
        DashboardViewStates[DashboardViewStates["SystemVersionView"] = 7] = "SystemVersionView";
    })(DashboardViewStates || (DashboardViewStates = {}));
    var Dashboard = (function () {
        function Dashboard() {
            var _this = this;
            this.ConstructNavigation();
            this.loadAllFilters();
            this.loadAllGroups();
            this.ConstructManagementViews();
            this.m_filterListUploadController = new Citadel.ListUploadOverlay();
            this.m_filterListUploadController.UploadCompleteCallback = (function () {
                _this.ForceTableRedraw(_this.m_tableFilterLists);
                _this.ForceTableRedraw(_this.m_tableGroups);
                _this.m_filterListUploadController.Hide();
                _this.loadAllFilters();
            });
            this.m_filterListUploadController.UploadFailedCallback = (function () {
                _this.ForceTableRedraw(_this.m_tableFilterLists);
                _this.ForceTableRedraw(_this.m_tableGroups);
                _this.m_filterListUploadController.Hide();
            });
        }
        Dashboard.prototype.loadAllFilters = function () {
            var _this = this;
            var ajaxSettings = {
                method: "GET",
                timeout: 60000,
                url: "api/admin/filterlist/all",
                success: function (data, textStatus, jqXHR) {
                    _this.m_allFilters = data;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(errorThrown);
                }
            };
            $.post(ajaxSettings);
        };
        Dashboard.prototype.loadAllGroups = function () {
            var _this = this;
            var ajaxSettings = {
                method: "GET",
                timeout: 60000,
                url: "api/admin/group/all",
                success: function (data, textStatus, jqXHR) {
                    _this.m_allGroups = data;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(errorThrown);
                }
            };
            $.post(ajaxSettings);
        };
        Dashboard.prototype.ConstructManagementViews = function () {
            this.m_viewUserManagement = document.getElementById('view_user_management');
            this.m_viewGroupManagement = document.getElementById('view_group_management');
            this.m_viewFilterManagement = document.getElementById('view_filter_management');
            this.m_viewUserDeactivationRequestManagement = document.getElementById('view_user_deactivation_request_management');
            this.m_viewAppManagement = document.getElementById('view_app_management');
            this.m_viewAppGroupManagement = document.getElementById('view_app_group_management');
            this.m_viewAppUserActivationManagement = document.getElementById('view_app_user_activations_management');
            this.m_viewSystemVersionManagement = document.getElementById('view_system_versions_management');
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
            var height = $("body").height();
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
                        title: 'User Name',
                        data: 'name',
                        className: 'content-left',
                        visible: true,
                        width: '200px',
                        render: (function (data, t, row, meta) {
                            var name = data;
                            if (data.length > 15) {
                                name = data.substring(0, 11) + "...";
                            }
                            return "<span class='mif-user self-scale-group fg-green'></span>  <b title='" + data + "'>" + name + "</b>";
                        })
                    },
                    {
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
                        title: '#Lic.Used / #Licenses',
                        data: 'activations_allowed',
                        className: 'content-center',
                        visible: true,
                        render: (function (data, t, row, meta) {
                            return "<span class='license_used'>" + row.activations_used + "</span> / <span class='license_allowed'>" + data + "</span>";
                        }),
                        width: '250px'
                    },
                    {
                        title: 'Report Level',
                        data: 'report_level',
                        visible: true,
                        render: (function (data, t, row, meta) {
                            var chk_report = (data === 1) ? "checked" : "";
                            var str = "<label class='switch-original'><input type='checkbox' id='user_report_" + row.id + "' " + chk_report + " /><span class='check'></span></label>";
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
                        render: (function (data, t, row, meta) {
                            if (data == null) {
                                return "";
                            }
                            if (data == 1) {
                                return "<span class='active-s status'>Active</span>";
                            }
                            else {
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
                        className: 'updated_date'
                    }
                ];
                var userTablesLoadFromAjaxSettings = {
                    url: "api/admin/users",
                    dataSrc: function (json) {
                        return json.data;
                    },
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
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: (function (settings) {
                        var that = _this;
                        $("#user_table").off("change", "input[type='checkbox']");
                        $("#user_table").on("change", "input[type='checkbox']", function () {
                            var id_str = this.id;
                            var val = 0;
                            if (this.checked) {
                                val = 1;
                            }
                            var checkAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: "api/admin/users/update_field",
                                data: { id: id_str, value: val },
                                success: function (data, textStatus, jqXHR) {
                                    that.ForceTableRedraw(that.m_tableUsers);
                                    return false;
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    console.log(errorThrown);
                                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                                    }
                                    else {
                                    }
                                }
                            };
                            $.post(checkAjaxSettings);
                        });
                    })
                };
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
                        visible: true,
                        className: 'content-left',
                        render: (function (data, t, row, meta) {
                            return "<span class='mif-organization self-scale-group fg-green'></span> - <b title='" + row.user_count + " users are registered in this group.'>" + data + "</b> <span class='user_count'>(" + row.user_count + ")</span>";
                        })
                    },
                    {
                        title: 'Primary DNS',
                        data: 'primary_dns',
                        visible: true,
                        render: (function (data, t, row, meta) {
                            var str = "";
                            if (data === null || data === "") {
                            }
                            else {
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
                        render: (function (data, t, row, meta) {
                            var str = "";
                            if (data === null || data === "") {
                            }
                            else {
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
                        render: (function (data, t, row, meta) {
                            if (data === null || data === "") {
                                return "";
                            }
                            var app_cfg = JSON.parse(row.app_cfg);
                            var chk_terminate = app_cfg.CannotTerminate ? "checked" : "";
                            var chk_internet = app_cfg.BlockInternet ? "checked" : "";
                            var chk_threshold = app_cfg.UseThreshold ? "checked" : "";
                            var str = "<label class='switch-original'><input type='checkbox' id='group_terminate_" + row.id + "' " + chk_terminate + " /><span class='check'></span></label>";
                            str += "<label class='switch-original'><input type='checkbox' id='group_internet_" + row.id + "' " + chk_internet + " /><span class='check'></span></label>";
                            str += "<label class='switch-original'><input type='checkbox' id='group_threshold_" + row.id + "' " + chk_threshold + " /><span class='check'></span></label>";
                            return str;
                        }),
                        className: 'content-left',
                        width: '290px'
                    },
                    {
                        title: 'Bypass',
                        data: 'bypass',
                        visible: true,
                        render: (function (data, t, row, meta) {
                            if (data === null || data === "") {
                                return "";
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
                        render: (function (data, t, row, meta) {
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
                        render: (function (data, t, row, meta) {
                            if (data == null) {
                                return "";
                            }
                            if (data == 1) {
                                return "<span class='active-s status'>Active</span>";
                            }
                            else {
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
                        className: 'updated_date'
                    }
                ];
                var groupTablesLoadFromAjaxSettings = {
                    url: "api/admin/groups",
                    dataSrc: function (json) {
                        return json.data;
                    },
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
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: (function (settings) {
                        var that = _this;
                        $("#group_table").off("change", "input[type='checkbox']");
                        $("#group_table").on("change", "input[type='checkbox']", function () {
                            var id_str = this.id;
                            var val = 0;
                            if (this.checked) {
                                val = 1;
                            }
                            var checkAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: "api/admin/groups/update_field",
                                data: { id: id_str, value: val },
                                success: function (data, textStatus, jqXHR) {
                                    that.ForceTableRedraw(that.m_tableGroups);
                                    return false;
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    console.log(errorThrown);
                                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                                    }
                                    else {
                                    }
                                }
                            };
                            $.post(checkAjaxSettings);
                        });
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
                        visible: true,
                        render: (function (data, t, row, meta) {
                            if (data == null) {
                                return "";
                            }
                            if (row.type === "Filters") {
                                return "<span class='mif-filter fg-green'></span> " + data;
                            }
                            else {
                                return "<span class='mif-warning fg-red'></span> " + data;
                            }
                        })
                    },
                    {
                        title: 'List Group Name',
                        data: 'namespace',
                        visible: true,
                        render: (function (data, t, row, meta) {
                            if (data == null) {
                                return "";
                            }
                            return data;
                        })
                    },
                    {
                        title: 'Type',
                        data: 'type',
                        visible: true,
                        render: (function (data, t, row, meta) {
                            if (data == null) {
                                return "";
                            }
                            if (data === "Filters") {
                                return "<span class='mif-filter fg-green'></span> " + data;
                            }
                            else {
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
                        className: 'updated_date'
                    }
                ];
                var filterTablesLoadFromAjaxSettings = {
                    url: "api/admin/filterlists",
                    dataSrc: function (json) {
                        return json.data;
                    },
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
                        visible: true,
                        render: (function (data, t, row, meta) {
                            return "<span class='mif-user self-scale-group fg-green'></span>  <b title='" + data + "'>" + data + "</b>";
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
                        render: (function (data, t, row, meta) {
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
                var userDeactivationRequestTablesLoadFromAjaxSettings = {
                    url: "api/admin/deactivationreq",
                    dataSrc: function (json) {
                        return json.data;
                    },
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
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: (function (settings) {
                        var that = _this;
                        $("#user_deactivation_request_table").off("change", "input[type='checkbox']");
                        $("#user_deactivation_request_table").on("change", "input[type='checkbox']", function () {
                            var id_str = this.id;
                            var val = 0;
                            if (this.checked) {
                                val = 1;
                            }
                            var checkAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: "api/admin/deactivationreq/update_field",
                                data: { id: id_str, value: val },
                                success: function (data, textStatus, jqXHR) {
                                    that.ForceTableRedraw(that.m_tableUserDeactivationRequests);
                                    return false;
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    console.log(errorThrown);
                                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                                    }
                                    else {
                                    }
                                }
                            };
                            $.post(checkAjaxSettings);
                        });
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
                        width: '200px',
                        render: (function (data, t, row, meta) {
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
                var appListTablesLoadFromAjaxSettings = {
                    url: "api/admin/app",
                    dataSrc: function (json) {
                        return json.data;
                    },
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
                        width: '200px',
                        render: (function (data, t, row, meta) {
                            return "<span class='mif-file-folder self-scale-group fg-green'></span>  <b title='" + data + "'>" + data + "</b>";
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
                var appGroupListTablesLoadFromAjaxSettings = {
                    url: "api/admin/app_group",
                    dataSrc: function (json) {
                        return json.data;
                    },
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
                        visible: true,
                        render: (function (data, t, row, meta) {
                            var name = data;
                            if (data.length > 17) {
                                name = data.substring(0, 14) + "...";
                            }
                            return "<span class='mif-user self-scale-group fg-green'></span>  <b title='" + data + "'>" + name + "</b>";
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
                        render: (function (data, t, row, meta) {
                            var user_ip = "<span class='mif-flow-tree self-scale-3'></span>";
                            var name = data;
                            if (data === null || data === undefined) {
                                name = "-";
                                data = "*";
                                return "";
                            }
                            else {
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
                        render: (function (data, t, row, meta) {
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
                        render: (function (data, t, row, meta) {
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
                var appUserActivationTablesLoadFromAjaxSettings = {
                    url: "api/admin/activations",
                    dataSrc: function (json) {
                        return json.data;
                    },
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
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: (function (settings) {
                        var that = _this;
                        $("#app_user_activations_table").off("change", "input[type='checkbox']");
                        $("#app_user_activations_table").on("change", "input[type='checkbox']", function () {
                            var id_str = this.id;
                            var val = 0;
                            if (this.checked) {
                                val = 1;
                            }
                            var checkAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: "api/admin/activations/update_field",
                                data: { id: id_str, value: val },
                                success: function (data, textStatus, jqXHR) {
                                    that.ForceTableRedraw(that.m_tableAppUserActivationTable);
                                    return false;
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    console.log(errorThrown);
                                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                                    }
                                    else {
                                    }
                                }
                            };
                            $.post(checkAjaxSettings);
                        });
                    })
                };
                _this.m_tableAppUserActivationTable = $('#app_user_activations_table').DataTable(appUserActivationTableSettings);
                $('<button id="refresh_user_activations"><span class="mif-loop2 "></span> Refresh</button>').appendTo('#app_user_activations_table_wrapper div.dataTables_filter');
                $("#refresh_user_activations").click(function (e) {
                    _this.ForceTableRedraw(_this.m_tableAppUserActivationTable);
                });
            });
            var systemVersionTableConstruction = (function () {
                var systemVersionTableColumns = [
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
                        render: (function (data, t, row, meta) {
                            var name = row.os_name;
                            var span = "";
                            if (data === "WIN") {
                                span = "<span class='mif-windows os_win'></span>";
                            }
                            else if (data === "OSX") {
                                span = "<span class='mif-apple os_mac'></span>";
                            }
                            else if (data === "LINUX") {
                                span = "<span class='mif-linux os_linux'></span>";
                            }
                            else {
                                span = "<span class='mif-notification os_mac'></span>";
                            }
                            if (row.active === 1) {
                                return span + " &nbsp; <b>" + name + "</b>";
                            }
                            else {
                                return "<span class='inactive'>" + span + " &nbsp; <b>" + name + "</b></span>";
                            }
                        })
                    },
                    {
                        title: 'App Name',
                        data: 'app_name',
                        className: 'content-left',
                        visible: true,
                        width: '140px',
                        render: (function (data, t, row, meta) {
                            if (row.active === 1) {
                                return data;
                            }
                            else {
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
                        render: (function (data, t, row, meta) {
                            if (row.active === 1) {
                                return data;
                            }
                            else {
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
                        render: (function (data, t, row, meta) {
                            if (row.active === 1) {
                                return data;
                            }
                            else {
                                return "<span class='inactive'>" + data + "</span>";
                            }
                        })
                    },
                    {
                        title: 'Release Date',
                        data: 'release_date',
                        visible: true,
                        render: (function (data, t, row, meta) {
                            if (row.active === 1) {
                                return data;
                            }
                            else {
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
                        render: (function (data, t, row, meta) {
                            if (row.active === 1) {
                                return data;
                            }
                            else {
                                return "<span class='inactive'>" + data + "</span>";
                            }
                        })
                    },
                    {
                        title: 'Beta',
                        data: 'beta',
                        visible: true,
                        width: '100px',
                        className: 'content-center sub_version_number',
                        render: (function (data, t, row, meta) {
                            if (row.active === 1) {
                                return data;
                            }
                            else {
                                return "<span class='inactive'>" + data + "</span>";
                            }
                        })
                    },
                    {
                        title: 'Stable',
                        data: 'stable',
                        visible: true,
                        width: '100px',
                        className: 'content-center sub_version_number',
                        render: (function (data, t, row, meta) {
                            if (row.active === 1) {
                                return data;
                            }
                            else {
                                return "<span class='inactive'>" + data + "</span>";
                            }
                        })
                    },
                    {
                        title: 'Changes',
                        data: 'changes',
                        className: 'content-left',
                        visible: true,
                        render: (function (data, t, row, meta) {
                            if (row.active === 1) {
                                return data;
                            }
                            else {
                                return "<span class='inactive'>" + data + "</span>";
                            }
                        })
                    },
                    {
                        title: 'Action',
                        data: 'active',
                        visible: true,
                        render: (function (data, t, row, meta) {
                            if (data === 1) {
                                return "<label class='checked-alone'></label>";
                            }
                            else {
                                return "<label class='switch-original'><input type='checkbox' id='versions_" + row.id + "' /><span class='check'></span></label>";
                            }
                        }),
                        className: 'content-left padding-left-10',
                        width: '60px'
                    }
                ];
                var systemVersionTablesLoadFromAjaxSettings = {
                    url: "api/admin/versions",
                    dataSrc: function (json) {
                        return json.data;
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "GET",
                    error: (function (jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status > 399 && jqXHR.status < 500) {
                        }
                    })
                };
                var systemVersionsTableSettings = {
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
                    rowCallback: (function (row, data) {
                        _this.OnTableRowCreated(row, data);
                    }),
                    drawCallback: (function (settings) {
                        var that = _this;
                        $("#system_versions_table").off("change", "input[type='checkbox']");
                        $("#system_versions_table").on("change", "input[type='checkbox']", function () {
                            if (!confirm("Do you want to set this version as default version?")) {
                                this.checked = false;
                                return;
                            }
                            var id_str = this.id;
                            var checkAjaxSettings = {
                                method: "POST",
                                timeout: 60000,
                                url: "api/admin/versions/update_status",
                                data: { id: id_str },
                                success: function (data, textStatus, jqXHR) {
                                    that.ForceTableRedraw(that.m_tableSystemVersions);
                                    return false;
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    console.log(errorThrown);
                                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                                    }
                                    else {
                                    }
                                }
                            };
                            $.post(checkAjaxSettings);
                        });
                    })
                };
                _this.m_tableSystemVersions = $('#system_versions_table').DataTable(systemVersionsTableSettings);
            });
            userTableConstruction();
            groupTableConstruction();
            filterTableConstruction();
            deactivationRequestConstruction();
            appListTableConstruction();
            appGroupListTableConstruction();
            appUserActivationTableConstruction();
            systemVersionTableConstruction();
        };
        Dashboard.prototype.ConstructNavigation = function () {
            this.m_btnSignOut = document.getElementById('btn_sign_out');
            this.m_tabBtnUsers = document.querySelector('a[href="#tab_users"]');
            this.m_tabBtnGroups = document.querySelector('a[href="#tab_groups"]');
            this.m_tabBtnFilterLists = document.querySelector('a[href="#tab_filter_lists"]');
            this.m_tabBtnUserRequest = document.querySelector('a[href="#tab_user_deactivation_requests"]');
            this.m_tabBtnAppGroup = document.querySelector('a[href="#tab_app_groups"]');
            this.m_tabBtnAppUserActivation = document.querySelector('a[href="#tab_app_user_activations"]');
            this.m_tabBtnSystemVersion = document.querySelector('a[href="#tab_system_versions"]');
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
            this.m_btnCreateVersion = document.getElementById('btn_version_add');
            this.m_btnDeleteVersion = document.getElementById('btn_version_delete');
            this.m_btnDeleteVersion.disabled = true;
            this.m_btnSystemPlatform = document.getElementById('btn_sysem_platform');
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
            this.m_btnCreateVersion.onclick = (function (e) {
                _this.OnClickAddVersion(e);
            });
            this.m_btnDeleteVersion.onclick = (function (e) {
                _this.OnClickDeleteVersion(e);
            });
            this.m_btnSystemPlatform.onclick = (function (e) {
                _this.OnClickPlatform(e);
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
            this.m_tabBtnSystemVersion.onclick = (function (e) {
                _this.ViewState = DashboardViewStates.SystemVersionView;
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
                case 'system_versions_table':
                    {
                        this.m_btnDeleteVersion.disabled = !itemIsActuallySelected;
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
                        userRecord_1.StartEditing(this.m_allGroups, data);
                    }
                    break;
                case 'group_table':
                    {
                        var groupRecord_1 = new Citadel.GroupRecord();
                        groupRecord_1.StartEditing(this.m_allFilters, data);
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
                case 'system_versions_table':
                    {
                        var versionRecord_1 = new Citadel.VersionRecord();
                        versionRecord_1.ActionCompleteCallback = (function (action) {
                            versionRecord_1.StopEditing();
                            _this.ForceTableRedraw(_this.m_tableSystemVersions);
                        });
                        versionRecord_1.StartEditing(data);
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
        Dashboard.prototype.OnClickPlatform = function (e) {
            var platformOverlay = new Citadel.PlatformOverlay();
            platformOverlay.StartEditing();
        };
        Dashboard.prototype.OnClickAddVersion = function (e) {
            var _this = this;
            var appVersion = new Citadel.VersionRecord();
            appVersion.ActionCompleteCallback = (function (action) {
                appVersion.StopEditing();
                _this.ForceTableRedraw(_this.m_tableSystemVersions);
            });
            appVersion.StartEditing();
        };
        Dashboard.prototype.OnClickDeleteVersion = function (e) {
            var _this = this;
            var selectedItem = this.m_tableSystemVersions.row('.selected').data();
            if (selectedItem != null) {
                var versionObject;
                try {
                    versionObject = Citadel.BaseRecord.CreateFromObject(Citadel.VersionRecord, selectedItem);
                    versionObject.ActionCompleteCallback = (function (action) {
                        _this.ForceTableRedraw(_this.m_tableSystemVersions);
                    });
                    if (confirm("Really delete user? THIS CANNOT BE UNDONE!!!")) {
                        versionObject.Delete();
                    }
                }
                catch (e) {
                    console.log('Failed to load user record from table selection.');
                    console.log(e);
                }
            }
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
                        _this.loadAllFilters();
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
                        _this.loadAllFilters();
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
                this.m_viewUserManagement.style.display = "none";
                this.m_viewGroupManagement.style.display = "none";
                this.m_viewFilterManagement.style.display = "none";
                this.m_viewUserDeactivationRequestManagement.style.display = "none";
                this.m_viewAppManagement.style.display = "none";
                this.m_viewAppGroupManagement.style.display = "none";
                this.m_viewAppUserActivationManagement.style.display = "none";
                this.m_viewSystemVersionManagement.style.display = "none";
                switch (value) {
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
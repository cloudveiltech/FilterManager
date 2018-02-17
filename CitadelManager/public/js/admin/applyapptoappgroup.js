var Citadel;
(function (Citadel) {
    var ApplyAppToAppGroup = (function () {
        function ApplyAppToAppGroup(dashboard) {
            this.m_parentDashboard = dashboard;
            this.ConstructUIElements();
        }
        ApplyAppToAppGroup.prototype.ConstructUIElements = function () {
            var _this = this;
            var that = this;
            this.m_overlay = document.querySelector('#overlay_apply_app_to_app_group');
            this.m_appNameList = document.querySelector("#app_name_list");
            this.m_leftSelect = document.querySelector("#apply_app_to_app_group_source_list");
            this.m_rightSelect = document.querySelector("#apply_app_to_app_group_target_list");
            this.m_btnMoveRightAll = document.querySelector("#apply_app_to_app_group_right_all_btn");
            this.m_btnMoveRight = document.querySelector("#apply_app_to_app_group_right_btn");
            this.m_btnMoveLeft = document.querySelector("#apply_app_to_app_group_left_btn");
            this.m_btnMoveLeftAll = document.querySelector("#apply_app_to_app_group_left_all_btn");
            this.m_arrLeftGroupData = [];
            this.m_arrRightGroupData = [];
            this.m_selectedGroups = [];
            this.m_applyButton = document.querySelector("#apply_app_to_app_group_appy");
            this.m_closeButton = document.querySelector('#apply_app_to_app_group_close');
            this.m_applyButton.onclick = (function (e) {
                _this.onApplyButtonClicked(e);
            });
            this.m_closeButton.onclick = (function (e) {
                _this.Hide();
            });
            this.m_btnMoveRightAll.onclick = (function (e) {
                _this.onMoveRightAllClicked(e);
            });
            this.m_btnMoveRight.onclick = (function (e) {
                _this.onMoveRightClicked(e);
            });
            this.m_btnMoveLeft.onclick = (function (e) {
                _this.onMoveLeftClicked(e);
            });
            this.m_btnMoveLeftAll.onclick = (function (e) {
                _this.onMoveLeftAllClicked(e);
            });
            this.m_appNameList.onchange = (function (e) {
                _this.onAppNameChanged(e);
            });
        };
        ApplyAppToAppGroup.prototype.Reset = function () {
            this.m_progressWait = new Citadel.ProgressWait();
            this.m_progressWait.Show("Loading", "Please wait...");
            this.m_closeButton.disabled = false;
            this.getRetrieveData();
        };
        ApplyAppToAppGroup.prototype.getRetrieveData = function () {
            var _this = this;
            var ajaxSettings = {
                method: "GET",
                timeout: 60000,
                url: "api/admin/apply_app_to_appgroup/data",
                data: {},
                success: function (data, textStatus, jqXHR) {
                    _this.m_apps = data.apps;
                    $(_this.m_appNameList).empty();
                    var id = 0;
                    var p = 0;
                    _this.m_apps.forEach(function (item) {
                        p++;
                        var newOption = document.createElement("option");
                        newOption.text = item.name;
                        newOption.value = item.id;
                        _this.m_appNameList.add(newOption);
                        if (p == 1) {
                            id = item.id;
                        }
                    });
                    _this.m_appGroups = data.app_groups;
                    _this.loadSelectedGroups(id);
                    _this.m_progressWait.Hide();
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    _this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(function () {
                        _this.m_progressWait.Hide();
                    }, 5000);
                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                    }
                    else {
                    }
                }
            };
            $.get(ajaxSettings);
        };
        ApplyAppToAppGroup.prototype.loadSelectedGroups = function (id) {
            var _this = this;
            this.m_applyButton.disabled = true;
            $('#spiner_2').show();
            var ajaxSettings = {
                method: "GET",
                timeout: 60000,
                url: "api/admin/apply_app_to_appgroup/selected_group/" + id,
                data: {},
                success: function (data, textStatus, jqXHR) {
                    $('#spiner_2').hide();
                    _this.m_applyButton.disabled = false;
                    _this.m_selectedGroups = [];
                    _this.m_unselectedGroups = [];
                    data.forEach(function (group) {
                        _this.m_selectedGroups.push(group.app_group_id);
                    });
                    _this.m_appGroups.forEach(function (group) {
                        var idx = _this.m_selectedGroups.indexOf(group.id);
                        if (idx < 0) {
                            _this.m_unselectedGroups.push(group.id);
                        }
                    });
                    _this.drawRightGroups();
                    _this.drawLeftGroups();
                    _this.m_progressWait.Hide();
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    _this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(function () {
                        _this.m_progressWait.Hide();
                    }, 5000);
                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                    }
                    else {
                    }
                }
            };
            $.get(ajaxSettings);
        };
        ApplyAppToAppGroup.prototype.drawLeftGroups = function () {
            var _this = this;
            $(this.m_leftSelect).empty();
            this.m_unselectedGroups.forEach(function (group_id) {
                var newOption = document.createElement("option");
                var item = _this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;
                _this.m_leftSelect.add(newOption);
            });
        };
        ApplyAppToAppGroup.prototype.getGroupItem = function (group_id) {
            var group_item = null;
            this.m_appGroups.forEach(function (item) {
                if (item.id == group_id) {
                    group_item = item;
                    return;
                }
            });
            return group_item;
        };
        ApplyAppToAppGroup.prototype.drawRightGroups = function () {
            var _this = this;
            $(this.m_rightSelect).empty();
            this.m_selectedGroups.forEach(function (group_id) {
                var newOption = document.createElement("option");
                var item = _this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;
                _this.m_rightSelect.add(newOption);
            });
        };
        ApplyAppToAppGroup.prototype.onAppNameChanged = function (e) {
            var sel_id = parseInt(this.m_appNameList.selectedOptions[0].value);
            this.loadSelectedGroups(sel_id);
        };
        ApplyAppToAppGroup.prototype.onMoveRightAllClicked = function (e) {
            var _this = this;
            this.m_unselectedGroups.forEach(function (group_id) {
                _this.m_selectedGroups.push(group_id);
            });
            this.m_unselectedGroups = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        ApplyAppToAppGroup.prototype.onMoveLeftAllClicked = function (e) {
            var _this = this;
            this.m_selectedGroups.forEach(function (group_id) {
                _this.m_unselectedGroups.push(group_id);
            });
            this.m_selectedGroups = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        ApplyAppToAppGroup.prototype.onMoveRightClicked = function (e) {
            if (this.m_leftSelect.selectedIndex == -1)
                return;
            for (var i = 0; i < this.m_leftSelect.selectedOptions.length; i++) {
                var sel_id = parseInt(this.m_leftSelect.selectedOptions[i].value);
                var sel_seq_idx = this.m_unselectedGroups.indexOf(sel_id);
                this.m_unselectedGroups.splice(sel_seq_idx, 1);
                this.m_selectedGroups.push(sel_id);
            }
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        ApplyAppToAppGroup.prototype.onMoveLeftClicked = function (e) {
            if (this.m_rightSelect.selectedIndex == -1)
                return;
            for (var i = 0; i < this.m_rightSelect.selectedOptions.length; i++) {
                var sel_id = parseInt(this.m_rightSelect.selectedOptions[i].value);
                var sel_seq_idx = this.m_selectedGroups.indexOf(sel_id);
                this.m_selectedGroups.splice(sel_seq_idx, 1);
                this.m_unselectedGroups.push(sel_id);
            }
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        ApplyAppToAppGroup.prototype.onApplyButtonClicked = function (e) {
            var _this = this;
            $('#spiner_2').show();
            var url = "api/admin/apply_app_to_app_group";
            var sel_id = parseInt(this.m_appNameList.selectedOptions[0].value);
            var dataObject = {
                app_id: sel_id,
                group_ids: this.m_selectedGroups
            };
            var ajaxSettings = {
                method: "POST",
                timeout: 60000,
                url: url,
                data: dataObject,
                success: function (data, textStatus, jqXHR) {
                    $('#spiner_2').hide();
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    _this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(function () {
                        _this.m_progressWait.Hide();
                    }, 5000);
                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                    }
                    else {
                    }
                }
            };
            $.post(ajaxSettings);
        };
        ApplyAppToAppGroup.prototype.Show = function (fadeInTimeMsec) {
            if (fadeInTimeMsec === void 0) { fadeInTimeMsec = 200; }
            this.Reset();
            $(this.m_overlay).fadeIn(fadeInTimeMsec);
        };
        ApplyAppToAppGroup.prototype.Hide = function (fadeOutTimeMsec) {
            if (fadeOutTimeMsec === void 0) { fadeOutTimeMsec = 200; }
            this.m_parentDashboard.ForceTableRedraw(this.m_parentDashboard.m_tableAppLists);
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        };
        return ApplyAppToAppGroup;
    }());
    Citadel.ApplyAppToAppGroup = ApplyAppToAppGroup;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=applyapptoappgroup.js.map
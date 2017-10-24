var Citadel;
(function (Citadel) {
    var ApplyToGroupOverlay = (function () {
        function ApplyToGroupOverlay() {
            this.ConstructUIElements();
        }
        ApplyToGroupOverlay.prototype.ConstructUIElements = function () {
            var _this = this;
            var that = this;
            this.m_overlay = document.querySelector('#overlay_apply_togroup');
            this.m_btnBlacklist = document.querySelector("#apply_togroup_radio_blacklist");
            this.m_btnWhitelist = document.querySelector("#apply_togroup_radio_whitelist");
            this.m_leftSelect = document.querySelector("#apply_togroup_source_list");
            this.m_rightSelect = document.querySelector("#apply_togroup_target_list");
            this.m_btnMoveRightAll = document.querySelector("#apply_togroup_right_all_btn");
            this.m_btnMoveRight = document.querySelector("#apply_togroup_right_btn");
            this.m_btnMoveLeft = document.querySelector("#apply_togroup_left_btn");
            this.m_btnMoveLeftAll = document.querySelector("#apply_togroup_left_all_btn");
            this.m_arrLeftGroupData = [];
            this.m_arrRightGroupData = [];
            this.m_textList = document.querySelector('#apply_togroup_white_blacklist');
            this.m_applyButton = document.querySelector("#apply_togroup_appy");
            this.m_closeButton = document.querySelector('#apply_togroup_close');
            this.m_applyButton.onclick = (function (e) {
                _this.onApplyButtonClicked(e);
            });
            this.m_closeButton.onclick = (function (e) {
                _this.Hide();
            });
            this.m_btnBlacklist.onclick = (function (e) {
                that.onBlistClicked(e);
            });
            this.m_btnWhitelist.onclick = (function (e) {
                that.onWlistClicked(e);
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
            this.Reset();
        };
        ApplyToGroupOverlay.prototype.Reset = function () {
            this.m_progressWait = new Citadel.ProgressWait();
            this.m_progressWait.Show("Loading", "Please wait...");
            this.m_closeButton.disabled = false;
            this.getRetrieveData();
            this.getRetrieveGroupData();
        };
        ApplyToGroupOverlay.prototype.getRetrieveGroupData = function () {
            var _this = this;
            var ajaxSettings = {
                method: "GET",
                timeout: 60000,
                url: "api/admin/groups",
                data: {},
                success: function (data, textStatus, jqXHR) {
                    _this.m_arrLeftGroupData = data;
                    _this.drawLeftGroups();
                    _this.m_progressWait.Hide();
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR.responseText);
                    console.log(errorThrown);
                    console.log(textStatus);
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
        ApplyToGroupOverlay.prototype.drawLeftGroups = function () {
            var _this = this;
            $(this.m_leftSelect).empty();
            this.m_arrLeftGroupData.forEach(function (item) {
                var newOption = document.createElement("option");
                newOption.text = item.name;
                newOption.value = item.id;
                _this.m_leftSelect.add(newOption);
            });
        };
        ApplyToGroupOverlay.prototype.drawRightGroups = function () {
            var _this = this;
            $(this.m_rightSelect).empty();
            this.m_arrRightGroupData.forEach(function (item) {
                var newOption = document.createElement("option");
                newOption.text = item.name;
                newOption.value = item.id;
                _this.m_rightSelect.add(newOption);
            });
        };
        ApplyToGroupOverlay.prototype.getRetrieveData = function (blackFlag) {
            var _this = this;
            if (blackFlag === void 0) { blackFlag = true; }
            var url = "api/admin/whitelists";
            if (blackFlag) {
                url = "api/admin/blacklists";
            }
            var ajaxSettings = {
                method: "GET",
                timeout: 60000,
                url: url,
                data: {},
                success: function (data, textStatus, jqXHR) {
                    console.log(data);
                    _this.m_textList.value = "";
                    data.forEach(function (line) {
                        var text = line.name.trim();
                        if (text.length > 0) {
                            _this.m_textList.value += text + "\n";
                        }
                    });
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR.responseText);
                    console.log(errorThrown);
                    console.log(textStatus);
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
        ApplyToGroupOverlay.prototype.onMoveRightAllClicked = function (e) {
            var _this = this;
            this.m_arrLeftGroupData.forEach(function (item) {
                _this.m_arrRightGroupData.push(item);
            });
            this.m_arrLeftGroupData = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        ApplyToGroupOverlay.prototype.onMoveLeftAllClicked = function (e) {
            var _this = this;
            this.m_arrRightGroupData.forEach(function (item) {
                _this.m_arrLeftGroupData.push(item);
            });
            this.m_arrRightGroupData = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        ApplyToGroupOverlay.prototype.onMoveRightClicked = function (e) {
            var _this = this;
            if (this.m_leftSelect.selectedIndex == -1)
                return;
            var sel_opt = this.m_leftSelect.selectedOptions[0];
            var sel_id = sel_opt.value * 1;
            var idx = -1;
            var sel_seq_idx = 0;
            this.m_arrLeftGroupData.forEach(function (item) {
                idx++;
                if (item.id == sel_id * 1) {
                    _this.m_arrRightGroupData.push(item);
                    sel_seq_idx = idx;
                    return;
                }
            });
            console.log("sel_id", sel_seq_idx);
            if (sel_seq_idx > -1) {
                this.m_arrLeftGroupData.splice(sel_seq_idx, 1);
            }
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        ApplyToGroupOverlay.prototype.onMoveLeftClicked = function (e) {
            var _this = this;
            if (this.m_rightSelect.selectedIndex == -1)
                return;
            var sel_opt = this.m_rightSelect.selectedOptions[0];
            var sel_id = sel_opt.value * 1;
            var idx = -1;
            this.m_arrRightGroupData.forEach(function (item) {
                idx++;
                if (item.id == sel_id * 1) {
                    _this.m_arrLeftGroupData.push(item);
                    return;
                }
            });
            if (idx > -1) {
                this.m_arrRightGroupData.splice(idx, 1);
            }
            this.drawLeftGroups();
            this.drawRightGroups();
        };
        ApplyToGroupOverlay.prototype.onBlistClicked = function (e) {
            console.log("BlackListClicked");
            e.stopImmediatePropagation();
            e.stopPropagation();
            this.getRetrieveData(true);
        };
        ApplyToGroupOverlay.prototype.onWlistClicked = function (e) {
            console.log("WhiteListClicked");
            var dataObject = {};
            e.stopImmediatePropagation();
            e.stopPropagation();
            this.getRetrieveData(false);
        };
        ApplyToGroupOverlay.prototype.onApplyButtonClicked = function (e) {
            var _this = this;
            console.log("Apply Button clicked");
            if (this.m_arrRightGroupData.length > 0) {
                var url = "api/admin/applytogroup";
                var dataObject = {
                    type: "",
                    id_list: []
                };
                if (this.m_btnWhitelist.checked) {
                    dataObject.type = "whitelist";
                }
                else {
                    dataObject.type = "blacklist";
                }
                var arr_id_1 = [];
                this.m_arrRightGroupData.forEach(function (item) {
                    arr_id_1.push(item.id);
                });
                dataObject.id_list = arr_id_1;
                var ajaxSettings = {
                    method: "POST",
                    timeout: 60000,
                    url: url,
                    data: dataObject,
                    success: function (data, textStatus, jqXHR) {
                        alert("Changed Black/White list for selected groups.");
                        Citadel.Dashboard.ForceTableRedraw(Citadel.Dashboard.m_tableGroups);
                        console.log(data);
                        console.log(textStatus);
                        return false;
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.log(jqXHR.responseText);
                        console.log(errorThrown);
                        console.log(textStatus);
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
            }
            else {
                alert("Please select groups to apply with black/white list.");
            }
        };
        ApplyToGroupOverlay.prototype.Show = function (fadeInTimeMsec) {
            if (fadeInTimeMsec === void 0) { fadeInTimeMsec = 200; }
            this.Reset();
            $(this.m_overlay).fadeIn(fadeInTimeMsec);
        };
        ApplyToGroupOverlay.prototype.Hide = function (fadeOutTimeMsec) {
            if (fadeOutTimeMsec === void 0) { fadeOutTimeMsec = 200; }
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        };
        return ApplyToGroupOverlay;
    }());
    Citadel.ApplyToGroupOverlay = ApplyToGroupOverlay;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=applytogroupoverlay.js.map
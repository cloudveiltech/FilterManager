var Citadel;
(function (Citadel) {
    var PlatformOverlay = (function () {
        function PlatformOverlay() {
            $("#btn_platform_cancel").hide();
            this.InitUIComponents();
        }
        PlatformOverlay.prototype.InitUIComponents = function () {
            this.m_addBtn = document.querySelector('#btn_platform_add');
            this.m_cancelBtn = document.querySelector('#btn_platform_cancel');
            this.m_closeBtn = document.querySelector('#btn_platform_close');
            this.m_editorOverlay = document.querySelector('#overlay_platform');
            this.InitButtonHandlers();
            this.loadDatas();
        };
        PlatformOverlay.prototype.InitButtonHandlers = function () {
            var _this = this;
            this.m_closeBtn.onclick = (function (e) {
                _this.StopEditing();
            });
            this.m_cancelBtn.onclick = (function (e) {
                $("#platform_type").val("WIN");
                $("#platform_input_os_name").val("");
                $("#platform_id").val("0");
                $("#btn_platform_add").html("Add");
                $("#btn_platform_cancel").hide();
            });
            this.m_addBtn.onclick = (function (e) {
                var platform_id = $("#platform_id").val();
                if (platform_id === "0")
                    _this.AddRecord();
                else
                    _this.UpdateRecord(platform_id);
            });
        };
        PlatformOverlay.prototype.AddRecord = function () {
            var _this = this;
            var platform_type = $("#platform_type").val();
            var os_name = $("#platform_input_os_name").val();
            if (os_name === '') {
                alert("Please input OS Name.");
                return;
            }
            var data = {
                platform: platform_type,
                os_name: os_name
            };
            var ajaxSettings = {
                method: "POST",
                timeout: 60000,
                url: "api/admin/platform/create",
                data: data,
                success: function (rev_data, textStatus, jqXHR) {
                    $("#platform_type").val("WIN");
                    $("#platform_input_os_name").val("");
                    $("#platform_id").val("0");
                    $("#btn_platform_add").html("Add");
                    $("#btn_platform_cancel").hide();
                    _this.loadDatas();
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(textStatus);
                }
            };
            $.post(ajaxSettings);
        };
        PlatformOverlay.prototype.UpdateRecord = function (version_id) {
            var _this = this;
            var platform_type = $("#platform_type").val();
            var os_name = $("#platform_input_os_name").val();
            if (os_name === '') {
                alert("Please input OS Name.");
                return;
            }
            var data = {
                platform: platform_type,
                os_name: os_name
            };
            var ajaxSettings = {
                method: "POST",
                timeout: 60000,
                url: "api/admin/platform/update/" + version_id,
                data: data,
                success: function (rev_data, textStatus, jqXHR) {
                    $("#platform_type").val("WIN");
                    $("#platform_input_os_name").val("");
                    $("#platform_id").val("0");
                    $("#btn_platform_add").html("Add");
                    $("#btn_platform_cancel").hide();
                    _this.loadDatas();
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(textStatus);
                }
            };
            $.post(ajaxSettings);
        };
        PlatformOverlay.prototype.StartEditing = function () {
            $(this.m_editorOverlay).fadeIn(250);
        };
        PlatformOverlay.prototype.StopEditing = function () {
            $(this.m_editorOverlay).fadeOut(200);
        };
        PlatformOverlay.prototype.loadDatas = function () {
            var _this = this;
            var spin_name = '#spin_platforms';
            $(spin_name).show();
            var ajaxSettings = {
                method: "GET",
                timeout: 60000,
                url: "api/admin/platforms",
                data: {},
                success: function (data, textStatus, jqXHR) {
                    _this._update_platforms(data.platforms);
                    $(spin_name).hide();
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(textStatus);
                }
            };
            $.get(ajaxSettings);
        };
        PlatformOverlay.prototype._update_platforms = function (list) {
            var that = this;
            var div_name = '#platforms';
            var str = "";
            var p = 0;
            $(div_name).empty();
            for (var _i = 0, list_1 = list; _i < list_1.length; _i++) {
                var item = list_1[_i];
                p++;
                str = "<div class='platform-item'>";
                if (item.platform === "WIN") {
                    str += "<span class='mif-windows os_win'></span>";
                }
                else if (item.platform === "OSX") {
                    str += "<span class='mif-apple os_mac' style='color: white'></span>";
                }
                else if (item.platform === "LINUX") {
                    str += "<span class='mif-linux os_linux' style='color: white'></span>";
                }
                else {
                    str += "<span class='mif-notification os_mac'></span>";
                }
                str += "<span class='os_name'>" + item.os_name + "</span>";
                str += "<span class='item-actions'>";
                str += "<a data-id='" + item.id + "' type-id='" + item.platform + "' class='edit_action'><span class='mif-pencil mif-1x fg-cyan'></span></a>";
                str += "<a data-id='" + item.id + "' type-id='" + item.platform + "' class='delete_action'><span class='mif-cancel mif-1x fg-cyan'></span></a>";
                str += "</span>";
                str += "</div>";
                $(div_name).append(str);
            }
            if (p === 0) {
                str = "<div class='platform-item'>";
                str += "<span class='version italic-text no-versions'>No platforms</span>";
                str += "</div>";
                $(div_name).append(str);
            }
            $(div_name).off("click", ".edit_action");
            $(div_name).on("click", ".edit_action", function () {
                var platform_id = $(this).attr("data-id");
                var platform = $(this).attr("type-id");
                var os_name = $(this).parent().prev().html();
                $("#platform_type").val(platform);
                $("#platform_input_os_name").val(os_name);
                $("#platform_id").val(platform_id);
                $("#btn_platform_add").html("Update");
                $("#btn_platform_cancel").show();
            });
            $(div_name).off("click", ".delete_action");
            $(div_name).on("click", ".delete_action", function () {
                var platform_id = parseInt($(this).attr("data-id"));
                if (confirm("Do you want to delete this platform?")) {
                    var ajaxSettings = {
                        method: "POST",
                        timeout: 60000,
                        url: "api/admin/platform/delete",
                        data: { platform_id: platform_id },
                        success: function (data, textStatus, jqXHR) {
                            that.loadDatas();
                            return false;
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log(textStatus);
                        }
                    };
                    $.post(ajaxSettings);
                }
            });
        };
        return PlatformOverlay;
    }());
    Citadel.PlatformOverlay = PlatformOverlay;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=platform.js.map
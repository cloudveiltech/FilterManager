/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
///<reference path="../progresswait.ts"/>
namespace Citadel {
    /**
     *
     *
     * @class AppVersion
     */
    export class PlatformOverlay {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────

        MESSAGE_OS_REQUIRED                 = 'Please input OS name.';
        MESSAGE_EXT_REQUIRED                = 'If extension is empty, then you many lost extension data. will you continue to save?';

        URL_FETCH_PLATFORMS                 = 'api/admin/platforms';
        URL_CREATE_PLATFORM                 = 'api/admin/platform/create';
        URL_UPDATE_PLATFORM                 = 'api/admin/platform/update/';
        URL_DELETE_PLATFORM                 = 'api/admin/platform/delete';

        URL_FETCH_EXTENSIONS                = 'api/admin/extensions';
        URL_FETCH_SELECTED_EXTENSIONS       = 'api/admin/extensions/selected/';
        URL_UPDATE_EXTENSIONS               = 'api/admin/extensions/update';

        SPAN_WIN                            = '<span class=\'mif-windows os_win\'></span>';
        SPAN_OSX                            = '<span class=\'mif-apple os_mac\' style=\'color: white\'></span>';
        SPAN_LINUX                          = '<span class=\'mif-linux os_linux\' style=\'color: white\'></span>';
        SPAN_OTHER                          = '<span class=\'mif-notification os_mac\'></span>';

        // ───────────────────────────────────────────────────────────────────
        //   :::::: M A I N   M E N U   B U T T O N   E L E M E N T S ::::::
        // ───────────────────────────────────────────────────────────────────
        private m_btnClose              : HTMLButtonElement;
        private m_btnCancel             : HTMLButtonElement;
        private m_btnAdd                : HTMLButtonElement;
        private m_btnUpdateExtension    : HTMLButtonElement;
        private m_editorOverlay         : HTMLDivElement;
        public  m_platforms_ext         : HTMLSelectElement;

        constructor() {
            $("#btn_platform_cancel").hide();
            this.InitUIComponents();
        }

        private InitUIComponents(): void {
            this.m_btnAdd               = document.querySelector('#btn_platform_add') as HTMLButtonElement;
            this.m_btnCancel            = document.querySelector('#btn_platform_cancel') as HTMLButtonElement;
            this.m_btnClose             = document.querySelector('#btn_platform_close') as HTMLButtonElement;
            this.m_editorOverlay        = document.querySelector('#overlay_platform') as HTMLDivElement;
            this.m_btnUpdateExtension   = document.querySelector('#btn_extension_save') as HTMLButtonElement;
            this.m_platforms_ext        = document.querySelector('#ext_platform_type') as HTMLSelectElement;

            this.InitButtonHandlers();
            this._loadPlatforms();
            this._loadExtensions();
            this._loadSelectedExtension();
        }

        private InitButtonHandlers(): void {
            this.m_btnClose.onclick = ((e: MouseEvent): any => {
                this.StopEditing();
            });

            this.m_btnCancel.onclick = ((e: MouseEvent): any => {
                $("#platform_type").val("WIN");
                $("#platform_input_os_name").val("");
                $("#platform_id").val("0");
                $("#btn_platform_add").html("Add");
                $("#btn_platform_cancel").hide();
            });

            this.m_btnAdd.onclick = ((e: MouseEvent): any => {
                const platform_id = $("#platform_id").val();
                if (platform_id === "0")
                    this.AddRecord();
                else
                    this.UpdateRecord(platform_id);
            });

            this.m_btnUpdateExtension.onclick = ((e: MouseEvent): any => {
                this.UpdateExtension();
            });

            this.m_platforms_ext.onchange = ((e: MouseEvent): any => {
                this._loadSelectedExtension();
            });
        }
        private AddRecord(): void {
            const platform_type = $("#platform_type").val();
            const os_name = $("#platform_input_os_name").val();

            if (os_name === '') {
                alert(this.MESSAGE_OS_REQUIRED);
                return;
            }

            let data = {
                platform: platform_type,
                os_name: os_name
            };

            let ajaxSettings: JQueryAjaxSettings = {
                method: "POST",
                timeout: 60000,
                url: this.URL_CREATE_PLATFORM,
                data: data,
                success: (rev_data: any): any => {
                    $("#platform_type").val("WIN");
                    $("#platform_input_os_name").val("");
                    $("#platform_id").val("0");
                    $("#btn_platform_add").html("Add");
                    $("#btn_platform_cancel").hide();
                    this._loadPlatforms();
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }

            $.ajax(ajaxSettings);
        }

        private UpdateRecord(version_id): void {
            const platform_type = $("#platform_type").val();
            const os_name = $("#platform_input_os_name").val();

            if (os_name === '') {
                alert(this.MESSAGE_OS_REQUIRED);
                return;
            }
            let data = {
                platform: platform_type,
                os_name: os_name
            };
            let ajaxSettings: JQueryAjaxSettings = {
                method: "POST",
                timeout: 60000,
                url: this.URL_UPDATE_PLATFORM + version_id,
                data: data,
                success: (rev_data: any): any => {
                    $("#platform_type").val("WIN");
                    $("#platform_input_os_name").val("");
                    $("#platform_id").val("0");
                    $("#btn_platform_add").html("Add");
                    $("#btn_platform_cancel").hide();
                    this._loadPlatforms();

                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }
            $.ajax(ajaxSettings);
        }

        private UpdateExtension(): void {
            const platform_type = $("#ext_platform_type").val();
            const extensions = $("#platform_input_ext").val();

            if (extensions === '') {
                if(!confirm(this.MESSAGE_EXT_REQUIRED))
                {
                    return;
                }
            }
            let data = {
                platform: platform_type,
                extensions: extensions
            };
            let ajaxSettings: JQueryAjaxSettings = {
                method: "POST",
                timeout: 60000,
                url: this.URL_UPDATE_EXTENSIONS,
                data: data,
                success: (rev_data: any): any => {
                    this._loadExtensions();
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }
            $.ajax(ajaxSettings);
        }

        public StartEditing(): void {
            $(this.m_editorOverlay).fadeIn(250);
        }

        public StopEditing(): void {
            $(this.m_editorOverlay).fadeOut(200);
        }

        public _loadSelectedExtension(): void {
            let sel_platform = $('#ext_platform_type').val();
            const spin_name = '#spin_extensions';
            $(spin_name).show();
            let ajaxSettings: JQueryAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: this.URL_FETCH_SELECTED_EXTENSIONS + sel_platform ,
                data: {},
                success: (data: any): any => {
                    $(spin_name).hide();
                    $('#platform_input_ext').val(data.extension);
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }

            $.ajax(ajaxSettings);
        }

        private _loadExtensions(): void {
            const spin_name = '#spin_extensions';
            $(spin_name).show();
            let ajaxSettings: JQueryAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: this.URL_FETCH_EXTENSIONS,
                data: {},
                success: (data: any): any => {
                    this._update_extensions(data.extensions);
                    $(spin_name).hide();
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }

            $.ajax(ajaxSettings);
        }

        private _loadPlatforms(): void {
            const spin_name = '#spin_platforms';
            $(spin_name).show();
            let ajaxSettings: JQueryAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: this.URL_FETCH_PLATFORMS,
                data: {},
                success: (data: any): any => {
                    this._update_platforms(data.platforms);
                    $(spin_name).hide();
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }

            $.ajax(ajaxSettings);
        }

        private _update_platforms(list: any[]): void {
            let that = this;
            const div_name = '#platforms';

            let str = "";
            let p = 0;
            $(div_name).empty();
            for (let item of list) {
                p++;
                str = "<div class='platform-item'>";
                if (item.platform === "WIN") {
                    str += this.SPAN_WIN;
                } else if (item.platform === "OSX") {
                    str += this.SPAN_OSX;
                } else if (item.platform === "LINUX") {
                    str += this.SPAN_LINUX;
                } else {
                    str += this.SPAN_OTHER;
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
                    let ajaxSettings: JQueryAjaxSettings = {
                        method: "POST",
                        timeout: 60000,
                        url: that.URL_DELETE_PLATFORM,
                        data: {
                            platform_id: platform_id
                        },
                        success: (data: any): any => {
                            that._loadPlatforms();
                            return false;
                        },
                        error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                            console.log(textStatus);
                        }
                    }

                    $.ajax(ajaxSettings);
                }
            });
        }

        private _update_extensions(list: any[]): void {
            let that = this;
            const div_name = '#extensions';

            let str = '';
            let str_sys_name = '';
            let str_extensions = '';
            $(div_name).empty();
            for (let item of list) {
                str = "<div class='platform-item extension-item' data-os='" + item.platform + "'>";

                if (item.platform === "WIN") {
                    str += this.SPAN_WIN ;
                    str_sys_name = 'Windows';
                } else if (item.platform === "OSX") {
                    str += this.SPAN_OSX;
                    str_sys_name = 'MacOS';
                } else if (item.platform === "LINUX") {
                    str += this.SPAN_LINUX;
                    str_sys_name = 'Linux';
                } else {
                    str += this.SPAN_OTHER;
                    str_sys_name = 'Other';
                }
                str_extensions = item.extensions === '' ? '-':item.extensions;
                str += "<label class='platform_os_name'>" + str_sys_name + "</label>";
                str += "<label class='extensions'>" + str_extensions + "</label>";
                str += "</div>";

                $(div_name).append(str);
            }

            $(div_name).off("click", ".extension-item");
            $(div_name).on("click", ".extension-item", function () {
                var os = $(this).attr("data-os");
                $(that.m_platforms_ext).val(os);
                that._loadSelectedExtension();
            });
        }
    }
}
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
        /**
         * The div container that represents the entirety of our HTML UI.
         *
         * @private
         * @type {HTMLDivElement}
         * @memberOf ApplyToGroupOverlay
         */
        private m_closeBtn: HTMLButtonElement;
        private m_cancelBtn: HTMLButtonElement;
        private m_addBtn: HTMLButtonElement;
        private m_editorOverlay: HTMLDivElement;

        /**
         * Creates an instance of AppVersion.
         *
         *
         * @memberOf AppVersion
         */
        constructor() {
            $("#btn_platform_cancel").hide();
            this.InitUIComponents();
        }

        private InitUIComponents(): void {
            this.m_addBtn = document.querySelector('#btn_platform_add') as HTMLButtonElement;
            this.m_cancelBtn = document.querySelector('#btn_platform_cancel') as HTMLButtonElement;
            this.m_closeBtn = document.querySelector('#btn_platform_close') as HTMLButtonElement;
            this.m_editorOverlay = document.querySelector('#overlay_platform') as HTMLDivElement;
            this.InitButtonHandlers();
            this.loadDatas();
        }

        private InitButtonHandlers(): void {
            this.m_closeBtn.onclick = ((e: MouseEvent): any => {
                this.StopEditing();
            });

            this.m_cancelBtn.onclick = ((e: MouseEvent): any => {
                $("#platform_type").val("WIN");
                $("#platform_input_os_name").val("");
                $("#platform_id").val("0");
                $("#btn_platform_add").html("Add");
                $("#btn_platform_cancel").hide();
            });

            this.m_addBtn.onclick = ((e: MouseEvent): any => {
                const platform_id = $("#platform_id").val();
                if (platform_id === "0")
                    this.AddRecord();
                else
                    this.UpdateRecord(platform_id);
            });
        }
        private AddRecord(): void {
            const platform_type = $("#platform_type").val();
            const os_name = $("#platform_input_os_name").val();

            if (os_name === '') {
                alert("Please input OS Name.");
                return;
            }

            let data = {
                platform: platform_type,
                os_name: os_name
            };

            let ajaxSettings: JQuery.UrlAjaxSettings = {
                method: "POST",
                timeout: 60000,
                url: "api/admin/platform/create",
                data: data,
                success: (rev_data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    $("#platform_type").val("WIN");
                    $("#platform_input_os_name").val("");
                    $("#platform_id").val("0");
                    $("#btn_platform_add").html("Add");
                    $("#btn_platform_cancel").hide();
                    this.loadDatas();
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }

            $.post(ajaxSettings);
        }

        private UpdateRecord(version_id): void {
            const platform_type = $("#platform_type").val();
            const os_name = $("#platform_input_os_name").val();

            if (os_name === '') {
                alert("Please input OS Name.");
                return;
            }
            let data = {
                platform: platform_type,
                os_name: os_name
            };
            let ajaxSettings: JQuery.UrlAjaxSettings = {
                method: "POST",
                timeout: 60000,
                url: "api/admin/platform/update/" + version_id,
                data: data,
                success: (rev_data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    $("#platform_type").val("WIN");
                    $("#platform_input_os_name").val("");
                    $("#platform_id").val("0");
                    $("#btn_platform_add").html("Add");
                    $("#btn_platform_cancel").hide();
                    this.loadDatas();

                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }
            $.post(ajaxSettings);
        }

        public StartEditing(): void {
            $(this.m_editorOverlay).fadeIn(250);
        }

        public StopEditing(): void {
            $(this.m_editorOverlay).fadeOut(200);
        }

        private loadDatas(): void {
            const spin_name = '#spin_platforms';
            $(spin_name).show();
            let ajaxSettings: JQuery.UrlAjaxSettings = {
                method: "GET",
                timeout: 60000,
                url: "api/admin/platforms",
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    this._update_platforms(data.platforms);
                    $(spin_name).hide();
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(textStatus);
                }
            }

            $.get(ajaxSettings);
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
                    str += "<span class='mif-windows os_win'></span>";
                } else if (item.platform === "OSX") {
                    str += "<span class='mif-apple os_mac' style='color: white'></span>";
                } else if (item.platform === "LINUX") {
                    str += "<span class='mif-linux os_linux' style='color: white'></span>";
                } else {
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
                    let ajaxSettings: JQuery.UrlAjaxSettings = {
                        method: "POST",
                        timeout: 60000,
                        url: "api/admin/platform/delete",
                        data: {
                            platform_id: platform_id
                        },
                        success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                            that.loadDatas();
                            return false;
                        },
                        error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                            console.log(textStatus);
                        }
                    }

                    $.post(ajaxSettings);
                }
            });
        }
    }
}
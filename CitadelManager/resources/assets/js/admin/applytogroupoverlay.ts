/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../progresswait.ts"/>

namespace Citadel
{
    
    /**
     * 
     * 
     * @class ApplyToGroupOverlay
     */
    export class ApplyToGroupOverlay
    {

        /**
         * The div container that represents the entirety of our HTML UI. 
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf ApplyToGroupOverlay
         */
        private m_overlay: HTMLDivElement;

        private m_btnWhitelist: HTMLInputElement;
        private m_btnBlacklist: HTMLInputElement;
        private m_textList: HTMLTextAreaElement;

        /**
         * Unselected Groups/Selected Groups to apply with Black/Whitelist.
         * 
         * @private
         * @type {HTMLSelectElement}
         * @memberOf ApplyToGroupOverlay
         */
        private m_arrLeftGroupData: any[];
        private m_arrRightGroupData: any[];
        private m_leftSelect: HTMLSelectElement;
        private m_rightSelect: HTMLSelectElement;
        /**
         * The close button. Should be available at any time, and shut down the overlay.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf ApplyToGroupOverlay
         */
        private m_closeButton: HTMLButtonElement;
        private m_applyButton: HTMLButtonElement;
        private m_btnMoveRightAll: HTMLButtonElement;
        private m_btnMoveRight: HTMLButtonElement;
        private m_btnMoveLeft:HTMLButtonElement;
        private m_btnMoveLeftAll:HTMLButtonElement;
        /**
         * Used to display full page, overlay progress event information to the
         * user.
         * 
         * @private
         * @type {ProgressWait}
         * @memberOf Dashboard
         */
        private m_progressWait: ProgressWait;

        constructor() 
        {
            // Build button/input handlers and references.
            this.ConstructUIElements();
        }

        private ConstructUIElements(): void
        {
            let that = this;
            this.m_overlay = document.querySelector('#overlay_apply_togroup') as HTMLDivElement;
            this.m_btnBlacklist = document.querySelector("#apply_togroup_radio_blacklist") as HTMLInputElement;
            this.m_btnWhitelist = document.querySelector("#apply_togroup_radio_whitelist") as HTMLInputElement;
            this.m_leftSelect   = document.querySelector("#apply_togroup_source_list") as HTMLSelectElement;
            this.m_rightSelect  = document.querySelector("#apply_togroup_target_list") as HTMLSelectElement;

            this.m_btnMoveRightAll = document.querySelector("#apply_togroup_right_all_btn") as HTMLButtonElement;
            this.m_btnMoveRight    = document.querySelector("#apply_togroup_right_btn") as HTMLButtonElement;
            this.m_btnMoveLeft   = document.querySelector("#apply_togroup_left_btn") as HTMLButtonElement;
            this.m_btnMoveLeftAll= document.querySelector("#apply_togroup_left_all_btn") as HTMLButtonElement;

            this.m_arrLeftGroupData = [];
            this.m_arrRightGroupData = [];
            this.m_textList = document.querySelector('#apply_togroup_white_blacklist') as HTMLTextAreaElement;
            this.m_applyButton = document.querySelector("#apply_togroup_appy") as HTMLButtonElement;
            this.m_closeButton = document.querySelector('#apply_togroup_close') as HTMLButtonElement;           
            
            this.m_applyButton.onclick = ((e:MouseEvent) =>
            {
                this.onApplyButtonClicked(e);
            });

            this.m_closeButton.onclick = ((e:MouseEvent) =>
            {
                this.Hide();
            });

            this.m_btnBlacklist.onclick = ((e: MouseEvent) =>
            {
                that.onBlistClicked(e);
            });
            this.m_btnWhitelist.onclick = ((e: MouseEvent) =>
            {
                that.onWlistClicked(e);
            });

            this.m_btnMoveRightAll.onclick = ((e: MouseEvent) =>
            {
                this.onMoveRightAllClicked(e);        
            });

            this.m_btnMoveRight.onclick = ((e: MouseEvent) =>
            {
                this.onMoveRightClicked(e);        
            });

            this.m_btnMoveLeft.onclick = ((e: MouseEvent) =>
            {
                this.onMoveLeftClicked(e);        
            });

            this.m_btnMoveLeftAll.onclick = ((e: MouseEvent) =>
            {
                this.onMoveLeftAllClicked(e);        
            });
            this.Reset();
        }

        private Reset(): void
        {
            this.m_progressWait = new ProgressWait();

            // Ensure cancel button is always available.
            this.m_progressWait.Show("Loading", "Please wait...");
            this.m_closeButton.disabled = false;
            this.getRetrieveData();
            this.getRetrieveGroupData();
        }

         /**
         * Get Group data with AJAX
         * 
         * @private
         * @type {}
         * @memberOf ApplyToGroupOverlay
         */
        private getRetrieveGroupData() {
            let ajaxSettings: JQueryAjaxSettings =
            {
                method: "GET",
                timeout: 60000,
                url: "api/admin/groups",
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                {
                    this.m_arrLeftGroupData = data;
                    this.drawLeftGroups();

                    this.m_progressWait.Hide();
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                {

                    console.log(jqXHR.responseText);
                    console.log(errorThrown);
                    console.log(textStatus);

                    this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(() => 
                        {
                            this.m_progressWait.Hide();
                        }, 5000);

                    if (jqXHR.status > 399 && jqXHR.status < 500)
                    {
                        // Almost certainly auth related error. Redirect to login
                        // by signalling for logout.
                        //window.location.href = 'login.php?logout';
                    }
                    else
                    {
                        
                    }
                }
            }
            $.get(ajaxSettings);
        }

        private drawLeftGroups() {
            $(this.m_leftSelect).empty();
            this.m_arrLeftGroupData.forEach((item: any): void =>
            {
                var newOption = document.createElement("option");
                
                newOption.text = item.name;
                newOption.value = item.id;                
                this.m_leftSelect.add(newOption);
            });
        }

        private drawRightGroups() {
            $(this.m_rightSelect).empty();
            this.m_arrRightGroupData.forEach((item: any): void =>
            {
                var newOption = document.createElement("option");
                
                newOption.text = item.name;
                newOption.value = item.id;                
                this.m_rightSelect.add(newOption);
            });
        }
        /**
        * Get Black/White list data with AJAX
        * 
        * @private
        * @type {blackFlag} true: Blacklist, false: Whitelist
        * @memberOf ApplyToGroupOverlay
        */
        private getRetrieveData(blackFlag=true) {
            let url = "api/admin/whitelists"
            if (blackFlag) {
                url = "api/admin/blacklists";
            }

            let ajaxSettings: JQueryAjaxSettings =
            {
                method: "GET",
                timeout: 60000,
                url: url,
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                {
                    console.log(data);
                    this.m_textList.value = "";
                    // this.m_progressWait.Hide();
                     data.forEach((line: any): void =>
                     {
                         var text = line.name.trim();
                         if(text.length > 0)
                         {
                             this.m_textList.value += text + "\n";
                         }
                     });
                     return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                {

                    console.log(jqXHR.responseText);
                    console.log(errorThrown);
                    console.log(textStatus);

                    this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(() => 
                        {
                            this.m_progressWait.Hide();
                        }, 5000);

                    if (jqXHR.status > 399 && jqXHR.status < 500)
                    {
                        // Almost certainly auth related error. Redirect to login
                        // by signalling for logout.
                        //window.location.href = 'login.php?logout';
                    }
                    else
                    {
                        
                    }
                }
            }
            $.get(ajaxSettings);
        }

        public onMoveRightAllClicked(e: MouseEvent): void {
            this.m_arrLeftGroupData.forEach((item: any): void =>
            {
                this.m_arrRightGroupData.push(item);
            });
            this.m_arrLeftGroupData = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        }

        public onMoveLeftAllClicked(e: MouseEvent): void {
            this.m_arrRightGroupData.forEach((item: any): void =>
            {
                this.m_arrLeftGroupData.push(item);
            });
            this.m_arrRightGroupData = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        }

        public onMoveRightClicked(e: MouseEvent): void {
            if(this.m_leftSelect.selectedIndex == -1) return;
            let sel_opt = this.m_leftSelect.selectedOptions[0];
            let sel_id = sel_opt.value * 1;
            let idx = -1;
            let sel_seq_idx = 0;
            this.m_arrLeftGroupData.forEach((item: any): void =>
            {
                idx ++;
                if(item.id == sel_id * 1) {
                    this.m_arrRightGroupData.push(item);
                    sel_seq_idx = idx;
                    return;                    
                }
            });
            console.log("sel_id", sel_seq_idx);
            if(sel_seq_idx > -1) {
                this.m_arrLeftGroupData.splice(sel_seq_idx,1);
            }
            
            this.drawLeftGroups();
            this.drawRightGroups();
            
        }

        public onMoveLeftClicked(e: MouseEvent): void {
            if(this.m_rightSelect.selectedIndex == -1) return;
            let sel_opt = this.m_rightSelect.selectedOptions[0];
            let sel_id = sel_opt.value * 1;
            let idx = -1;
            this.m_arrRightGroupData.forEach((item: any): void =>
            {
                idx ++;
                if(item.id == sel_id * 1) {
                    this.m_arrLeftGroupData.push(item);
                    return;                    
                }
            });

            if(idx > -1) {
                this.m_arrRightGroupData.splice(idx,1);
            }
            
            this.drawLeftGroups();
            this.drawRightGroups();
            
        }
        /**
         * Shows the HTML UI. Takes the datatables data from the lists table and tries
         * to extract existing filter groups/namespaces to show them as suggestions
         * inside the input field.
         * 
         * @param {e} MouseEvent
         * @param {number} [fadeInTimeMsec=200]
         * 
         * @memberOf ApplyToGroupOverlay
         */
        public onBlistClicked(e: MouseEvent): void
        {
            console.log("BlackListClicked");
            // Stop the event so it doesn't go anywhere else. We're handling it here.
            e.stopImmediatePropagation();
            e.stopPropagation();
            this.getRetrieveData(true);
        }


        public onWlistClicked(e: MouseEvent): void
        {
            console.log("WhiteListClicked");
            let dataObject = {};
            // Stop the event so it doesn't go anywhere else. We're handling it here.
            e.stopImmediatePropagation();
            e.stopPropagation();
            this.getRetrieveData(false);
        }

        public onApplyButtonClicked(e: MouseEvent): void
        {
            console.log("Apply Button clicked");
            if(this.m_arrRightGroupData.length > 0) {
                let url = "api/admin/applytogroup"
                let dataObject = {
                    type: "",
                    id_list: []
                };
                if (this.m_btnWhitelist.checked) {
                    dataObject.type = "whitelist";
                } else 
                {
                    dataObject.type = "blacklist";
                }
                let arr_id = [];
                // Get id list from right list
                this.m_arrRightGroupData.forEach((item: any): void =>
                {
                    arr_id.push(item.id);
                });
                dataObject.id_list = arr_id;

                let ajaxSettings: JQueryAjaxSettings =
                {
                    method: "POST",
                    timeout: 60000,
                    url: url,
                    data: dataObject,
                    success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                    {
                         alert("Changed Black/White list for selected groups.");
                         Citadel.Dashboard.ForceTableRedraw(Citadel.Dashboard.m_tableGroups);
                        console.log(data);
                        console.log(textStatus);
                         return false;
                    },
                    error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                    {
    
                        console.log(jqXHR.responseText);
                        console.log(errorThrown);
                        console.log(textStatus);
    
                        this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                        setTimeout(() => 
                            {
                                this.m_progressWait.Hide();
                            }, 5000);
    
                        if (jqXHR.status > 399 && jqXHR.status < 500)
                        {
                            // Almost certainly auth related error. Redirect to login
                            // by signalling for logout.
                            //window.location.href = 'login.php?logout';
                        }
                        else
                        {
                            
                        }
                    }
                }
                $.post(ajaxSettings);
            } else {
                alert("Please select groups to apply with black/white list.");
            }
        }
        /**
         * Shows the HTML UI. Takes the datatables data from the lists table and tries
         * to extract existing filter groups/namespaces to show them as suggestions
         * inside the input field.
         * 
         * @param {DataTables.DataTable} allLists
         * @param {number} [fadeInTimeMsec=200]
         * 
         * @memberOf ListUploadOverlay
         */
        public Show(fadeInTimeMsec: number = 200): void
        {
            this.Reset();
            $(this.m_overlay).fadeIn(fadeInTimeMsec);
        }

        /**
         * Hides the HTML UI.
         * 
         * @param {number} [fadeOutTimeMsec=200]
         * 
         * @memberOf ListUploadOverlay
         */
        public Hide(fadeOutTimeMsec: number = 200): void
        {
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        }
    }
}
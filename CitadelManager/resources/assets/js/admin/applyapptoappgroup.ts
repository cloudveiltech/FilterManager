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
    export class ApplyAppToAppGroup
    {

        /**
         * The div container that represents the entirety of our HTML UI. 
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf ApplyToGroupOverlay
         */
        private m_parentDashboard: Citadel.Dashboard;
        private m_overlay: HTMLDivElement;
        private m_appNameList: HTMLSelectElement;
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

        private m_apps: any[];
        private m_appGroups: any[];
        private m_selectedGroups: any[];
        private m_unselectedGroups: any[];
        constructor(dashboard) 
        {
            this.m_parentDashboard = dashboard;
            // Build button/input handlers and references.
            this.ConstructUIElements();
        }

        private ConstructUIElements(): void
        {
            let that = this;
            this.m_overlay = document.querySelector('#overlay_apply_app_to_app_group') as HTMLDivElement;
            this.m_appNameList = document.querySelector("#app_name_list") as HTMLSelectElement;
            this.m_leftSelect   = document.querySelector("#apply_app_to_app_group_source_list") as HTMLSelectElement;
            this.m_rightSelect  = document.querySelector("#apply_app_to_app_group_target_list") as HTMLSelectElement;

            this.m_btnMoveRightAll = document.querySelector("#apply_app_to_app_group_right_all_btn") as HTMLButtonElement;
            this.m_btnMoveRight    = document.querySelector("#apply_app_to_app_group_right_btn") as HTMLButtonElement;
            this.m_btnMoveLeft   = document.querySelector("#apply_app_to_app_group_left_btn") as HTMLButtonElement;
            this.m_btnMoveLeftAll= document.querySelector("#apply_app_to_app_group_left_all_btn") as HTMLButtonElement;

            this.m_arrLeftGroupData = [];
            this.m_arrRightGroupData = [];
            this.m_selectedGroups = [];
            this.m_applyButton = document.querySelector("#apply_app_to_app_group_appy") as HTMLButtonElement;
            this.m_closeButton = document.querySelector('#apply_app_to_app_group_close') as HTMLButtonElement;           
            
            this.m_applyButton.onclick = ((e:MouseEvent) =>
            {
                this.onApplyButtonClicked(e);
            });

            this.m_closeButton.onclick = ((e:MouseEvent) =>
            {
                this.Hide();
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
            this.m_appNameList.onchange = ((e: MouseEvent) =>
            {
                this.onAppNameChanged(e);
            });
        }

        private Reset(): void
        {
            this.m_progressWait = new ProgressWait();

            // Ensure cancel button is always available.
            this.m_progressWait.Show("Loading", "Please wait...");
            this.m_closeButton.disabled = false;
            this.getRetrieveData();
        }

         /**
         * Get Group data with AJAX
         * 
         * @private
         * @type {}
         * @memberOf ApplyToGroupOverlay
         */
        private getRetrieveData() {
            let ajaxSettings: JQueryAjaxSettings =
            {
                method: "GET",
                timeout: 60000,
                url: "api/admin/apply_app_to_appgroup/data",
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                {
                    this.m_apps = data.apps;
                    $(this.m_appNameList).empty();
                    var id = 0;
                    var p = 0;
                    this.m_apps.forEach((item: any): void =>
                    {
                        p++;
                        var newOption = document.createElement("option");
                        newOption.text = item.name;
                        newOption.value = item.id;                
                        this.m_appNameList.add(newOption);
                        if (p == 1) {
                            id = item.id;
                        }
                    });
                    this.m_appGroups = data.app_groups;
                    this.loadSelectedGroups(id);
                    this.m_progressWait.Hide();
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                {

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
        private loadSelectedGroups(id: Number) {
            this.m_applyButton.disabled = true;
            $('#spiner_2').show();
            let ajaxSettings: JQueryAjaxSettings =
            {
                method: "GET",
                timeout: 60000,
                url: "api/admin/apply_app_to_appgroup/selected_group/" + id,
                data: {},
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                {
                    $('#spiner_2').hide();
                    this.m_applyButton.disabled = false;
                    this.m_selectedGroups = [];
                    this.m_unselectedGroups = [];
                    data.forEach((group: any): void =>
                    {
                        this.m_selectedGroups.push(group.app_group_id);
                    });
                    
                    this.m_appGroups.forEach((group: any): void =>
                    {
                        let idx = this.m_selectedGroups.indexOf(group.id);                                                
                        if(idx < 0) {                            
                            this.m_unselectedGroups.push(group.id);
                        }
                    });
                    this.drawRightGroups(); 
                    this.drawLeftGroups();                 
                    this.m_progressWait.Hide();
                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                {

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
            this.m_unselectedGroups.forEach((group_id): void =>
            {
                var newOption = document.createElement("option");
                var item = this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;                
                this.m_leftSelect.add(newOption);
            });
        }
        private getGroupItem(group_id) {
            var group_item = null;
            this.m_appGroups.forEach((item: any): void =>
            {
                if(item.id == group_id) {
                    group_item = item;
                    return;
                }
            });
            return group_item;
        }
        private drawRightGroups() {
            $(this.m_rightSelect).empty();
            this.m_selectedGroups.forEach((group_id): void =>
            {
                var newOption = document.createElement("option");
                var item = this.getGroupItem(group_id);
                newOption.text = item.group_name;
                newOption.value = item.id;                
                this.m_rightSelect.add(newOption);
            });
        }

        public onAppNameChanged(e) {            
            let sel_id = parseInt(this.m_appNameList.selectedOptions[0].value);
            this.loadSelectedGroups(sel_id);
        }
        /**
        * Get Black/White list data with AJAX
        * 
        * @private
        * @type {blackFlag} true: Blacklist, false: Whitelist
        * @memberOf ApplyToGroupOverlay
        */
       
        public onMoveRightAllClicked(e: MouseEvent): void {
            this.m_unselectedGroups.forEach((group_id): void =>
            {
                this.m_selectedGroups.push(group_id);
            });
            this.m_unselectedGroups = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        }

        public onMoveLeftAllClicked(e: MouseEvent): void {
            this.m_selectedGroups.forEach((group_id): void =>
            {
                this.m_unselectedGroups.push(group_id);
            });
            this.m_selectedGroups = [];
            this.drawLeftGroups();
            this.drawRightGroups();
        }

        public onMoveRightClicked(e: MouseEvent): void {
            if(this.m_leftSelect.selectedIndex == -1) return;
            for (var i = 0; i < this.m_leftSelect.selectedOptions.length; i++) {
                let sel_id = parseInt(this.m_leftSelect.selectedOptions[i].value);                
                let sel_seq_idx = this.m_unselectedGroups.indexOf(sel_id);
                this.m_unselectedGroups.splice(sel_seq_idx,1);
                this.m_selectedGroups.push(sel_id);
            }
            
            this.drawLeftGroups();
            this.drawRightGroups();
            
        }

        public onMoveLeftClicked(e: MouseEvent): void {
            if(this.m_rightSelect.selectedIndex == -1) return;
            for (var i = 0; i < this.m_rightSelect.selectedOptions.length; i++) {                
                let sel_id = parseInt(this.m_rightSelect.selectedOptions[i].value);
                let sel_seq_idx = this.m_selectedGroups.indexOf(sel_id);
                this.m_selectedGroups.splice(sel_seq_idx,1);
                this.m_unselectedGroups.push(sel_id);
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
        
        public onApplyButtonClicked(e: MouseEvent): void
        {
            $('#spiner_2').show();
            let url = "api/admin/apply_app_to_app_group"            
            let sel_id = parseInt(this.m_appNameList.selectedOptions[0].value);

            let dataObject = {
                app_id: sel_id,
                group_ids: this.m_selectedGroups
            };
            let ajaxSettings: JQueryAjaxSettings =
            {
                method: "POST",
                timeout: 60000,
                url: url,
                data: dataObject,
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                {                 
                    $('#spiner_2').hide();

                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                {   
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
            this.m_parentDashboard.ForceTableRedraw(this.m_parentDashboard.m_tableAppLists);
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        }
    }
}
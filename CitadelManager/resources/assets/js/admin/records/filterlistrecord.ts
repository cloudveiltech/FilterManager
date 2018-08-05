/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel {
    export class FilterListRecord extends BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        ERROR_MESSAGE_DELAY_TIME = 5000;
        FADE_IN_DELAY_TIME = 200;

        MESSAGE_ACTION_FAILED = 'Error reported by the server during action.\n Check console for more information.';
        MESSAGE_ACTION_DELETING = 'Deleting record from server.';

        TITLE_ACTION_FAILED = 'Action Failed';
        TITLE_ACTION_DELETING = 'Deleting Record';

        URL_ROUTE = 'api/admin/filterlists';

        // ──────────────────────────────────────────────────────────
        //   :::::: F I L T E R L I S T       M E M B E R S ::::::
        // ──────────────────────────────────────────────────────────
        private m_filterId : string;
        private m_filterCategoryName: string;
        private m_filterType: string;
        private m_filterListNamespace: string;
        private m_numRuleEntries: number;

        public get RecordRoute(): string {
            return this.URL_ROUTE;
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions {
            let opt: JQueryValidation.ValidationOptions = {

            };
            return opt;
        }

        constructor() {
            super();
        }

        protected LoadFromObject(value: Object): void {
            this.m_filterId             = value['id'];
            this.m_filterCategoryName   = value['category'];
            this.m_filterType           = value['type'];
            this.m_filterListNamespace  = value['namespace'];
            this.m_numRuleEntries       = value['num_entries'];
        }

        protected LoadFromForm(): void {
            // Do nothing. No editor for this.
        }

        public ToObject(): Object {
            let obj = {
                'id'            : this.m_filterId,
                'category'      : this.m_filterCategoryName,
                'type'          : this.m_filterType,
                'namespace'     : this.m_filterListNamespace,
                'num_entries'   : this.m_numRuleEntries
            };

            return obj;
        }

        public StopEditing(): void {

        }

        public DeleteAllInNamespace(constrainToType: boolean): void {

            this.m_progressWait.Show(this.TITLE_ACTION_DELETING, this.MESSAGE_ACTION_DELETING);

            let dataObject = this.ToObject();

            let ajaxSettings: JQuery.UrlAjaxSettings = {
                method: "DELETE",
                timeout: 60000,
                url: constrainToType == true ? this.RecordRoute + '/namespace/' + dataObject['namespace'] + '/' + dataObject['type'] : this.RecordRoute + '/namespace/' + dataObject['namespace'],
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    this.m_progressWait.Hide();

                    if (this.m_actionCompleteCallback != null) {
                        this.m_actionCompleteCallback("Deleted");
                    }

                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(jqXHR.responseText);

                    this.m_progressWait.Show(this.TITLE_ACTION_FAILED, this.MESSAGE_ACTION_FAILED);
                    setTimeout(() => {
                        this.m_progressWait.Hide();
                    }, this.ERROR_MESSAGE_DELAY_TIME);
                }
            }

            $.ajax(ajaxSettings);
        }
    }
}
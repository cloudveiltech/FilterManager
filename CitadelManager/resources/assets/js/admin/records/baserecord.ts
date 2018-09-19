/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel {

    interface ActionCompleteCallback {
        (action: string): void;
    }

    export abstract class BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        MESSAGE_SAVING              = 'Saving record to server';
        MESSAGE_DELETING            = 'Deleting record from server';
        MESSAGE_ACTION_FAILED       = 'Error reported by the server during action.\n %ERROR_MSG% \nCheck console for more information.';

        TITLE_SAVING                = 'Saving Record';
        TITLE_DELETING              = 'Saving Record';
        TITLE_ACTION_FAILED         = 'Action Failed';

        BTN_LABEL_ADD_APP           = 'Add';
        BTN_LABEL_EDIT_APP          = 'Save';

        ERROR_MESSAGE_DELAY_TIME    = 5000;
        FADE_IN_DELAY_TIME          = 200;

        URL_ROUTE                   = 'api/admin/app';
        URL_APPGROUP_DATA           = 'api/admin/get_appgroup_data'

        // ───────────────────────────────────
        //   :::::: C A L L B A C K S ::::::
        // ───────────────────────────────────
        protected m_actionCompleteCallback  : ActionCompleteCallback;
        protected m_progressWait            : ProgressWait;

        public get ActionCompleteCallback(): ActionCompleteCallback {
            return this.m_actionCompleteCallback;
        }

        public set ActionCompleteCallback(value: ActionCompleteCallback) {
            this.m_actionCompleteCallback = value;
        }
        // ────────────────────────────────────────────────────
        //   :::::: M E M B E R     F U N C T I O N S ::::::
        // ────────────────────────────────────────────────────
        public abstract get RecordRoute(): string;

        protected abstract get ValidationOptions(): JQueryValidation.ValidationOptions;

        constructor() {
            this.m_progressWait = new ProgressWait();
        }

        public static CreateFromObject < RType extends BaseRecord > (type: {
            new(): RType;
        }, data: Object): RType {
            let inst = new type();
            inst.LoadFromObject(data);
            return inst;
        }

        public static GetRecordRoute < RType extends BaseRecord > (type: {
            new(): RType;
        }): string {
            let inst = new type();
            return inst.RecordRoute;
        }

        protected abstract LoadFromObject(value: Object): void;
        protected abstract LoadFromForm(): void;
        public abstract ToObject(): Object;

        public abstract StopEditing(): void;

        protected OnFormSubmitClicked(e: Event, newlyCreated: boolean): any {
            if (!e.defaultPrevented) {
                e.stopImmediatePropagation();
                e.stopPropagation();

                this.Save(newlyCreated);
            }

            return false;
        }

        public Save(newlyCreated: boolean = false): void {
            this.LoadFromForm();

            let dataObject = this.ToObject();
            this.m_progressWait.Show(this.TITLE_SAVING, this.MESSAGE_SAVING);

            let ajaxSettings: JQueryAjaxSettings = {
                method: newlyCreated == true ? "POST" : "PATCH",
                timeout: 60000,
                url: newlyCreated == true ? this.RecordRoute : this.RecordRoute + '/' + dataObject['id'],
                data: dataObject,
                success: (data: any): any => {
                    this.m_progressWait.Hide();
                    if (this.m_actionCompleteCallback != null) {
                        this.m_actionCompleteCallback(newlyCreated == true ? "Created" : "Updated");
                    }

                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    this.m_progressWait.Show(this.TITLE_ACTION_FAILED, this.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));
                    setTimeout(() => {
                        this.m_progressWait.Hide();
                    }, 5000);
                }
            }

            $.ajax(ajaxSettings);
        }

        public Delete(): void {

            this.m_progressWait.Show(this.TITLE_DELETING, this.MESSAGE_DELETING);
            let dataObject = this.ToObject();
            let ajaxSettings: JQueryAjaxSettings = {
                method: "DELETE",
                timeout: 60000,
                url: this.RecordRoute + '/' + dataObject['id'],
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    this.m_progressWait.Hide();
                    if (this.m_actionCompleteCallback != null) {
                        this.m_actionCompleteCallback("Deleted");
                    }

                    return false;
                },

                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(jqXHR.responseText);
                    this.m_progressWait.Show(this.TITLE_ACTION_FAILED, this.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));
                    setTimeout(() => {
                        this.m_progressWait.Hide();
                    }, this.ERROR_MESSAGE_DELAY_TIME);
                }
            }

            $.ajax(ajaxSettings);
        }
    }
}
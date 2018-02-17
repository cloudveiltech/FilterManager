/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel
{

    /**
     * Definition for callbacks that record classes can invoked when various
     * actions have been completed. This is due to the fact that each record
     * class also assumes the responsibility to drive the UI for editing itself,
     * given the nature of these records.
     * 
     * @interface ActionCompleteCallback
     */
    interface ActionCompleteCallback
    {
        (action: string): void;
    }

    export abstract class BaseRecord
    {
        protected m_actionCompleteCallback: ActionCompleteCallback;

        protected m_progressWait: ProgressWait;

        public get ActionCompleteCallback(): ActionCompleteCallback
        {
            return this.m_actionCompleteCallback;
        }

        public set ActionCompleteCallback(value: ActionCompleteCallback)
        {
            this.m_actionCompleteCallback = value;
        }

        public abstract get RecordRoute(): string;


        protected abstract get ValidationOptions(): JQueryValidation.ValidationOptions;

        /**
         * Creates an instance of UserRecord.
         * 
         * 
         * @memberOf UserRecord
         */
        constructor() 
        {
            this.m_progressWait = new ProgressWait();
        }

        /**
         * Creates a new instance of the supplied record type and populates its
         * properties with the given object.
         * 
         * @static
         * @template RType
         * @param {{ new (): RType; }} type The type of BaseRecord to create.
         * @param {Object} data The data to populate the record with.
         * @returns {RType} A new instance of the requested record type with its
         * properties populated with the supplied object's data.
         * 
         * @memberOf BaseRecord
         */
        public static CreateFromObject<RType extends BaseRecord>(type: { new (): RType; }, data: Object): RType
        {
            let inst = new type();
            inst.LoadFromObject(data);
            return inst;
        }

        /**
         * 
         * 
         * @static
         * @template RType
         * @param {{ new (): RType; }} type
         * @returns {string}
         * 
         * @memberOf BaseRecord
         */
        public static GetRecordRoute<RType extends BaseRecord>(type: { new (): RType; }): string
        {
            let inst = new type();
            return inst.RecordRoute;
        }

        /**
         * Attempts to populate the instance with the property names and values
         * of the given object.
         * 
         * @protected
         * @abstract
         * @param {Object} value The object from which to populate our inner
         * properties.
         * 
         * @memberOf BaseRecord
         */
        protected abstract LoadFromObject(value: Object): void;

        /**
         * Populates the properties of the record from the current input values
         * of the record editor form.
         * 
         * @protected
         * @abstract
         * 
         * @memberOf BaseRecord
         */
        protected abstract LoadFromForm(): void;

        /**
         * Converts the object's data into a general object for serialization.
         * 
         * @abstract
         * @returns {Object} An object with property/value keypairs for serialization.
         * 
         * @memberOf BaseRecord
         */
        public abstract ToObject(): Object;

        /**
         * Initiates the process within the record of cleaning up and or hiding
         * its visual content editing controls.
         * 
         * @abstract
         * 
         * @memberOf BaseRecord
         */
        public abstract StopEditing(): void;

        /**
         * 
         * 
         * @protected
         * @param {Event} e
         * @param {boolean} newlyCreated
         * @returns {*}
         * 
         * @memberOf BaseRecord
         */
        protected OnFormSubmitClicked(e: Event, newlyCreated: boolean): any
        {
            // If default not prevented, then form validation
            // was a success.
            if (!e.defaultPrevented)
            {
                e.stopImmediatePropagation();
                e.stopPropagation();

                // Call save while indicating that we've created
                // something new, so as to have the correct call
                // params go to the server.
                this.Save(newlyCreated);
            }

            return false;
        }

        public Save(newlyCreated: boolean = false): void
        {
            // Before getting an object rep of the data, call for a 
            // load of current user input values.
            this.LoadFromForm();

            // Get this record's data as an object we can serialize.
            let dataObject = this.ToObject();
            console.log(dataObject);
            this.m_progressWait.Show('Saving Record', 'Saving record to server.');

            let ajaxSettings: JQueryAjaxSettings =
                {
                    // PHP script expects post.
                    method: newlyCreated == true ? "POST" : "PATCH",

                    // 60 seconds or quit.
                    timeout: 60000,

                    // Sent to setup.php.
                    url: newlyCreated == true ? this.RecordRoute : this.RecordRoute + '/' + dataObject['id'],

                    // Just submit form data.
                    data: dataObject,

                    // Callback if the call was a success.
                    success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                    {
                        this.m_progressWait.Hide();

                        if (this.m_actionCompleteCallback != null)
                        {
                            this.m_actionCompleteCallback(newlyCreated == true ? "Created" : "Updated");
                        }

                        return false;
                    },

                    // Callback if the call was a failure.
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

            // POST the auth request.
            $.post(ajaxSettings);
        }

        /**
         * Destroys this record in the database.
         * 
         * 
         * @memberOf BaseRecord
         */
        public Delete(): void
        {

            this.m_progressWait.Show('Deleting Record', 'Deleting record from server.');

            let dataObject = this.ToObject();

            let ajaxSettings: JQueryAjaxSettings =
                {
                    // PHP script expects post.
                    method: "DELETE",

                    // 60 seconds or quit.
                    timeout: 60000,

                    contents: { _token: $('meta[name="csrf-token"]').attr('content') },

                    // Sent to setup.php.
                    url: this.RecordRoute + '/' + dataObject['id'],

                    // Callback if the call was a success.
                    success: (data: any, textStatus: string, jqXHR: JQueryXHR): any =>
                    {
                        this.m_progressWait.Hide();

                        if (this.m_actionCompleteCallback != null)
                        {
                            this.m_actionCompleteCallback("Deleted");
                        }

                        return false;
                    },

                    // Callback if the call was a failure.
                    error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any =>
                    {
                        console.log(jqXHR.responseText);

                        this.m_progressWait.Show('Action Failed', 'Error reported by the server during action. Check console for more information.');

                        if (jqXHR.status > 399 && jqXHR.status < 500)
                        {
                            // Almost certainly auth related error. Redirect to login
                            // by signalling for logout.
                            //window.location.href = 'login.php?logout';
                        }
                        else
                        {
                            setTimeout(() => 
                            {
                                this.m_progressWait.Hide();
                            }, 5000);
                        }
                    }
                }

            // POST the auth request.
            $.ajax(ajaxSettings);
        }
    }
}
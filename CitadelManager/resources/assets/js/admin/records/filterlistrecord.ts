/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel
{
    export class FilterListRecord extends BaseRecord
    {

        /**
         * The unique ID of this filter list.
         * 
         * @private
         * @type {number}
         * @memberOf FilterListRecord
         */
        private m_filterId: number;

        /**
         * The category name that this filter list represents or belongs to.
         * 
         * @private
         * @type {string}
         * @memberOf FilterListRecord
         */
        private m_filterCategoryName: string;

        /**
         * The named type of this filter list.
         * 
         * @private
         * @type {string}
         * @memberOf FilterListRecord
         */
        private m_filterType: string;

        /**
         * The namespace of this filter list.
         * 
         * @private
         * @type {string}
         * @memberOf FilterListRecord
         */
        private m_filterListNamespace: string;

        /**
         * The total number of rules in this filter list.
         * 
         * @private
         * @type {number}
         * @memberOf FilterListRecord
         */
        private m_numRuleEntries: number;


        /**
         * Gets the base API route from this record type.
         * 
         * @readonly
         * @type {string}
         * @memberOf GroupRecord
         */
        public get RecordRoute(): string
        {
            return 'api/admin/filterlists';
        }

        protected get ValidationOptions(): JQueryValidation.ValidationOptions
        {
            let opt: JQueryValidation.ValidationOptions =
                {

                };
            return opt;
        }

        constructor()
        {
            super();
        }

        protected LoadFromObject(value: Object): void
        {
            this.m_filterId = value['id'];
            this.m_filterCategoryName = value['category'];
            this.m_filterType = value['type'];
            this.m_filterListNamespace = value['namespace'];
            this.m_numRuleEntries = value['num_entries'];
        }

        protected LoadFromForm(): void
        {
            // Do nothing. No editor for this.
        }

        public ToObject(): Object
        {
            let obj =
                {
                    'id': this.m_filterId,
                    'category': this.m_filterCategoryName,
                    'type': this.m_filterType,
                    'namespace': this.m_filterListNamespace,
                    'num_entries': this.m_numRuleEntries
                };

            return obj;
        }

        public StopEditing(): void
        {

        }

        /**
         * Requests the deletion of this filter list record, along with all other lists in the same namespace. It's also possible
         * to constrain this mass deletion to the same type as this list.
         * @param constrainToType Whether or not to constrain the deletion to those of the same type as well. For example, delete only triggers
         * in the same namespace, or filters, NLP, etc.
         */
        public DeleteAllInNamespace(constrainToType: boolean): void
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
                    url: constrainToType == true ? this.RecordRoute + '/namespace/' + dataObject['namespace'] + '/' + dataObject['type'] : this.RecordRoute + '/namespace/' + dataObject['namespace'] + '/',

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
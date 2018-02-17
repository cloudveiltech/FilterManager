/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../progresswait.ts"/>

// Disable dropzone autodiscover in the global scope.
Dropzone.autoDiscover = false;

namespace Citadel
{

    interface UploadComplete
    {
        (): void;
    }

    interface UploadFailed
    {
        (reason: string | Error): void;
    }

    /**
     * 
     * 
     * @class ListUploadOverlay
     */
    export class ListUploadOverlay
    {

        /**
         * Callback that is triggered on each successful upload.
         * 
         * @private
         * @type {UploadComplete}
         * @memberOf ListUploadOverlay
         */
        private m_uploadCompleteCallback: UploadComplete;

        public get UploadCompleteCallback(): UploadComplete
        {
            return this.m_uploadCompleteCallback;
        }

        public set UploadCompleteCallback(value: UploadComplete)
        {
            this.m_uploadCompleteCallback = value;
        }

        /**
         * Callback that is triggered on each failed upload.
         * 
         * @private
         * @type {UploadFailed}
         * @memberOf ListUploadOverlay
         */
        private m_uploadFailedCallback: UploadFailed;

        public get UploadFailedCallback(): UploadFailed
        {
            return this.m_uploadFailedCallback;
        }

        public set UploadFailedCallback(value: UploadFailed)
        {
            this.m_uploadFailedCallback = value;
        }

        /**
         * The div container that represents the entirety of our HTML UI. 
         * 
         * @private
         * @type {HTMLDivElement}
         * @memberOf ListUploadOverlay
         */
        private m_overlay: HTMLDivElement;

        /**
         * A readonly display to show the user the file they have selected for upload.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf ListUploadOverlay
         */
        private m_listSelectedFileNameInput: HTMLInputElement;

        /**
         * The input element where the user may define a namespace for the lists that are to be uploaded.
         * Namespaces permit multiple sets of the same list categories. They are also referred to as list
         * groups, but the function is precisely that of a namespace: to prevent name collisions.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf ListUploadOverlay
         */
        private m_listNamespaceInput: HTMLInputElement;

        /**
         * The checkbox toggle used to indicate whether or not this upload should overwrite any and all
         * categories that are of the same name within the same namespace, or if the contents of matching
         * categories should be merged and then prune to unique entries only.
         * 
         * @private
         * @type {HTMLInputElement}
         * @memberOf ListUploadOverlay
         */
        private m_overwriteOnUploadInput: HTMLInputElement;

        /**
         * The submit button. This should only be made enabled when a file is currently
         * in the dropzone queue.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf ListUploadOverlay
         */
        private m_submitButton: HTMLButtonElement;

        /**
         * The cancel button. Should be available at any time, and shut down the overlay.
         * 
         * @private
         * @type {HTMLButtonElement}
         * @memberOf ListUploadOverlay
         */
        private m_cancelButton: HTMLButtonElement;

        /**
         * Holds suggestions for the namespace input box. Dynamically
         * populated to hold existing namespaces.
         * 
         * @private
         * @type {HTMLDataListElement}
         * @memberOf ListUploadOverlay
         */
        private m_existingNamespaceDataList: HTMLDataListElement;

        /**
         * Used to display full page, overlay progress event information to the
         * user.
         * 
         * @private
         * @type {ProgressWait}
         * @memberOf Dashboard
         */
        private m_progressWait: ProgressWait;

        /**
         * Dropzone control.
         * 
         * @private
         * @type {Dropzone}
         * @memberOf Dashboard
         */
        private m_filterListDropzone: Dropzone;

        constructor() 
        {
            // Build dropzone for uploading filter lists.
            this.ConstructDropzone();

            // Build button/input handlers and references.
            this.ConstructUIElements();
        }

        private ConstructDropzone(): void
        {
            // Ensure progresswait exists for dropzone.
            this.m_progressWait = new ProgressWait();

            // Build the filter list upload dropzone.
            let dropzoneOptions: DropzoneOptions =
                {
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    clickable: '#upload_list_select_file',
                    acceptedFiles: '.tar.gz,.model,.zip',

                    // Don't just start going as soon as a file is selected.
                    autoProcessQueue: false,
                    maxFiles: 1,
                    parallelUploads: 1,
                    url: 'api/admin/filterlists/upload',
                    complete: ((file: DropzoneFile): void =>
                    {
                        this.m_filterListDropzone.removeAllFiles();

                        if (file.accepted == true)
                        {
                            this.m_progressWait.Show("Upload Complete", "File upload complete.");
                            setTimeout(() =>
                            {
                                this.m_progressWait.Hide();
                                
                                if(this.m_uploadCompleteCallback != null)
                                {
                                    this.m_uploadCompleteCallback();
                                }

                            }, 4000);
                        }
                    }),
                    error: ((file: DropzoneFile, message: string | Error, xhr: XMLHttpRequest): void =>
                    {
                        // Set the file accepted to false so that we can track this in other callbacks
                        // and know that it is a failed upload.
                        file.accepted = false;
                        
                        this.m_progressWait.Show("Upload Failed", xhr.responseText);

                        setTimeout(() =>
                        {
                            this.m_progressWait.Hide();
                            
                            if(this.m_uploadFailedCallback != null)
                            {
                                this.m_uploadFailedCallback(message);
                            }

                        }, 4000);

                        this.m_filterListDropzone.removeAllFiles();
                    }),
                    maxfilesexceeded: ((file: DropzoneFile): void =>
                    {
                        this.m_filterListDropzone.removeAllFiles();
                        this.m_filterListDropzone.addFile(file);

                        this.m_listSelectedFileNameInput.value = file.name;

                        this.m_submitButton.disabled = false;
                    }),
                    addedfile: ((file:DropzoneFile) : void => 
                    {
                        this.m_listSelectedFileNameInput.value = file.name;

                        this.m_submitButton.disabled = false;
                    }),
                    uploadprogress: ((file: DropzoneFile, progress: number, bytesSent: number): any =>
                    {
                        // We have no control over the flow of events from dropzone. As such, we set the 
                        // accepted property on files to false whenever a failure happens, so that when
                        // another function such as progress is called, we can tell if the file behind
                        // the event is a failed upload, and act accordingly.
                        if (file.accepted)
                        {
                            this.m_progressWait.Show("Uploading File", 'Uploading file. ' + progress.toFixed(2) + '%. File is being processed. Do not refresh your browser.');
                        }
                    }),
                    sending: ((file:DropzoneFile, xhr:XMLHttpRequest, formData:FormData): void =>
                    {
                        // This is where you're going to send all the extra post data that you want
                        // along with an upload.

                        if(this.m_listNamespaceInput.value == null || this.m_listNamespaceInput.value.length <= 0)
                        {
                            this.m_listNamespaceInput.value = "Default";
                        }

                        formData.append('overwrite', this.m_overwriteOnUploadInput.checked ? '1' : '0');
                        formData.append('namespace', this.m_listNamespaceInput.value);
                    })
                };

            this.m_filterListDropzone = new Dropzone('#filter_list_dropzone', dropzoneOptions);
        }

        private ConstructUIElements(): void
        {
            this.m_overlay = document.querySelector('#overlay_list_upload') as HTMLDivElement;

            this.m_listSelectedFileNameInput = document.querySelector('#upload_list_selected_file_name') as HTMLInputElement;

            this.m_listNamespaceInput = document.querySelector('#upload_list_namespace') as HTMLInputElement;

            this.m_overwriteOnUploadInput = document.querySelector('#upload_list_overwrite') as HTMLInputElement;

            this.m_submitButton = document.querySelector('#upload_list_submit') as HTMLButtonElement;

            this.m_cancelButton = document.querySelector('#upload_list_cancel') as HTMLButtonElement;           

            this.m_existingNamespaceDataList = document.querySelector('#existing_list_namespaces') as HTMLDataListElement;            

            this.m_submitButton.onclick = ((e:MouseEvent) =>
            {
                // Let dropzone do its thing.
                this.m_filterListDropzone.processQueue();
            });

            this.m_cancelButton.onclick = ((e:MouseEvent) =>
            {
                this.Hide();
            });

            this.Reset();
        }

        private Reset(): void
        {
            // By default, disable submit button.
            this.m_submitButton.disabled = true;

            // Ensure cancel button is always available.
            this.m_cancelButton.disabled = false;

            // Clear out dropzone.
            this.m_filterListDropzone.removeAllFiles();

            // Clear out existing namespace suggestions.
            while (this.m_existingNamespaceDataList.firstChild) 
            {
                this.m_existingNamespaceDataList.removeChild(this.m_existingNamespaceDataList.firstChild);
            }

            // Form defaults.
            this.m_listNamespaceInput.value = "Default";
            this.m_listSelectedFileNameInput.value = "";
            this.m_overwriteOnUploadInput.checked = false;
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
        public Show(allLists: DataTables.DataTable, fadeInTimeMsec: number = 200): void
        {
            this.Reset();

            // Try to build out suggestions based on existing filter namespaces, if any.
            if(allLists != null && allLists.length > 0)
            {
                let uniqueNamespaces = {};
                let distinct = [];

                allLists.each((elm: any) : void =>
                {
                    if( typeof(uniqueNamespaces[elm['namespace']]) == "undefined"){
                        distinct.push(elm['namespace']);
                    }

                    uniqueNamespaces[elm['namespace']] = 0;
                });

                distinct.forEach((value: any) : void =>
                {   
                    let existingNamespaceOption = document.createElement('option') as HTMLOptionElement;
                    existingNamespaceOption.value = value;
                    this.m_existingNamespaceDataList.appendChild(existingNamespaceOption);
                });

            }

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
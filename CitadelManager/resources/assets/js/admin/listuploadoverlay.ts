/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../progresswait.ts"/>

// Disable dropzone autodiscover in the global scope.
Dropzone.autoDiscover = false;

namespace Citadel {

    interface UploadComplete {
        (): void;
    }

    interface UploadFailed {
        (reason: string | Error): void;
    }

    /**
     *
     *
     * @class ListUploadOverlay
     */
    export class ListUploadOverlay {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        ERROR_MESSAGE_DELAY_TIME        = 5000;
        FADE_IN_DELAY_TIME              = 200;

        URL_FILTER_UPLOAD               = 'api/admin/filterlists/upload';

        TITLE_COMPLETE                  = 'Upload Complete';
        TITLE_FAILED                    = 'Upload Failed';
        TITLE_UPLOADING                 = 'Uploading File';
        MESSAGE_COMPLETE                = 'File upload complete.';
        MESSAGE_UPLOADING_PREFIX        = 'Uploading file.';
        MESSAGE_UPLOADING_SUFFIX        = '%. File is being processed. Do not refresh your browser.';

        // ───────────────────────────────────────────────────
        //   :::::: C A L L B A C K S  ::::::
        // ───────────────────────────────────────────────────
        private m_uploadCompleteCallback: UploadComplete;

        public get UploadCompleteCallback(): UploadComplete {
            return this.m_uploadCompleteCallback;
        }

        public set UploadCompleteCallback(value: UploadComplete) {
            this.m_uploadCompleteCallback = value;
        }

        private m_uploadFailedCallback: UploadFailed;

        public get UploadFailedCallback(): UploadFailed {
            return this.m_uploadFailedCallback;
        }

        public set UploadFailedCallback(value: UploadFailed) {
            this.m_uploadFailedCallback = value;
        }

        // ───────────────────────────────────────────────
        //   :::::: H T M L      E L E M E N T S  ::::::
        // ───────────────────────────────────────────────
        private m_overlay               : HTMLDivElement;
        private m_inputSelectedFile     : HTMLInputElement;
        private m_inputNamespace        : HTMLInputElement;
        private m_inputOverWrite        : HTMLInputElement;

        private m_btnSubmit             : HTMLButtonElement;
        private m_btnCancel             : HTMLButtonElement;

        private m_listExistNamespace    : HTMLDataListElement;

        private m_progressWait          : ProgressWait;
        private m_filterListDropzone    : Dropzone;

        constructor() {
            this.ConstructDropzone();
            this.ConstructUIElements();
        }

        private ConstructDropzone(): void {
            this.m_progressWait = new ProgressWait();

            let dropzoneOptions: Dropzone.DropzoneOptions = {
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                clickable: '#upload_list_select_file',
                acceptedFiles: '.tar.gz,.model,.zip',
                autoProcessQueue: false,
                maxFiles: 1,
                parallelUploads: 1,
                url: this.URL_FILTER_UPLOAD,
                complete: ((file: Dropzone.DropzoneFile): void => {
                    this.m_filterListDropzone.removeAllFiles();

                    if (file.accepted == true) {
                        this.m_progressWait.Show(this.TITLE_COMPLETE, this.MESSAGE_COMPLETE);

                        setTimeout(() => {
                            this.m_progressWait.Hide();
                            if (this.m_uploadCompleteCallback != null) {
                                this.m_uploadCompleteCallback();
                            }
                        }, this.ERROR_MESSAGE_DELAY_TIME);
                    }
                }),
                error: ((file: Dropzone.DropzoneFile, message: string | Error, xhr: XMLHttpRequest): void => {

                    file.accepted = false;
                    this.m_progressWait.Show(this.TITLE_FAILED, xhr.responseText);

                    setTimeout(() => {
                        this.m_progressWait.Hide();

                        if (this.m_uploadFailedCallback != null) {
                            this.m_uploadFailedCallback(message);
                        }
                    }, this.ERROR_MESSAGE_DELAY_TIME);

                    this.m_filterListDropzone.removeAllFiles();
                }),
                maxfilesexceeded: ((file: Dropzone.DropzoneFile): void => {
                    this.m_filterListDropzone.removeAllFiles();
                    this.m_filterListDropzone.addFile(file);
                    this.m_inputSelectedFile.value = file.name;
                    this.m_btnSubmit.disabled = false;
                }),
                addedfile: ((file: Dropzone.DropzoneFile): void => {
                    this.m_inputSelectedFile.value = file.name;
                    this.m_btnSubmit.disabled = false;
                }),
                uploadprogress: ((file: Dropzone.DropzoneFile, progress: number, bytesSent: number): any => {
                    if (file.accepted) {
                        this.m_progressWait.Show(this.TITLE_UPLOADING, this.MESSAGE_UPLOADING_PREFIX + progress.toFixed(2) + this.MESSAGE_UPLOADING_SUFFIX);
                    }
                }),
                sending: ((file: Dropzone.DropzoneFile, xhr: XMLHttpRequest, formData: FormData): void => {

                    if (this.m_inputNamespace.value == null || this.m_inputNamespace.value.length <= 0) {
                        this.m_inputNamespace.value = "Default";
                    }
                    formData.append('overwrite', this.m_inputOverWrite.checked ? '1' : '0');
                    formData.append('namespace', this.m_inputNamespace.value);
                })
            };

            this.m_filterListDropzone = new Dropzone('#filter_list_dropzone', dropzoneOptions);
        }

        private ConstructUIElements(): void {
            this.m_overlay              = document.querySelector('#overlay_list_upload') as HTMLDivElement;
            this.m_inputSelectedFile    = document.querySelector('#upload_list_selected_file_name') as HTMLInputElement;
            this.m_inputNamespace       = document.querySelector('#upload_list_namespace') as HTMLInputElement;
            this.m_inputOverWrite       = document.querySelector('#upload_list_overwrite') as HTMLInputElement;
            this.m_btnSubmit            = document.querySelector('#upload_list_submit') as HTMLButtonElement;
            this.m_btnCancel            = document.querySelector('#upload_list_cancel') as HTMLButtonElement;
            this.m_listExistNamespace   = document.querySelector('#existing_list_namespaces') as HTMLDataListElement;

            this.m_btnSubmit.onclick = ((e: MouseEvent) => {
                this.m_filterListDropzone.processQueue();
            });

            this.m_btnCancel.onclick = ((e: MouseEvent) => {
                this.Hide();
            });

            this.Reset();
        }

        private Reset(): void {
            this.m_btnSubmit.disabled = true;
            this.m_btnCancel.disabled = false;

            this.m_filterListDropzone.removeAllFiles();

            while (this.m_listExistNamespace.firstChild) {
                this.m_listExistNamespace.removeChild(this.m_listExistNamespace.firstChild);
            }

            this.m_inputNamespace.value = "Default";
            this.m_inputSelectedFile.value = "";
            this.m_inputOverWrite.checked = false;
        }

        public Show(allLists: DataTables.Api, fadeInTimeMsec: number = 200): void {
            this.Reset();

            if (allLists != null && allLists.length > 0) {
                let uniqueNamespaces = {};
                let distinct = [];

                allLists.each((elm: any): void => {
                    if (typeof (uniqueNamespaces[elm['namespace']]) == "undefined") {
                        distinct.push(elm['namespace']);
                    }

                    uniqueNamespaces[elm['namespace']] = 0;
                });

                distinct.forEach((value: any): void => {
                    let existingNamespaceOption = document.createElement('option') as HTMLOptionElement;
                    existingNamespaceOption.value = value;
                    this.m_listExistNamespace.appendChild(existingNamespaceOption);
                });

            }

            $(this.m_overlay).fadeIn(fadeInTimeMsec);
        }

        public Hide(fadeOutTimeMsec: number = 200): void {
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        }
    }
}
Dropzone.autoDiscover = false;
var Citadel;
(function (Citadel) {
    var ListUploadOverlay = (function () {
        function ListUploadOverlay() {
            this.ConstructDropzone();
            this.ConstructUIElements();
        }
        Object.defineProperty(ListUploadOverlay.prototype, "UploadCompleteCallback", {
            get: function () {
                return this.m_uploadCompleteCallback;
            },
            set: function (value) {
                this.m_uploadCompleteCallback = value;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ListUploadOverlay.prototype, "UploadFailedCallback", {
            get: function () {
                return this.m_uploadFailedCallback;
            },
            set: function (value) {
                this.m_uploadFailedCallback = value;
            },
            enumerable: true,
            configurable: true
        });
        ListUploadOverlay.prototype.ConstructDropzone = function () {
            var _this = this;
            this.m_progressWait = new Citadel.ProgressWait();
            var dropzoneOptions = {
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                clickable: '#upload_list_select_file',
                acceptedFiles: '.tar.gz,.model,.zip',
                autoProcessQueue: false,
                maxFiles: 1,
                parallelUploads: 1,
                url: 'api/admin/filterlists/upload',
                complete: (function (file) {
                    _this.m_filterListDropzone.removeAllFiles();
                    if (file.accepted == true) {
                        _this.m_progressWait.Show("Upload Complete", "File upload complete.");
                        setTimeout(function () {
                            _this.m_progressWait.Hide();
                            if (_this.m_uploadCompleteCallback != null) {
                                _this.m_uploadCompleteCallback();
                            }
                        }, 4000);
                    }
                }),
                error: (function (file, message, xhr) {
                    file.accepted = false;
                    _this.m_progressWait.Show("Upload Failed", xhr.responseText);
                    setTimeout(function () {
                        _this.m_progressWait.Hide();
                        if (_this.m_uploadFailedCallback != null) {
                            _this.m_uploadFailedCallback(message);
                        }
                    }, 4000);
                    _this.m_filterListDropzone.removeAllFiles();
                }),
                maxfilesexceeded: (function (file) {
                    _this.m_filterListDropzone.removeAllFiles();
                    _this.m_filterListDropzone.addFile(file);
                    _this.m_listSelectedFileNameInput.value = file.name;
                    _this.m_submitButton.disabled = false;
                }),
                addedfile: (function (file) {
                    _this.m_listSelectedFileNameInput.value = file.name;
                    _this.m_submitButton.disabled = false;
                }),
                uploadprogress: (function (file, progress, bytesSent) {
                    if (file.accepted) {
                        _this.m_progressWait.Show("Uploading File", 'Uploading file. ' + progress.toFixed(2) + '%. File is being processed. Do not refresh your browser.');
                    }
                }),
                sending: (function (file, xhr, formData) {
                    if (_this.m_listNamespaceInput.value == null || _this.m_listNamespaceInput.value.length <= 0) {
                        _this.m_listNamespaceInput.value = "Default";
                    }
                    formData.append('overwrite', _this.m_overwriteOnUploadInput.checked ? '1' : '0');
                    formData.append('namespace', _this.m_listNamespaceInput.value);
                })
            };
            this.m_filterListDropzone = new Dropzone('#filter_list_dropzone', dropzoneOptions);
        };
        ListUploadOverlay.prototype.ConstructUIElements = function () {
            var _this = this;
            this.m_overlay = document.querySelector('#overlay_list_upload');
            this.m_listSelectedFileNameInput = document.querySelector('#upload_list_selected_file_name');
            this.m_listNamespaceInput = document.querySelector('#upload_list_namespace');
            this.m_overwriteOnUploadInput = document.querySelector('#upload_list_overwrite');
            this.m_submitButton = document.querySelector('#upload_list_submit');
            this.m_cancelButton = document.querySelector('#upload_list_cancel');
            this.m_existingNamespaceDataList = document.querySelector('#existing_list_namespaces');
            this.m_submitButton.onclick = (function (e) {
                _this.m_filterListDropzone.processQueue();
            });
            this.m_cancelButton.onclick = (function (e) {
                _this.Hide();
            });
            this.Reset();
        };
        ListUploadOverlay.prototype.Reset = function () {
            this.m_submitButton.disabled = true;
            this.m_cancelButton.disabled = false;
            this.m_filterListDropzone.removeAllFiles();
            while (this.m_existingNamespaceDataList.firstChild) {
                this.m_existingNamespaceDataList.removeChild(this.m_existingNamespaceDataList.firstChild);
            }
            this.m_listNamespaceInput.value = "Default";
            this.m_listSelectedFileNameInput.value = "";
            this.m_overwriteOnUploadInput.checked = false;
        };
        ListUploadOverlay.prototype.Show = function (allLists, fadeInTimeMsec) {
            var _this = this;
            if (fadeInTimeMsec === void 0) { fadeInTimeMsec = 200; }
            this.Reset();
            if (allLists != null && allLists.length > 0) {
                var uniqueNamespaces_1 = {};
                var distinct_1 = [];
                allLists.each(function (elm) {
                    if (typeof (uniqueNamespaces_1[elm['namespace']]) == "undefined") {
                        distinct_1.push(elm['namespace']);
                    }
                    uniqueNamespaces_1[elm['namespace']] = 0;
                });
                distinct_1.forEach(function (value) {
                    var existingNamespaceOption = document.createElement('option');
                    existingNamespaceOption.value = value;
                    _this.m_existingNamespaceDataList.appendChild(existingNamespaceOption);
                });
            }
            $(this.m_overlay).fadeIn(fadeInTimeMsec);
        };
        ListUploadOverlay.prototype.Hide = function (fadeOutTimeMsec) {
            if (fadeOutTimeMsec === void 0) { fadeOutTimeMsec = 200; }
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        };
        return ListUploadOverlay;
    }());
    Citadel.ListUploadOverlay = ListUploadOverlay;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=listuploadoverlay.js.map
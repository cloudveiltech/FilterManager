var Citadel;
(function (Citadel) {
    var BaseRecord = (function () {
        function BaseRecord() {
            this.m_progressWait = new Citadel.ProgressWait();
        }
        Object.defineProperty(BaseRecord.prototype, "ActionCompleteCallback", {
            get: function () {
                return this.m_actionCompleteCallback;
            },
            set: function (value) {
                this.m_actionCompleteCallback = value;
            },
            enumerable: true,
            configurable: true
        });
        BaseRecord.CreateFromObject = function (type, data) {
            var inst = new type();
            inst.LoadFromObject(data);
            return inst;
        };
        BaseRecord.GetRecordRoute = function (type) {
            var inst = new type();
            return inst.RecordRoute;
        };
        BaseRecord.prototype.OnFormSubmitClicked = function (e, newlyCreated) {
            if (!e.defaultPrevented) {
                e.stopImmediatePropagation();
                e.stopPropagation();
                this.Save(newlyCreated);
            }
            return false;
        };
        BaseRecord.prototype.Save = function (newlyCreated) {
            var _this = this;
            if (newlyCreated === void 0) { newlyCreated = false; }
            this.LoadFromForm();
            var dataObject = this.ToObject();
            console.log(dataObject);
            this.m_progressWait.Show('Saving Record', 'Saving record to server.');
            var ajaxSettings = {
                method: newlyCreated == true ? "POST" : "PATCH",
                timeout: 60000,
                url: newlyCreated == true ? this.RecordRoute : this.RecordRoute + '/' + dataObject['id'],
                data: dataObject,
                success: function (data, textStatus, jqXHR) {
                    _this.m_progressWait.Hide();
                    if (_this.m_actionCompleteCallback != null) {
                        _this.m_actionCompleteCallback(newlyCreated == true ? "Created" : "Updated");
                    }
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR.responseText);
                    console.log(errorThrown);
                    console.log(textStatus);
                    _this.m_progressWait.Show('Action Failed', 'Error reported by the server during action.\n' + jqXHR.responseText + '\nCheck console for more information.');
                    setTimeout(function () {
                        _this.m_progressWait.Hide();
                    }, 5000);
                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                    }
                    else {
                    }
                }
            };
            $.post(ajaxSettings);
        };
        BaseRecord.prototype.Delete = function () {
            var _this = this;
            this.m_progressWait.Show('Deleting Record', 'Deleting record from server.');
            var dataObject = this.ToObject();
            var ajaxSettings = {
                method: "DELETE",
                timeout: 60000,
                contents: { _token: $('meta[name="csrf-token"]').attr('content') },
                url: this.RecordRoute + '/' + dataObject['id'],
                success: function (data, textStatus, jqXHR) {
                    _this.m_progressWait.Hide();
                    if (_this.m_actionCompleteCallback != null) {
                        _this.m_actionCompleteCallback("Deleted");
                    }
                    return false;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR.responseText);
                    _this.m_progressWait.Show('Action Failed', 'Error reported by the server during action. Check console for more information.');
                    if (jqXHR.status > 399 && jqXHR.status < 500) {
                    }
                    else {
                        setTimeout(function () {
                            _this.m_progressWait.Hide();
                        }, 5000);
                    }
                }
            };
            $.ajax(ajaxSettings);
        };
        return BaseRecord;
    }());
    Citadel.BaseRecord = BaseRecord;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=baserecord.js.map
var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
var Citadel;
(function (Citadel) {
    var FilterListRecord = (function (_super) {
        __extends(FilterListRecord, _super);
        function FilterListRecord() {
            _super.call(this);
        }
        Object.defineProperty(FilterListRecord.prototype, "RecordRoute", {
            get: function () {
                return 'api/admin/filterlists';
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(FilterListRecord.prototype, "ValidationOptions", {
            get: function () {
                var opt = {};
                return opt;
            },
            enumerable: true,
            configurable: true
        });
        FilterListRecord.prototype.LoadFromObject = function (value) {
            this.m_filterId = value['id'];
            this.m_filterCategoryName = value['category'];
            this.m_filterType = value['type'];
            this.m_filterListNamespace = value['namespace'];
            this.m_numRuleEntries = value['num_entries'];
        };
        FilterListRecord.prototype.LoadFromForm = function () {
        };
        FilterListRecord.prototype.ToObject = function () {
            var obj = {
                'id': this.m_filterId,
                'category': this.m_filterCategoryName,
                'type': this.m_filterType,
                'namespace': this.m_filterListNamespace,
                'num_entries': this.m_numRuleEntries
            };
            return obj;
        };
        FilterListRecord.prototype.StopEditing = function () {
        };
        FilterListRecord.prototype.DeleteAllInNamespace = function (constrainToType) {
            var _this = this;
            this.m_progressWait.Show('Deleting Record', 'Deleting record from server.');
            var dataObject = this.ToObject();
            var ajaxSettings = {
                method: "DELETE",
                timeout: 60000,
                contents: { _token: $('meta[name="csrf-token"]').attr('content') },
                url: constrainToType == true ? this.RecordRoute + '/namespace/' + dataObject['namespace'] + '/' + dataObject['type'] : this.RecordRoute + '/namespace/' + dataObject['namespace'] + '/',
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
        return FilterListRecord;
    }(Citadel.BaseRecord));
    Citadel.FilterListRecord = FilterListRecord;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=filterlistrecord.js.map
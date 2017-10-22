var Citadel;
(function (Citadel) {
    var ApplyToGroupOverlay = (function () {
        function ApplyToGroupOverlay() {
            this.ConstructUIElements();
        }
        ApplyToGroupOverlay.prototype.ConstructUIElements = function () {
            var _this = this;
            this.m_overlay = document.querySelector('#overlay_apply_togroup');
            this.m_closeButton = document.querySelector('#apply_togroup_close');
            this.m_closeButton.onclick = (function (e) {
                _this.Hide();
            });
            this.Reset();
        };
        ApplyToGroupOverlay.prototype.Reset = function () {
            this.m_closeButton.disabled = false;
        };
        ApplyToGroupOverlay.prototype.Show = function (fadeInTimeMsec) {
            if (fadeInTimeMsec === void 0) { fadeInTimeMsec = 200; }
            this.Reset();
            $(this.m_overlay).fadeIn(fadeInTimeMsec);
        };
        ApplyToGroupOverlay.prototype.Hide = function (fadeOutTimeMsec) {
            if (fadeOutTimeMsec === void 0) { fadeOutTimeMsec = 200; }
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        };
        return ApplyToGroupOverlay;
    }());
    Citadel.ApplyToGroupOverlay = ApplyToGroupOverlay;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=applytogroupoverlay.js.map
var Citadel;
(function (Citadel) {
    var ProgressWait = (function () {
        function ProgressWait() {
            this.m_overlay = document.getElementById('overlay_loading');
            this.m_title = document.getElementById('overlay_loading_title');
            this.m_message = document.getElementById('overlay_loading_message');
        }
        ProgressWait.prototype.Show = function (title, message, fadeInTimeMsec) {
            if (message === void 0) { message = ""; }
            if (fadeInTimeMsec === void 0) { fadeInTimeMsec = 200; }
            this.m_title.innerText = title;
            this.m_message.innerHTML = '<div>' + message + '</div>';
            $(this.m_overlay).fadeIn(fadeInTimeMsec);
        };
        ProgressWait.prototype.Hide = function (fadeOutTimeMsec) {
            if (fadeOutTimeMsec === void 0) { fadeOutTimeMsec = 200; }
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        };
        return ProgressWait;
    }());
    Citadel.ProgressWait = ProgressWait;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=progresswait.js.map
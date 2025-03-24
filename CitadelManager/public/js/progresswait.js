var Citadel;
(function (Citadel) {
    class ProgressWait {
        constructor() {
            this.m_overlay = document.getElementById('overlay_loading');
            this.m_title = document.getElementById('overlay_loading_title');
            this.m_message = document.getElementById('overlay_loading_message');
        }
        Show(title, message = "", fadeInTimeMsec = 200) {
            this.m_title.innerText = title;
            this.m_message.innerHTML = '<div>' + message + '</div>';
            $(this.m_overlay).fadeIn(fadeInTimeMsec);
        }
        Hide(fadeOutTimeMsec = 200) {
            $(this.m_overlay).fadeOut(fadeOutTimeMsec);
        }
    }
    Citadel.ProgressWait = ProgressWait;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=progresswait.js.map
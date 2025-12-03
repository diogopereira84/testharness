(function () {
    window.ttd_dom_ready = window.ttd_dom_ready || function (cb) {
        if (document.readyState === "complete" ||
            (document.readyState !== "loading" && !document.documentElement.doScroll)) {
            cb();
        } else {
            let mcb = () => {
                document.removeEventListener("DOMContentLoaded", mcb);
                cb();
            };
            document.addEventListener("DOMContentLoaded", mcb);
        }
    }
}());

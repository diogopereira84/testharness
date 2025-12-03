require(["jquery"],function($) {
    let notificationSeenCookieValue = getCookie("notificationSeen");
    //For retail Only excluding cart,checkout and retail submit order page
    var pathname = window.location.pathname;

    if (!notificationSeenCookieValue
        && !pathname.match(/checkout/g)
        && !pathname.match(/ordersuccess/g)
    ) {
        $('#notificationBanner').show();
    }

    /**
     * Check if Cookie exist by name
     */
    function getCookie(cname) {
        if (document.cookie.match(/^(.*;)?\s*notificationSeen\s*=\s*[^;]+(.*)?$/)) {
            return true;
        } else {
            return false;
        }
    }
    $('.notification-banner-close-icon').on('click', function () {
        $('#notificationBanner').hide();
        document.cookie = "notificationSeen=Shown;secure;path=/";
    });

    $(document).ready(function () {
        $("span.notification-banner-close-icon").keyup(function(event) {
            if (event.which === 13) {
                $(this).trigger('click');
            }
        });
    });
});

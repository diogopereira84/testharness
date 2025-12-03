define([
    'jquery'
], function ($) {
    'use strict';

    return $.extend({
        injectCrumb: function () {
            var timeStamp = new Date().getTime();
            var url = BASE_URL + "fedexcatalog/product/breadcrumb/" + timeStamp;
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    ref: document.referrer,
                    pid: $('#pid').val()
                },
                cache: false,
                success: function (response) {
                    var breadcrumbs = JSON.parse(response.data);
                    if (breadcrumbs.label) {
                        var bsize = $('.breadcrumbs ul li').length;
                        $('.breadcrumbs ul').find(' > li:nth-last-child(1)').before(
                            '<li class="item '
                            + (bsize - 1) +
                            '"><a href="'
                            + breadcrumbs.link +
                            '" title="'
                            + breadcrumbs.title +
                            '">'
                            + breadcrumbs.label +
                            '</a></li>'
                        );
                    }
                }
            });
        }
    });
});

define(['jquery', 'Magento_Ui/js/modal/modal', 'domReady!'], function($, modal) {
    var videoThumnails = $('.video-thumbnail:not([data-entryid=""])');
    if (typeof window.kaltura !== 'undefined' && videoThumnails.length) {
        registerEvents();
        setPlayIconUI();
    }
    function registerEvents() {
        var kalturaPlayerModal = $('.kaltura-player-modal'),
            modalOptions = {
                buttons: [],
                modalClass: 'kaltura-modal'
            },
            playerOptions = {
                'targetId': 'cms-kvideo-player',
                'wid': '',
                'uiconf_id': window.kaltura.uiconfig_id,
                'flashvars': {
                    'thumbnailUrl': ''
                },
                'entry_id': ''
            };
        kalturaPlayerModal.modal(modalOptions);
        videoThumnails.on('click', function(event) {
            event.stopPropagation();
            event.preventDefault();
            playerOptions.entry_id = $(this).data('entryid');
            playerOptions.wid = $(this).data('wid');
            if($(this).hasClass('cms-img-wrapper')) {
                playerOptions.flashvars.thumbnailUrl = $(this).find('>img:visible').attr('src');
            } else if ($(this).hasClass('pagebuilder-banner-wrapper')) {
                var viewportWidth = window.innerWidth,
                    bannerImgUrls = JSON.parse($(this).data('background-images').replaceAll('\\',''));
                if (viewportWidth < 767) {
                    playerOptions.flashvars.thumbnailUrl = bannerImgUrls.mobile_image;
                } else if (viewportWidth < 1200) {
                    playerOptions.flashvars.thumbnailUrl = bannerImgUrls.mobile_medium_image;
                } else {
                    playerOptions.flashvars.thumbnailUrl = bannerImgUrls.desktop_medium_image;
                }
            }
            kalturaPlayerModal.modal(modalOptions).modal('openModal');
        });
        kalturaPlayerModal.on('modalopened', function () {
            kWidget.embed(playerOptions);
        });
        kalturaPlayerModal.on('modalclosed', function () {
            kWidget.destroy('cms-kvideo-player');
        });
    }
    function setPlayIconUI () {
        videoThumnails.each(function() {
            var overlayDiv = $('<div/>', {class: 'absolute play-overlay pointer'}),
                playIcon = $('<a/>', {class: 'absolute fs-24 luma-icon play-icon no-underline', href: '#', role: 'button'});
            overlayDiv.append(playIcon);
            $(this).addClass('relative').append(overlayDiv);
        });
    }
});
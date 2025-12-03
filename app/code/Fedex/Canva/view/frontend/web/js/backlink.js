define([
    'jquery',
    'mage/url',
    'Fedex_Canva/js/view/canva',
    'jquery-ui-modules/widget'
], function($, url, canva) {
    'use strict';

    $.widget('mage.canvaBackLink', {
        options: {
            artworkId: null,
            canva: canva,
        },
        _create: function() {

            $(document).on('canva:backlink:show', $.proxy(this._showBacklink, this));

            this._on({
                'click': $.proxy(this._backToCanvaEditor, this)
            });
        },
        _backToCanvaEditor: function () {
            window.location = canva.getURL();
        },
        _showBacklink: function () {
            $(this.element).show();
        }
    });

    return $.mage.canvaBackLink;
});

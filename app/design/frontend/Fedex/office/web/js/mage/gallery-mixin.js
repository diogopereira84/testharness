define([
    'underscore',
    'mage/template',
    'text!mage/gallery/gallery.html',
    'mage/translate'
], function (_, template, galleryTpl, $t) {
    'use strict';

    return function (gallery) {
        return gallery.extend({
            initialize: function () {
                this._super();
            },

            initGallery: function () {
                this._super();

                let thumbs = document.querySelectorAll('.fotorama__nav__frame');

                thumbs.forEach((thumb, index) => {
                    thumb.setAttribute('data-testid', `image-gallery-${index + 1}`)
                })
            },
        });
    };
});
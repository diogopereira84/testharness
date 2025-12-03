define(['jquery'], function ($) {
    'use strict';

    return {
        init: function () {
            this.bindTagsInput();
        },

        updatePlaceholder: function ($tagsInput) {
            $tagsInput.attr('placeholder', window.tags.length ? '' : 'Text input');
        },

        renderTags: function () {
            const self = this;
            let $tagsWrapper = $('#catalogSettingTagWrapper');
            let $tagsInput = $('#shared-catalog-setting-tag');

            $tagsWrapper.find('.tag-chip').remove();
            window.tags.forEach(function (tag, idx) {
                let $chip = $('<span class="tag-chip"></span>').text(tag);
                let $close = $('<span class="tag-chip-close">&times;</span>');
                $chip.append($close);
                $tagsWrapper.append($chip);

                $chip.on('click', function (e) {
                    e.stopPropagation();
                    window.tags.splice(idx, 1);
                    self.renderTags();
                    self.updatePlaceholder($tagsInput);
                    $tagsInput.focus();
                });
            });

            $tagsWrapper.append($tagsInput);
            this.updatePlaceholder($tagsInput);

            if ($('#product-tags-hidden').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'product-tags-hidden',
                    name: 'product_tags',
                    value: window.tags.join(',')
                }).appendTo('#product-config-setting');
            } else {
                $('#product-tags-hidden').val(window.tags.join(','));
            }
        },

        bindTagsInput: function () {
            const self = this;
            let $tagsInput = $('#shared-catalog-setting-tag');

            window.renderTags = function () {
                self.renderTags();
            };

            $tagsInput.on('keyup', function (e) {
                let val = $tagsInput.val().trim().replace(',', '');
                if (e.which === 13 && val.length > 0) {
                    if (window.tags.indexOf(val) === -1) window.tags.push(val);
                    self.renderTags();
                    $tagsInput.val('');
                    self.updatePlaceholder($tagsInput);
                    $tagsInput.focus();
                }
            });

            let currentTagVal = $tagsInput.val().trim();
            if (currentTagVal.length > 0 && window.tags.indexOf(currentTagVal) === -1) {
                window.tags.push(currentTagVal);
                self.renderTags();
            }

            this.updatePlaceholder($tagsInput);
        },
    };
});
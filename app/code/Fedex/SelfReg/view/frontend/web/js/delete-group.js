/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'uiRegistry',
    'mage/translate'
], function ($, confirm, alert, registry) {
    'use strict';

    $.widget('mage.groupDelete', {
        options: {
            isAjax: false,
            id: '',
            deleteUrl: '',
            gridProvider: '',
        },

        /**
         * Create widget
         *
         * @private
         */
        _create: function () {
            this._bind();
        },

        /**
         * Bind listeners on elements
         *
         * @private
         */
        _bind: function () {
            this._on({
                'deleteGroup': '_deleteGroup'
            });
        },

        /**
         * Ajax for delete group
         *
         * @private
         */
        _sendAjax: function (options) {
            var groupId = options.id;
            var deleteUrl = options.deleteUrl

            if (!this.options.isAjax) {
                this.options.isAjax = true;

                $.ajax({
                    url: deleteUrl,
                    data: {
                        groupId: groupId
                    },
                    type: 'POST',
                    dataType: 'json',
                    showLoader: true,
                });
                window.location.reload();
            }
        },

        /**
         * Set popup for delete
         *
         * @private
         */
        _deleteGroup: function (e) {
            var self = this,
                options;
            let alertIconImage = typeof(window.checkout) != 'undefined' && typeof(window.checkout.alert_icon_image) != 'undefined' && window.checkout.alert_icon_image != null ? window.checkout.alert_icon_image : '';

            e.preventDefault();
            options = {
                modalClass: 'modal-slide popup-tree delete-group',
                buttons: [{
                    text: $.mage.__('NO, CANCEL'),
                    'class': 'action primary cancel',

                    /** @inheritdoc */
                    click: function (event) {
                        this.closeModal(event);
                    }
                },{
                    text: $.mage.__('YES, DELETE'),
                    'class': 'action primary delete',

                    /** @inheritdoc */
                    click: function (event) {
                        self._sendAjax(self.options);
                        this.closeModal(event);
                    }
                }],
                title: '<img src="' + alertIconImage + '" class="delete-alert-icon-img" aria-label="delete_image" />',
                content: $.mage.__("<h3 class='delete-title-name'>This action cannot be undone</h3><p>Would you like to delete this user group?</p>")
            };

            confirm(options);
        }
    });

    return $.mage.groupDelete;
});

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

    $.widget('mage.userDelete', {
        options: {
            isAjax: false,
            id: '',
            deleteUrl: '',
            setInactiveUrl: '',
            gridProvider: '',
            inactiveClass: 'inactive'
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
                'deleteUser': '_deleteUser'
            });
        },

        /**
         * Ajax for delete customer
         *
         * @private
         */
        _sendAjax: function (url) {
            var self = this,
                data = {
                    'customer_id': this.options.id
                };

            if (!this.options.isAjax) {
                this.options.isAjax = true;

                $.ajax({
                    url: url,
                    data: data,
                    type: 'post',
                    dataType: 'json',
                    showLoader: true,

                    /** @inheritdoc */
                    success: function (res) {

                        if (res.status === 'error') {
                            alert({
                                modalClass: 'restriction-modal-quote',
                                responsive: true,
                                innerScroll: true,
                                title: $.mage.__('Cannot Delete Customer'),
                                content: res.message
                            });
                        } else {
                            registry.get(self.options.gridProvider).reload({
                                refresh: true
                            });
                        }
                    },

                    /** @inheritdoc */
                    complete: function () {
                        self.options.isAjax = false;
                    }
                });
            }
        },

        /**
         * Set popup for delete
         *
         * @private
         */
        _deleteUser: function (e) {
            var self = this,
                options;

            e.preventDefault();
            options = {
                modalClass: 'modal-slide popup-tree delete-user',
                buttons: [{
                    text: $.mage.__('DELETE'),
                    'class': 'action primary delete',

                    /** @inheritdoc */
                    click: function (event) {
                        self._sendAjax(self.options.deleteUrl);
                        this.closeModal(event);
                    }
                }, {
                    text: $.mage.__('SET AS INACTIVE'),
                    'class': 'action primary inactive ' + this.options.inactiveClass,

                    /** @inheritdoc */
                    click: function (event) {
                        self._sendAjax(self.options.setInactiveUrl);
                        this.closeModal(event);
                    }
                }, {
                    text: $.mage.__('<b>CANCEL</b>'),
                    'class': 'action primary cancel',

                    /** @inheritdoc */
                    click: function (event) {
                        this.closeModal(event);
                    }
                }],
                title: $.mage.__('Delete this user?'),
                content: $.mage.__("<p>This action will permanently delete the user's account and content, but the user's orders and quotes will still be visible to the merchant.</p></br><p> To temporaliry lock the user instead, set their status to \"Inactive\". While inactive, the user's content will remain available to parent users. </p>") //eslint-disable-line max-len
            };

            confirm(options);
        }
    });

    return $.mage.userDelete;
});

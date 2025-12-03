define([
    'Magento_Ui/js/form/element/ui-select',
    'jquery',
    'mage/url',
    'uiRegistry',
], function (Select, $, urlBuilder, uiRegistry) {
    'use strict';
    return Select.extend({

        initialize: function (){
            this._super();
            const path = window.location.pathname;
            const pathSegments = path.split('/');
            const idIndex = pathSegments.indexOf('id');
            if (idIndex !== -1 && pathSegments[idIndex + 1]) {
                var registry = require('uiRegistry');
                registry.get(
                    'new_approver_add.new_approver_add.genral.users', // namespace for users
                    function (usersField) {
                        usersField.disabled(false);
                    }
                );

                registry.get(
                    'new_approver_add.new_approver_add.genral.order_approver', // namespace for users
                    function (usersField) {
                        usersField.disabled(false);
                    }
                );
            }
        },

        /**
         * Parse data and set it to options.
         *
         * @param {Object} data - Response data object.
         * @returns {Object}
         */
        setParsed: function (data) {
            var option = this.parseData(data);
            if (data.error) {
                return this;
            }
            this.options([]);
            this.setOption(option);
            this.set('newOption', option);
        },
        /**
         * Normalize option object.
         *
         * @param {Object} data - Option object.
         * @returns {Object}
         */
        parseData: function (data) {
            return {
                value: data.site.id,
                label: data.site.name
            };
        },

        onUpdate: function(value){
            $('[data-index="site"] .admin__field-error').remove();
            if (value != '') {
                var fetchUrl = window.ajaxUrlToGetCustomer;
                // Make an AJAX request to fetch filtered users on site selected
                $.ajax({
                    url: fetchUrl,
                    type: 'POST',
                    data: { site_id: value },
                    success: function (response) {
                        if (response && response.users) {
                            var registry = require('uiRegistry');
                            registry.get(
                                'new_approver_add.new_approver_add.genral.users', // namespace for users
                                function (usersField) {
                                    usersField.disabled(false);
                                    if (usersField) {
                                        usersField.value(null);
                                        usersField.cacheOptions.plain = [];
                                        usersField.options(response.users);
                                        usersField.error(false);
                                    } else {
                                        console.error('Users field not found in registry');
                                    }
                                }
                            );

                            registry.get(
                                'new_approver_add.new_approver_add.genral.order_approver', // namespace for order_approver
                                function (usersField) {
                                    usersField.disabled(false);
                                    if (usersField) {
                                        usersField.value(null);
                                        usersField.cacheOptions.plain = [];
                                        usersField.options(response.users);
                                        usersField.error(false);
                                    } else {
                                        console.error('Users field not found in registry');
                                    }
                                }
                            );
                        }
                    },
                    error: function () {
                        console.error('Error fetching users for site ' + value);
                    }
                });
            } else {
                location.reload();
            }
        },
    });
});

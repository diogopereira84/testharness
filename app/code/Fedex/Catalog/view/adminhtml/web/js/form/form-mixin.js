define([
    'underscore',
    'Magento_Ui/js/lib/spinner',
    'jquery',
    'mageUtils',
    'Magento_Ui/js/core/app',
], function (_, loader, $, utils, app) {
    'use strict';

    /**
     * Format params
     *
     * @param {Object} params
     * @returns {Array}
     */
    function prepareParams(params) {
        var result = '?';

        _.each(params, function (value, key) {
            result += key + '=' + value + '&';
        });

        return result.slice(0, -1);
    }

    /**
     * Makes ajax request
     *
     * @param {Object} params
     * @param {Object} data
     * @param {String} url
     * @returns {*}
     */
    function makeRequest(params, data, url) {
        var save = $.Deferred();

        data = utils.serialize(data);
        data['form_key'] = window.FORM_KEY;

        if (!url) {
            save.resolve();
        }

        $('body').trigger('processStart');

        $.ajax({
            url: url + prepareParams(params),
            data: data,
            dataType: 'json',

            /**
             * Success callback.
             * @param {Object} resp
             * @returns {Boolean}
             */
            success: function (resp) {
                if (resp.ajaxExpired) {
                    window.location.href = resp.ajaxRedirect;
                }

                if (!resp.error) {
                    save.resolve(resp);

                    return true;
                }

                $('body').notification('clear');
                $.each(resp.messages, function (key, message) {
                    $('body').notification('add', {
                        error: resp.error,
                        message: message,

                        /**
                         * Inserts message on page
                         * @param {String} msg
                         */
                        insertMethod: function (msg) {
                            $('.page-main-actions').after(msg);
                        }
                    });
                });
            },

            /**
             * Complete callback.
             */
            complete: function () {
                $('body').trigger('processStop');
            }
        });

        return save.promise();
    }

    return function (Form) {
        return Form.extend({
            hideLoader: function () {
                loader.get(this.name).hide();

                $(document).trigger('form-uicomponent-done', this.name);
                return this;
            },

            /**
             * Updates data from server.
             */
            reload: function () {
                makeRequest(this.params, this.data, this.reloadUrl).then(function (data) {
                    app(data, true);
                    $(document).trigger('attribute-set-changed');
                });
            }
        });
    }
});

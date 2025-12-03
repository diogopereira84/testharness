/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery'
], function ($) {
    'use strict';

    let mixin = {
        /**
         * Init header elements
         */
        initHeader: function () {
            var labels = [],
                data;
            var indexes = ['custom_billing_credit_card','custom_billing_invoiced','custom_billing_shipping'];


            if (!this.labels().length) {
                _.each(this.childTemplate.children, function (cell) {
                    data = this.createHeaderTemplate(cell.config);
                    cell.config.labelVisible = false;
                    var requiredEntry = !!cell.config.validation;
                      if(jQuery.inArray(this.index, indexes) !== -1 && cell.config.validation !== undefined){
                            if(cell.config.validation['required-entry']!== undefined)
                            {
                                requiredEntry = cell.config.validation['required-entry'];
                            }
                       }
                    _.extend(data, {
                        defaultLabelVisible: data.visible(),
                        label: cell.config.label,
                        name: cell.name,
                        required: requiredEntry,
                        columnsHeaderClasses: cell.config.columnsHeaderClasses,
                        sortOrder: cell.config.sortOrder
                    });
                    labels.push(data);
                }, this);
                this.labels(_.sortBy(labels, 'sortOrder'));
            }
        },
    };

    return function (target) {
        return target.extend(mixin);
    }
});

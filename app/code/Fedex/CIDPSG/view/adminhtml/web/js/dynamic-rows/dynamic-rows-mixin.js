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
         * Processing pages before addChild
         *
         * @param {Object} ctx - element context
         * @param {Number|String} index - element index
         * @param {Number|String} prop - additional property to element
         */
         processingAddChild: function (ctx, index, prop) {
            this.bubble('addChild', false);

            if (this.relatedData.length && this.relatedData.length % this.pageSize === 0) {
                this.pages(this.pages() + 1);
                this.nextPage();
            } else if (~~this.currentPage() !== this.pages()) {
                this.currentPage(this.pages());
            }

            if(this._elems.length <= 10) {
                this.addChild(ctx, index, prop);
            } else if ($(".customer-fields-container-collapse").length > 0) {
                $(".psg-customer-limit-msg").remove();
                $("<span class='psg-customer-limit-msg'> Only 10 custom fields are allowed.</span>").insertAfter('button[data-action="add_new_row"]');
            } else {
                this.addChild(ctx, index, prop);
            }
        }
    };

    return function (target) {
        return target.extend(mixin);
    }
});

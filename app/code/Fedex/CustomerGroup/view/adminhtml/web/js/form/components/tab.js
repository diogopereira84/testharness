/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'uiCollection',
    'fedex/storage'
], function (Collection,fxoStorage) {
    'use strict';

    return Collection.extend({
        defaults: {
            uniqueProp:     'active',
            active:         false,
            isActive:       true,
            wasActivated:   false
        },

        /**
         * Extends instance with defaults. Invokes parent initialize method.
         * Calls initListeners and pushParams methods.
         */
        initialize: function () {
            this._super()
                .setUnique();
        },

        /**
         * Calls initObservable of parent class.
         * Defines observable properties of instance.
         * @return {Object} - reference to instance
         */
        initObservable: function () {
            this._super()
                .observe('active wasActivated');
            // B-1877864 - Adding flag to check folder permission tab
            if(this._super().index === 'customergroup_catalog_permission') {
                this.isActive = false;
            }
            return this;
        },

        /**
         * Is being invoked on children update.
         * Sets changed property to one incoming.
         *
         * @param  {Boolean} hasChanged
         */
        onChildrenUpdate: function (hasChanged) {
            if (!hasChanged) {
                hasChanged = _.some(this.delegate('hasChanged'));
            }
            this.bubble('update', hasChanged);
            this.changed(hasChanged);
        },
        /**
         * Sets active property to true, then invokes pushParams method.
         */
        activate: function () {
            this.active(true);
            // B-1877864 - checking if parent group not selected and set active for  folder permission tab accordingly
            var isSelectedParentGroupID;
            if(window.e383157Toggle){
                isSelectedParentGroupID = fxoStorage.get("isSelectedParentGroupID");
            }else{
                isSelectedParentGroupID = localStorage.getItem("isSelectedParentGroupID");
            }
            if(!this.isActive && isSelectedParentGroupID !== "true" && isSelectedParentGroupID !== true) {
                this.active(false);
            }
            this.wasActivated(true);

            this.setUnique();

            return true;
        }
    });
});

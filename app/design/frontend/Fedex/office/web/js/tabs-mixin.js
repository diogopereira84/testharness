define([
    'jquery',
    'jquery-ui-modules/widget',
    'jquery/ui-modules/widgets/tabs',
    'mage/mage',
    'mage/collapsible'
], function ($) {
    'use strict';

    var tabsWidgetMixin = {
        _processPanels: function () {
            var isNotNested = this._isNotNested.bind(this);

            this.contents = this.element
                .find(this.options.content)
                .filter(isNotNested);

            if (this.contents.length === 0) {
                this.contents = this.element.parent().find(this.options.content);
            }

            this.collapsibles = this.element
                .find(this.options.collapsibleElement)
                .filter(isNotNested);

            this.collapsibles
                .attr('role', 'presentation')
                .parent()
                .attr('role', 'tablist');

            this.headers = this.element
                .find(this.options.header)
                .filter(isNotNested);

            if (this.headers.length === 0) {
                this.headers = this.collapsibles;
            }
            this.triggers = this.element
                .find(this.options.trigger)
                .filter(isNotNested);

            if (this.triggers.length === 0) {
                this.triggers = this.headers;
            }
            this._callCollapsible();
        },
    };

    return function (targetWidget) {
        $.widget('mage.tabs', targetWidget, tabsWidgetMixin);

        return $.mage.tabs;
    };
});

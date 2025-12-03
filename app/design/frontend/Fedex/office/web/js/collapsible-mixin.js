define(['jquery'], function ($) {
    'use strict';
    return function(widget) {
        $.widget('mage.collapsible', widget, {
            _processPanels: function () {
                var headers, triggers;
                this.element.attr('data-collapsible', 'true');
                if (typeof this.options.header === 'object') {
                    this.header = this.options.header;
                } else {
                    headers = this.element.find(this.options.header);
                    if (headers.length > 0) {
                        this.header = headers.eq(0);
                    } else {
                        this.header = this.element;
                    }
                }
                if (typeof this.options.content === 'object') {
                    this.content = this.options.content;
                } else {
                    this.content = this.header.next(this.options.content).eq(0);
                }
                // ARIA (init aria attributes)
                if (this.header.attr('id')) {
                    this.content.attr('aria-labelledby', this.header.attr('id'));
                }
                if (this.content.attr('id')) {
                    this.header.attr('aria-controls', this.content.attr('id'));
                }
                this.header
                    .attr({
                        'role': 'tab',
                        'aria-expanded': this.options.active
                    });
    
                // For collapsible widget only (not tabs or accordion)
                if (this.header.parent().attr('role') !== 'presentation') {
                    this.header
                        .parent()
                        .attr('role', 'tablist')
                        .attr('aria-busy', 'true');
                }
                
                this.content.attr({
                    'role': 'tabpanel'
                });

                if (typeof this.options.trigger === 'object') {
                    this.trigger = this.options.trigger;
                } else {
                    triggers = this.header.find(this.options.trigger);
                    if (triggers.length > 0) {
                        this.trigger = triggers.eq(0);
                    } else {
                        this.trigger = this.header;
                    }
                }
            },
            _open: function () {
                this.element.trigger('beforeOpen');
                this.options.active = true;
                if (this.options.ajaxContent) {
                    this._loadContent();
                }
                if (this.options.saveState) {
                    this.storage.set(this.stateKey, true);
                }
                if (this.options.openedState) {
                    this.element.addClass(this.options.openedState);
                }
                if (this.options.collateral.element && this.options.collateral.openedState) {
                    $(this.options.collateral.element).addClass(this.options.collateral.openedState);
                }
                if (this.options.closedState) {
                    this.element.removeClass(this.options.closedState);
                }
                if (this.icons) {
                    this.header.children('[data-role=icons]')
                        .removeClass(this.options.icons.header)
                        .addClass(this.options.icons.activeHeader);
                }
                // ARIA (updates aria attributes)
                this.header.attr({
                    'aria-expanded': 'true'
                });
                this.element.trigger('dimensionsChanged', {
                    opened: true
                });
            },
            _close: function () {
                this.options.active = false;
                if (this.options.saveState) {
                    this.storage.set(this.stateKey, false);
                }
                if (this.options.openedState) {
                    this.element.removeClass(this.options.openedState);
                }
                if (this.options.collateral.element && this.options.collateral.openedState) {
                    $(this.options.collateral.element).removeClass(this.options.collateral.openedState);
                }
                if (this.options.closedState) {
                    this.element.addClass(this.options.closedState);
                }
                if (this.icons) {
                    this.header.children('[data-role=icons]')
                        .removeClass(this.options.icons.activeHeader)
                        .addClass(this.options.icons.header);
                }
                // ARIA (updates aria attributes)
                this.header.attr({
                    'aria-expanded': "false"
                });
                this.element.trigger('dimensionsChanged', {
                    opened: false
                });
            }
        });
        return $.mage.collapsible;
    };
});
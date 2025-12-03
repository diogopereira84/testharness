// app/design/frontend/Fedex/office/web/js/lib/accessible-select.js
define(['jquery'], function($) {
    'use strict';
    
    return {
        /**
         * Create keyboard handlers for any select component
         * @param {Object} component - Component with basic interface
         * @param {Object} options - Optional customizations
         */
        createHandlers: function(component, options = {}) {
            
            // Simple interface check - just warn if methods missing
            this._checkInterface(component);
            
            var settings = {
                enableArrows: true,
                enableTab: true,
                ...options
            };

            // Strategy for select field behavior
            var selectStrategy = {
                handleEnterSpace: function(event) {
                    event.preventDefault();
                    component.toggle();
                },
                
                handleEscape: function(event) {
                    component.close();
                },
                
                handleTab: function(event) {
                    if (settings.enableTab && component.isOpen()) {
                        component.close();
                    }
                },
                
                handleArrows: function(event) {
                    if (settings.enableArrows) {
                        event.preventDefault();
                        if (!component.isOpen()) component.toggle();
                        component.focusFirst && component.focusFirst();
                    }
                }
            };

            // Strategy for option behavior
            var optionStrategy = {
                handleEnterSpace: function(event, data) {
                    event.preventDefault();
                    component.select(data);
                },
                
                handleEscape: function(event) {
                    component.close();
                    component.focusSelect && component.focusSelect();
                },
                
                handleTab: function(event) {
                    if (settings.enableTab) {
                        component.close();
                    }
                },
                
                handleArrows: function(event) {
                    if (settings.enableArrows) {
                        event.preventDefault();
                        component.navigate && component.navigate(event.target, event.which === 40 ? 'down' : 'up');
                    }
                }
            };
            
            return {
                selectKeydownHandler: function(data, event) {
                    return this._executeStrategy(selectStrategy, data, event);
                },
                
                optionKeydownHandler: function(data, event) {
                    return this._executeStrategy(optionStrategy, data, event);
                },
                
                _executeStrategy: function(strategy, data, event) {
                    switch(event.which) {
                        case 13: case 32: // Enter/Space
                            strategy.handleEnterSpace(event, data);
                            break;
                        case 27: // Escape
                            strategy.handleEscape(event);
                            break;
                        case 9: // Tab
                            strategy.handleTab(event);
                            break;
                        case 38: case 40: // Arrows
                            strategy.handleArrows(event);
                            break;
                    }
                    return true;
                }
            };
        },
        
        /**
         * Soft interface check - warns but doesn't break
         */
        _checkInterface: function(component) {
            var required = ['toggle', 'close', 'isOpen', 'select'];
            var missing = required.filter(method => typeof component[method] !== 'function');
            
            if (missing.length > 0) {
                console.warn('Accessibility: Component missing methods:', missing);
            }
        }
    };
});
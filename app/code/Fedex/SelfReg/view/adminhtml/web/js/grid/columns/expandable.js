define([
    'Magento_Ui/js/grid/columns/expandable',
    'ko',
    'jquery'
], function (Expandable, ko, $) {
    'use strict';

    return Expandable.extend({
        defaults: {
            bodyTmpl: 'Fedex_SelfReg/grid/cells/expandable',
            visibleItemsLimit: 5,
            hasPreview: true
        },

        /**
         * Initialize observable properties
         */
        initialize: function () {
            this._super();
            
            // Initialize expanded rows tracking
            this.expandedRows = ko.observable({});
            
            return this;
        },

        /**
         * Initialize component observables
         */
        initObservable: function () {
            this._super();
            
            this.observe(['expandedRows']);
            
            return this;
        },

        /**
         * Check if column data should show tooltip
         */
        isExpandable: function(row) {
            var data = row[this.index];
            var limit = this.visibleItemsLimit || 5;
            
            return Array.isArray(data) && data.length > limit;
        },

        /**
         * Check if specific row is expanded
         */
        isRowExpanded: function(rowId) {
            var expandedState = this.expandedRows();
            return expandedState[rowId] || false;
        },

        /**
         * Toggle expansion for specific row only
         */
        toggleRow: function(rowId) {
            var expandedState = this.expandedRows();
            expandedState[rowId] = !expandedState[rowId];
            this.expandedRows(expandedState);
            return false; // Prevent default link behavior
        },

        /**
         * Show popup with all items
         */
        showAllItems: function(data, event) {
            event.preventDefault();
            
            // Get the row data from the event context
            var rowData = ko.dataFor(event.target);
            var columnData = rowData[this.index];
            
            if (Array.isArray(columnData) && columnData.length > 0) {
                var content = columnData.map(function(item, index) {
                    return (index + 1) + '. ' + item;
                }).join('\n');
                
                alert('All Items:\n\n' + content);
            }
            
            return false;
        },

        openTooltip: function(data, event) {
            event.preventDefault();
            event.stopPropagation();

            var tooltipDiv = event.target.nextElementSibling;
            var triggerElement = event.target;

            if (tooltipDiv && tooltipDiv.classList.contains('custom-scrollable-tooltip')) {
                if (tooltipDiv.style.display === 'none' || tooltipDiv.style.display === '') {
                    // Hide all other tooltips first
                    var allTooltips = document.querySelectorAll('.custom-scrollable-tooltip');
                    allTooltips.forEach(function(tip) {
                        tip.style.display = 'none';
                        // Hide arrow
                        var arrowElement = tip.nextElementSibling;
                        if (arrowElement && arrowElement.classList.contains('custom-scrollable-tooltip-arrow')) {
                            arrowElement.style.display = 'none';
                            arrowElement.style.visibility = 'hidden';
                        }
                        // Remove arrow visibility class
                        tip.classList.remove('arrow-visible');
                    });

                    // Calculate position relative to viewport (getBoundingClientRect already includes scroll)
                    var rect = triggerElement.getBoundingClientRect();
                    var viewportHeight = window.innerHeight;
                    var viewportWidth = window.innerWidth;

                    // Temporarily show tooltip to get its dimensions
                    tooltipDiv.style.display = 'block';
                    tooltipDiv.style.visibility = 'hidden';
                    var tooltipRect = tooltipDiv.getBoundingClientRect();

                    // Calculate optimal position above the trigger
                    var tooltipHeight = tooltipRect.height;
                    var tooltipWidth = tooltipRect.width;
                    var triggerWidth = rect.width;

                    // Calculate position above trigger (default)
                    var topPosition = rect.top - tooltipHeight - 20;
                    var leftPosition = rect.left + (triggerWidth / 2) - (tooltipWidth / 2);

                    // Ensure tooltip doesn't go off-screen vertically
                    if (topPosition < 10) {
                        // If not enough space above, position below
                        topPosition = rect.bottom + 10;
                    } else if (topPosition + tooltipHeight > viewportHeight - 10) {
                        // If tooltip would go below viewport, position above
                        topPosition = rect.top - tooltipHeight - 20;
                    }

                    // Ensure tooltip doesn't go off-screen horizontally
                    if (leftPosition < 10) {
                        leftPosition = 10;
                    } else if (leftPosition + tooltipWidth > viewportWidth - 10) {
                        leftPosition = viewportWidth - tooltipWidth - 10;
                    }

                    // Apply calculated positions
                    tooltipDiv.style.top = topPosition + 'px';
                    tooltipDiv.style.left = leftPosition + 'px';
                    tooltipDiv.style.visibility = 'visible';

                    // Add class to make arrow visible with bright color
                    tooltipDiv.classList.add('arrow-visible');

                    // Position arrow dynamically below tooltip using dedicated function
                    this.positionTooltipArrow(tooltipDiv, topPosition, leftPosition, tooltipWidth, tooltipHeight);
                } else {
                    // Hide tooltip and arrow
                    tooltipDiv.style.display = 'none';
                    var arrowElement = tooltipDiv.nextElementSibling;
                    if (arrowElement && arrowElement.classList.contains('custom-scrollable-tooltip-arrow')) {
                        arrowElement.style.display = 'none';
                        arrowElement.style.visibility = 'hidden';
                    }
                    tooltipDiv.classList.remove('arrow-visible');
                }
            }

            return false;
        },

        /**
         * Position arrow element relative to tooltip
         */
        positionTooltipArrow: function(tooltipDiv, topPosition, leftPosition, tooltipWidth, tooltipHeight) {
            var arrowElement = tooltipDiv.nextElementSibling;
            
            if (!arrowElement || !arrowElement.classList.contains('custom-scrollable-tooltip-arrow')) {
                console.warn('Tooltip arrow element not found or missing required class');
                return false;
            }

            // Calculate arrow position (centered below tooltip)
            var arrowTop = topPosition + tooltipHeight;
            var arrowLeft = leftPosition + (tooltipWidth / 2);
            
            // Apply positioning with error handling
            try {
                arrowElement.style.position = 'fixed';
                arrowElement.style.top = arrowTop + 'px';
                arrowElement.style.left = arrowLeft + 'px';
                arrowElement.style.transform = 'translateX(-50%)';
                return true;
            } catch (error) {
                console.error('Error positioning tooltip arrow:', error);
                return false;
            }
        },

        /**
         * Close tooltip programmatically
         */
        closeTooltip: function(data, event) {
            var tooltip = event.target.closest('.custom-scrollable-tooltip');
            if (tooltip) {
                tooltip.style.display = 'none';
                var arrow = tooltip.nextElementSibling;
                if (arrow && arrow.classList.contains('custom-scrollable-tooltip-arrow')) {
                    arrow.style.display = 'none';
                    arrow.style.visibility = 'hidden';
                }
                tooltip.classList.remove('arrow-visible');
            }
            return false;
        },
        /**
         * Initialize tooltips after render
         */
        afterRender: function() {
            this._super();
            
            var self = this;
            
            // Initialize tooltips using jQuery UI
            setTimeout(function() {
                $('.show-more-tooltip').each(function() {
                    var $element = $(this);
                    var tooltipContent = $element.attr('data-tooltip-content');
                    
                    if (tooltipContent && !$element.data('tooltip-initialized')) {
                        $element.tooltip({
                            content: tooltipContent,
                            classes: {
                                "ui-tooltip": "admin__tooltip admin__tooltip--expandable"
                            },
                            position: {
                                my: "center bottom-10",
                                at: "center top",
                                collision: "flip"
                            },
                            show: {
                                delay: 200
                            },
                            hide: {
                                delay: 100
                            }
                        });
                        
                        $element.data('tooltip-initialized', true);
                    }
                });
            }, 500);
        }
    });
});
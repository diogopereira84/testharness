define([
    'jquery',
    'Magento_Ui/js/lib/view/utils/dom-observer',
], function ($, $do){
    'use strict';

    function formatSpecifications(specifications) {
        let htmlContent = '';

        for (const [key, value] of Object.entries(specifications)) {
            // Check if the value is an object (but not an array)
            if (typeof value === 'object' && !Array.isArray(value)) {
                htmlContent +=
                "<div>" +
                    "<strong>" + key + "</strong>" +
                    "<div>" + formatSpecifications(value) + "</div>" +
                "</div>";
            }
            // Check if the value is an array
            else if (Array.isArray(value)) {
                htmlContent +=
                "<div class='group'>" +
                    "<div class='title d-flex flex-col h-center fedex-bold text-uppercase weight-700'>" +
                        "<strong>" + key + "</strong>" +
                    "</div>" +
                    "<ul class='section'>";

                value.forEach(function (item) {
                    if (typeof item === 'object') {
                        htmlContent +=
                        '<li>' + formatSpecifications(item) + '</li>';
                    } else {
                        htmlContent +=
                        '<li>' + item + '</li>';
                    }
                });

                htmlContent += '</ul></div>';
            }
            else {
                htmlContent +=
                "<div class='attribute d-flex'>" +
                    "<span class='label d-flex w-50 flex-col h-center text-capitalize'>" +
                        key +
                    "</span>" +
                    "<span class='value d-flex w-50 flex-col h-center fedex-light text-capitalize'>" +
                        value +
                    "</span>" +
                "</div>";
            }
        }

        return htmlContent;
    }

    return function(config = {}) {
        var specificationsModule = {
            init: function() {                
                $do.get('.tab-specifications-wrapper', function () {
                    if(window.productSpecifications) {
                        $(".tab-specifications-wrapper").show();
                    }
                });
            },
            
            update: function(sku) {
                $do.get('#tab-specifications', function () {
                    let specificationsString = window.productSpecifications[sku];

                    if (specificationsString) {
                        try {
                            let specifications = JSON.parse(specificationsString);
                            let formattedHtml = formatSpecifications(specifications);

                            $('#tab-specifications').html(formattedHtml);
                        }
                        catch (e) {
                            $('#tab-specifications').html(
                                'Error parsing specifications data.'
                            );
                            console.error('JSON parsing error: ', e);
                        }
                    } else {
                        $('#tab-specifications').html(
                            'No specifications available for this product.'
                        );
                    }
                });
            }
        };

        if(config.simpleProductAutoInit) {
            specificationsModule.init();

            if(window.productSpecifications) {
                const sku = Object.keys(window.productSpecifications)[0];
                specificationsModule.update(sku);
            }
        }
        
        return specificationsModule;
    };
});
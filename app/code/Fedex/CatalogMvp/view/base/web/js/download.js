define([
    "jquery",
    "jquery/ui"
], function($){
    "use strict";
     
    function main(config, element) {
        var downloadAjaxUrl = config.downloadAjaxUrl;

        /**
         * Download file from admin
         */
        $(document).on('click','.download-field-configured .download-configure-img',function() {
            let productName = $("input[name='product[name]']").val();
            let externalProd = $("textarea[name='product[external_prod]']").val();

            let requestData = {
                productName: productName,
                externalProd: externalProd,
            };

            if (productName && externalProd) {
                ajaxDownloadConfigureFile(requestData, 'admin');
            }
        });

        /**
         * Download file from customer admin
         */
        $(document).on('click','.dropdown-menu .action_kebab_right_item_download',function() {
            let productId = null;
            $('.product-item-checkbox:checked').each(function() {
                productId = $(this).attr('name');
            });

            if (productId) {
                let requestData = {
                    productId: productId
                };

                ajaxDownloadConfigureFile(requestData);
            }
        });

        /**
         * Download file from customer admin
         */
        $(document).on('click','.menu-item-label-download',function() {
            let productId = $(this).children('a').attr('data-item-id');

            if (productId) {
                let requestData = {
                    productId: productId
                };

                ajaxDownloadConfigureFile(requestData);
            }
        });

        /**
         * Close download error modal
         */
        $(document).on('click','#download-error-messages .close',function() {
            $('.catalog-category-view .top-container').removeClass('download-modal');
            $("#download-error-messages").remove();
        });

        /**
         * ADA on download error modal close
         */
        $(document).on('keypress','#download-error-messages .close',function(e) {
             if (e.which == 13) {
                $('.catalog-category-view .top-container').removeClass('download-modal');
                $("#download-error-messages").remove();
             } 
        });

        /**
         * Call ajax to download file
         */
        function ajaxDownloadConfigureFile(requestData, area = null) {
            let downloadUrl = downloadAjaxUrl;
            $.ajax({
                type: 'post',
                url: downloadUrl,
                showLoader: true,
                data: requestData,
                dataType: 'text',
                cache: false,
                success: function(responseData) {
                    $("#download-error-messages").remove();
                    $('.catalog-category-view .top-container').removeClass('download-modal');
                    if (responseData) {
                        window.open(responseData, '_blank');     
                    } else {
                        $("html, body").animate({ scrollTop: 0 }, "fast");
                        if (area == "admin") {
                            $('<div id="download-error-messages"><div class="messages"><div class="message message-error error"><div>Oops! Unable to download at the moment. API error encountered. Please try again.</div></div></div></div>').insertAfter('.page-main-actions');
                        } else {
                            $('.catalog-category-view .top-container').addClass('download-modal');
                            $('<div class="reorder-error" id="download-error-messages"><div class="reorder-error-box-inner"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none"><rect opacity="0.01" width="40" height="40" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M6.66667 20.0001C6.66667 27.3639 12.6362 33.3334 20 33.3334C27.3638 33.3334 33.3333 27.3639 33.3333 20.0001C33.3333 12.6363 27.3638 6.66675 20 6.66675C12.6362 6.66675 6.66667 12.6363 6.66667 20.0001ZM31.6667 20.0001C31.6667 26.4434 26.4433 31.6667 20 31.6667C13.5567 31.6667 8.33334 26.4434 8.33334 20.0001C8.33334 13.5568 13.5567 8.33341 20 8.33341C26.4433 8.33341 31.6667 13.5568 31.6667 20.0001ZM13.5774 14.756C13.252 14.4306 13.252 13.9029 13.5774 13.5775C13.9029 13.2521 14.4305 13.2521 14.7559 13.5775L20 18.8216L25.2441 13.5775C25.5695 13.2521 26.0972 13.2521 26.4226 13.5775C26.748 13.9029 26.748 14.4306 26.4226 14.756L21.1785 20.0001L26.4226 25.2442C26.748 25.5696 26.748 26.0972 26.4226 26.4227C26.0972 26.7481 25.5695 26.7481 25.2441 26.4227L20 21.1786L14.7559 26.4227C14.4305 26.7481 13.9029 26.7481 13.5774 26.4227C13.252 26.0972 13.252 25.5696 13.5774 25.2442L18.8215 20.0001L13.5774 14.756Z" fill="white"/></svg></div><div class="reorder-error-msg"><p class="reorder-error-notification-msg">Oops! Unable to download at the moment. API error encountered. Please try again.</p></div><div class="close" tabindex="0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><rect opacity="0.01" width="24" height="24" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M6.14645 6.14645C5.95118 6.34171 5.95118 6.65829 6.14645 6.85355L11.2929 12L6.14645 17.1464C5.95118 17.3417 5.95118 17.6583 6.14645 17.8536C6.34171 18.0488 6.65829 18.0488 6.85355 17.8536L12 12.7071L17.1464 17.8536C17.3417 18.0488 17.6583 18.0488 17.8536 17.8536C18.0488 17.6583 18.0488 17.3417 17.8536 17.1464L12.7071 12L17.8536 6.85355C18.0488 6.65829 18.0488 6.34171 17.8536 6.14645C17.6583 5.95118 17.3417 5.95118 17.1464 6.14645L12 11.2929L6.85355 6.14645C6.65829 5.95118 6.34171 5.95118 6.14645 6.14645Z" fill="#333333"/></svg></div></div>').insertAfter('.catalog-category-view .nav-sections');
                        }
                    }  
                }
            });
        }
    };

    return main;  
});

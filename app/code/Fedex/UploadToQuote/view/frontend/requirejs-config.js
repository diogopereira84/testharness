/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
let config = {
    map: {
        '*': {
            uploadToQuoteSummary: 'Fedex_UploadToQuote/js/upload_to_quote_summary',
            uploadToQuoteCheckout: 'Fedex_UploadToQuote/js/upload-to-quote-checkout',
            uploadToQuoteCustomerAccount: 'Fedex_UploadToQuote/js/upload_to_quote_customer_account',
            uploadToQuoteHistory: 'Fedex_UploadToQuote/js/upload_to_quote_history',
            uploadToQuoteDetail: 'Fedex_UploadToQuote/js/upload_to_quote_details',
            uploadToQuoteModelPopup: 'Fedex_UploadToQuote/js/upload_to_quote_model_popup',
            uploadToQuotePreviewPopup: 'Fedex_UploadToQuote/js/preview_configurator',
            uploadToQuoteDecline: 'Fedex_UploadToQuote/js/upload_to_quote_decline',
            uploadToQuoteDeleteItem: 'Fedex_UploadToQuote/js/upload_to_quote_delete_item',
            uploadToQuoteQueueListener: 'Fedex_UploadToQuote/js/upload_to_quote_queue_listener',
            cartItemEditDisable: 'Fedex_UploadToQuote/js/cart_item_edit_disable',
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/summary/item/details/thumbnail': {
                'Fedex_UploadToQuote/js/view/summary/item/details/thumbnail-mixin': true
            }
        }
    }
};

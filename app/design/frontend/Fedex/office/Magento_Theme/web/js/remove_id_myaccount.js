require(['jquery'
    ],function($){
        $(document).ready(function() {   
                $('.customer-menu ul.header.links a').attr('id','');
                //B-1461963 Ensure the pickup screen complies with ADA WCAG 2.1 AA standards
                $(document).ajaxStop(function() {
                $('.commercial-store-home .page-wrapper .columns aside.modal-custom #opc-sidebar .opc-block-summary .items-in-cart').attr('aria-busy', true);
                //B-1473290 - Ensure the payment screen complies with ADA WCAG 2.1 AA standards
                $('.commercial-store-home .page-wrapper .columns .checkout-container .opc-wrapper .opc li#step_code ul.checkout-breadcrumb > li').attr('role', '');
            });
    });
});

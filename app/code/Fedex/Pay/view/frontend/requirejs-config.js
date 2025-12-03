let config = {
    map: {
        '*': {
          
            'Magento_Checkout/template/payment.html':
                'Fedex_Pay/template/payment.html'   
        }
    },
    paths: {
        'Magento_Checkout/js/view/payment': 'Fedex_Pay/js/view/payment',
        'Magento_Checkout/js/model/step-navigator': 'Fedex_Pay/js/model/step-navigator',
        'Magento_Checkout/js/view/progress-bar': 'Fedex_Pay/js/view/progress-bar',
        'Magento_Tax/js/view/checkout/summary/tax': "Fedex_Pay/js/view/checkout/summary/tax",
	    'ans1': 'Fedex_Pay/js/view/ans1', 
        'jsbn': 'Fedex_Pay/js/view/jsbn',
        'rsaes': 'Fedex_Pay/js/view/rsaes-oeap',
        'rusha': 'Fedex_Pay/js/view/rusha',
        'cardValidator': 'Fedex_Pay/js/view/card-validator',
    },
};

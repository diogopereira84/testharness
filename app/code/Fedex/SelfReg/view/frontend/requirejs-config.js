var config = {
    paths: {            
            'owlcarousel': "Fedex_SelfReg/js/owl-carousel.min"
        },   
    shim: {
        'owlcarousel': {
            deps: ['jquery']
        }
    },
    map: 
    {
        '*': 
        {
            'Magento_Company/js/user-edit':'Fedex_SelfReg/js/user-edit',
            'reloadGrid': 'Fedex_SelfReg/js/componentReloader',
            'Fedex_SelfReg/bulkedit':'Fedex_SelfReg/js/users/bulkedit',
            'changeUserGroupModal':'Fedex_SelfReg/js/changeUserGroupModal',
            'newUserGroupModal':'Fedex_SelfReg/js/newUserGroupModal',
            'bulkEditWarningModal':'Fedex_SelfReg/js/bulkEditWarningModal',
        }
    }
};

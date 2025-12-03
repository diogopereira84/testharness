define([
    "jquery",
    "Magento_Ui/js/modal/modal"
], function ($) {
    return function renderOverlay(options)
    {
        let self = this;
        let divId = options.id;

        this.modal = $('<div>').attr({id: divId}).html(options.content()).modal({
            modalClass: 'magento',
            title: options.title,
            type: 'slide',
            closed: function (e, modal) {
                modal.modal.remove();
            },
            opened: function () {
                if (options.opened) {
                    options.opened.call(self);
                }
            },
            buttons: [{
                text: $.mage.__('Cancel'),
                'class': 'action cancel',
                click: function () {
                    this.closeModal();
                }
            }, {
                text: $.mage.__('Save'),
                'class': 'action primary upload-button',
                click: function () {
                    options.action.call(self);
                }
            }]
        });
        this.modal.modal('openModal');
    }
});

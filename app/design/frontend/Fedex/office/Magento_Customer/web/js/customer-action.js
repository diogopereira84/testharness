function changeText(e){
    require(['jquery', "Magento_Ui/js/modal/modal"], function($, modal) {

        var options = {
            type: 'popup',
            responsive: false,
            clickableOverlay: false,
            modalClass: 'profile-refresh-popup',
            responsiveClass: "modal-slide-disable",
            title: false,
            buttons: [{
                text: $.mage.__('Refresh'),
                class: '',
                click: function () {
                    this.closeModal();
                    profileUpdate(e);
                }
            }]
        };

	    var popup = modal(options, $('#modal'));

        if($('a.edit-account-link span').html() == 'Edit') {
            var pophtml = '<div id="modal"><div class="modal-body-content"><h2>Click Refresh to see your changes</h2></div></div>';
            $(".profile-refresh-popup .modal-inner-wrap .modal-content").html(pophtml);
            var url;
            if (window.isImprovingPasswordToggle) {
                url = $(e).data('url');
            } else {
                url = $('.edit-account-link').attr('data-url');
            }
            window.open(url, '_blank');
            $('#modal').modal(options).modal('openModal');
            $('a.edit-account-link').attr('target','');
            return false;
        }
    });
}

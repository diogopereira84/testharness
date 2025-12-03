require([
    "jquery",
    "Magento_Ui/js/modal/modal",
    "Magento_Ui/js/modal/alert",
    "mage/url",
    "Fedex_CustomerGroup/js/popup-dropdown",
    'fedex/storage'
], function($, modal, alert, urlBuilder, select2,fxoStorage){
    var url = $('#popup-url').val();
    var selectedIds = [];
    var unselectedIds = [];
    $("body").on("click", ".data-grid-checkbox-cell .admin__control-checkbox", function () {
        if ($(this).is(':checked')){
            if(!(selectedIds.indexOf($(this).val()) !== -1)) {
                selectedIds.push($(this).val());
            }
            if((unselectedIds.indexOf($(this).val()) !== -1)) {
                var index = unselectedIds.indexOf($(this).val());
                if (index > -1) {
                    unselectedIds.splice(index, 1)
                }
            }
        }
        if ($(this).is(':unchecked')){
            if(!(unselectedIds.indexOf($(this).val()) !== -1)) {
                unselectedIds.push($(this).val());
            }
            if((selectedIds.indexOf($(this).val()) !== -1)) {
                var index = selectedIds.indexOf($(this).val());
                if (index > -1) {
                    selectedIds.splice(index, 1)
                }
            }
        }
        
        if(window.e383157Toggle){
            fxoStorage.set('selectedCustomerIds', selectedIds);
        }else{
            localStorage.setItem("selectedCustomerIds", selectedIds);
        }

    });
    $('#asign_group').select2({
        ajax: {
            url: url,
            delay: 250,
            method: "GET",
            dataType: "json",
            showLoader: true,
            async: true,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page
                };
            },
            error: function (response) {
                $(".messages").css("display", "none");
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: {
                        more: (params.page * 1) < data.noOfPages
                    }
                };
            },
            cache: true
        },
        placeholder: 'Search Customer Group...',
        theme: "classic",
        templateSelection: formatListSelection
    });
    function formatListSelection (item) {
        return item.text;
    }
    $(document).on('click', "#group-model-popup-button", function(){
        let localSelectedIds;
        let selectedIds;
        if(window.e383157Toggle){
            selectedIds = fxoStorage.get("selectedCustomerIds");
        }else{
            localSelectedIds = localStorage.getItem("selectedCustomerIds");
            selectedIds = JSON.parse(localSelectedIds);
        }
        var message = "You haven't selected any items!";
        if(selectedIds!=null && selectedIds.length > 0) {
            var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: 'Assign Customer Group',
            buttons: [{
                    text: 'Save',
                    class: 'primary',
                    click: function () {
                        saveFunction(selectedIds);
                        this.closeModal();
                    }
                }]
            };
            var popup = modal(options, $('#custom-model-popup'));
            popup.openModal();
            $('#custom-model-popup').css('display', 'block');
        } else {
                alert({
                        content: message
                    });
                }
    });

    function saveFunction(selectedIds) {
        var customerGroupId  = $("#asign_group").val();
        var url = $('#save-url').val();
        $.ajax({
            type: "post",
            url: url,
            showLoader: true,
            data: {
                group: customerGroupId,
                selectedIds: selectedIds
            },
            dataType: "json",
            success: function(data) {
                if (data.redirect) {
                   window.location.href = data.redirect;
                }
            },
            error: function (response) {
                $(".messages").css("display", "none");
            },
        });
    }
});

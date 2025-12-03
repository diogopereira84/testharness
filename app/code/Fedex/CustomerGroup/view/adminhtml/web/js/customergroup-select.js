require([
    "jquery",
    "mage/url",
    "Fedex_CustomerGroup/js/select-dropdown"
], function($, urlBuilder, select2){
    var url = $('#ajax_url').val();
    var value = $('#parent_group_id').val();
    $('#parent_group_id').selectdropdown({
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
        placeholder: 'Select a group...',
        theme: "classic",
        templateSelection: formatListSelection
    });
    function formatListSelection (item) {
        return item.text;
    }

});

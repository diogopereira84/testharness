define(['jquery'], function ($) {
    'use strict';

    let fxoStorage;

    return {
        init: function (storage) {
            fxoStorage = storage;
            this.bindSharedCatalogInput();
            this.bindDiscountAccountNumber();
        },

        renderSharedCatalogDropdown: function (catalogs) {
            catalogs.sort((a, b) => a.name.localeCompare(b.name));
            let html = '';

            if (!catalogs.length) {
                html = '<div style="padding:10px;">No shared catalogs found.</div>';
            } else {
                $.each(catalogs, function (i, catalog) {
                    html += '<div class="shared-catalog-item" data-id="' + catalog.id + '" style="padding:10px; cursor:pointer;">' + catalog.name + '</div>';
                });
            }

            $('#shared-catalog-dropdown').html(html);
            $('#shared-catalog-input').data('catalogs', catalogs);
        },

        filterSharedCatalogDropdown: function (catalogs, searchTerm) {
            const filtered = catalogs.filter(c => c.name.toLowerCase().indexOf(searchTerm) !== -1);
            let html = '';

            if (!filtered.length) {
                html = '<div style="padding:10px;">No shared catalogs found.</div>';
            } else {
                $.each(filtered, function (i, catalog) {
                    html += '<div class="shared-catalog-item" data-id="' + catalog.id + '" style="padding:10px; cursor:pointer;">' + catalog.name + '</div>';
                });
            }

            $('#shared-catalog-dropdown').html(html).show();
        },

        bindSharedCatalogInput: function () {
            const self = this;

            $(document).on('click', '#shared-catalog-input', function (e) {
                e.stopPropagation();
                const $dropdown = $('#shared-catalog-dropdown').toggle();

                if ($dropdown.is(':empty')) {
                    $dropdown.html('<div style="padding:10px;">Loading...</div>');
                    $.ajax({
                        url: window.sharedCatalogsUrl,
                        type: 'GET',
                        dataType: 'json',
                        showLoader: true,
                        success: function (response) {
                            self.renderSharedCatalogDropdown(response.catalogs || []);
                        },
                        error: function (xhr, status, err) {
                            console.error('Error fetching shared catalogs:', err);
                            $dropdown.html('<div style="padding:10px;">Error loading catalogs.</div>');
                        }
                    });
                }
            });

            $(document).on('keyup', '#shared-catalog-input', function () {
                let searchTerm = $(this).val().toLowerCase(),
                    catalogs = $(this).data('catalogs') || [];
                self.filterSharedCatalogDropdown(catalogs, searchTerm);
            });

            $(document).on('click', '.shared-catalog-item', function () {
                let name = $(this).text(),
                    id = $(this).data('id');
                $('#shared-catalog-input').val(name).attr('data-id', id).trigger('input');
                $('#shared-catalog-dropdown').hide();
                fxoStorage.set('selectedSharedCatalogId', id);
                $(document).trigger('fetchDiscountNumber');
            });

            $(document).on('mousedown', function (e) {
                if (!$(e.target).closest('.control').length) {
                    $('#shared-catalog-dropdown').hide();
                }
            });
        },

        bindDiscountAccountNumber: function () {
            $(document).on('fetchDiscountNumber', function (e) {
                let sharedCatalogId = fxoStorage.get('selectedSharedCatalogId') || '';
                $.ajax({
                    url: window.discountNumberUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: { shared_catalog_id: sharedCatalogId },
                    showLoader: true,
                    success: function (response) {
                        let discountNumber = (response && typeof response.discount_number !== 'undefined' && response.discount_number !== null && String(response.discount_number).trim() !== '')
                            ? String(response.discount_number)
                            : null;
                        fxoStorage.set('DiscountNumber', discountNumber);
                    },
                });
            });
        }
    };
});
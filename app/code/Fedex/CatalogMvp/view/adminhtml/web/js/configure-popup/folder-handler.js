define(['jquery'], function ($) {
    'use strict';

    let fxoStorage, modal;

    return {
        init: function (storage, modalLib) {
            fxoStorage = storage;
            modal = modalLib;

            this.initFolderModals();
            this.bindFolderPopup();
            this.bindCreateFolderModal();
        },

        initFolderModals: function () {
            try {
                // Folder selection modal
                this.folderModal = modal({
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'folder-popup-modal',
                    title: $.mage.__('Add to Folder'),
                    buttons: [{
                        text: $.mage.__('Close'),
                        class: 'action-secondary',
                        click: function () { this.closeModal(); }
                    }]
                }, $('#folder-popup'));

                // Create folder modal
                this.createFolderModal = modal({
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'create-folder-popup-modal',
                    title: $.mage.__('Create New Folder'),
                    buttons: []
                }, $('#create-folder-modal'));

            } catch (error) {
                console.error('Error initializing folder modals:', error);
            }
        },

        updateFolderSaveState: function () {
            let $selectedFolder = $('#selected-folder');
            if ($selectedFolder.length) {
                let value = $selectedFolder.val() ? $selectedFolder.val().trim() : '';
                $('#folder-save').prop('disabled', !value);
            }
        },

        bindTreeEvents: function ($tree) {
            const self = this;
            $tree.off('click', '.toggle').on('click', '.toggle', function () {
                const $toggle = $(this);
                $toggle.toggleClass('expanded');
                $toggle.siblings('.children').slideToggle();
                $toggle.html($toggle.hasClass('expanded') ? '&#9660;' : '&#9654;');
            });

            $tree.off('click', '.category-label').on('click', '.category-label', function () {
                $tree.find('.category-label').removeClass('selected');
                $(this).addClass('selected');
                $('#selected-folder').val($(this).text()).attr('data-id', $(this).data('id'));
                fxoStorage.set('selectedCategoryId', $(this).data('id'));
                self.updateFolderSaveState();
            });
        },

        expandFirstRootCategory: function () {
            const $firstRoot = $('#folder-tree > ul > li:first-child');
            if ($firstRoot.length) {
                const $toggle = $firstRoot.find('> .toggle');
                const $children = $firstRoot.find('> .children');
                $toggle.addClass('expanded').html('&#9660;');
                $children.show();
            }
        },

        renderCategoryTree: function (categories) {
            const self = this;
            let html = '<ul>';
            categories.forEach(function (cat) {
                html += '<li class="' + (cat.optgroup && cat.optgroup.length > 0 ? '' : 'no-sub-cat') + '">';

                if (cat.optgroup && cat.optgroup.length > 0) {
                    html += '<span class="toggle" style="cursor:pointer;">&#9654; </span>';
                }
                html += '<span class="category-label" data-id="' + cat.value + '" style="cursor:pointer;">' + cat.label + '</span>';
                if (cat.optgroup && cat.optgroup.length > 0) {
                    html += '<div class="children" style="display:none;">' + self.renderCategoryTree(cat.optgroup) + '</div>';
                }
                html += '</li>';
            });
            html += '</ul>';
            return html;
        },

        flattenCategoriesWithPath: function (categories, searchTerm, parentPath) {
            const self = this;
            parentPath = parentPath || [];
            let matches = [];
            categories.forEach(function (cat) {
                let currentPath = parentPath.concat(cat.label);
                if (cat.label.toLowerCase().indexOf(searchTerm) !== -1) {
                    matches.push({ ...cat, path: parentPath.join(' / ') });
                }
                if (cat.optgroup && cat.optgroup.length > 0) {
                    matches = matches.concat(self.flattenCategoriesWithPath(cat.optgroup, searchTerm, currentPath));
                }
            });
            return matches;
        },

        renderFlatCategoryList: function (categories) {
            let html = '<ul>';
            categories.forEach(function (cat) {
                html += '<li>';
                html += '<span class="category-label" data-id="' + cat.value + '" style="cursor:pointer;">' + cat.label + '</span>';
                if (cat.path) html += ' <span style="color:#888;font-size:12px;">(' + cat.path + ')</span>';
                html += '</li>';
            });
            html += '</ul>';
            return html;
        },

        bindFolderPopup: function () {
            const self = this;

            $(document).on('fetchFoldersData', function (e) {
                let sharedCatalogId = fxoStorage.get('selectedSharedCatalogId') || '';
                $('#folder-tree').html('<div style="padding:10px;">Loading...</div>');

                $.ajax({
                    url: window.categoriesUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: { shared_catalog_id: sharedCatalogId },
                    showLoader: true,
                    success: function (response) {
                        const categories = response.categories || [];
                        const rootCategory = response.default_root_category || {};
                        $('#folder-tree').data('allCategories', categories);
                        $('#folder-tree').html(self.renderCategoryTree(categories));

                        if (!$('#selected-folder').val()) {
                            const selectedCategoryId = fxoStorage.get('selectedCategoryId');
                            const selectedCategoryName = fxoStorage.get('selectedCategoryName');
                            if (selectedCategoryId && selectedCategoryName) {
                                $('#selected-folder').val(selectedCategoryName).attr('data-id', selectedCategoryId).trigger('input');
                                fxoStorage.set('selectedCategoryId', selectedCategoryId);
                                self.updateFolderSaveState();
                            }
                        }

                        self.updateFolderSaveState();

                        $('.shared-catalog-title').off('click').on('click', function () {
                            $('#selected-folder').val('Shared Catalog').attr('data-id', rootCategory.value || '');
                            fxoStorage.set('selectedCategoryId', rootCategory.value || '');
                            self.updateFolderSaveState();
                        });

                        self.bindTreeEvents($('#folder-tree'));
                        self.expandFirstRootCategory();
                    },
                    error: function () {
                        $('#folder-tree').html('<div style="padding:10px;">Error loading categories.</div>');
                    }
                });
            });

            $(document).on('keyup', '#folder-search', function () {
                const searchTerm = $(this).val().toLowerCase();
                const allCategories = $('#folder-tree').data('allCategories') || [];
                if (!searchTerm) {
                    $('#folder-tree').html(self.renderCategoryTree(allCategories));
                    self.bindTreeEvents($('#folder-tree'));
                    return;
                }
                const filtered = self.flattenCategoriesWithPath(allCategories, searchTerm);
                $('#folder-tree').html(self.renderFlatCategoryList(filtered));
                self.bindTreeEvents($('#folder-tree'));
            });

            $(document).on('click', '#folder-cancel', function () {
                $('#folder-popup').modal('closeModal');
                $('#configure-popup').modal('openModal');
            });
        },

        bindCreateFolderModal: function () {
            const self = this;

            $(document).on('click', '#create-folder', function (e) {
                e.preventDefault();
                $('#folder-popup').modal('closeModal');
                $('#create-folder-modal').modal('openModal');

                const sharedCatalogId = fxoStorage.get('selectedSharedCatalogId') || '';
                $('#parent-folder-tree').html('<div style="padding:10px;">Loading...</div>');

                $.ajax({
                    url: window.categoriesUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: { shared_catalog_id: sharedCatalogId },
                    showLoader: true,
                    success: function (response) {
                        const categories = response.categories || [];
                        const parentRootCategory = response.default_root_category || {};
                        $('#parent-folder-tree').data('allCategories', categories);
                        $('#parent-folder-tree').html(self.renderCategoryTree(categories));

                        $('.shared-catalog-title').off('click').on('click', function () {
                            $('#parent-folder-selected').val('Shared Catalog').attr('data-id', parentRootCategory.value || '');
                        });

                        self.bindParentTreeEvents($('#parent-folder-tree'));
                        self.expandFirstParentRootCategory();
                    },
                    error: function () { $('#parent-folder-tree').html('Error loading folders'); }
                });

                $('#new-folder-name').val('');
                $('#parent-folder-selected').val('').removeAttr('data-id');
                $('#create-folder-confirm').prop('disabled', true);
            });

            $('#new-folder-name').on('input', function () {
                $('#create-folder-confirm').prop('disabled', $(this).val().trim().length === 0);
            });

            $(document).on('click', '#create-folder-confirm', function () {
                const folderName = $('#new-folder-name').val().trim();
                const parentId = $('#parent-folder-selected').attr('data-id') || '';
                if (!folderName) return;

                $.ajax({
                    url: window.createCategoriesUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: { name: folderName, parent_id: parentId },
                    showLoader: true,
                    success: function (response) {
                        if (response.success) {
                            $('#create-folder-modal').modal('closeModal');
                            $('#folder-popup').modal('openModal');
                            $('#selected-folder').val(response.folder_name).attr('data-id', response.folder_id);
                            fxoStorage.set('selectedCategoryId', response.folder_id);
                            $(document).trigger('fetchFoldersData');
                            $('#selected-folder').trigger('input');
                        } else {
                            alert(response.message || 'Error creating folder');
                        }
                    },
                    error: function () { alert('Error creating folder'); }
                });
            });

            $(document).on('click', '#create-folder-cancel', function () {
                $('#create-folder-modal').modal('closeModal');
                $('#folder-popup').modal('openModal');
                $(document).trigger('fetchFoldersData');
            });
        },

        bindParentTreeEvents: function ($tree) {
            $tree.off('click', '.toggle').on('click', '.toggle', function () {
                const $toggle = $(this);
                $toggle.toggleClass('expanded');
                $toggle.siblings('.children').slideToggle();
                $toggle.html($toggle.hasClass('expanded') ? '&#9660;' : '&#9654;');
            });

            $tree.off('click', '.category-label').on('click', '.category-label', function () {
                $tree.find('.category-label').removeClass('selected');
                $(this).addClass('selected');
                $('#parent-folder-selected').val($(this).text()).attr('data-id', $(this).data('id'));
            });
        },

        expandFirstParentRootCategory: function () {
            const $firstRoot = $('#parent-folder-tree > ul > li:first-child');
            if ($firstRoot.length) {
                const $toggle = $firstRoot.find('> .toggle');
                const $children = $firstRoot.find('> .children');
                $toggle.addClass('expanded').html('&#9660;');
                $children.show();
                $firstRoot.find('> .category-label').addClass('selected');
            }
        }
    };
});
define([
    'jquery',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'domReady!'
], function($, $dom) {
    'use strict';
    
    if (!window?.checkout?.is_ten_categories_fix_toggle_enable) {
        return;
    }

    // Function to set height of category tree
    function setCategoryTreeHeight() {
        let $categoryLi = $('li.mvp-catalog-move-popup-category-l-0');
        let categoryHeight = $categoryLi.height() * 10;
        let movePopupCategoryTree = $('.mvp-catalog-move-popup-category-tree');

        movePopupCategoryTree.css('overflow-y', 'auto');
        movePopupCategoryTree.height(categoryHeight);
    }

    // Call setCategoryTreeHeight on document ready
    $dom.get('.modal-popup.mvp-move-modal.modal-slide._inner-scroll._show', function (elem) {
        setCategoryTreeHeight();
    });

    // Resize event handler for window
    $(window).resize(function() {
        if ($('.modal-popup.mvp-move-modal.modal-slide._inner-scroll').hasClass('_show')) {
            setCategoryTreeHeight(); // Call setCategoryTreeHeight when modal is shown
        }
    });
});

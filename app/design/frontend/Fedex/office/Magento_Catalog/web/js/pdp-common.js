require(['jquery', 'utils', 'Magento_Ui/js/lib/view/utils/dom-observer', 'domReady!'],function ($, jsutils, $do) {

    if(window.tigerE424573OptimizingProductCards) {
        // Apply Pattern Fly truncation to product name
        $('.related-products-area .product-item-name').each(function() {
            const [truncatedText, isTruncated] = jsutils.patternFlyTruncation($(this).text().trim());
            if (isTruncated) {
                $(this).html(truncatedText).addClass("break-all");
            }
        });
    }
    jsutils.renderPdpCarousel('.related-products-area .product-items');

    // About this Product Desktop Tabs
    let isMobile = window.matchMedia("only screen and (max-width: 1199px)").matches;
    let lastClickedBtn = '';

    function handleTabClick(tabElement) {
        $('.tab.item.title').removeClass('active');
        $(tabElement).addClass('active');
        lastClickedBtn = tabElement;

        if (!isMobile) {
            $('.about-this-product.item.content').hide();
            const tabContent = tabElement.getAttribute('href');
            $(tabContent).show();
        }
    }

    $(document).on('click', '.tab-buttons-desktop .tab.item.title', function(e) {
        e.preventDefault();
        handleTabClick(this);
    });

    // Call on load to set initial state
    const initialTab = $('.tab-buttons-desktop .tab.item.title').first();
    if (initialTab.length) {
        handleTabClick(initialTab[0]);
    }

    function hideTabsOnMobile() {
        if ($(window).width() < 1200) {
            const $activeTab = $('.tab-buttons-desktop .tab.item.title.active');
            const $header = $('#product-info-tabs .detail-tabs-head[aria-controls="' + $activeTab.attr('aria-controls') + '"]');
            const $panel = $('#' + $activeTab.attr('aria-controls'));
            closeAllAccordionPanels();
            openAccordionPanel($header, $panel);
        } else {
            $('.product.data.items button:eq(0)').trigger('click');
        }
    }

    $(window).on("resize", function() {
        hideTabsOnMobile();
        let visibleTabContent = $('.tabs-content').find('.item.content:visible');
        let activeBtnMobile = $(visibleTabContent).prev('.tab.item.title');
        let clickedBtnRef = $(lastClickedBtn).attr('href');
        let activeBtnDesktop = $('.tab-buttons-desktop').find('button[href="' + clickedBtnRef  + '"]');

        if (lastClickedBtn) {
            $(activeBtnMobile).addClass('active');
            $(activeBtnDesktop).addClass('active');
        }
    });

    $do.get(".recent-products-area a.product-item-photo", function() {
        /*
         * This ensures that the entire card—title, description,
         * and image—is clickable, in accordance with acceptance criteria number 4
         * from E-424573.
        **/

        $('.with-prod-ctlg-standard .recent-products-area .product-item').each(function() {
            let itemUrl = $(this).find('a.product-item-photo').attr('href');

            $(this).wrap(
                '<a ' +
                    'href="' + itemUrl + '"' +
                    'class="all-unset product-item-wrapper" ' +
                '</a>'
            );
        });
    });

    // Initialize accordion state for mobile
    if (isMobile) {
        closeAllAccordionPanels();
    }

    // Accordion toggle handler
    $(document).on('click','#product-info-tabs .detail-tabs-head', function() {
        const $header = $(this);
        const $panel = $('#' + $header.attr('aria-controls'));
        const isExpanded = $header.attr('aria-expanded') === 'true';

        if (isExpanded) {
            closeAccordionPanel($header, $panel);
        } else {
            closeAllAccordionPanels();
            openAccordionPanel($header, $panel);
        }
    });

    function closeAccordionPanel($header, $panel) {
        $header.attr('aria-expanded', 'false');
        $panel.css('display', 'none');
    }

    function openAccordionPanel($header, $panel) {
        $header.attr('aria-expanded', 'true');
        $panel.css('display', 'block');
    }

    function closeAllAccordionPanels() {
        $('#product-info-tabs .detail-tabs-head').attr('aria-expanded', 'false');
        $('#product-info-tabs .about-this-product.item.content').css('display', 'none');
    }
});

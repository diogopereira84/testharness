define(['domReady!'], function() {
    'use strict';

    const generateMenu = (data) => {
        if (!data) return;

        const existingMenu = document.querySelector('.topmenuLoaded');
        if (existingMenu) {
            existingMenu.remove();
        }

        const referenceElement = document.querySelector('.btn-toggle-search');
        if (referenceElement) {

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.trim();
            const newMenu = tempDiv.firstElementChild;
            if (newMenu) {
                newMenu.classList.add('topmenuLoaded');
                referenceElement.insertAdjacentElement('beforebegin', newMenu);
                newMenu.dispatchEvent(new CustomEvent('contentUpdated', {
                    bubbles: true
                }));
            }
        }

        document.querySelector('.navigation')?.classList.add('ajax-updated');
        handleResponsiveChanges();
    };

    const handleResponsiveChanges = () => {
        if (window.innerWidth < 1024 &&
            (!document.body.classList.contains('catalog-mvp-break-points') ||
                document.body.classList.contains('cms-sde-home'))) {
            const retailCartMobile = document.getElementById('retail_cart_mobile');
            const nav = document.querySelector('nav');
            if (retailCartMobile && nav && !retailCartMobile.parentElement.classList.contains('topmenuLoaded')) {
                nav.after(retailCartMobile);
            }
        }
    };

    const emptyHtml = '<nav class="navigation" data-action="navigation">' +
        '    <ul data-mage-init=\'{"menu":{"responsive":true, "expanded":true, "position":{"my":"left top","at":"left bottom"}}}\'>' +
        '        <li class="level0 nav-0 home-menu ui-menu-item" role="presentation">' +
        '            <a href="#" class="level-top ui-menu-item-wrapper" aria-haspopup="false" id="ui-id-0">' +
        '                <span>Home</span>' +
        '            </a>\n' +
        '        </li>\n' +
        '        <li class="level0 nav-1 category-item first parent">' +
        '            <a href="#" class="level-top">' +
        '                <span>Shared Catalog</span>' +
        '            </a>\n' +
        '            <ul class="level0 submenu">' +
        '                <li class="level1 nav-1-1 category-item first">' +
        '                    <a href="#" class="">' +
        '                        <span></span>' +
        '                    </a>' +
        '                </li>' +
        '            </ul>' +
        '        </li>' +
        '        <li class="level0 nav-2 category-item parent">' +
        '            <a href="#" class="level-top">' +
        '                <span>Print Products</span>' +
        '            </a>\n' +
        '            <ul class="level0 submenu">' +
        '                <li class="level1 nav-2-1 category-item first">' +
        '                    <a href="#" class="">' +
        '                        <span></span>' +
        '                    </a>' +
        '                </li>' +
        '            </ul>' +
        '        </li>' +
        '    </ul>' +
        '</nav>';

    const fetchTopMenu = async (url) => {
        const mageCache = JSON.parse(localStorage.getItem('mage-cache-storage') || '{}');
        const cachedMenu = mageCache.topmenu;
        const cachedTime = mageCache['topmenu-loaded-time'];
        const currentTime = Math.floor(Date.now() / 1000);
        generateMenu(cachedMenu ?? emptyHtml);

        if (cachedTime && (currentTime - parseInt(cachedTime, 10)) <= 120) {
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error(`Network response failed: ${response.statusText}`);

            const data = await response.text();
            if (data) {
                mageCache.topmenu = data;
                mageCache['topmenu-loaded-time'] = currentTime.toString();
                localStorage.setItem('mage-cache-storage', JSON.stringify(mageCache));
                generateMenu(data);
            }
        } catch (error) {
            console.error('Failed to fetch menu:', error);
        }
    };

    return function (config) {
        let shouldSkipFetchingMenu = false;
        const excludedClasses = window.checkout?.tiger_top_menu_excluded_classes;

        if(excludedClasses?.length) {
            const bodyClassList = document.body.classList;
            shouldSkipFetchingMenu = excludedClasses.split(',').some(styleClass => bodyClassList.contains(styleClass.trim()));
        }

        if(config?.topmenuUrl && !shouldSkipFetchingMenu) {
            fetchTopMenu(config.topmenuUrl);
        }
    };
});

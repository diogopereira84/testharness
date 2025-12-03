define(['jquery','domReady!'], function($) {
    var $productTiles, 
        tileLimit, 
        debounceResizeTimeOut,
        $showMoreLink = $('.shop-by-type .show-more a'),
        totalTileCount = $('.shop-by-type .tile-view').length;

    if(totalTileCount <= 2) {
        $('.shop-by-type .pagebuilder-column-group').addClass('col2-tile-group');
        $('.shop-by-type .tile-view').addClass('col2-tile-view');
        setBulletsUI();
    } else {
        $('.shop-by-type .pagebuilder-column-group').addClass('col3-tile-group');
        $('.shop-by-type .tile-view').addClass('col3-tile-view');
        setTileView();
        registerEvents();
    }

    function setTileView (resizeFlag = false) {
        var isMobile = window.matchMedia('only screen and (max-width: 767px)').matches;
        if(isMobile) {
            tileLimit = 3;
        } else {
            tileLimit = 6;
        }
        if(resizeFlag && !isMobile) {
            $('.shop-by-type .tile-view:lt('+ tileLimit + ')').removeClass('hide');   
        }
        if(totalTileCount > tileLimit) {
            $productTiles = $('.shop-by-type .tile-view:gt('+ (tileLimit-1) + ')');
            if(!$showMoreLink.hasClass('less')) {
                $productTiles.addClass('hide');
            }
            $('.shop-by-type .show-more').removeClass('hide');
        } else {
            $('.shop-by-type .show-more').addClass('hide');
        }
        setBulletsUI();
    }

    function setBulletsUI () {
        var ulBulletList = $('.shop-by-type .tile-view .bullets ul'),
            ulMaxHeight = 0
            ulHeight = 0;
        ulBulletList.height('');
        ulBulletList.each(function () {
            if($(this).find('li').length > 3) {
                $(this).find('li:gt(2)').remove();
            }
            ulHeight = $(this).height();
            if(ulHeight > ulMaxHeight) {
                ulMaxHeight = ulHeight;
            }
        });
        ulBulletList.height(ulMaxHeight);
    }

    function debounceResizeHandler () {
        clearTimeout(debounceResizeTimeOut);
        debounceResizeTimeOut = setTimeout(function() {
            setTileView(true);
        }, 500);
    }

    function registerEvents () {
        $showMoreLink.on('click',function(event) {
            event.preventDefault();
            if ($(this).hasClass('less')) {
                $(this).removeClass('less');
                $(this).text('SHOW MORE PRODUCTS');
                $productTiles.addClass('hide');
            } else {
                $(this).addClass('less');
                $(this).text('SHOW LESS PRODUCTS');
                $productTiles.removeClass('hide');
            }
        });
        $(window).on('resize',debounceResizeHandler);
    }
});

define([
    'jquery',
 ],
 function($){
     return function() {
         $.widget('mage.custommenu', $.mage.menu, {
            _toggleMobileMode: function () {
                 var subMenus;
                 $(this.element).off('mouseenter mouseleave');
                 this._on({
                     /**
                      * @param {jQuery.Event} event
                      */
                     'click .ui-menu-item:has(a)': function (event) {
                         var target;
                        //  D-115065 Unable to open categories in Mobile view - Magento upgrade issue
                         event.preventDefault();
                        target = $(event.target).closest('.ui-menu-item');
                        if (target.has('.ui-menu').length) {
                            this.expand(event);
                        }
                        target.get(0).scrollIntoView();
                        if (!target.hasClass('parent') || !target.has('.ui-menu').length) {
                            window.location.href = target.find('> a').attr('href');
                        }
                     },
                 });
                 subMenus = this.element.find('.parent');
                 $.each(subMenus, $.proxy(function (index, item) {
                     var category = $(item).find('> a span').not('.ui-menu-icon').text(),
                         categoryUrl = $(item).find('> a').attr('href'),
                         menu = $(item).find('> .ui-menu');
                     this.categoryLink = $('<a>')
                         .attr('href', categoryUrl)
                         .text($.mage.__('All %1').replace('%1', category));
                     this.categoryParent = $('<li>')
                         .addClass('ui-menu-item all-category')
                         .html(this.categoryLink);
                     if (menu.find('.all-category').length === 0) {
                         menu.prepend(this.categoryParent);
                     }
                 }, this));
            },
             /*B-1598909 : RT-ECVS-Feedback-Do not show Print Product Category, if product not available*/
             isExpanded: function () {
                 var subMenus = this.element.find(this.options.menus),
                     expandedMenus = subMenus.find(this.options.menus),
                     count = expandedMenus.children().length;

                 expandedMenus.addClass('expanded');

                 if(count == 0){
                     console.log(expandedMenus.prev().children().first());
                     expandedMenus.prev().children().first().css("display","none");
                     expandedMenus.css("visibility","hidden");
                 }else{
                     expandedMenus.prev().children().first().css("display","block");
                     expandedMenus.css("visibility","show");
                 }

                 jQuery('.nav-sections .ui-menu-icon').each(function(){
                     if(jQuery(this).parent().next().children().length === 0){
                         jQuery(this).hide();
                         jQuery(this).parent().next().css('visibility','hidden');
                     }
                 });
             }
         });
        return $.mage.custommenu;
     }
 });

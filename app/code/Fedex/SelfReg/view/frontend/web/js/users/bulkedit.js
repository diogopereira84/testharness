define(['jquery', 'matchMedia'], function ($,mediaCheck) {
    'use strict';
     mediaCheck({
        media: '(min-width: 768px)',
        entry: function () {
            $(".expand-more").remove();
            $(".control.users-list").removeClass('manage-user-scroll');
            $(".control.users-list").removeClass('singel-user');
        },
        exit: function () {
            $(".expand-more").remove();
            $(".control.users-list").css({"display":"table-cell"});
           let expandmoresvg= '<svg width="22" height="12" viewBox="0 0 22 12" fill="none" xmlns="http://www.w3.org/2000/svg">'+
           '<path fill-rule="evenodd" clip-rule="evenodd" d="M0.512795 0.878294C0.764018 0.609127 1.18588 0.59458 1.45504'+
 '0.845803L11.0002 9.75458L20.5453 0.845803C20.8145 0.59458 21.2363 0.609127 21.4875 0.878294C21.7388 1.14746 21.7242 1.56932 21.455'+
 '1.82054L11.455 11.1539C11.1989 11.3929 10.8014 11.3929 10.5453 11.1539L0.545286 1.82054C0.276119 1.56932 0.261572 1.14746 0.512795 0.878294Z" fill="#333333"/>'+
'</svg>';
            $(".expand-more-custom-class").append('<div><button class="expand-more">'+expandmoresvg+'</button></div>');  
            $(".expand-more").click(function(e){
                e.preventDefault();
                $(".users-tag-container.show-more").show();
            if($(".users-tag-container").length > 1){
                    $(".control.users-list").scrollTop(0);
                    $(".control.users-list").toggleClass('singel-user');
                    $(".control.users-list").toggleClass('manage-user-scroll');
                }
             });
           }
         });
    });

define(["jquery",
        "mage/url",
        "Magento_Ui/js/modal/modal"],
    function(
        $,
        url,
        modal
    ){
    "use strict";
    $(document).ready(function() {
        $(".msg-container").attr("tabindex", "0");
        $(".nav.item.current").attr("tabindex", "0");
        document.addEventListener("keydown", function(e) {
            if (e.code == "ArrowDown") {
                $(":focus").each(function() {
                    let  _this = this;
                    if ($(_this).parent(".nav.item").length > 0) {
                        if ($(_this).parent(".nav.item").next(".nav.item").children("a").length > 0){
                            $(_this).parent(".nav.item").next(".nav.item").children("a").trigger('focus');
                        } else if ($(_this).parent(".nav.item").next(".nav.item").children("span").length > 0) {
                            if ($(_this).parent(".nav.item").next(".nav.item").next(".nav.item").children("a").length > 0) {
                                $(_this).parent(".nav.item").next(".nav.item").next(".nav.item").children("a").trigger('focus');
                            } else if ($(_this).parent(".nav.item").next(".nav.item").next(".nav.item").children("strong").length > 0) {
                                $(_this).parent(".nav.item").next(".nav.item").next(".nav.item").trigger('focus');
                            }
                        } else if ($(_this).parent(".nav.item").next(".nav.item").children("strong").length > 0) {
                            $(_this).parent(".nav.item").next(".nav.item").trigger('focus');
                        }
                    }
                    if ($(_this).children("strong").length > 0) {
                        $(_this).next(".nav.item").children("a").trigger('focus');
                    }
                    e.preventDefault();
                });
            } else if (e.code == "ArrowUp") {
                $(":focus").each(function() {
                    let  _this = this;
                    if ($(_this).parent(".nav.item").length > 0) {
                        if ($(_this).parent(".nav.item").prev(".nav.item").children("a").length > 0) {
                            $(_this).parent(".nav.item").prev(".nav.item").children("a").trigger('focus');
                        } else if($(_this).parent(".nav.item").prev(".nav.item").children("span").length > 0) {
                            $(_this).parent(".nav.item").prev(".nav.item").prev(".nav.item").children("a").trigger('focus');
                        } else if($(_this).parent(".nav.item").prev(".nav.item").children("strong").length > 0) {
                            $(_this).parent(".nav.item").prev(".nav.item").trigger('focus');
                        }
                    }
                    if ($(_this).children("strong").length > 0) {
                        if($(_this).prev(".nav.item").children("a").length > 0) {
                            $(_this).prev(".nav.item").children("a").trigger('focus');
                        } else if ($(_this).prev(".nav.item").children("span").length > 0) {
                            $(_this).prev(".nav.item").prev(".nav.item").children("a").trigger('focus');
                        }
                    }
                    e.preventDefault();
                });
            }
        });
    });
});

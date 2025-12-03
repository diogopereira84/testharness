require(['jquery'], function($){
  $( document ).ready(function() {
	messageText();
  });
  $( window ).on( "load resize", function() {
	messageText();
  });
  function messageText() {
	var desktopMessage = $(".pagebuilder-banner-wrapper .pagebuilder-poster-content .largetscreenmessage").html();
	var laptopMessage = $.parseHTML($(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").attr("messagelaptop"));
	var tabletMessage = $.parseHTML($(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").attr("messagetablet"));
	var mobileMessage = $.parseHTML($(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").attr("messagemobile"));
	var isDesktop = window.matchMedia("only screen and (min-width: 1440px)").matches;
	var isLaptop = window.matchMedia("only screen and (max-width: 1439px)").matches;
	var isTablet = window.matchMedia("only screen and (max-width: 1199px)").matches;
	var isMobile = window.matchMedia("only screen and (max-width: 767px)").matches;
	$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").show();
	if(isDesktop) {
		if(desktopMessage && desktopMessage.length > 0) {
			$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").html(desktopMessage);
		}
	}
	if(isLaptop) {
		if(laptopMessage && laptopMessage.length > 0) {
			$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").html(laptopMessage);
		} else if(desktopMessage && desktopMessage.length > 0) {
			$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").html(desktopMessage);
		}
	}
	if(isTablet) {
		if(tabletMessage && tabletMessage.length > 0) {
			$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").html(tabletMessage);
		} else if(laptopMessage && laptopMessage.length > 0) {
			$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").html(laptopMessage);
		} else if(desktopMessage && desktopMessage.length > 0) {
			$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").html(desktopMessage);
		}
	}
	if(isMobile) {
		if(mobileMessage && mobileMessage.length > 0) {
			$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").html(mobileMessage);
		} else if(tabletMessage && tabletMessage.length > 0) {
			$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").html(tabletMessage);
		} else if(laptopMessage && laptopMessage.length > 0) {
			$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").html(laptopMessage);
		} else if(desktopMessage && desktopMessage.length > 0) {
			$(".pagebuilder-banner-wrapper .pagebuilder-poster-content .message").html(desktopMessage);
		}
	}
  }
});

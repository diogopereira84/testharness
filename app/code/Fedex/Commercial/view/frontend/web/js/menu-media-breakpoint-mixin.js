define([], function () {
    'use strict';
    return function (widget) {
        //D-94802: Issues fixes in 769px width
        widget.menu.prototype.options.mediaBreakpoint = '(max-width: 769px)';
        return widget;
    };
});

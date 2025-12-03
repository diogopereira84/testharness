define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';
    // D-129269 - Fix for Firefox not supporting :has pseudo-class
    $(".webpage-jumplinks").parent("[data-content-type='row']").addClass('jumplinksRow');
});
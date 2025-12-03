/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([], function() {
    'use strict';
    
    function megamenuGDLScript(e) {
        window.FDX.GDL.push(['config:load', ['fxo']]);
        window.FDX.GDL.push(['event:publish', ['link', 'click/auto', {
            url: e.getAttribute('href'),
            linkIdentifier: e.getAttribute('data-analytics'),
        }]]);
    }
    window.megamenuGDLScript = megamenuGDLScript;
});

define([
    'jquery',
    'Fedex_MarketplacePunchout/js/model/auth'
], function ($, auth) {
    'use strict';

    return async function (config) {
        try {
            const verifier = auth.getVerifier();
            const challenger = await auth.getChallenger(verifier);
            await auth.punchout(verifier, challenger, config);
        } catch (error) {
            console.error('Punchout process failed:', error, {config});
        }
    };
});

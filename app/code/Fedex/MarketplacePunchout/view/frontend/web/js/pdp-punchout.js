define([
    'jquery',
    'Fedex_MarketplacePunchout/js/model/auth'
], function ($, auth) {
    'use strict';

    return function (config, element) {
        config.payload.form_key = $(element).siblings('input[name="form_key"]').val() || $.mage.cookies.get('form_key') || '';

        if (!config.payload.form_key) {
            console.error('Form key is missing');

            return;
        }

        $(element).off('.punchout').on('click.punchout', async () => {
            try {
                const verifier = auth.getVerifier();
                const challenger = await auth.getChallenger(verifier);

                await auth.punchout(verifier, challenger, config);
            } catch (error) {
                console.error('Punchout process failed:', error);
            }
        });
    };
});

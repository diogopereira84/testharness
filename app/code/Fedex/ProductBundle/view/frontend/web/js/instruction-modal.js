define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'fedex/storage'
], function ($, modal, fxoStorage) {
    'use strict';

    const FedexProductBundle = {
        shouldShowModal(storageKey) {
            return fxoStorage.get(storageKey);
        },
        clearModalFlag(storageKey) {
            fxoStorage.delete(storageKey);
        },
        initInstructionModal(config, element, storageKey) {
            const autoOpen = this.shouldShowModal(storageKey);
            $(element).modal({
                autoOpen: !!autoOpen,
                ...config
            });
            if (autoOpen) {
                this.clearModalFlag(storageKey);
            }
        }
    };

    return function (config, element) {
        FedexProductBundle.initInstructionModal(config, element, 'showBundleInstructionModalOnCart');
    };
});

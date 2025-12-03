define(['lz-string'], function(LZString) {
    'use strict';

    if (!window.d198297_toggle) {
        return {
            initialize: function(){}
        }
    }

    const initialize = () => {
        const originalSetItem = Storage.prototype.setItem;
        const originalGetItem = Storage.prototype.getItem;

        window.getDecompressedMageCacheStorage = function() {
            return LZString.decompress(
                originalGetItem.call(localStorage, 'mage-cache-storage')
            );
        };
        originalSetItem.call(
            localStorage,
            'mage-cache-storage-compressed',
            'Yes. (Use the method `window.getDecompressedMageCacheStorage()` if you need to debug the data.)'
        );

        Storage.prototype.setItem = function(key, value) {
            if (key === 'mage-cache-storage') {
                try {
                    const compressedValue = LZString.compress(value);
                    return originalSetItem.call(this, key, compressedValue);
                } catch (e) {
                    console.error('Compression failed:', e);
                    return originalSetItem.call(this, key, value);
                }
            }
            return originalSetItem.call(this, key, value);
        };

        Storage.prototype.getItem = function(key) {
            if (key === 'mage-cache-storage') {
                let compressedValue;
                try {
                    compressedValue = originalGetItem.call(this, key);
                    if (compressedValue !== null) {
                        const decompressed = LZString.decompress(compressedValue);
                        if (decompressed === null) {
                            try {
                                const newCompressed = LZString.compress(compressedValue);
                                originalSetItem.call(this, key, newCompressed);
                            } catch (e) {
                                console.error('Compression failed in getItem:', e);
                            }
                            return compressedValue;
                        }
                        return decompressed;
                    }
                    return null;
                } catch (e) {
                    console.error('Decompression failed:', e);
                    if (compressedValue === undefined) {
                        compressedValue = originalGetItem.call(this, key);
                    }
                    if (compressedValue !== null) {
                        try {
                            const newCompressed = LZString.compress(compressedValue);
                            originalSetItem.call(this, key, newCompressed);
                        } catch (e) {
                            console.error('Compression failed in getItem catch block:', e);
                        }
                    }
                    return compressedValue;
                }
            }
            return originalGetItem.call(this, key);
        };

        window.localStorage.getItem = Storage.prototype.getItem;
        window.localStorage.setItem = Storage.prototype.setItem;
    }
    initialize();

    return {
        initialize: initialize
    }
});

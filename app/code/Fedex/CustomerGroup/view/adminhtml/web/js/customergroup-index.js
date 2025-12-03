require([
    "jquery",
    "mage/url",
    'fedex/storage',
    "domReady!"
], function ($, urlBuilder, fxoStorage) {
    if (window.e383157Toggle) {
        fxoStorage.set('groupName', '');
        fxoStorage.set('parentGroupID', null);
        fxoStorage.set('newCategoryIds', null);
    } else {
        localStorage.setItem("groupName", '');
        localStorage.setItem("parentGroupID", null);
        localStorage.setItem("newCategoryIds", null);
    }
});

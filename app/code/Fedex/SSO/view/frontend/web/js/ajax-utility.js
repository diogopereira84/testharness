define(['jquery'], function ($) {
    'use strict';
    return {
        /* Ajax get method
        * @url: The URL to invoke
        * @data: The payload object
        * @loader: Show full page loader or not
        * @dataType: Type of data expected back from the server
        * @successCallback: The success callback method signature
        * @errorCallback: The error callback signature
        */
        get: function (url, data, loader, dataType, successCallback, errorCallback) {
            $.ajax({
                url: url + $.param(data),
                showLoader: loader,
                dataType: dataType,
                type: 'GET',
                success: function (result) {
                    if (successCallback) successCallback(result)
                },
                error: function (xhr, error) {
                    if (errorCallback) errorCallback(xhr, error)
                }
            });
        },
      
        /* Ajax post method
        * @url: The URL to invoke
        * @data: The payload and format of data
        * @loader: Show full page loader or not
        * @dataType: Type of data expected back from the server
        * @successCallback: The success callback method signature
        * @errorCallback: The error callback signature
        */
        post: function (url, headers, data, loader, dataType, successCallback, errorCallback) {
            $.ajax({
                url: url,
                showLoader: loader,
                headers: headers,
                data: data,
                type: 'POST',
                dataType: dataType,
                success: function (result) {
                    if (successCallback) successCallback(result)
                },
                error: function (xhr, error) {
                    if (errorCallback) errorCallback(xhr, error)
                }
            });
        }
    }
});

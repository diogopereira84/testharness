/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'mage/url',
     'fedex/storage'
], function (
    $,
    url,
    fxoStorage
) {
    initiateUploadToQuoteActionQueue();

    /**
     * Initiate Upload to quote action queue
     *
     * @returns void
     */
    function initiateUploadToQuoteActionQueue() {
        let interval = setInterval(function () {
            let uploadToQuoteAction = window.e383157Toggle
                ? fxoStorage.get("uploadToQuoteActionQueueResquestedTime")
                : localStorage.getItem("uploadToQuoteActionQueueResquestedTime");
            if (uploadToQuoteAction) {
                let requestedTime = uploadToQuoteAction;
                let currDateTime = new Date();
                let timeDiff = (currDateTime.getTime() - requestedTime) / 1000;
                if (timeDiff >= 10) {
                    $('.succ-msg').fadeOut("slow");
                    $('.err-msg').fadeOut("slow");
                    clearInterval(interval);
                    let processQueueUrl = url.build('uploadtoquote/index/processqueue');
                    $.ajax({
                        url: processQueueUrl,
                        showLoader: false,
                        type: "POST",
                        dataType: 'json',
                        data: {},
                        success: function (result) {
                            if (!result.isQueueStop) {
                                initiateUploadToQuoteActionQueue();
                            } else {
                                if (window.e383157Toggle) {
                                    fxoStorage.delete("uploadToQuoteActionQueueResquestedTime");
                                } else {
                                    localStorage.removeItem("uploadToQuoteActionQueueResquestedTime");
                                }
                            }
                        }
                    });
                }
            } else {
                clearInterval(interval);
            }
        }, 1000);
    }

    /**
     * Return function
     */
    return {
        initiateUploadToQuoteActionQueue: initiateUploadToQuoteActionQueue,
    };
});
